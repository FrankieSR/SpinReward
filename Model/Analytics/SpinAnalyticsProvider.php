<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\Analytics;

use Doroshko\SpinReward\Api\SpinAnalyticsProviderInterface;
use Doroshko\SpinReward\Model\ResourceModel\SpinAnalytics\CollectionFactory as SpinAnalyticsCollectionFactory;

class SpinAnalyticsProvider implements SpinAnalyticsProviderInterface
{
    private SpinAnalyticsCollectionFactory $collectionFactory;

    public function __construct(
        SpinAnalyticsCollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Returns the number of spins for the given email on the specified date.
     *
     * @param string $email
     * @param string|null $date
     * @return int
     */
    public function getSpinCountByEmail(string $email, ?string $date = null): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);
        $collection->getSelect()->where('(spin_status IS NULL OR spin_status = ?)', 'completed');

        if ($date) {
            $collection->addFieldToFilter('spin_date', ['gteq' => $date . ' 00:00:00']);
            $collection->addFieldToFilter('spin_date', ['lteq' => $date . ' 23:59:59']);
        }

        return (int)$collection->getSize();
    }

    /**
     * Returns the number of spins for the given email and wheel within the specified date range.
     *
     * @param string $email
     * @param int $wheelId
     * @param string $startDate
     * @param string $endDate
     * @return int
     */
    public function getSpinCountByEmailAndWheel(string $email, int $wheelId, string $startDate, string $endDate): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);
        $collection->addFieldToFilter('wheel_id', $wheelId);
        $collection->addFieldToFilter('spin_date', ['gteq' => $startDate]);
        $collection->addFieldToFilter('spin_date', ['lteq' => $endDate]);
        $collection->getSelect()->where('(spin_status IS NULL OR spin_status = ?)', 'completed');

        return (int)$collection->getSize();
    }

    /**
     * @inheritDoc
     */
    public function getSpinCountByCustomerAndWheel(int $customerId, int $wheelId, string $startDate, string $endDate): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('wheel_id', $wheelId);
        $collection->addFieldToFilter('spin_date', ['gteq' => $startDate]);
        $collection->addFieldToFilter('spin_date', ['lteq' => $endDate]);
        $collection->getSelect()->where('(spin_status IS NULL OR spin_status = ?)', 'completed');

        return (int)$collection->getSize();
    }

    /**
     * Returns all spins for the given email.
     *
     * @param string $email
     * @return array
     */
    public function getSpinsByEmail(string $email): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);
        return $collection->getData();
    }
}
