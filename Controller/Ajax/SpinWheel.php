<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Doroshko\SpinReward\Api\WheelRepositoryInterface;
use Doroshko\SpinReward\Api\SpinAnalyticsRepositoryInterface;
use Doroshko\SpinReward\Api\SpinLimitValidatorInterface;
use Doroshko\SpinReward\Model\Analytics\EventLogger;
use Doroshko\SpinReward\Model\CouponGenerator;
use Doroshko\SpinReward\Service\ProbabilityCalculator;
use Doroshko\SpinReward\Model\WishValidationValidator;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Doroshko\SpinReward\Model\SpinCompletionState;

/**
 * Controller for spinning the wheel and sending a coupon email
 */
class SpinWheel implements HttpPostActionInterface
{
    private const ERROR_INVALID_WHEEL_ID = 'Invalid Wheel ID.';
    private const ERROR_WHEEL_NOT_ACTIVE = 'Wheel is not active.';
    private const ERROR_SECTORS_NOT_CONFIGURED = 'Wheel sectors are not configured.';
    private const ERROR_WISH_REQUIRED = 'Wish message is required.';
    private const ERROR_EMAIL_REQUIRED = 'Email is required.';
    private const ERROR_COUPON_GENERATION_FAILED = 'Failed to generate coupon.';
    private const ERROR_PROCESSING_REQUEST = 'An error occurred while processing your request.';
    private const ERROR_SPIN_LIMIT_REACHED = 'You have reached the spin limit.';
    private const ERROR_CONSENT_REQUIRED = 'Consent is required.';
    private const ERROR_EMAIL_SENDING_FAILED = 'Failed to send coupon email.';
    private const ERROR_WHEEL_NOT_AVAILABLE = 'Wheel is not available.';
    private const ERROR_SPIN_LOCKED = 'A spin is already in progress. Please try again.';
    private const EMAIL_TEMPLATE_CONFIG_PATH = 'wishreward_settings/general/winning_coupon_email_template';

    private JsonFactory $jsonFactory;
    private RequestInterface $request;
    private LoggerInterface $logger;
    private WheelRepositoryInterface $wheelRepository;
    private SpinAnalyticsRepositoryInterface $spinAnalyticsRepository;
    
