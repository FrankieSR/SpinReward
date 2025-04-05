<?php

declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Doroshko\WishReward\Model\CouponGenerator;
use Doroshko\WishReward\Model\ProbabilityCalculator;
use Doroshko\WishReward\Model\LocalMLValidator;

class SpinWheel implements HttpPostActionInterface
{
    private const ERROR_INVALID_WHEEL_ID = 'Invalid Wheel ID.';
    private const ERROR_WHEEL_NOT_ACTIVE = 'Wheel is not active.';
    private const ERROR_SECTORS_NOT_CONFIGURED = 'Wheel sectors are not configured.';
    private const ERROR_WISH_REQUIRED = 'Wish message is required.';
    private const ERROR_EMAIL_REQUIRED = 'Email is required.';
    private const ERROR_COUPON_GENERATION_FAILED = 'Failed to generate coupon.';
    private const ERROR_PROCESSING_REQUEST = 'An error occurred while processing your request.';

    private JsonFactory $jsonFactory;
    private RequestInterface $request;
    private LoggerInterface $logger;
    private WheelRepositoryInterface $wheelRepository;
    private CouponGenerator $couponGenerator;
    private ProbabilityCalculator $probabilityCalculator;
    private LocalMLValidator $mlValidator;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        WheelRepositoryInterface $wheelRepository,
        CouponGenerator $couponGenerator,
        ProbabilityCalculator $probabilityCalculator,
        LocalMLValidator $mlValidator
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->wheelRepository = $wheelRepository;
        $this->couponGenerator = $couponGenerator;
        $this->probabilityCalculator = $probabilityCalculator;
        $this->mlValidator = $mlValidator;
    }

    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();
        $postData = $this->request->getPostValue();
        $wheelId = (int)($postData['wheel_id'] ?? 0);

        $this->logger->debug('SpinWheel request received with params: ' . json_encode($postData));

        // Валидация wheel_id
        if ($wheelId <= 0) {
            $this->logger->warning('Invalid wheel_id provided: ' . $wheelId);
            return $resultJson->setData(['success' => false, 'message' => __(self::ERROR_INVALID_WHEEL_ID)]);
        }

        try {
            $wheel = $this->wheelRepository->getById($wheelId);
            if (!$wheel->isActive()) {
                return $resultJson->setData(['success' => false, 'message' => __(self::ERROR_WHEEL_NOT_ACTIVE)]);
            }

            $this->logger->debug('Wheel loaded: ' . json_encode($wheel->getData()));

            $sectors = json_decode($wheel->getWheelConfig() ?? '{}', true);
            $this->logger->debug('Sectors: ' . json_encode($sectors));

            if (empty($sectors)) {
                return $resultJson->setData(['success' => false, 'message' => __(self::ERROR_SECTORS_NOT_CONFIGURED)]);
            }

            // Валидация Wish Area, если включена
            if ($wheel->getIsWishAreaEnabled()) {
                $wishMessage = trim($postData['wish'] ?? '');
                $this->logger->debug('Wish message: ' . $wishMessage);
                if (empty($wishMessage)) {
                    return $resultJson->setData(['success' => false, 'message' => __(self::ERROR_WISH_REQUIRED)]);
                }

                $validation = $this->mlValidator->validateText($wishMessage);
                $this->logger->debug('Validation result: ' . json_encode($validation));
                if ($validation['status'] === 'invalid') {
                    return $resultJson->setData([
                        'success' => false,
                        'status' => $validation['status'],
                        'reason' => $validation['reason']
                    ]);
                }
            }

            // Валидация Email, если Wish Area выключена, а Email Input включен
            if (!$wheel->getIsWishAreaEnabled() && $wheel->getIsEmailInputEnabled()) {
                $email = trim($postData['email'] ?? '');
                $this->logger->debug('Email: ' . $email);
                if (empty($email)) {
                    return $resultJson->setData(['success' => false, 'message' => __(self::ERROR_EMAIL_REQUIRED)]);
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $resultJson->setData(['success' => false, 'message' => __('Invalid email format.')]);
                }
            }

            $this->logger->debug('Calculating winning sector...');
            $winningSector = $this->probabilityCalculator->getWinningSector($sectors);
            $this->logger->debug('Winning sector: ' . json_encode($winningSector));
            $couponCode = null;

            if ($ruleId = ($winningSector['rule_id'] ?? null)) {
                $this->logger->debug('Generating coupon for rule_id: ' . $ruleId);
                $couponCode = $this->couponGenerator->generate((int)$ruleId);
                if (!$couponCode) {
                    $this->logger->warning('Failed to generate coupon for sector: ' . $winningSector['id']);
                    return $resultJson->setData(['success' => false, 'message' => __(self::ERROR_COUPON_GENERATION_FAILED)]);
                }
                $this->logger->debug('Coupon code generated: ' . $couponCode);
            }

            return $resultJson->setData([
                'success' => true,
                'sector_id' => $winningSector['id'],
                'coupon_code' => $couponCode,
                'message' => $couponCode
                    ? __('You won: %1', $winningSector['label'])
                    : __('Try again: %1', $winningSector['label'])
            ]);
        } catch (LocalizedException $e) {
            $this->logger->error('Localized exception in SpinWheel: ' . $e->getMessage());
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error in SpinWheel: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return $resultJson->setData(['success' => false, 'message' => __(self::ERROR_PROCESSING_REQUEST)]);
        }
    }
}
