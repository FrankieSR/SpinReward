<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Block;

use Magento\Framework\View\Element\Template;
use Doroshko\WishReward\Api\Data\WheelInterface;
use Magento\Customer\Model\Session as CustomerSession;

class WheelPopup extends Template
{
    protected $wheel;
    protected $customerSession;

    public function __construct(
        Template\Context $context,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
    }

    public function setWheel(WheelInterface $wheel): self
    {
        $this->wheel = $wheel;
        return $this;
    }

    public function getWheel(): ?WheelInterface
    {
        return $this->wheel;
    }

    public function getSpinUrl(): string
    {
        return $this->getUrl('wishreward/wheel/spin');
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }
}