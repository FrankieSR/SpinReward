<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\Analytics;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class EventLogger
{
    private const EVENT_TABLE = 'wishreward_spin_event';
    private const DAILY_TABLE = 'wishreward_analytics_daily';

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function recordEvent(string $eventType, array $data = []): void
    {
        $eventType = strtolower(trim($eventType));
        $wheelId = (int)($data['wheel_id'] ?? 0);

        if ($eventType === '' || $wheelId <= 0) {
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            $storeId = (int)($data['store_id'] ?? $this->getCurrentStoreId());
            $websiteId = (int)($data['website_id'] ?? $this->getCurrentWebsiteId());
            $row = [
                'spin_id' => isset($data['spin_id']) ? (int)$data['spin_id'] : null,
                'wheel_id' => $wheelId,
                'store_id' => $storeId,
                'website_id' => $websiteId,
                'event_type' => $eventType,
                'event_value' => isset($data['event_value']) ? (int)$data['event_value'] : 1,
                'email_hash' => $this->hashValue($data['email'] ?? null),
                'ip_hash' => $this->hashValue($data['ip_address'] ?? null),
                'session_hash' => $this->hashValue($data['session_id'] ?? null),
                'sector_id' => $this->normalizeString($data['sector_id'] ?? null, 64),
                'sector_label' => $this->normalizeString($data['sector_label'] ?? null, 255),
                'coupon_code_hash' => $this->hashValue($data['coupon_code'] ?? null),
                'order_id' => isset($data['order_id']) ? (int)$data['order_id'] : null,
                'order_increment_id' => $this->normalizeString($data['order_increment_id'] ?? null, 32),
                'block_reason' => $this->normalizeString($data['block_reason'] ?? null, 128),
                'ml_status' => $this->normalizeString($data['ml_status'] ?? null, 32),
                'ml_score' => isset($data['ml_score']) ? (float)$data['ml_score'] : null,
                'ml_category' => $this->normalizeString($data['ml_category'] ?? null, 64),
                'ml_duration_ms' => isset($data['ml_duration_ms']) ? (int)$data['ml_duration_ms'] : null,
                'metadata_json' => $this->normalizeMetadata($data['metadata'] ?? []),
                'created_at' => isset($data['created_at']) ? (string)$data['created_at'] : $now->format('Y-m-d H:i:s'),
            ];

            $connection->insert($this->resourceConnection->getTableName(self::EVENT_TABLE), $row);
            $this->updateDailyAggregate($row, $data);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to record wishreward event', [
                'event_type' => $eventType,
                'wheel_id' => $wheelId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateDailyAggregate(array $eventRow, array $data): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::DAILY_TABLE);
        $date = substr((string)$eventRow['created_at'], 0, 10);

        $select = $connection->select()
            ->from($table)
            ->where('aggregate_date = ?', $date)
            ->where('wheel_id = ?', (int)$eventRow['wheel_id'])
            ->where('store_id = ?', (int)$eventRow['store_id'])
            ->where('website_id = ?', (int)$eventRow['website_id'])
            ->limit(1);

        $existing = $connection->fetchRow($select);
        $metrics = $this->resolveMetrics($eventRow, $data);

        if ($existing) {
            $updates = [];
            foreach ($metrics as $field => $value) {
                if (is_float($value)) {
                    $updates[$field] = (float)($existing[$field] ?? 0) + $value;
                } else {
                    $updates[$field] = (int)($existing[$field] ?? 0) + (int)$value;
                }
            }

            if ($updates) {
                $updates['updated_at'] = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
                $connection->update(
                    $table,
                    $updates,
                    [
                        'aggregate_date = ?' => $date,
                        'wheel_id = ?' => (int)$eventRow['wheel_id'],
                        'store_id = ?' => (int)$eventRow['store_id'],
                        'website_id = ?' => (int)$eventRow['website_id'],
                    ]
                );
            }

            return;
        }

        $row = [
            'aggregate_date' => $date,
            'wheel_id' => (int)$eventRow['wheel_id'],
            'store_id' => (int)$eventRow['store_id'],
            'website_id' => (int)$eventRow['website_id'],
            'popup_impressions' => 0,
            'cta_clicks' => 0,
            'popup_opens' => 0,
            'spin_submits' => 0,
            'spin_validated' => 0,
            'ml_passed' => 0,
            'ml_rejected' => 0,
            'wins' => 0,
            'losses' => 0,
            'coupons_generated' => 0,
            'emails_sent' => 0,
            'emails_failed' => 0,
            'coupons_applied' => 0,
            'orders_count' => 0,
            'blocked_attempts' => 0,
            'limit_blocked' => 0,
            'revenue' => 0.0,
            'discount_amount' => 0.0,
            'created_at' => $eventRow['created_at'],
            'updated_at' => $eventRow['created_at'],
        ] + $metrics;

        $connection->insert($table, $row);
    }

    private function resolveMetrics(array $eventRow, array $data): array
    {
        $eventType = (string)$eventRow['event_type'];
        $isWin = (bool)($data['is_win'] ?? false);

        return match ($eventType) {
            'popup_impression' => ['popup_impressions' => 1],
            'cta_click' => ['cta_clicks' => 1],
            'popup_open' => ['popup_opens' => 1],
            'spin_submit' => ['spin_submits' => 1],
            'spin_validated' => [
                'spin_validated' => 1,
                'wins' => $isWin ? 1 : 0,
                'losses' => $isWin ? 0 : 1,
            ],
            'ml_passed' => ['ml_passed' => 1],
            'ml_rejected' => [
                'ml_rejected' => 1,
                'blocked_attempts' => 1,
            ],
            'coupon_generated' => ['coupons_generated' => 1],
            'email_sent' => ['emails_sent' => 1],
            'email_failed' => ['emails_failed' => 1],
            'coupon_applied' => ['coupons_applied' => 1],
            'order_placed' => [
                'orders_count' => 1,
                'revenue' => (float)($data['revenue'] ?? 0),
                'discount_amount' => (float)($data['discount_amount'] ?? 0),
            ],
            'order_canceled', 'order_refunded' => [],
            'validation_failed', 'spin_blocked', 'lock_blocked' => [
                'blocked_attempts' => 1,
            ],
            'limit_blocked' => [
                'blocked_attempts' => 1,
                'limit_blocked' => 1,
            ],
            default => [],
        };
    }

    private function getCurrentStoreId(): int
    {
        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function getCurrentWebsiteId(): int
    {
        try {
            return (int)$this->storeManager->getStore()->getWebsiteId();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function hashValue(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        return hash('sha256', mb_strtolower($value));
    }

    private function normalizeString(mixed $value, int $length): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private function normalizeMetadata(mixed $metadata): ?string
    {
        if (is_array($metadata) || is_object($metadata)) {
            return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $metadata = trim((string)$metadata);
        return $metadata === '' ? null : $metadata;
    }
}
