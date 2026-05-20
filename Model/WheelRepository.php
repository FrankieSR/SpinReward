<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model;

use Doroshko\SpinReward\Api\WheelRepositoryInterface;
use Doroshko\SpinReward\Api\Data\WheelInterface;
use Doroshko\SpinReward\Model\ResourceModel\Wheel as ResourceWheel;
use Doroshko\SpinReward\Model\ResourceModel\Wheel\CollectionFactory as WheelCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

class WheelRepository implements WheelRepositoryInterface
{
    private ResourceWheel $resource;
    private WheelFactory $wheelFactory;
    private WheelCollectionFactory $collectionFactory;
    private StoreManagerInterface $storeManager;
    private CustomerSession $customerSession;
    private TimezoneInterface $timezone;
    private LoggerInterface $logger;

    public function __construct(
        ResourceWheel $resource,
        WheelFactory $wheelFactory,
        WheelCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        TimezoneInterface $timezone,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->wheelFactory = $wheelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->logger = $logger;
    }

    public function save(WheelInterface $wheel): WheelInterface
    {
        $startDate = $wheel->getStartDate();
        if ($startDate) {
            $wheel->setStartDate($this->convertToUtc($startDate));
        }
        $endDate = $wheel->getEndDate();
        if ($endDate) {
            $wheel->setEndDate($this->convertToUtc($endDate));
        }

        try {
            $this->resource->save($wheel);
            $this->logger->debug('Wheel saved', ['wheel_id' => $wheel->getWheelId()]);

            return $wheel;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to save wheel: %1', $e->getMessage()));
        }
    }

    public function getById(int $wheelId): WheelInterface
    {
        $wheel = $this->wheelFactory->create();
        $this->resource->load($wheel, $wheelId);
        if (!$wheel->getId()) {
            throw new NoSuchEntityException(__('Wheel with id "%1" does not exist.', $wheelId));
        }

        return $wheel;
    }

    public function isEligibleWheel(WheelInterface $wheel): bool
    {
        if (!$wheel->isActive()) {
            return false;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();
        $currentDateTime = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $storeviews = array_filter(array_map('trim', explode(',', (string)$wheel->getStoreviews())), static function (string $value): bool {
            return $value !== '';
        });

        if (!in_array((string)$storeId, $storeviews, true)) {
            return false;
        }

        $customerGroups = array_filter(array_map('trim', explode(',', (string)$wheel->getAllowedCustomerGroups())), static function (string $value): bool {
            return $value !== '';
        });

        if (!in_array((string)$customerGroupId, $customerGroups, true)) {
            return false;
        }

        $startDate = trim((string)$wheel->getStartDate());
        if ($startDate !== '' && $startDate > $currentDateTime) {
            return false;
        }

        $endDate = trim((string)$wheel->getEndDate());
        if ($endDate !== '' && $endDate < $currentDateTime) {
            return false;
        }

        return true;
    }

    public function getEligibleWheelById(int $wheelId): ?WheelInterface
    {
        try {
            $wheel = $this->getById($wheelId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $this->isEligibleWheel($wheel) ? $wheel : null;
    }

    public function delete(WheelInterface $wheel): bool
    {
        try {
            $this->resource->delete($wheel);
            $this->logger->debug('Wheel deleted', ['wheel_id' => $wheel->getId()]);

            return true;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to delete wheel: %1', $e->getMessage()));
        }
    }

    public function deleteById(int $wheelId): bool
    {
        return $this->delete($this->getById($wheelId));
    }

    public function getEligiblePopup(): ?WheelInterface
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();
        $currentDateTime = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('storeviews', ['finset' => $storeId])
            ->addFieldToFilter('allowed_customer_groups', ['finset' => $customerGroupId])
            ->addFieldToFilter('start_date', [['lteq' => $currentDateTime], ['null' => true]])
            ->addFieldToFilter('end_date', [['gteq' => $currentDateTime], ['null' => true]])
            ->setOrder('wheel_id', 'ASC')
            ->setPageSize(1);

        $wheel = $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;

        return $wheel;
    }

    private function convertToUtc(string $localDate): string
    {
        try {
            $dateTime = new \DateTime($localDate, new \DateTimeZone($this->timezone->getConfigTimezone()));
            $dateTime->setTimezone(new \DateTimeZone('UTC'));
            return $dateTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $localDate;
        }
    }
}
