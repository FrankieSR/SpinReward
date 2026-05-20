<?php

declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Doroshko\SpinReward\Api\WheelRepositoryInterface;
use Doroshko\SpinReward\Api\Data\WheelInterface;
use Doroshko\SpinReward\Api\Data\WheelInterfaceFactory;
use Psr\Log\LoggerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\Filter\Date as DateFilter;

class Save extends Action implements HttpPostActionInterface
{
    /** @var string Admin resource for wheel editing */
    public const ADMIN_RESOURCE = 'Doroshko_SpinReward::wheel_edit';
    /** @var string Event dispatched before saving wheel */
    private const EVENT_WHEEL_BEFORE_SAVE = 'wishreward_wheel_before_save';
    /** @var string Event dispatched after saving wheel */
    private const EVENT_WHEEL_AFTER_SAVE = 'wishreward_wheel_after_save';
    /** @var string Path for image uploads */
    private const IMAGE_UPLOAD_PATH = 'wysiwyg/wishreward/';
    /** @var string Default CTA position */
    private const CTA_POSITION_BOTTOM_RIGHT = 'bottom-right';
    /** @var string Default popup theme */
    private const THEME_LIGHT = 'light';
    /** @var string Default popup scroll trigger */
    private const POPUP_SCROLL_TRIGGER_NONE = 'none';
    /** @var string Form key for redirect action */
    private const FORM_KEY_BACK = 'back';
    /** @var string Form value for edit redirect */
    private const FORM_VALUE_EDIT = 'edit';
    /** @var array<int, string> Allowed CTA positions */
    private const ALLOWED_CTA_POSITIONS = ['bottom-right', 'top-left', 'top-right', 'bottom-left'];
    /** @var array<int, string> Allowed popup themes */
    private const ALLOWED_POPUP_THEMES = ['light', 'dark'];
    /** @var array<int, string> Allowed attempts period units */
    private const ALLOWED_ATTEMPTS_UNITS = ['day', 'week', 'month', 'year', 'forever'];

    private WheelRepositoryInterface $wheelRepository;
    private WheelInterfaceFactory $wheelFactory;
    private EventManagerInterface $eventManager;
    private LoggerInterface $logger;
    private UploaderFactory $uploaderFactory;
    private Filesystem $filesystem;
    private DateFilter $dateFilter;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param WheelRepositoryInterface $wheelRepository
     * @param WheelInterfaceFactory $wheelFactory
     * @param EventManagerInterface $eventManager
     * @param LoggerInterface $logger
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param DateFilter $dateFilter
     */
    public function __construct(
        Action\Context $context,
        WheelRepositoryInterface $wheelRepository,
        WheelInterfaceFactory $wheelFactory,
        EventManagerInterface $eventManager,
        LoggerInterface $logger,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        DateFilter $dateFilter
    ) {
        parent::__construct($context);
        $this->wheelRepository = $wheelRepository;
        $this->wheelFactory = $wheelFactory;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->dateFilter = $dateFilter;
    }

    /**
     * Execute save action.
     *
     * @return ResultInterface
     */
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
            $wheelId = isset($data[WheelInterface::WHEEL_ID]) ? (int)$data[WheelInterface::WHEEL_ID] : 0;
            $wheel = $this->loadOrCreateWheel($wheelId);
            $this->populateWheelData($wheel, $data);

            $this->eventManager->dispatch(self::EVENT_WHEEL_BEFORE_SAVE, ['wheel' => $wheel, 'data' => $data]);
            $this->wheelRepository->save($wheel);
            $this->eventManager->dispatch(self::EVENT_WHEEL_AFTER_SAVE, ['wheel' => $wheel]);

