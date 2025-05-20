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
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'DOROSHKO_WISHREWARD::WHEEL_EDIT';
    private const EVENT_WHEEL_BEFORE_SAVE = 'wishreward_wheel_before_save';
    private const EVENT_WHEEL_AFTER_SAVE = 'wishreward_wheel_after_save';
    private const IMAGE_UPLOAD_PATH = 'wysiwyg/wishreward/';

    private const CTA_POSITION_BOTTOM_RIGHT = 'bottom-right';
    private const THEME_LIGHT = 'light';
    private const POPUP_SCROLL_TRIGGER_NONE = 'none';

    private WheelRepositoryInterface $wheelRepository;
    private WheelInterfaceFactory $wheelFactory;
    private EventManagerInterface $eventManager;
    private LoggerInterface $logger;
    private DataPersistorInterface $dataPersistor;
    private UploaderFactory $uploaderFactory;
    private Filesystem $filesystem;

    public function __construct(
        Action\Context $context,
        WheelRepositoryInterface $wheelRepository,
        WheelInterfaceFactory $wheelFactory,
        EventManagerInterface $eventManager,
        LoggerInterface $logger,
        DataPersistorInterface $dataPersistor,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->wheelRepository = $wheelRepository;
        $this->wheelFactory = $wheelFactory;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->dataPersistor = $dataPersistor;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParams();

        if (!$data) {
            $this->messageManager->addErrorMessage(__('No data provided to save.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->validateWheelData($data);
            $wheelId = isset($data['wheel_id']) ? (int)$data['wheel_id'] : 0;
            $wheel = $this->loadOrCreateWheel($wheelId);
            $this->populateWheelData($wheel, $data);

            $this->eventManager->dispatch(self::EVENT_WHEEL_BEFORE_SAVE, ['wheel' => $wheel, 'data' => $data]);
            $this->wheelRepository->save($wheel);
            $this->eventManager->dispatch(self::EVENT_WHEEL_AFTER_SAVE, ['wheel' => $wheel]);

            $this->messageManager->addSuccessMessage(__('Wheel "%1" has been saved successfully.', $wheel->getTitle()));
            $redirectPath = (isset($data['back']) && $data['back'] === 'edit') ? '*/*/edit' : '*/*/index';
            return $resultRedirect->setPath($redirectPath, ['wheel_id' => $wheel->getWheelId()]);
        } catch (\Exception $e) {
            $this->logger->error('Error saving wheel', [
                'wheel_id' => $data['wheel_id'] ?? 'new',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->messageManager->addExceptionMessage($e, __('An error occurred while saving the wheel: %1', $e->getMessage()));
            return $resultRedirect->setPath('*/*/edit', ['wheel_id' => $data['wheel_id'] ?? null]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     * @throws LocalizedException
     */
    private function validateWheelData(array $data): void
    {
        if (empty($data['title'])) {
            throw new LocalizedException(__('Title is required.'));
        }

        if (!empty($data['start_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['start_date']);
            $errors = \DateTime::getLastErrors();
            if ($date === false || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
                throw new LocalizedException(__('Invalid start date format.'));
            }
        }

        if (!empty($data['end_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['end_date']);
            $errors = \DateTime::getLastErrors();
            if ($date === false || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
                throw new LocalizedException(__('Invalid end date format.'));
            }
        }

        if (!empty($data['wheel_config'])) {
            json_decode((string)$data['wheel_config']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LocalizedException(__('Invalid JSON format in wheel configuration.'));
            }
        }

        if (!isset($data['storeviews']) || (!is_array($data['storeviews']) && !is_string($data['storeviews']))) {
            throw new LocalizedException(__('Store views must be provided.'));
        }
    }

    private function loadOrCreateWheel(int $wheelId): WheelInterface
    {
        if ($wheelId > 0) {
            return $this->wheelRepository->getById($wheelId);
        }
        return $this->wheelFactory->create();
    }

    /**
     * @param WheelInterface $wheel
     * @param array<string, mixed> $data
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
        $wheel->setCtaPosition((string)($data['cta_position'] ?? self::CTA_POSITION_BOTTOM_RIGHT));
        $wheel->setCtaCustomCss((string)($data['cta_custom_css'] ?? ''));

        $this->processImage($wheel, $data, 'cta_image', 'setCtaImage');
        $this->processImage($wheel, $data, 'popup_company_logo', 'setPopupCompanyLogo');

        $wheel->setPopupTitle((string)($data['popup_title'] ?? ''));
        $wheel->setPopupDescription((string)($data['popup_description'] ?? ''));
        $wheel->setIsWishAreaEnabled((bool)($data['is_wish_area_enabled'] ?? false));
        $wheel->setIsEmailInputEnabled((bool)($data['is_email_input_enabled'] ?? false));
        $wheel->setPopupDelay((int)($data['popup_delay'] ?? 0));
        $wheel->setPopupScrollTrigger((string)($data['popup_scroll_trigger'] ?? self::POPUP_SCROLL_TRIGGER_NONE));
        $wheel->setPopupOncePerSession((bool)($data['popup_once_per_session'] ?? true));

        $wheel->setPopupButtonText((string)($data['popup_button_text'] ?? ''));
        $wheel->setPopupCompanyText((string)($data['popup_company_text'] ?? ''));
        $wheel->setPopupDeclineText((string)($data['popup_decline_text'] ?? ''));
        $wheel->setPopupCloseText((string)($data['popup_close_text'] ?? ''));
        $wheel->setPopupTermsText((string)($data['popup_terms_text'] ?? ''));

        $wheel->setIsScrollEnabled((bool)($data['is_scroll_enabled'] ?? false));
        $wheel->setScrollPercentage((int)($data['scroll_percentage'] ?? 50));
        $wheel->setIsTimeoutEnabled((bool)($data['is_timeout_enabled'] ?? false));
        $wheel->setTimeoutDuration((int)($data['timeout_duration'] ?? 5000));
        $wheel->setIsExitEnabled((bool)($data['is_exit_enabled'] ?? false));

        $wheel->setPopupTheme((string)($data['popup_theme'] ?? self::THEME_LIGHT));
    }

    /**
     * @param WheelInterface $wheel
     * @param array<string, mixed> $data
     * @param string $fieldName
     * @param string $setterMethod
     * @return void
     */
    private function processImage(WheelInterface $wheel, array $data, string $fieldName, string $setterMethod): void
    {
        if (!isset($data[$fieldName][0]['url'], $data[$fieldName][0]['name'])) {
            $wheel->$setterMethod(null);
            return;
        }

        $fileName = ltrim((string)$data[$fieldName][0]['name'], '/');
        $imagePath = self::IMAGE_UPLOAD_PATH . $fileName;

        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$mediaDir->isFile($imagePath)) {
            $this->logger->warning("Image file does not exist in media: $imagePath");
            $wheel->$setterMethod(null);
            return;
        }

        $wheel->$setterMethod($imagePath);
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeToArray($value): array
    {
        if (is_string($value)) {
            return array_filter(explode(',', $value));
        }
        return is_array($value) ? $value : [];
    }

    /**
     * @param array<int, string> $values
     * @return string
     */
    private function arrayToCommaString(array $values): string
    {
        return !empty($values) ? implode(',', $values) : '';
    }

    /**
     * @param string|array|null $config
     * @return string
     */
    private function normalizeWheelConfig($config): string
    {
        if (is_string($config)) {
            json_decode($config);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $config;
            }
            return '[]';
        }
        if (is_array($config)) {
            $encoded = json_encode($config);
            return $encoded === false ? '[]' : $encoded;
        }
        return '[]';
    }
}
