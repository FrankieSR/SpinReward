<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\Analytics;

use Doroshko\SpinReward\Model\ResourceModel\SpinAnalytics\CollectionFactory as SpinAnalyticsCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class ReportService
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly SpinAnalyticsCollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getDashboardPayload(array $filters = []): array
    {
        $summary = $this->getSummary($filters);

        return [
            'summary' => $summary,
            'funnel' => $this->buildFunnel($summary),
            'daily_series' => $this->getDailySeries($filters),
            'wheel_series' => $this->getWheelSeries($filters),
            'sector_series' => $this->getSectorSeries($filters),
            'device_series' => $this->getGroupedSeries('device_type', 8, false, $filters),
            'utm_series' => $this->getGroupedSeries('utm_source', 8, false, $filters),
        ];
    }

    public function getLatestSpins(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $collection = $this->buildLatestSpinsCollection($filters);
        $totalRecords = (int)$collection->getSize();
        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        $fullCollection = $this->buildLatestSpinsCollection($filters);
        $allItems = [];
        foreach ($fullCollection as $item) {
            $allItems[] = $item->getData();
        }

        $emailWheelCounts = [];
        foreach ($allItems as $item) {
            $spinStatus = (string)($item['spin_status'] ?? '');
            if ($spinStatus !== '' && $spinStatus !== 'completed') {
                continue;
            }

            $key = strtolower(trim((string)($item['email'] ?? ''))) . '|' . (int)($item['wheel_id'] ?? 0);
            $emailWheelCounts[$key] = ($emailWheelCounts[$key] ?? 0) + 1;
        }

        $items = [];
        foreach ($collection as $item) {
            $data = $item->getData();
            $key = strtolower(trim((string)($data['email'] ?? ''))) . '|' . (int)($data['wheel_id'] ?? 0);
            $data['email_wheel_spin_count'] = $emailWheelCounts[$key] ?? 1;
            $data['email'] = $this->maskEmail((string)($data['email'] ?? ''));
            $data['ip_address'] = $this->maskIp((string)($data['ip_address'] ?? ''));
            $items[] = $data;
        }

        return [
            'totalRecords' => $totalRecords,
            'items' => $items,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    public function exportLatestSpinsCsv(array $filters = []): string
    {
        $collection = $this->buildLatestSpinsCollection($filters);
        $rows = [
            [
                'ID',
                'Created At',
                'Wheel ID',
                'Store ID',
                'Email',
                'IP Address',
                'Status',
                'Result',
                'Block Reason',
                'Sector',
                'Coupon',
                'Validation Status',
                'Email Status',
                'Order ID',
                'Order Status',
                'Grand Total',
                'Discount',
            ],
        ];

        foreach ($collection as $item) {
            $data = $item->getData();
            $rows[] = [
                (string)($data['id'] ?? ''),
                (string)($data['created_at'] ?? ''),
                (string)($data['wheel_id'] ?? ''),
                (string)($data['store_id'] ?? ''),
                $this->maskEmail((string)($data['email'] ?? '')),
                $this->maskIp((string)($data['ip_address'] ?? '')),
                (string)($data['spin_status'] ?? ''),
                (string)($data['spin_result'] ?? ''),
                (string)($data['block_reason'] ?? ''),
                (string)($data['spin_prize_label'] ?? ''),
                (string)($data['coupon_code'] ?? ''),
                (string)($data['ml_status'] ?? ''),
                (string)($data['email_send_status'] ?? ''),
                (string)($data['order_id'] ?? ''),
                (string)($data['order_status'] ?? ''),
                (string)($data['base_grand_total'] ?? ''),
                (string)($data['base_discount_amount'] ?? ''),
            ];
        }

        $lines = array_map(fn(array $row): string => $this->csvLine($row), $rows);
        return implode("\n", $lines);
    }

    private function getSummary(array $filters = []): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('wishreward_analytics_daily');

        $select = $connection->select()->from($table, [
            'popup_impressions' => new \Zend_Db_Expr('COALESCE(SUM(popup_impressions), 0)'),
            'cta_clicks' => new \Zend_Db_Expr('COALESCE(SUM(cta_clicks), 0)'),
            'popup_opens' => new \Zend_Db_Expr('COALESCE(SUM(popup_opens), 0)'),
            'spin_submits' => new \Zend_Db_Expr('COALESCE(SUM(spin_submits), 0)'),
            'spin_validated' => new \Zend_Db_Expr('COALESCE(SUM(spin_validated), 0)'),
            'ml_passed' => new \Zend_Db_Expr('COALESCE(SUM(ml_passed), 0)'),
            'ml_rejected' => new \Zend_Db_Expr('COALESCE(SUM(ml_rejected), 0)'),
            'wins' => new \Zend_Db_Expr('COALESCE(SUM(wins), 0)'),
            'losses' => new \Zend_Db_Expr('COALESCE(SUM(losses), 0)'),
            'coupons_generated' => new \Zend_Db_Expr('COALESCE(SUM(coupons_generated), 0)'),
            'emails_sent' => new \Zend_Db_Expr('COALESCE(SUM(emails_sent), 0)'),
            'emails_failed' => new \Zend_Db_Expr('COALESCE(SUM(emails_failed), 0)'),
            'coupons_applied' => new \Zend_Db_Expr('COALESCE(SUM(coupons_applied), 0)'),
            'orders_count' => new \Zend_Db_Expr('COALESCE(SUM(orders_count), 0)'),
            'blocked_attempts' => new \Zend_Db_Expr('COALESCE(SUM(blocked_attempts), 0)'),
            'limit_blocked' => new \Zend_Db_Expr('COALESCE(SUM(limit_blocked), 0)'),
            'revenue' => new \Zend_Db_Expr('COALESCE(SUM(revenue), 0)'),
            'discount_amount' => new \Zend_Db_Expr('COALESCE(SUM(discount_amount), 0)'),
        ]);
        $this->applyDailyFilters($select, $filters);

        $summary = $connection->fetchRow($select) ?: [];
        $summary['popup_open_rate'] = $this->rate((int)($summary['popup_opens'] ?? 0), (int)($summary['popup_impressions'] ?? 0));
        $summary['spin_conversion'] = $this->rate((int)($summary['spin_validated'] ?? 0), (int)($summary['popup_opens'] ?? 0));
        $summary['win_rate'] = $this->rate((int)($summary['wins'] ?? 0), (int)($summary['spin_validated'] ?? 0));
        $summary['coupon_redemption_rate'] = $this->rate((int)($summary['coupons_applied'] ?? 0), (int)($summary['coupons_generated'] ?? 0));
        $summary['order_conversion'] = $this->rate((int)($summary['orders_count'] ?? 0), (int)($summary['coupons_generated'] ?? 0));
        $summary['revenue_per_spin'] = $this->divide((float)($summary['revenue'] ?? 0), (int)($summary['spin_validated'] ?? 0));
        $summary['cost_per_order'] = $this->divide((float)($summary['discount_amount'] ?? 0), (int)($summary['orders_count'] ?? 0));
        $summary['email_wheel_spin_count'] = $this->getPeakAttemptsPerEmailWheel();

        return $summary;
    }

    private function buildFunnel(array $summary): array
    {
        $steps = [
            ['label' => 'Popup shown', 'count' => (int)($summary['popup_impressions'] ?? 0)],
            ['label' => 'Popup opened', 'count' => (int)($summary['popup_opens'] ?? 0)],
            ['label' => 'Spin submitted', 'count' => (int)($summary['spin_submits'] ?? 0)],
            ['label' => 'Valid spin', 'count' => (int)($summary['spin_validated'] ?? 0)],
            ['label' => 'Coupon generated', 'count' => (int)($summary['coupons_generated'] ?? 0)],
            ['label' => 'Coupon applied', 'count' => (int)($summary['coupons_applied'] ?? 0)],
            ['label' => 'Order placed', 'count' => (int)($summary['orders_count'] ?? 0)],
        ];

        $previous = null;
        foreach ($steps as $index => $step) {
            if ($previous === null || $previous <= 0) {
                $steps[$index]['dropoff'] = '—';
            } else {
                $dropoff = max(0, 1 - ($step['count'] / $previous));
                $steps[$index]['dropoff'] = sprintf('%s drop', number_format($dropoff * 100, 0));
            }

            $previous = max(1, $step['count']);
        }

        return $steps;
    }

    private function getDailySeries(array $filters = []): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('wishreward_analytics_daily');
        $select = $connection->select()
            ->from($table, [
                'label' => 'aggregate_date',
                'value' => new \Zend_Db_Expr('COALESCE(SUM(spin_submits), 0)'),
            ])
            ->group('aggregate_date')
            ->order('aggregate_date ASC');
        $this->applyDailyFilters($select, $filters);

        return array_map(static fn(array $row): array => [
            'label' => (string)$row['label'],
            'value' => (int)$row['value'],
        ], $connection->fetchAll($select));
    }

    private function getWheelSeries(array $filters = []): array
    {
        return $this->getGroupedSeries('wheel_id', 8, true, $filters);
    }

    private function getSectorSeries(array $filters = []): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('wishreward_spin_analytics');
        $select = $connection->select()
            ->from($table, [
                'label' => new \Zend_Db_Expr("COALESCE(NULLIF(spin_prize_label, ''), spin_result, 'n/a')"),
                'value' => new \Zend_Db_Expr('COUNT(*)'),
            ])
            ->where('(spin_status IS NULL OR spin_status <> ?)', 'blocked')
            ->group('label')
            ->order('value DESC')
            ->limit(8);
        $this->applySpinFilters($select, $filters);

        return array_map(static fn(array $row): array => [
            'label' => (string)$row['label'],
            'value' => (int)$row['value'],
        ], $connection->fetchAll($select));
    }

    private function getGroupedSeries(string $column, int $limit = 8, bool $includeRevenue = false, array $filters = []): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('wishreward_spin_analytics');
        $columns = [
            'label' => new \Zend_Db_Expr(sprintf("COALESCE(NULLIF(%s, ''), 'n/a')", $column)),
            'value' => new \Zend_Db_Expr('COUNT(*)'),
        ];

        if ($includeRevenue) {
            $columns['revenue'] = new \Zend_Db_Expr('COALESCE(SUM(base_grand_total), 0)');
            $columns['discount_amount'] = new \Zend_Db_Expr('COALESCE(SUM(base_discount_amount), 0)');
        }

        $select = $connection->select()
            ->from($table, $columns)
            ->where('(spin_status IS NULL OR spin_status <> ?)', 'blocked')
            ->group('label')
            ->order('value DESC')
            ->limit($limit);
        $this->applySpinFilters($select, $filters);

        $rows = $connection->fetchAll($select);
        return array_map(static function (array $row) use ($includeRevenue): array {
            $item = [
                'label' => (string)$row['label'],
                'value' => (int)$row['value'],
            ];

            if ($includeRevenue) {
                $item['revenue'] = (float)($row['revenue'] ?? 0);
                $item['discount_amount'] = (float)($row['discount_amount'] ?? 0);
            }

            return $item;
        }, $rows);
    }

    private function buildLatestSpinsCollection(array $filters = [])
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder('created_at', 'DESC');

        if (!empty($filters['wheel_id'])) {
            $collection->addFieldToFilter('wheel_id', (int)$filters['wheel_id']);
        }

        if (!empty($filters['email'])) {
            $collection->addFieldToFilter('email', ['like' => '%' . $filters['email'] . '%']);
        }

        if (!empty($filters['spin_result'])) {
            $collection->addFieldToFilter('spin_result', ['like' => '%' . $filters['spin_result'] . '%']);
        }

        if (!empty($filters['spin_status'])) {
            $collection->addFieldToFilter('spin_status', ['like' => '%' . $filters['spin_status'] . '%']);
        }

        if (!empty($filters['block_reason'])) {
            $collection->addFieldToFilter('block_reason', ['like' => '%' . $filters['block_reason'] . '%']);
        }

        if (!empty($filters['ml_status'])) {
            $collection->addFieldToFilter('ml_status', ['like' => '%' . $filters['ml_status'] . '%']);
        }

        if (!empty($filters['device_type'])) {
            $collection->addFieldToFilter('device_type', ['like' => '%' . $filters['device_type'] . '%']);
        }

        if (!empty($filters['utm_source'])) {
            $collection->addFieldToFilter('utm_source', ['like' => '%' . $filters['utm_source'] . '%']);
        }

        if (!empty($filters['sector_id'])) {
            $collection->addFieldToFilter('sector_id', ['like' => '%' . $filters['sector_id'] . '%']);
        }

        if (!empty($filters['created_at']['from'])) {
            $collection->addFieldToFilter('created_at', ['gteq' => $filters['created_at']['from'] . ' 00:00:00']);
        }

        if (!empty($filters['created_at']['to'])) {
            $collection->addFieldToFilter('created_at', ['lteq' => $filters['created_at']['to'] . ' 23:59:59']);
        }

        return $collection;
    }

    private function getPeakAttemptsPerEmailWheel(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('wishreward_spin_analytics');
        $sql = sprintf(
            'SELECT COALESCE(MAX(attempts), 0) FROM (SELECT COUNT(*) AS attempts FROM %s WHERE (spin_status IS NULL OR spin_status = \'completed\') GROUP BY LOWER(email), wheel_id) t',
            $table
        );

        return (int)$connection->fetchOne($sql);
    }

    private function applyDailyFilters(\Zend_Db_Select $select, array $filters): void
    {
        if (!empty($filters['wheel_id'])) {
            $select->where('wheel_id = ?', (int)$filters['wheel_id']);
        }

        if (!empty($filters['store_id'])) {
            $select->where('store_id = ?', (int)$filters['store_id']);
        }

        if (!empty($filters['website_id'])) {
            $select->where('website_id = ?', (int)$filters['website_id']);
        }

        if (!empty($filters['created_at']['from'])) {
            $select->where('aggregate_date >= ?', substr((string)$filters['created_at']['from'], 0, 10));
        }

        if (!empty($filters['created_at']['to'])) {
            $select->where('aggregate_date <= ?', substr((string)$filters['created_at']['to'], 0, 10));
        }
    }

    private function applySpinFilters(\Zend_Db_Select $select, array $filters): void
    {
        if (!empty($filters['wheel_id'])) {
            $select->where('wheel_id = ?', (int)$filters['wheel_id']);
        }

        if (!empty($filters['store_id'])) {
            $select->where('store_id = ?', (int)$filters['store_id']);
        }

        if (!empty($filters['website_id'])) {
            $select->where('website_id = ?', (int)$filters['website_id']);
        }

        if (!empty($filters['spin_result'])) {
            $select->where('spin_result LIKE ?', '%' . $filters['spin_result'] . '%');
        }

        if (!empty($filters['spin_status'])) {
            $select->where('spin_status LIKE ?', '%' . $filters['spin_status'] . '%');
        }

        if (!empty($filters['block_reason'])) {
            $select->where('block_reason LIKE ?', '%' . $filters['block_reason'] . '%');
        }

        if (!empty($filters['ml_status'])) {
            $select->where('ml_status LIKE ?', '%' . $filters['ml_status'] . '%');
        }

        if (!empty($filters['device_type'])) {
            $select->where('device_type LIKE ?', '%' . $filters['device_type'] . '%');
        }

        if (!empty($filters['utm_source'])) {
            $select->where('utm_source LIKE ?', '%' . $filters['utm_source'] . '%');
        }

        if (!empty($filters['sector_id'])) {
            $select->where('sector_id LIKE ?', '%' . $filters['sector_id'] . '%');
        }

        if (!empty($filters['created_at']['from'])) {
            $select->where('created_at >= ?', $filters['created_at']['from'] . ' 00:00:00');
        }

        if (!empty($filters['created_at']['to'])) {
            $select->where('created_at <= ?', $filters['created_at']['to'] . ' 23:59:59');
        }
    }

    private function rate(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? $numerator / $denominator : 0.0;
    }

    private function divide(float $numerator, int $denominator): float
    {
        return $denominator > 0 ? $numerator / $denominator : 0.0;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || strpos($email, '@') === false) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        if (strlen($local) <= 2) {
            $local = str_repeat('*', strlen($local));
        } else {
            $local = substr($local, 0, 1) . str_repeat('*', max(2, strlen($local) - 2)) . substr($local, -1);
        }

        return $local . '@' . $domain;
    }

    private function maskIp(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return $ip;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return count($parts) === 4 ? $parts[0] . '.' . $parts[1] . '.***.***' : $ip;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, 8) . ':****:****:****';
        }

        return $ip;
    }

    private function csvLine(array $columns): string
    {
        return implode(',', array_map(static function ($value): string {
            $value = (string)$value;
            return '"' . str_replace('"', '""', $value) . '"';
        }, $columns));
    }
}
