<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\Analytics;

use Doroshko\WishReward\Api\SpinAnalyticsRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class SpinAnalyticsRepository implements SpinAnalyticsRepositoryInterface
{
    private $spinAnalyticsFactory;
    private $collectionFactory;
    private $logger;

    public function __construct(
        \Doroshko\WishReward\Model\Analytics\SpinAnalyticsFactory $spinAnalyticsFactory,
        \Doroshko\WishReward\Model\ResourceModel\SpinAnalytics\CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->spinAnalyticsFactory = $spinAnalyticsFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    public function saveSpin(array $data): void
    {
        try {
            $requiredFields = ['wheel_id', 'spin_date'];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new CouldNotSaveException(__("Missing required field: %1", $field));
                }
            }

            $spinAnalytics = $this->spinAnalyticsFactory->create();
            $spinAnalytics->setData($data)->save();
        } catch (\Exception $e) {
            $this->logger->error('Failed to save spin analytics: ' . $e->getMessage(), ['exception' => $e]);
            throw new CouldNotSaveException(__($e->getMessage()));
        }
    }

    public function deleteByEmail(string $email): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);

        foreach ($collection as $item) {
            $item->delete();
        }
    }
}
