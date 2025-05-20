<?php
declare(strict_types=1);

namespace Doroshko\WishReward\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Doroshko\WishReward\Api\Data\WheelInterface;

class WheelPopupViewModel implements ArgumentInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WheelInterface
     */
    private $wheel;

    /**
     * WheelPopupViewModel constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WheelInterface $wheel
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WheelInterface $wheel
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->wheel = $wheel;
    }

    public function setWheel(WheelInterface $wheel): void
    {
        $this->wheel = $wheel;
    }

    public function getWheel(): ?WheelInterface
    {
        return $this->wheel;
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

    /**
     * Get wheel configuration
     *
     * @return string|null
     */
    public function getWheelConfig(): ?string
    {
        return $this->wheel->getWheelConfig();
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

    public function getIsCtaEnabled(): bool
    {
        return $this->wheel ? $this->wheel->getIsCtaEnabled() : false;
    }

    public function getCtaLabel(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaLabel() : null;
    }

    public function getCtaButtonText(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaButtonText() : null;
    }

    public function getCtaImage(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaImage() : null;
    }

    public function getCtaPosition(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaPosition() : null;
    }

    public function getCtaCustomCss(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaCustomCss() : null;
    }

    /**
     * Get popup title
     *
     * @return string|null
     */
    public function getPopupTitle(): ?string
    {
        return $this->wheel->getPopupTitle();
    }

    /**
     * Get popup description
     *
     * @return string|null
     */
    public function getPopupDescription(): ?string
    {
        return $this->wheel->getPopupDescription();
    }

    /**
     * Check if wish area is enabled
     *
     * @return bool
     */
    public function getIsWishAreaEnabled(): bool
    {
        return $this->wheel->getIsWishAreaEnabled();
    }

    /**
     * Check if email input is enabled
     *
     * @return bool
     */
    public function getIsEmailInputEnabled(): bool
    {
        return $this->wheel ? $this->wheel->getIsEmailInputEnabled() : false;
    }

    /**
     * Get rotation duration
     *
     * @return int|null
     */
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

    public function getPopupOncePerSession(): bool
    {
        return $this->wheel ? $this->wheel->getPopupOncePerSession() : false;
    }

    /**
     * Get popup company logo
     *
     * @return string|null
     */
    public function getPopupCompanyLogo(): ?string
    {
        return $this->wheel
            ? $this->wheel->getPopupCompanyLogo()
            : null;
    }

    /**
     * Get popup company text
     *
     * @return string|null
     */
    public function getPopupCompanyText(): ?string
    {
        return $this->wheel->getPopupCompanyText();
    }

    /**
     * Get popup button text
     *
     * @return string|null
     */
    public function getPopupButtonText(): ?string
    {
        return $this->wheel->getPopupButtonText();
    }

    /**
     * Get popup decline text
     *
     * @return string|null
     */
    public function getPopupDeclineText(): ?string
    {
        return $this->wheel->getPopupDeclineText();
    }

    /**
     * Get popup theme
     *
     * @return string
     */
    public function getPopupTheme(): string
    {
        return $this->wheel->getPopupTheme() ?: 'light';
    }
}