            $this->messageManager->addSuccessMessage(__('Wheel "%1" has been saved successfully.', $wheel->getTitle()));
            $redirectPath = (isset($data[self::FORM_KEY_BACK]) && $data[self::FORM_KEY_BACK] === self::FORM_VALUE_EDIT)
                ? '*/*/edit'
                : '*/*/index';
            return $resultRedirect->setPath($redirectPath, [WheelInterface::WHEEL_ID => $wheel->getWheelId()]);
        } catch (\Exception $e) {
            $this->logger->error('Error saving wheel', [
                WheelInterface::WHEEL_ID => $data[WheelInterface::WHEEL_ID] ?? 'new',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->messageManager->addExceptionMessage($e, __('An error occurred while saving the wheel: %1', $e->getMessage()));

            return $resultRedirect->setPath('*/*/edit', [WheelInterface::WHEEL_ID => $data[WheelInterface::WHEEL_ID] ?? null]);
        }
    }

    /**
     * Validate wheel form data.
     *
     * @param array<string, mixed> $data Form data
     * @throws LocalizedException If validation fails
     */
    private function validateWheelData(array $data): void
    {
        if (empty($data[WheelInterface::TITLE])) {
            throw new LocalizedException(__('Title is required.'));
        }

        $startDate = $this->parseDateField($data[WheelInterface::START_DATE] ?? null);
        if (!empty($data[WheelInterface::START_DATE]) && $startDate === null) {
            throw new LocalizedException(__('Invalid start date format.'));
        }

        $endDate = $this->parseDateField($data[WheelInterface::END_DATE] ?? null);
        if (!empty($data[WheelInterface::END_DATE]) && $endDate === null) {
            throw new LocalizedException(__('Invalid end date format.'));
        }

        if ($startDate !== null && $endDate !== null && $endDate < $startDate) {
            throw new LocalizedException(__('End date cannot be earlier than start date.'));
        }

        if (!empty($data[WheelInterface::WHEEL_CONFIG])) {
            json_decode((string)$data[WheelInterface::WHEEL_CONFIG]);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LocalizedException(__('Invalid JSON format in wheel configuration.'));
            }
        }

        if (!isset($data[WheelInterface::STOREVIEWS]) || (!is_array($data[WheelInterface::STOREVIEWS]) && !is_string($data[WheelInterface::STOREVIEWS]))) {
            throw new LocalizedException(__('Store views must be provided.'));
        }

        if (!empty($data[WheelInterface::CTA_POSITION]) && !in_array($data[WheelInterface::CTA_POSITION], self::ALLOWED_CTA_POSITIONS, true)) {
            throw new LocalizedException(__('Invalid CTA position.'));
        }

        if (!empty($data[WheelInterface::POPUP_THEME]) && !in_array($data[WheelInterface::POPUP_THEME], self::ALLOWED_POPUP_THEMES, true)) {
            throw new LocalizedException(__('Invalid popup theme.'));
        }

        if (isset($data[WheelInterface::SCROLL_PERCENTAGE]) && ($data[WheelInterface::SCROLL_PERCENTAGE] < 0 || $data[WheelInterface::SCROLL_PERCENTAGE] > 100)) {
            throw new LocalizedException(__('Scroll percentage must be between 0 and 100.'));
        }

        if (isset($data[WheelInterface::TIMEOUT_DURATION]) && $data[WheelInterface::TIMEOUT_DURATION] < 0) {
            throw new LocalizedException(__('Timeout duration cannot be negative.'));
        }

        if (isset($data[WheelInterface::ATTEMPTS_PER_USER]) && $data[WheelInterface::ATTEMPTS_PER_USER] > 0) {
            if (empty($data[WheelInterface::ATTEMPTS_PERIOD_UNIT]) || !in_array($data[WheelInterface::ATTEMPTS_PERIOD_UNIT], self::ALLOWED_ATTEMPTS_UNITS, true)) {
                throw new LocalizedException(__('Invalid attempts period unit.'));
            }
        }
    }

    /**
     * Load or create a wheel entity.
     *
     * @param int $wheelId Wheel ID
     * @return WheelInterface
     */
    private function loadOrCreateWheel(int $wheelId): WheelInterface
    {
        if ($wheelId > 0) {
            return $this->wheelRepository->getById($wheelId);
        }
        return $this->wheelFactory->create();
    }

    /**
     * Populate wheel entity with form data.
     *
     * @param WheelInterface $wheel Wheel entity
     * @param array<string, mixed> $data Form data
     */
    private function populateWheelData(WheelInterface $wheel, array $data): void
    {
        $this->setBasicSettings($wheel, $data);
        $this->setCtaSettings($wheel, $data);
        $this->setPopupSettings($wheel, $data);
        $this->setTriggerSettings($wheel, $data);
    }

    /**
     * Set basic wheel settings.
     *
     * @param WheelInterface $wheel Wheel entity
     * @param array<string, mixed> $data Form data
     */
    private function setBasicSettings(WheelInterface $wheel, array $data): void
    {
        $wheel->setTitle((string)($data[WheelInterface::TITLE] ?? ''));
        $wheel->setIsActive((bool)($data[WheelInterface::IS_ACTIVE] ?? false));
        $wheel->setWinMessage((string)($data[WheelInterface::WIN_MESSAGE] ?? ''));
        $wheel->setNoWinMessage((string)($data[WheelInterface::NO_WIN_MESSAGE] ?? ''));
        $wheel->setStartDate($this->normalizeDateValue($data[WheelInterface::START_DATE] ?? null));
        $wheel->setEndDate($this->normalizeDateValue($data[WheelInterface::END_DATE] ?? null));
        $wheel->setStoreviews($this->arrayToCommaString($this->normalizeToArray($data[WheelInterface::STOREVIEWS] ?? ['0'])));
        $wheel->setAllowedCustomerGroups($this->arrayToCommaString($this->normalizeToArray($data[WheelInterface::ALLOWED_CUSTOMER_GROUPS] ?? [])));
        $wheel->setWheelConfig($this->normalizeWheelConfig($data[WheelInterface::WHEEL_CONFIG] ?? '[]'));
    }

    /**
     * Set CTA-related wheel settings.
     *
     * @param WheelInterface $wheel Wheel entity
     * @param array<string, mixed> $data Form data
     */
    private function setCtaSettings(WheelInterface $wheel, array $data): void
    {
        $wheel->setIsCtaEnabled((bool)($data[WheelInterface::IS_CTA_ENABLED] ?? false));
        $wheel->setCtaLabel((string)($data[WheelInterface::CTA_LABEL] ?? ''));
        $wheel->setCtaButtonText((string)($data[WheelInterface::CTA_BUTTON_TEXT] ?? ''));
        $wheel->setCtaPosition((string)($data[WheelInterface::CTA_POSITION] ?? self::CTA_POSITION_BOTTOM_RIGHT));
        $wheel->setCtaCustomCss((string)($data[WheelInterface::CTA_CUSTOM_CSS] ?? ''));
        $this->processImage($wheel, $data, WheelInterface::CTA_IMAGE, 'setCtaImage');
    }

    /**
     * Set popup-related wheel settings.
     *
     * @param WheelInterface $wheel Wheel entity
     * @param array<string, mixed> $data Form data
     */
    private function setPopupSettings(WheelInterface $wheel, array $data): void
    {
        $this->processImage($wheel, $data, WheelInterface::POPUP_COMPANY_LOGO, 'setPopupCompanyLogo');
        $wheel->setPopupTitle((string)($data[WheelInterface::POPUP_TITLE] ?? ''));
        $wheel->setPopupDescription((string)($data[WheelInterface::POPUP_DESCRIPTION] ?? ''));
        $wheel->setIsWishAreaEnabled((bool)($data[WheelInterface::IS_WISH_AREA_ENABLED] ?? false));
        $wheel->setPopupDelay((int)($data[WheelInterface::POPUP_DELAY] ?? 0));
        $wheel->setPopupScrollTrigger((string)($data[WheelInterface::POPUP_SCROLL_TRIGGER] ?? self::POPUP_SCROLL_TRIGGER_NONE));
        $wheel->setPopupButtonText((string)($data[WheelInterface::POPUP_BUTTON_TEXT] ?? ''));
        $wheel->setPopupCompanyText((string)($data[WheelInterface::POPUP_COMPANY_TEXT] ?? ''));
        $wheel->setPopupDeclineText((string)($data[WheelInterface::POPUP_DECLINE_TEXT] ?? ''));
        $wheel->setPopupCloseText((string)($data[WheelInterface::POPUP_CLOSE_TEXT] ?? ''));
        $wheel->setPopupTermsText((string)($data[WheelInterface::POPUP_TERMS_TEXT] ?? ''));

        $attemptsPerUser = isset($data[WheelInterface::ATTEMPTS_PER_USER]) ? (int)$data[WheelInterface::ATTEMPTS_PER_USER] : 1;
        if ($attemptsPerUser <= 0) {
            $attemptsPerUser = 1;
        }
        $wheel->setAttemptsPerUser($attemptsPerUser);
        $wheel->setAttemptsPeriodUnit($data[WheelInterface::ATTEMPTS_PERIOD_UNIT] ?? '');
    }

    /**
     * Set trigger-related wheel settings.
     *
     * @param WheelInterface $wheel Wheel entity
     * @param array<string, mixed> $data Form data
     */
    private function setTriggerSettings(WheelInterface $wheel, array $data): void
    {
        $wheel->setIsScrollEnabled((bool)($data[WheelInterface::IS_SCROLL_ENABLED] ?? false));
        $wheel->setScrollPercentage((int)($data[WheelInterface::SCROLL_PERCENTAGE] ?? 50));
        $wheel->setIsTimeoutEnabled((bool)($data[WheelInterface::IS_TIMEOUT_ENABLED] ?? false));
        $wheel->setTimeoutDuration((int)($data[WheelInterface::TIMEOUT_DURATION] ?? 5000));
        $wheel->setIsExitEnabled((bool)($data[WheelInterface::IS_EXIT_ENABLED] ?? false));
        $wheel->setPopupTheme((string)($data[WheelInterface::POPUP_THEME] ?? self::THEME_LIGHT));
    }

    /**
     * Process image upload for wheel entity.
     *
     * @param WheelInterface $wheel Wheel entity
     * @param array<string, mixed> $data Form data
     * @param string $fieldName Image field name
     * @param string $setterMethod Setter method name
     * @throws LocalizedException If image validation fails
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

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $this->logger->warning("Invalid image file extension: $extension");
            $wheel->$setterMethod(null);
            throw new LocalizedException(__('Invalid image file type. Allowed: %1', implode(', ', $allowedExtensions)));
        }

        $fileSize = $mediaDir->stat($imagePath)['size'];
        if ($fileSize > 2 * 1024 * 1024) {
            $this->logger->warning("Image file too large: $fileSize bytes");
            $wheel->$setterMethod(null);
            throw new LocalizedException(__('Image file size exceeds 2MB limit.'));
        }

        $wheel->$setterMethod($imagePath);
    }

    /**
     * Normalize value to array.
     *
     * @param mixed $value Input value
     * @return array<int, string>
     */
    private function normalizeToArray($value): array
    {
        if (is_string($value)) {
            $items = explode(',', $value);
            return array_filter(array_map('trim', $items), function($v) {
                return $v !== '';
            });
        }
        return is_array($value) ? $value : [];
    }

    /**
     * Convert array to comma-separated string.
     *
     * @param array<int, string> $values Input array
     * @return string
     */
    private function arrayToCommaString(array $values): string
    {
        return !empty($values) ? implode(',', $values) : '';
    }

    /**
     * Normalize wheel configuration.
     *
     * @param string|array|null $config Input configuration
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

    private function parseDateField(?string $value): ?\DateTimeImmutable
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if ($value === '0000-00-00' || str_starts_with($value, '0000-00-00 ')) {
            return null;
        }

        $formats = [
            'Y-m-d',
            'Y-m-d H:i:s',
            'Y-m-d\\TH:i:sP',
            'Y-m-d\\TH:i:s.uP',
            'Y-m-d\\TH:i:s.vP',
        ];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date !== false && !$this->hasDateErrors($errors)) {
                return $date;
            }
        }

        try {
            $normalized = $this->dateFilter->filter($value);
            if (is_string($normalized) && $normalized !== '') {
                return \DateTimeImmutable::createFromFormat('!Y-m-d', $normalized) ?: null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function normalizeDateValue(?string $value): ?string
    {
        $date = $this->parseDateField($value);
        return $date ? $date->format('Y-m-d') : null;
    }

    private function hasDateErrors(array|false $errors): bool
    {
        if ($errors === false) {
            return false;
        }

        return ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0;
    }
}
