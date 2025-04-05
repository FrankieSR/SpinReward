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

    public function __construct(
        ResourceWheel $resource,
        WheelFactory $wheelFactory,
        WheelCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezone,
        RequestInterface $request,
        LayoutInterface $layout
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
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to save wheel: %1', $e->getMessage()));
        }

        return $wheel;
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

    public function delete(WheelInterface $wheel): bool
    {
        try {
            $this->resource->delete($wheel);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to delete wheel: %1', $e->getMessage()));
        }

        return true;
    }

    public function deleteById(int $wheelId): bool
    {
        return $this->delete($this->getById($wheelId));
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

    public function getFirstActiveWheel(): ?WheelInterface
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('storeviews', ['finset' => $storeId])
            ->addFieldToFilter('allowed_customer_groups', ['finset' => $customerGroupId])
            ->setOrder('wheel_id', 'ASC')
            ->setPageSize(1);

        $wheel = $collection->getFirstItem();
        return $wheel->getId() ? $wheel : null;
    }

    /**
     * Get eligible popup based on current layout context.
     *
     * @return WheelInterface|null
     */
    public function getEligiblePopup(): ?WheelInterface
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();
        $currentDateTime = $this->timezone->date()->format('Y-m-d H:i:s');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('storeviews', ['finset' => $storeId])
            ->addFieldToFilter('allowed_customer_groups', ['finset' => $customerGroupId])
            ->addFieldToFilter('start_date', [['lteq' => $currentDateTime], ['null' => true]])
            ->addFieldToFilter('end_date', [['gteq' => $currentDateTime], ['null' => true]])
            ->setOrder('wheel_id', 'ASC')
            ->setPageSize(1);

        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }

    /**
     * Get eligible popup based on provided page handle and URL.
     *
     * @param string $pageHandle
     * @param string $pageUrl
     * @return WheelInterface|null
     */
    public function getEligiblePopupForHandle(string $pageHandle, string $pageUrl): ?WheelInterface
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();
        $currentDateTime = $this->timezone->date()->format('Y-m-d H:i:s');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('storeviews', ['finset' => $storeId])
            ->addFieldToFilter('allowed_customer_groups', ['finset' => $customerGroupId])
            ->addFieldToFilter('start_date', [['lteq' => $currentDateTime], ['null' => true]])
            ->addFieldToFilter('end_date', [['gteq' => $currentDateTime], ['null' => true]])
            ->setOrder('wheel_id', 'ASC')
            ->setPageSize(1);

        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }
}