    private CouponGenerator $couponGenerator;
    private ProbabilityCalculator $probabilityCalculator;
    private WishValidationValidator $mlValidator;
    private Session $customerSession;
    private TransportBuilder $transportBuilder;
    private StateInterface $inlineTranslation;
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private ManagerInterface $messageManager;
    private EmailValidator $emailValidator;
    private Header $httpHeader;
    private SessionManagerInterface $sessionManager;
    private SpinLimitValidatorInterface $spinLimitValidator;
    private ResourceConnection $resourceConnection;
    private EventLogger $eventLogger;
    private SpinCompletionState $spinCompletionState;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        WheelRepositoryInterface $wheelRepository,
        SpinAnalyticsRepositoryInterface $spinAnalyticsRepository,
        SpinLimitValidatorInterface $spinLimitValidator,
        CouponGenerator $couponGenerator,
        ProbabilityCalculator $probabilityCalculator,
        WishValidationValidator $mlValidator,
        Session $customerSession,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager,
        EmailValidator $emailValidator,
        Header $httpHeader,
        SessionManagerInterface $sessionManager,
        ResourceConnection $resourceConnection,
        EventLogger $eventLogger,
        SpinCompletionState $spinCompletionState
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->wheelRepository = $wheelRepository;
        $this->spinAnalyticsRepository = $spinAnalyticsRepository;
        $this->spinLimitValidator = $spinLimitValidator;
        $this->couponGenerator = $couponGenerator;
        $this->probabilityCalculator = $probabilityCalculator;
        $this->mlValidator = $mlValidator;
        $this->customerSession = $customerSession;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->emailValidator = $emailValidator;
        $this->httpHeader = $httpHeader;
        $this->sessionManager = $sessionManager;
        $this->resourceConnection = $resourceConnection;
        $this->eventLogger = $eventLogger;
        $this->spinCompletionState = $spinCompletionState;
    }

    /**
     * Execute the spin wheel action
     */
    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();
        $postData = $this->request->getPostValue();
        $wheelId = (int)($postData['wheel_id'] ?? 0);

        try {
            if ($wheelId <= 0) {
                return $this->createErrorResponse(self::ERROR_INVALID_WHEEL_ID, 400);
            }

            $wheel = $this->wheelRepository->getEligibleWheelById($wheelId);
            if (!$wheel) {
                $this->logger->warning('Wheel is not eligible for spin request', ['wheel_id' => $wheelId]);
                return $this->createErrorResponse(self::ERROR_WHEEL_NOT_AVAILABLE, 404);
            }

            $sectors = $this->getWheelSectors($wheel);
            if (empty($sectors)) {
                return $this->createErrorResponse(self::ERROR_SECTORS_NOT_CONFIGURED);
            }

            $validationResult = $this->validateRequest($postData);
            $email = (string)($validationResult['email'] ?? trim((string)($postData['email'] ?? '')));
            $consentGiven = (bool)($postData['consent_given'] ?? false);
            $spinId = $this->createSpinRecord($wheelId, $email, $wheel, $postData, $sectors, $consentGiven);
            $this->eventLogger->recordEvent('spin_submit', [
                'spin_id' => $spinId,
                'wheel_id' => $wheelId,
                'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                'email' => $email,
                'metadata' => [
                    'device_type' => $postData['device_type'] ?? null,
                    'utm_source' => $postData['utm_source'] ?? null,
                    'utm_medium' => $postData['utm_medium'] ?? null,
                    'utm_campaign' => $postData['utm_campaign'] ?? null,
                ],
            ]);

            if (!$validationResult['success']) {
                $blockReason = (string)($validationResult['message'] ?? self::ERROR_PROCESSING_REQUEST);
                $this->markSpinBlocked($spinId, $blockReason);
                $this->eventLogger->recordEvent('validation_failed', [
                    'spin_id' => $spinId,
                    'wheel_id' => $wheelId,
                    'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                    'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                    'email' => $email,
                    'block_reason' => $blockReason,
                ]);

                return $resultJson->setData($validationResult);
            }

            $lockName = $this->buildSpinLockName($wheelId, $email, $wheel->getAttemptsPeriodUnit());
            if (!$this->acquireSpinLock($lockName)) {
                $this->markSpinBlocked($spinId, self::ERROR_SPIN_LOCKED);

                return $this->createErrorResponse(self::ERROR_SPIN_LOCKED, 429);
            }

            $winningSector = [];
            $couponCode = null;
            $emailResult = ['success' => true];

            try {
                if ($wheel->getIsWishAreaEnabled()) {
                    $wishStartedAt = microtime(true);
                    $wishValidation = $this->validateWishMessage((string)($postData['wish'] ?? ''));
                    $wishDurationMs = (int)round((microtime(true) - $wishStartedAt) * 1000);

                    if (!$wishValidation['success']) {
                        $mlStatus = 'rejected';
                        $this->updateSpinRecord($spinId, [
                            'spin_status' => 'blocked',
                            'block_reason' => (string)($wishValidation['reason'] ?? $wishValidation['message'] ?? self::ERROR_WISH_REQUIRED),
                            'ml_status' => $mlStatus,
                            'ml_category' => (string)($wishValidation['reason'] ?? 'wish_validation_failed'),
                            'ml_duration_ms' => $wishDurationMs,
                        ]);
                        $this->eventLogger->recordEvent('ml_rejected', [
                            'spin_id' => $spinId,
                            'wheel_id' => $wheelId,
                            'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                            'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                            'email' => $email,
                            'block_reason' => (string)($wishValidation['reason'] ?? 'wish_validation_failed'),
                            'ml_status' => $mlStatus,
                            'ml_category' => (string)($wishValidation['reason'] ?? 'wish_validation_failed'),
                            'ml_duration_ms' => $wishDurationMs,
                            'metadata' => [
                                'wish_validation' => $wishValidation,
                            ],
                        ]);
                        $this->updateSpinRecord($spinId, [
                            'spin_result' => 'blocked',
                        ]);

                        return $resultJson->setData($wishValidation);
                    }

                    $this->updateSpinRecord($spinId, [
                        'ml_status' => 'passed',
                        'ml_duration_ms' => $wishDurationMs,
                    ]);
                    $this->eventLogger->recordEvent('ml_passed', [
                        'spin_id' => $spinId,
                        'wheel_id' => $wheelId,
                        'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                        'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                        'email' => $email,
                        'ml_status' => 'passed',
                        'ml_duration_ms' => $wishDurationMs,
                    ]);
                }

                if (!$this->spinLimitValidator->canSpin($email, $wheelId)) {
                    $this->markSpinBlocked($spinId, self::ERROR_SPIN_LIMIT_REACHED);
                    $this->eventLogger->recordEvent('limit_blocked', [
                        'spin_id' => $spinId,
                        'wheel_id' => $wheelId,
                        'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                        'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                        'email' => $email,
                        'block_reason' => 'limit_blocked',
                    ]);

                    return $resultJson->setData([
                        'success' => false,
                        'message' => __(self::ERROR_SPIN_LIMIT_REACHED)
                    ]);
                }

                $winningSector = $this->probabilityCalculator->getWinningSector($sectors);
                $isWin = !empty($winningSector['rule_id']);

                if ($ruleId = ($winningSector['rule_id'] ?? null)) {
                    $couponCode = $this->generateCoupon((int)$ruleId, (string)($winningSector['id'] ?? ''));
                    if (!$couponCode) {
                        $this->markSpinBlocked($spinId, self::ERROR_COUPON_GENERATION_FAILED);
                        $this->eventLogger->recordEvent('spin_blocked', [
                            'spin_id' => $spinId,
                            'wheel_id' => $wheelId,
                            'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                            'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                            'email' => $email,
                            'block_reason' => self::ERROR_COUPON_GENERATION_FAILED,
                        ]);
                        throw new LocalizedException(__(self::ERROR_COUPON_GENERATION_FAILED));
                    }

                    $this->eventLogger->recordEvent('coupon_generated', [
                        'spin_id' => $spinId,
                        'wheel_id' => $wheelId,
                        'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                        'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                        'email' => $email,
                        'sector_id' => (string)($winningSector['id'] ?? ''),
                        'sector_label' => (string)($winningSector['label'] ?? ''),
                        'coupon_code' => $couponCode,
                    ]);
                }

                $this->eventLogger->recordEvent('spin_validated', [
                    'spin_id' => $spinId,
                    'wheel_id' => $wheelId,
                    'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                    'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                    'email' => $email,
                    'sector_id' => (string)($winningSector['id'] ?? ''),
                    'sector_label' => (string)($winningSector['label'] ?? ''),
                    'is_win' => !empty($couponCode),
                ]);

                $spinStatus = $couponCode ? 'completed' : 'completed';
                $spinResult = $couponCode ? 'win' : 'lose';
                $this->updateSpinRecord($spinId, [
                    'spin_status' => $spinStatus,
                    'spin_result' => $spinResult,
                    'sector_id' => (string)($winningSector['id'] ?? ''),
                    'spin_prize_label' => (string)($winningSector['label'] ?? ''),
                    'coupon_code' => $couponCode,
                    'block_reason' => null,
                    'ml_status' => $wheel->getIsWishAreaEnabled() ? 'passed' : null,
                ]);

                $this->spinCompletionState->markCompleted($wheel, [
                    'result' => $spinResult,
                    'coupon_code' => $couponCode,
                    'message' => $couponCode
                        ? (string)($wheel->getWinMessage() ?: __('Congratulations'))
                        : (string)($wheel->getNoWinMessage() ?: __('Maybe next time')),
                    'sector_label' => (string)($winningSector['label'] ?? ''),
                    'spin_result' => $spinResult,
                ]);
            } finally {
                $this->releaseSpinLock($lockName);
            }

            if ($couponCode) {
                $emailResult = $this->sendCouponEmail($email, $couponCode, (string)($winningSector['label'] ?? ''));
                $this->updateSpinRecord($spinId, [
                    'email_send_status' => !empty($emailResult['success']) ? 'sent' : 'failed',
                    'email_error' => $emailResult['success'] ? null : (string)($emailResult['message'] ?? self::ERROR_EMAIL_SENDING_FAILED),
                    'email_sent_at' => !empty($emailResult['success']) ? gmdate('Y-m-d H:i:s') : null,
                ]);
                $this->eventLogger->recordEvent(!empty($emailResult['success']) ? 'email_sent' : 'email_failed', [
                    'spin_id' => $spinId,
                    'wheel_id' => $wheelId,
                    'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
                    'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
                    'email' => $email,
                    'coupon_code' => $couponCode,
                    'ml_status' => $wheel->getIsWishAreaEnabled() ? 'passed' : null,
                    'metadata' => [
                        'success' => !empty($emailResult['success']),
                    ],
                ]);
            }

            return $resultJson->setData([
                'success' => true,
                'sector_id' => $winningSector['id'] ?? null,
                'spin_result' => $spinResult,
                'result_text' => $winningSector['result_text'] ?? null,
                'coupon_code' => $couponCode,
                'message' => $couponCode
                    ? (string)($wheel->getWinMessage() ?: __('Congratulations'))
                    : (string)($wheel->getNoWinMessage() ?: __('Maybe next time'))
            ]);
        } catch (LocalizedException $e) {
            $this->logger->error('Localized exception in SpinWheel: ' . $e->getMessage(), ['exception' => $e]);
            return $this->createErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error in SpinWheel: ' . $e->getMessage(), ['exception' => $e]);
            return $this->createErrorResponse(self::ERROR_PROCESSING_REQUEST);
        }
    }

    /**
     * Validate the incoming request data
     *
     * @param array $postData
     * @return array
     */
    private function validateRequest(array $postData): array
    {
        $email = $this->customerSession->isLoggedIn()
            ? trim((string)$this->customerSession->getCustomer()->getEmail())
            : trim((string)($postData['email'] ?? ''));

        if (empty($email)) {
            return ['success' => false, 'message' => __(self::ERROR_EMAIL_REQUIRED)];
        }

        if (!$this->emailValidator->isValid($email)) {
            return ['success' => false, 'message' => __('Invalid email format.')];
        }

        if (!(bool)($postData['consent_given'] ?? false)) {
            return ['success' => false, 'message' => __(self::ERROR_CONSENT_REQUIRED)];
        }

        return ['success' => true, 'email' => $email];
    }

    /**
     * Get wheel sectors from configuration
     *
     * @param mixed $wheel
     * @return array
     */
    private function getWheelSectors($wheel): array
    {
        $sectors = json_decode($wheel->getWheelConfig() ?? '{}', true);

        return $sectors;
    }

    /**
     * Validate wish message
     *
     * @param string $wishMessage
     * @return array
     */
    private function validateWishMessage(string $wishMessage): array
    {
        $wishMessage = trim($wishMessage);

        if (empty($wishMessage)) {
            return ['success' => false, 'message' => __(self::ERROR_WISH_REQUIRED)];
        }

        $validation = $this->mlValidator->validateText($wishMessage);

        if ($validation['status'] === 'invalid') {
            return [
                'success' => false,
                'status' => $validation['status'],
                'reason' => $validation['reason']
            ];
        }

        return ['success' => true];
    }

    /**
     * Generate coupon for the winning sector
     *
     * @param int $ruleId
     * @param string $sectorId
     * @return string|null
     */
    private function generateCoupon(int $ruleId, string $sectorId): ?string
    {
        $couponCode = $this->couponGenerator->generate($ruleId);

        if (!$couponCode) {
            $this->logger->warning('Failed to generate coupon for sector: ' . $sectorId);
            return null;
        }

        return $couponCode;
    }

    /**
     * Send coupon email to the customer
     *
     * @param string $email
     * @param string $couponCode
     * @param string $sectorLabel
     * @return array
     */
    private function sendCouponEmail(string $email, string $couponCode, string $sectorLabel): array
    {
        $translationSuspended = false;

        try {
            $customerName = $this->customerSession->isLoggedIn() ? $this->customerSession->getCustomer()->getName() : 'Customer';
            $store = $this->storeManager->getStore();
            $storeId = $store->getId();
            $storeUrl = $store->getBaseUrl();

            $templateId = $this->scopeConfig->getValue(
                self::EMAIL_TEMPLATE_CONFIG_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            if (!$templateId) {
                $this->logger->error('Email template not configured in wishreward_settings/general/winning_coupon_email_template');
                return [
                    'success' => false,
                    'message' => __('You won: %1. Email sending failed due to missing template configuration.', $sectorLabel)
                ];
            }

            $templateVars = [
                'customer_name' => $customerName,
                'coupon_code' => $couponCode,
                'store_url' => $storeUrl
            ];

            $this->inlineTranslation->suspend();
            $translationSuspended = true;
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope('general')
                ->addTo($email, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->messageManager->addSuccessMessage(__('Coupon email sent to %1.', $email));

            return ['success' => true];
        } catch (MailException $e) {
            $this->logger->error('Email sending failed: ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Failed to send coupon email, please contact support.'));
            return [
                'success' => false,
                'message' => __('You won: %1. Email sending failed, please contact support.', $sectorLabel)
            ];
        } finally {
            if ($translationSuspended) {
                $this->inlineTranslation->resume();
            }
        }
    }

    private function createSpinRecord(int $wheelId, string $email, $wheel, array $postData, array $sectors, bool $consentGiven): int
    {
        $data = [
            'wheel_id' => $wheelId,
            'customer_id' => $this->customerSession->isLoggedIn() ? (int)$this->customerSession->getCustomerId() : null,
            'email' => $email,
            'spin_result' => 'submitted',
            'spin_prize_label' => null,
            'coupon_code' => null,
            'spin_date' => gmdate('Y-m-d H:i:s'),
            'ip_address' => $this->anonymizeIp(method_exists($this->request, 'getClientIp') ? $this->request->getClientIp() : null),
            'is_guest' => $this->customerSession->isLoggedIn() ? 0 : 1,
            'consent_given' => $consentGiven ? 1 : 0,
            'store_id' => (int)($postData['store_id'] ?? $this->storeManager->getStore()->getId()),
            'website_id' => (int)($postData['website_id'] ?? $this->storeManager->getStore()->getWebsiteId()),
            'spin_status' => 'submitted',
            'block_reason' => null,
            'sector_probability_snapshot' => json_encode($sectors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'utm_source' => $postData['utm_source'] ?? null,
            'utm_medium' => $postData['utm_medium'] ?? null,
            'utm_campaign' => $postData['utm_campaign'] ?? null,
            'user_agent' => substr((string)$this->httpHeader->getHttpUserAgent(), 0, 512),
            'device_type' => $this->normalizeDeviceType($postData['device_type'] ?? null),
            'session_id' => $this->sessionManager->getSessionId(),
            'page_url' => $postData['page_url'] ?? (method_exists($this->request, 'getServer') ? $this->request->getServer('HTTP_REFERER') : null),
            'referrer_url' => $postData['referrer_url'] ?? (method_exists($this->request, 'getServer') ? $this->request->getServer('HTTP_REFERER') : null),
            'is_redeemed' => 0,
            'redeemed_at' => null,
            'spin_count_session' => 0,
            'email_send_status' => null,
            'email_error' => null,
            'email_sent_at' => null,
            'coupon_applied_at' => null,
            'base_subtotal' => null,
            'base_discount_amount' => null,
            'base_grand_total' => null,
        ];

        return $this->spinAnalyticsRepository->saveSpin($data);
    }

    private function updateSpinRecord(int $spinId, array $data): void
    {
        if ($spinId <= 0) {
            return;
        }

        $this->spinAnalyticsRepository->updateSpin($spinId, $data);
    }

    private function markSpinBlocked(int $spinId, string $blockReason): void
    {
        $this->updateSpinRecord($spinId, [
            'spin_status' => 'blocked',
            'spin_result' => 'blocked',
            'block_reason' => $blockReason,
        ]);
    }

    private function normalizeDeviceType(mixed $deviceType): ?string
    {
        $deviceType = strtolower(trim((string)$deviceType));
        $validDeviceTypes = ['mobile', 'tablet', 'desktop'];

        if ($deviceType === '') {
            return null;
        }

        if (!in_array($deviceType, $validDeviceTypes, true)) {
            $this->logger->warning('Invalid device_type provided: ' . $deviceType);
            return null;
        }

        return $deviceType;
    }

    private function buildSpinLockName(int $wheelId, string $email, string $periodUnit): string
    {
        $bucket = $this->getPeriodBucket($periodUnit);
        $raw = sprintf('%d|%s|%s', $wheelId, strtolower(trim($email)), $bucket);

        return 'wishreward_spin_' . substr(sha1($raw), 0, 40);
    }

    private function getPeriodBucket(string $periodUnit): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return match (strtolower($periodUnit)) {
            'week' => $now->format('o-\WW'),
            'month' => $now->format('Y-m'),
            'year' => $now->format('Y'),
            'forever' => 'forever',
            default => $now->format('Y-m-d'),
        };
    }

    private function acquireSpinLock(string $lockName, int $timeoutSeconds = 3): bool
    {
        try {
            $result = $this->resourceConnection->getConnection()->fetchOne(
                'SELECT GET_LOCK(?, ?)',
                [$lockName, $timeoutSeconds]
            );

            return (string)$result === '1';
        } catch (\Throwable $e) {
            $this->logger->error('Failed to acquire spin lock', ['lock' => $lockName, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function releaseSpinLock(string $lockName): void
    {
        try {
            $this->resourceConnection->getConnection()->fetchOne(
                'SELECT RELEASE_LOCK(?)',
                [$lockName]
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to release spin lock', ['lock' => $lockName, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Create error response
     *
     * @param string $message
     * @return Json
     */
    private function createErrorResponse(string $message, int $statusCode = 400): Json
    {
        return $this->jsonFactory->create()
            ->setHttpResponseCode($statusCode)
            ->setData(['success' => false, 'message' => __($message)]);
    }

    /**
     * Anonymize IP address
     *
     * @param string $ip
     * @return string
     */
    private function anonymizeIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return null;
        }

        $length = strlen($packed);
        if ($length === 4) {
            return inet_ntop(substr($packed, 0, 3) . "\x00");
        }

        if ($length === 16) {
            return inet_ntop(substr($packed, 0, 8) . str_repeat("\x00", 8));
        }

        return null;
    }
}
