<?php
declare(strict_types=1);

namespace Doroshko\WishReward\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Doroshko\WishReward\Api\Data\WheelInterface;
use Magento\Customer\Model\Session as CustomerSession;

class WheelPopupViewModel implements ArgumentInterface
{
    private WheelInterface $wheel;
    private CustomerSession $customerSession;

    public function __construct(
        WheelInterface $wheel,
        CustomerSession $customerSession
    ) {
        $this->wheel = $wheel;
        $this->customerSession = $customerSession;
    }

    public function setWheel(WheelInterface $wheel): void
    {
        $this->wheel = $wheel;
    }

    public function getWheel(): ?WheelInterface
    {
        return $this->wheel;
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getWheelId(): ?int
    {
        return $this->wheel ? $this->wheel->getWheelId() : null;
    }

    public function getTitle(): string
    {
        return $this->wheel ? $this->wheel->getTitle() : '';
    }

    public function isActive(): bool
    {
        return $this->wheel ? $this->wheel->isActive() : false;
    }

    public function getStartDate(): ?string
    {
        return $this->wheel ? $this->wheel->getStartDate() : null;
    }

    public function getEndDate(): ?string
    {
        return $this->wheel ? $this->wheel->getEndDate() : null;
    }

    public function getWinMessage(): ?string
    {
        return $this->wheel ? $this->wheel->getWinMessage() : null;
    }

    public function getNoWinMessage(): ?string
    {
        return $this->wheel ? $this->wheel->getNoWinMessage() : null;
    }

    public function getAttemptsPerUser(): ?int
    {
        return $this->wheel ? $this->wheel->getAttemptsPerUser() : null;
    }

    public function getAttemptsPeriodUnit(): ?string
    {
        return $this->wheel ? $this->wheel->getAttemptsPeriodUnit() : null;
    }

    public function getWheelConfig(): ?string
    {
        return $this->wheel ? $this->wheel->getWheelConfig() : null;
    }

    public function getStoreviews(): array
    {
        return $this->wheel && $this->wheel->getStoreviews() 
            ? explode(',', $this->wheel->getStoreviews()) 
            : [];
    }

    public function getAllowedCustomerGroups(): array
    {
        return $this->wheel && $this->wheel->getAllowedCustomerGroups() 
            ? explode(',', $this->wheel->getAllowedCustomerGroups()) 
            : [];
    }

    public function getPopupTitle(): ?string
    {
        return $this->wheel ? $this->wheel->getPopupTitle() : null;
    }

    public function getPopupDescription(): ?string
    {
        return $this->wheel ? $this->wheel->getPopupDescription() : null;
    }

    public function getIsWishAreaEnabled(): bool
    {
        return $this->wheel ? $this->wheel->getIsWishAreaEnabled() : false;
    }

    public function getRotationDuration(): ?int
    {
        return $this->wheel ? $this->wheel->getRotationDuration() : null;
    }

    public function getPopupDelay(): ?int
    {
        return $this->wheel ? $this->wheel->getPopupDelay() : null;
    }

    public function getPopupScrollTrigger(): ?string
    {
        return $this->wheel ? $this->wheel->getPopupScrollTrigger() : null;
    }

    public function getPopupCompanyLogo(): ?string
    {
        return $this->wheel ? $this->wheel->getPopupCompanyLogo() : null;
    }

    public function getPopupCompanyText(): ?string
    {
        return $this->wheel ? $this->wheel->getPopupCompanyText() : null;
    }

    public function getPopupButtonText(): ?string
    {
        return $this->wheel ? $this->wheel->getPopupButtonText() : null;
    }

    public function getPopupDeclineText(): ?string
    {
        return $this->wheel ? $this->wheel->getPopupDeclineText() : null;
    }

    public function getPopupTheme(): string
    {
        return $this->wheel ? $this->wheel->getPopupTheme() : 'light';
    }

    public function getPopupTermsText(): ?string
    {
        return $this->wheel->getPopupTermsText();
    }
}
