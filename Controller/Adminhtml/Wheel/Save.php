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
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Message\ManagerInterface;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Doroshko_WishReward::wheel_edit';
    private const EVENT_WHEEL_BEFORE_SAVE = 'wishreward_wheel_before_save';
    private const EVENT_WHEEL_AFTER_SAVE  = 'wishreward_wheel_after_save';

    private WheelRepositoryInterface $wheelRepository;
    private WheelInterfaceFactory $wheelFactory;
    private EventManagerInterface $eventManager;
    private LoggerInterface $logger;
    private DataPersistorInterface $dataPersistor;
    public $messageManager;

    public function __construct(
        Action\Context $context,
        WheelRepositoryInterface $wheelRepository,
        WheelInterfaceFactory $wheelFactory,
        EventManagerInterface $eventManager,
        LoggerInterface $logger,
        DataPersistorInterface $dataPersistor,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->wheelRepository = $wheelRepository;
        $this->wheelFactory    = $wheelFactory;
        $this->eventManager    = $eventManager;
        $this->logger          = $logger;
        $this->dataPersistor   = $dataPersistor;
        $this->messageManager  = $messageManager;
    }

    /**
     * Execute save action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->_request->getParams();

        if (!$data) {
            $this->messageManager->addErrorMessage(__('No data provided to save.'));
            return $resultRedirect->setPath('*/*/index');
        }

        // Логируем входящие данные для отладки
        $this->logger->debug('Form data received: ' . json_encode($data));

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
            $this->logger->critical('Unexpected error while saving wheel: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
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

        $wheel->setStoreviews($this->arrayToCommaString($this->normalizeToArray($data['storeviews'] ?? ['0'])));
        $wheel->setAllowedCustomerGroups($this->arrayToCommaString($this->normalizeToArray($data['allowed_customer_groups'] ?? [])));
        $wheel->setWheelConfig($this->normalizeWheelConfig($data['wheel_config'] ?? '[]'));

        $wheel->setIsCtaEnabled((bool)($data['is_cta_enabled'] ?? false));
        $wheel->setCtaLabel((string)($data['cta_label'] ?? ''));
        $wheel->setCtaButtonText((string)($data['cta_button_text'] ?? ''));
        $wheel->setCtaPosition((string)($data['cta_position'] ?? 'bottom-right'));
        $wheel->setCtaCustomCss((string)($data['cta_custom_css'] ?? ''));

        // Process images using universal method
        $this->processImage($wheel, $data, 'cta_image', 'setCtaImage');
        $this->processImage($wheel, $data, 'popup_company_logo', 'setPopupCompanyLogo');

        // Popup settings
        $wheel->setPopupTitle((string)($data['popup_title'] ?? ''));
        $wheel->setPopupDescription((string)($data['popup_description'] ?? ''));
        $wheel->setIsWishAreaEnabled((bool)($data['is_wish_area_enabled'] ?? false));
        $wheel->setIsEmailInputEnabled((bool)($data['is_email_input_enabled'] ?? false));
        $wheel->setPopupDelay((int)($data['popup_delay'] ?? 0));
        $wheel->setPopupScrollTrigger((string)($data['popup_scroll_trigger'] ?? 'none'));
        $wheel->setPopupOncePerSession((bool)($data['popup_once_per_session'] ?? true));

        // New popup fields
        $wheel->setPopupButtonText((string)($data['popup_button_text'] ?? ''));
        $wheel->setPopupCompanyText((string)($data['popup_company_text'] ?? ''));
        $wheel->setPopupDeclineText((string)($data['popup_decline_text'] ?? ''));
        $wheel->setPopupCloseText((string)($data['popup_close_text'] ?? ''));
        $wheel->setPopupTermsText((string)($data['popup_terms_text'] ?? ''));

        // New Trigger fields
        $wheel->setIsScrollEnabled((bool)($data['is_scroll_enabled'] ?? false));
        $wheel->setScrollPercentage((int)($data['scroll_percentage'] ?? 50));
        $wheel->setIsTimeoutEnabled((bool)($data['is_timeout_enabled'] ?? false));
        $wheel->setTimeoutDuration((int)($data['timeout_duration'] ?? 5000));
        $wheel->setIsExitEnabled((bool)($data['is_exit_enabled'] ?? false));
        // $wheel->setIsExitEnabled((bool)($data['once_per_user'] ?? false));

        // Theme setting
        $wheel->setPopupTheme((string)($data['popup_theme'] ?? 'light'));
    }

    /**
     * Universal method for processing image data
     *
     * @param WheelInterface $wheel
     * @param array $data
     * @param string $fieldName Field name in data array
     * @param string $setterMethod Setter method name in WheelInterface
     * @return void
     */
    private function processImage(WheelInterface $wheel, array $data, string $fieldName, string $setterMethod): void
    {
        $image = $data[$fieldName] ?? null;
        if (is_array($image) && !empty($image[0]['name'])) {
            $wheel->$setterMethod('wysiwyg/wishreward/' . $image[0]['name']);
        } elseif (is_string($image)) {
            $wheel->$setterMethod($image);
        } else {
            $wheel->$setterMethod(null);
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