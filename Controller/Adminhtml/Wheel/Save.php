<?php

declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Doroshko\WishReward\Api\Data\WheelInterface;
use Doroshko\WishReward\Api\Data\WheelInterfaceFactory;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Doroshko_WishReward::wheel_edit';
    private const EVENT_WHEEL_BEFORE_SAVE = 'wishreward_wheel_before_save';
    private const EVENT_WHEEL_AFTER_SAVE  = 'wishreward_wheel_after_save';

    private WheelRepositoryInterface $wheelRepository;
    private WheelInterfaceFactory $wheelFactory;
    private EventManagerInterface $eventManager;
    private LoggerInterface $logger;

    public function __construct(
        Action\Context $context,
        WheelRepositoryInterface $wheelRepository,
        WheelInterfaceFactory $wheelFactory,
        EventManagerInterface $eventManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->wheelRepository = $wheelRepository;
        $this->wheelFactory    = $wheelFactory;
        $this->eventManager    = $eventManager;
        $this->logger          = $logger;
    }

    /**
     * Execute save action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->getRequest()->getPostValue();

        if (!$data) {
            $this->messageManager->addErrorMessage(__('No data provided to save.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $wheel = $this->loadOrCreateWheel((int)($data['wheel_id'] ?? 0));
            $this->populateWheelData($wheel, $data);

            $this->eventManager->dispatch(self::EVENT_WHEEL_BEFORE_SAVE, ['wheel' => $wheel, 'data' => $data]);
            $this->wheelRepository->save($wheel);
            $this->eventManager->dispatch(self::EVENT_WHEEL_AFTER_SAVE, ['wheel' => $wheel]);

            $this->messageManager->addSuccessMessage(__('Wheel has been saved successfully.'));
            $redirectPath = (isset($data['back']) && $data['back'] === 'edit') ? '*/*/edit' : '*/*/index';
            return $resultRedirect->setPath($redirectPath, ['wheel_id' => $wheel->getWheelId()]);
        } catch (LocalizedException $e) {
            $this->logger->error('Localized exception while saving wheel: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['wheel_id' => $data['wheel_id'] ?? null]);
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error while saving wheel: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An unexpected error occurred: %1', $e->getMessage()));
            return $resultRedirect->setPath('*/*/edit', ['wheel_id' => $data['wheel_id'] ?? null]);
        }
    }

    /**
     * Load existing wheel or create new one
     *
     * @param int $wheelId
     * @return WheelInterface
     */
    private function loadOrCreateWheel(int $wheelId): WheelInterface
    {
        return $wheelId > 0
            ? $this->wheelRepository->getById($wheelId)
            : $this->wheelFactory->create();
    }

    /**
     * Populate wheel entity with form data
     *
     * @param WheelInterface $wheel
     * @param array $data
     * @return void
     */
    private function populateWheelData(WheelInterface $wheel, array $data): void
    {
        $wheel->setTitle((string)($data['title'] ?? ''));
        $wheel->setIsActive((bool)($data['is_active'] ?? false));
        $wheel->setWinMessage((string)($data['win_message'] ?? ''));
        $wheel->setNoWinMessage((string)($data['no_win_message'] ?? ''));
        $wheel->setStartDate($data['start_date'] ?? null);
        $wheel->setEndDate($data['end_date'] ?? null);

        // Нормализация массивов и JSON-конфигураций
        $wheel->setStoreviews($this->arrayToCommaString($this->normalizeToArray($data['storeviews'] ?? ['0'])));
        $wheel->setAllowedCustomerGroups($this->arrayToCommaString($this->normalizeToArray($data['allowed_customer_groups'] ?? [])));
        $wheel->setWheelConfig($this->normalizeWheelConfig($data['wheel_config'] ?? '[]'));
        $wheel->setDisplayOnPages($this->arrayToCommaString($this->normalizeToArray($data['display_on_pages'] ?? [])));

        $wheel->setIsCtaEnabled((bool)($data['is_cta_enabled'] ?? false));
        $wheel->setCtaLabel((string)($data['cta_label'] ?? ''));
        $wheel->setCtaButtonText((string)($data['cta_button_text'] ?? ''));
        $wheel->setCtaPosition((string)($data['cta_position'] ?? 'bottom-right'));
        $wheel->setCtaCustomCss((string)($data['cta_custom_css'] ?? ''));
        $this->processCtaImage($wheel, $data);

        $wheel->setPopupTitle((string)($data['popup_title'] ?? ''));
        $wheel->setPopupDescription((string)($data['popup_description'] ?? ''));
        $wheel->setIsWishAreaEnabled((bool)($data['is_wish_area_enabled'] ?? false));
        $wheel->setIsEmailInputEnabled((bool)($data['is_email_input_enabled'] ?? false));
    }

    /**
     * Process CTA image data
     *
     * @param WheelInterface $wheel
     * @param array $data
     * @return void
     */
    private function processCtaImage(WheelInterface $wheel, array $data): void
    {
        if (isset($data['cta_image'][0]['file']) && !empty($data['cta_image'][0]['file'])) {
            $wheel->setCtaImage($data['cta_image'][0]['file']);
        } elseif (isset($data['cta_image']) && empty($data['cta_image'])) {
            $wheel->setCtaImage(null);
        }
    }

    /**
     * Normalize value to array
     *
     * @param mixed $value
     * @return array
     */
    private function normalizeToArray($value): array
    {
        if (is_string($value)) {
            return array_filter(explode(',', $value));
        }
        return is_array($value) ? $value : [];
    }

    /**
     * Convert array to comma-separated string
     *
     * @param array $values
     * @return string
     */
    private function arrayToCommaString(array $values): string
    {
        return !empty($values) ? implode(',', $values) : '';
    }

    /**
     * Normalize wheel configuration data
     *
     * Если переданная строка является корректным JSON, возвращаем её как есть.
     * Если это массив – кодируем его в JSON, иначе возвращаем пустой JSON-массив.
     *
     * @param mixed $config
     * @return string
     */
    private function normalizeWheelConfig($config): string
    {
        if (is_string($config)) {
            json_decode($config);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $config;
            }
        }
        return is_array($config) ? json_encode($config) : '[]';
    }

    /**
     * Check if action is allowed for current user
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
