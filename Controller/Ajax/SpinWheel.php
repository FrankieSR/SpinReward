<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Ajax;

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
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Doroshko\WishReward\Api\SpinLogRepositoryInterface;
use Doroshko\WishReward\Model\CouponGenerator;
use Doroshko\WishReward\Model\ProbabilityCalculator;
use Doroshko\WishReward\Model\LocalMLValidator;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;

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
    private const ERROR_SPIN_LIMIT_REACHED = 'You have reached the spin limit for today.';
    private const ERROR_CONSENT_REQUIRED = 'Consent is required.';
    private const ERROR_EMAIL_SENDING_FAILED = 'Failed to send coupon email.';
    private const EMAIL_TEMPLATE_CONFIG_PATH = 'wishreward_settings/general/winning_coupon_email_template';

    private JsonFactory $jsonFactory;
    private RequestInterface $request;
    private LoggerInterface $logger;
    private WheelRepositoryInterface $wheelRepository;
    private SpinLogRepositoryInterface $spinLogRepository;
    private CouponGenerator $couponGenerator;
    private ProbabilityCalculator $probabilityCalculator;
    private LocalMLValidator $mlValidator;
    private Session $customerSession;
    private TransportBuilder $transportBuilder;
    private StateInterface $inlineTranslation;
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private ManagerInterface $messageManager;
    private EmailValidator $emailValidator;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        WheelRepositoryInterface $wheelRepository,
        SpinLogRepositoryInterface $spinLogRepository,
        CouponGenerator $couponGenerator,
        ProbabilityCalculator $probabilityCalculator,
        LocalMLValidator $mlValidator,
        Session $customerSession,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager,
        EmailValidator $emailValidator
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->wheelRepository = $wheelRepository;
        $this->spinLogRepository = $spinLogRepository;
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
    }

    /**
     * Execute the spin wheel action
     */
    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();
        $postData = $this->request->getPostValue();

        try {
            $validationResult = $this->validateRequest($postData);
            if (!$validationResult['success']) {
                return $resultJson->setData($validationResult);
            }

            $wheelId = (int)$postData['wheel_id'];
            $email = $validationResult['email'];
            $consentGiven = (bool)($postData['consent_given'] ?? false);

            $wheel = $this->wheelRepository->getById($wheelId);
            if (!$wheel->isActive()) {
                return $this->createErrorResponse(self::ERROR_WHEEL_NOT_ACTIVE);
            }

            $sectors = $this->getWheelSectors($wheel);
            if (empty($sectors)) {
                return $this->createErrorResponse(self::ERROR_SECTORS_NOT_CONFIGURED);
            }

            if ($wheel->getIsWishAreaEnabled()) {
                $wishValidation = $this->validateWishMessage($postData['wish'] ?? '');
                if (!$wishValidation['success']) {
                    return $resultJson->setData($wishValidation);
                }
            }

            $winningSector = $this->probabilityCalculator->getWinningSector($sectors);
            $this->logger->debug('Winning sector: ' . json_encode($winningSector));

            $couponCode = null;
            if ($ruleId = ($winningSector['rule_id'] ?? null)) {
                $couponCode = $this->generateCoupon($ruleId, $winningSector['id']);
                if (!$couponCode) {
                    return $this->createErrorResponse(self::ERROR_COUPON_GENERATION_FAILED);
                }

                $emailResult = $this->sendCouponEmail($email, $couponCode, $winningSector['label']);
                if (!$emailResult['success']) {
                    return $resultJson->setData($emailResult);
                }
            }

            $this->saveSpinLog($wheelId, $email, $couponCode ?: $winningSector['label'], $consentGiven);

            return $resultJson->setData([
                'success' => true,
                'sector_id' => $winningSector['id'],
                'result_text' => $winningSector['result_text'],
                'coupon_code' => $couponCode,
                'message' => $couponCode
                    ? __('You won: %1. Check your email for the coupon code.', $winningSector['label'])
                    : __('Try again: %1', $winningSector['label'])
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
        $this->logger->debug('SpinWheel request received with params: ' . json_encode($postData));

        $wheelId = (int)($postData['wheel_id'] ?? 0);
        if ($wheelId <= 0) {
            $this->logger->warning('Invalid wheel_id provided: ' . $wheelId);
            return ['success' => false, 'message' => __(self::ERROR_INVALID_WHEEL_ID)];
        }

        $email = trim($postData['email'] ?? '') ?: ($this->customerSession->isLoggedIn() ? $this->customerSession->getCustomer()->getEmail() : '');
        if (empty($email)) {
            return ['success' => false, 'message' => __(self::ERROR_EMAIL_REQUIRED)];
        }

        if (!$this->emailValidator->isValid($email)) {
            return ['success' => false, 'message' => __('Invalid email format.')];
        }

        if (!(bool)($postData['consent_given'] ?? false)) {
            return ['success' => false, 'message' => __(self::ERROR_CONSENT_REQUIRED)];
        }

        if (!$this->spinLogRepository->canSpin($email)) {
            $this->logger->info('Spin limit reached for email: ' . $email);
            return ['success' => false, 'message' => __(self::ERROR_SPIN_LIMIT_REACHED)];
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
        $this->logger->debug('Sectors: ' . json_encode($sectors));
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
        $this->logger->debug('Wish message: ' . $wishMessage);

        if (empty($wishMessage)) {
            return ['success' => false, 'message' => __(self::ERROR_WISH_REQUIRED)];
        }

        $validation = $this->mlValidator->validateText($wishMessage);
        $this->logger->debug('Validation result: ' . json_encode($validation));

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
        $this->logger->debug('Generating coupon for rule_id: ' . $ruleId);
        $couponCode = $this->couponGenerator->generate($ruleId);

        if (!$couponCode) {
            $this->logger->warning('Failed to generate coupon for sector: ' . $sectorId);
            return null;
        }

        $this->logger->debug('Coupon code generated: ' . $couponCode);
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
                    'success' => true,
                    'message' => __('You won: %1. Email sending failed due to missing template configuration.', $sectorLabel)
                ];
            }

            $templateVars = [
                'customer_name' => $customerName,
                'coupon_code' => $couponCode,
                'store_url' => $storeUrl
            ];

            $this->inlineTranslation->suspend();
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
            $this->inlineTranslation->resume();

            $this->logger->debug('Coupon email sent to: ' . $email);
            $this->messageManager->addSuccessMessage(__('Coupon email sent to %1.', $email));

            return ['success' => true];
        } catch (MailException $e) {
            $this->logger->error('Email sending failed: ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Failed to send coupon email, please contact support.'));
            return [
                'success' => true,
                'message' => __('You won: %1. Email sending failed, please contact support.', $sectorLabel)
            ];
        }
    }

    /**
     * Save spin log
     *
     * @param int $wheelId
     * @param string $email
     * @param string $spinResult
     * @param bool $consentGiven
     */
    private function saveSpinLog(int $wheelId, string $email, string $spinResult, bool $consentGiven): void
    {
        $this->spinLogRepository->saveSpin([
            'wheel_id' => $wheelId,
            'customer_id' => $this->customerSession->isLoggedIn() ? $this->customerSession->getCustomerId() : null,
            'email' => $email,
            'spin_result' => $spinResult,
            'spin_date' => date('Y-m-d H:i:s'),
            'ip_address' => $this->anonymizeIp($this->request->getClientIp()),
            'is_guest' => $this->customerSession->isLoggedIn() ? 0 : 1,
            'consent_given' => $consentGiven
        ]);
    }

    /**
     * Create error response
     *
     * @param string $message
     * @return Json
     */
    private function createErrorResponse(string $message): Json
    {
        return $this->jsonFactory->create()->setData(['success' => false, 'message' => __($message)]);
    }

    /**
     * Anonymize IP address
     *
     * @param string $ip
     * @return string
     */
    private function anonymizeIp(string $ip): string
    {
        return preg_replace('/\.\d+$/', '.xxx', $ip);
    }
}
