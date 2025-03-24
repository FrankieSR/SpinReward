<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Block;

use Magento\Framework\View\Element\Template;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;

class CtaButton extends Template
{
    protected $wheelRepository;
    protected $storeManager;
    protected $customerSession;

    public function __construct(
        Template\Context $context,
        WheelRepositoryInterface $wheelRepository,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->wheelRepository = $wheelRepository;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    public function getWheelData(): ?array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();

        $collection = $this->wheelRepository->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('storeviews', ['finset' => $storeId])
            ->addFieldToFilter('allowed_customer_groups', ['finset' => $customerGroupId])
            ->setOrder('wheel_id', 'ASC')
            ->setPageSize(1);

        $wheel = $collection->getFirstItem();
        if (!$wheel->getId()) {
            return null;
        }

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $startDate = $wheel->getStartDate() ? new \DateTime($wheel->getStartDate(), new \DateTimeZone('UTC')) : null;
        $endDate = $wheel->getEndDate() ? new \DateTime($wheel->getEndDate(), new \DateTimeZone('UTC')) : null;

        if (($startDate && $currentDate < $startDate) || ($endDate && $currentDate > $endDate)) {
            return null;
        }

        return [
            'wheel_id' => $wheel->getWheelId(),
            'is_cta_enabled' => $wheel->getIsCtaEnabled(),
            'cta_label' => $wheel->getCtaLabel(),
            'cta_button_text' => $wheel->getCtaButtonText(),
            'cta_image' => $wheel->getCtaImage() ? $this->getMediaUrl($wheel->getCtaImage()) : null,
            'cta_position' => $wheel->getCtaPosition(),
            'cta_custom_css' => $wheel->getCtaCustomCss()
        ];
    }

    public function getAjaxUrl(): string
    {
        return $this->getUrl('wishreward/wheel/popup');
    }

    protected function getMediaUrl(string $filePath): string
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $filePath;
    }
}