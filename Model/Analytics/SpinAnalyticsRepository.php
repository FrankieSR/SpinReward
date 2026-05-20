<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\Analytics;

use Doroshko\SpinReward\Api\SpinAnalyticsRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class SpinAnalyticsRepository implements SpinAnalyticsRepositoryInterface
{
    private $spinAnalyticsFactory;
    private $collectionFactory;
    private $logger;

    private const ALLOWED_FIELDS = [
        'wheel_id',
        'customer_id',
        'email',
        'spin_result',
        'spin_prize_label',
        'coupon_code',
        'spin_date',
        'ip_address',
        'is_guest',
        'consent_given',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'page_url',
        'referrer_url',
        'user_agent',
        'device_type',
        'session_id',
        'is_redeemed',
        'redeemed_at',
        'order_id',
        'order_increment_id',
        'order_status',
        'store_id',
        'website_id',
        'spin_status',
        'block_reason',
        'sector_id',
        'sector_probability_snapshot',
        'ml_status',
        'ml_score',
        'ml_category',
        'ml_duration_ms',
        'email_send_status',
        'email_error',
        'email_sent_at',
        'coupon_applied_at',
        'base_subtotal',
        'base_discount_amount',
        'base_grand_total',
        'spin_count_session',
    ];

    private const MAX_LENGTHS = [
        'email' => 255,
        'spin_result' => 255,
        'spin_prize_label' => 255,
        'coupon_code' => 64,
        'ip_address' => 45,
        'utm_source' => 64,
        'utm_medium' => 64,
        'utm_campaign' => 128,
        'page_url' => 512,
        'referrer_url' => 512,
        'user_agent' => 512,
        'device_type' => 32,
        'session_id' => 128,
        'order_increment_id' => 32,
        'order_status' => 32,
        'spin_status' => 32,
        'block_reason' => 128,
        'sector_id' => 64,
        'sector_probability_snapshot' => 65535,
        'ml_status' => 32,
        'ml_category' => 64,
        'email_send_status' => 32,
        'email_error' => 255,
        'redeemed_at' => 19,
        'email_sent_at' => 19,
        'coupon_applied_at' => 19,
    ];

    public function __construct(
        \Doroshko\SpinReward\Model\Analytics\SpinAnalyticsFactory $spinAnalyticsFactory,
        \Doroshko\SpinReward\Model\ResourceModel\SpinAnalytics\CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->spinAnalyticsFactory = $spinAnalyticsFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    public function saveSpin(array $data): int
    {
        try {
            $data = $this->normalizeData($data);
            $requiredFields = ['wheel_id', 'spin_date'];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new CouldNotSaveException(__("Missing required field: %1", $field));
                }
            }

            $spinAnalytics = $this->spinAnalyticsFactory->create();
            $spinAnalytics->setData($data)->save();

            return (int)$spinAnalytics->getId();
        } catch (\Exception $e) {
            $this->logger->error('Failed to save spin analytics: ' . $e->getMessage(), ['exception' => $e]);
            throw new CouldNotSaveException(__($e->getMessage()));
        }
    }

    public function updateSpin(int $spinId, array $data): void
    {
        try {
            $data = $this->normalizeData($data);

            if ($spinId <= 0) {
                throw new CouldNotSaveException(__('Invalid spin ID'));
            }

            $spinAnalytics = $this->spinAnalyticsFactory->create();
            $spinAnalytics->load($spinId);

            if (!$spinAnalytics->getId()) {
                throw new CouldNotSaveException(__('Spin not found'));
            }

            $spinAnalytics->addData($data)->save();
        } catch (\Exception $e) {
            $this->logger->error('Failed to update spin analytics: ' . $e->getMessage(), ['exception' => $e]);
            throw new CouldNotSaveException(__($e->getMessage()));
        }
    }

    public function findByCouponCode(string $couponCode): ?array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('coupon_code', $couponCode);
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();
        if (!$item || !$item->getId()) {
            return null;
        }

        return $item->getData();
    }

    public function deleteByEmail(string $email): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);

        foreach ($collection as $item) {
            $item->delete();
        }
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach (self::ALLOWED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if ($value === null || $value === '') {
                $normalized[$field] = null;
                continue;
            }

            if (in_array($field, ['wheel_id', 'customer_id', 'is_guest', 'consent_given', 'is_redeemed', 'order_id', 'store_id', 'website_id', 'ml_duration_ms', 'spin_count_session'], true)) {
                $normalized[$field] = is_scalar($value) ? (int)$value : null;
                continue;
            }

            if (in_array($field, ['ml_score', 'base_subtotal', 'base_discount_amount', 'base_grand_total'], true)) {
                $normalized[$field] = is_scalar($value) ? (float)$value : null;
                continue;
            }

            if (isset(self::MAX_LENGTHS[$field])) {
                if (!is_scalar($value) && !($value instanceof \Stringable)) {
                    $normalized[$field] = null;
                    continue;
                }

                $normalized[$field] = $this->truncate((string)$value, self::MAX_LENGTHS[$field]);
                continue;
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }

    private function truncate(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
