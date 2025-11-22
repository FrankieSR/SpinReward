<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model;

use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Doroshko\WishReward\Api\Data\WheelInterface;
use Doroshko\WishReward\Model\ResourceModel\Wheel as ResourceWheel;
use Doroshko\WishReward\Model\ResourceModel\Wheel\CollectionFactory as WheelCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

class WheelRepository implements WheelRepositoryInterface
{
    private ResourceWheel $resource;
    private WheelFactory $wheelFactory;
    private WheelCollectionFactory $collectionFactory;
    private StoreManagerInterface $storeManager;
    private CustomerSession $customerSession;
    private ScopeConfigInterface $scopeConfig;
    private TimezoneInterface $timezone;
    private RequestInterface $request;
    private LayoutInterface $layout;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ResourceWheel $resource,
        WheelFactory $wheelFactory,
        WheelCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezone,
        RequestInterface $request,
        LayoutInterface $layout,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->wheelFactory = $wheelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezone;
        $this->request = $request;
        $this->layout = $layout;
        $this->cache = $cache;
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

            $cacheKey = 'wishreward_wheel_' . $wheel->getWheelId();

            $this->cache->remove($cacheKey);
            $this->cache->clean(['wishreward_eligible_popup']);
            $this->logger->debug('Wheel cache invalidated', ['wheel_id' => $wheel->getWheelId()]);

            return $wheel;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to save wheel: %1', $e->getMessage()));
        }
    }

    public function getById(int $wheelId): WheelInterface
    {
        $cacheKey = 'wishreward_wheel_' . $wheelId;
        $cached = $this->cache->load($cacheKey);
        if ($cached !== false) {
            try {
                $wheel = unserialize($cached);
                if ($wheel instanceof WheelInterface) {
                    $this->logger->debug('Wheel loaded from cache', ['wheel_id' => $wheelId]);
                    return $wheel;
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to unserialize wheel cache', ['wheel_id' => $wheelId, 'error' => $e->getMessage()]);
            }
        }

        $wheel = $this->wheelFactory->create();
        $this->resource->load($wheel, $wheelId);
        if (!$wheel->getId()) {
            throw new NoSuchEntityException(__('Wheel with id "%1" does not exist.', $wheelId));
        }

        if ($wheel->isActive()) {
            $this->cache->save(
                serialize($wheel),
                $cacheKey,
                ['wishreward_wheel'],
                3600 
            );
            $this->logger->debug('Wheel saved to cache', ['wheel_id' => $wheelId]);
        } else {
            $this->logger->debug('Wheel not cached (inactive)', ['wheel_id' => $wheelId]);
        }

        return $wheel;
    }

    public function delete(WheelInterface $wheel): bool
    {
        try {
            $wheelId = $wheel->getId();
            $this->resource->delete($wheel);

            $cacheKey = 'wishreward_wheel_' . $wheelId;
            $this->cache->remove($cacheKey);
            $this->cache->clean(['wishreward_eligible_popup']);
            $this->logger->debug('Wheel cache invalidated', ['wheel_id' => $wheelId]);

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
        $cacheKey = 'wishreward_eligible_popup_' . $storeId . '_' . $customerGroupId;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== false) {
            try {
                $wheel = unserialize($cached);
                if ($wheel instanceof WheelInterface || $wheel === null) {
                    $this->logger->debug('Eligible popup loaded from cache', ['store_id' => $storeId, 'customer_group_id' => $customerGroupId]);
                    return $wheel;
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to unserialize eligible popup cache', ['cache_key' => $cacheKey, 'error' => $e->getMessage()]);
            }
        }

        $currentDateTime = $this->timezone->date()->format('Y-m-d H:i:s');
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('storeviews', ['finset' => $storeId])
            ->addFieldToFilter('allowed_customer_groups', ['finset' => $customerGroupId])
            ->addFieldToFilter('start_date', [['lteq' => $currentDateTime], ['null' => true]])
            ->addFieldToFilter('end_date', [['gteq' => $currentDateTime], ['null' => true]])
            ->setOrder('wheel_id', 'ASC')
            ->setPageSize(1);

        $wheel = $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;

        $this->cache->save(
            serialize($wheel),
            $cacheKey,
            ['wishreward_eligible_popup'],
            3600
        );
        $this->logger->debug('Eligible popup saved to cache', ['store_id' => $storeId, 'customer_group_id' => $customerGroupId]);

        return $wheel;
    }

    public function cacheActiveWheels(): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);

        foreach ($collection as $wheel) {
            $cacheKey = 'wishreward_wheel_' . $wheel->getId();
            $this->cache->save(
                serialize($wheel),
                $cacheKey,
                ['wishreward_wheel'],
                3600
            );
            $this->logger->debug('Active wheel cached', ['wheel_id' => $wheel->getId()]);
        }
        $this->logger->info('Active wheels cached', ['count' => $collection->getSize()]);
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
