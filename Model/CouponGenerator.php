<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model;

use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class CouponGenerator
{
    private CouponFactory $couponFactory;
    private RuleFactory $ruleFactory;
    private RuleResource $ruleResource;
    private CouponRepositoryInterface $couponRepository;
    private DateTime $dateTime;
    private LoggerInterface $logger;

    public function __construct(
        CouponFactory $couponFactory,
        RuleFactory $ruleFactory,
        RuleResource $ruleResource,
        CouponRepositoryInterface $couponRepository,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->couponFactory = $couponFactory;
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->couponRepository = $couponRepository;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Generate a coupon for the given rule ID
     *
     * @param int $ruleId
     * @return string|null Coupon code or null on failure
     * @throws LocalizedException
     */
    public function generate(int $ruleId): ?string
    {
        try {
            if ($ruleId <= 0) {
                throw new LocalizedException(__('Invalid rule ID provided.'));
            }

            $rule = $this->ruleFactory->create();
            $this->ruleResource->load($rule, $ruleId);

            if (!$rule->getId()) {
                throw new LocalizedException(__('Sales rule with ID %1 does not exist.', $ruleId));
            }

            if (!$rule->getIsActive()) {
                throw new LocalizedException(__('Sales rule with ID %1 is not active.', $ruleId));
            }

            $couponCode = $this->generateUniqueCode();
            $coupon = $this->couponFactory->create();
            $coupon->setRuleId($ruleId)
                ->setCode($couponCode)
                ->setUsageLimit(1)
                ->setUsagePerCustomer(1)
                ->setCreatedAt($this->dateTime->gmtDate())
                ->setType(1);

            $this->couponRepository->save($coupon);

            return $couponCode;
        } catch (LocalizedException $e) {
            $this->logger->error(__('Validation error for rule ID %1: %2', $ruleId, $e->getMessage()));
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error(__('Failed to generate coupon for rule ID %1: %2', $ruleId, $e->getMessage()));
            return null;
        }
    }

    /**
     * Generate a unique coupon code
     *
     * @return string
     */
    private function generateUniqueCode(): string
    {
        return 'COUPON-' . strtoupper(bin2hex(random_bytes(4)));
    }
}
