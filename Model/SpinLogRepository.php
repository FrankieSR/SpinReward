<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model;

use Doroshko\WishReward\Api\SpinLogRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class SpinLogRepository implements SpinLogRepositoryInterface
{
    private $spinLogFactory;
    private $collectionFactory;

    public function __construct(
        \Doroshko\WishReward\Model\SpinLogFactory $spinLogFactory,
        \Doroshko\WishReward\Model\ResourceModel\SpinLog\CollectionFactory $collectionFactory
    ) {
        $this->spinLogFactory = $spinLogFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function saveSpin(array $data): void
    {
        try {
            $spinLog = $this->spinLogFactory->create();
            $spinLog->setData($data)->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
    }

    public function getSpinCountByEmail(string $email, ?string $date = null): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);
        if ($date) {
            $collection->addFieldToFilter('spin_date', ['gteq' => $date . ' 00:00:00']);
            $collection->addFieldToFilter('spin_date', ['lteq' => $date . ' 23:59:59']);
        }
        return $collection->getSize();
    }

    public function canSpin(string $email, int $maxSpins = 1): bool
    {
        $today = date('Y-m-d');
        return $this->getSpinCountByEmail($email, $today) < $maxSpins;
    }

    public function getSpinsByEmail(string $email): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);
        return $collection->getData();
    }

    public function deleteByEmail(string $email): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email);
        foreach ($collection as $item) {
            $item->delete();
        }
    }

    public function getTotalSpins(): int
    {
        return $this->collectionFactory->create()->getSize();
    }
}
