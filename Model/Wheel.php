<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model;

use Magento\Framework\Model\AbstractModel;
use Doroshko\WishReward\Api\Data\WheelInterface;

/**
 * Wheel Model
 */
class Wheel extends AbstractModel implements WheelInterface
{
    protected function _construct()
    {
        $this->_init(\Doroshko\WishReward\Model\ResourceModel\Wheel::class);
    }
    
    public function getWheelId(): ?int
    {
        return $this->getData('wheel_id') ? (int)$this->getData('wheel_id') : null;
    }
    
    public function setWheelId(int $id): self
    {
        $this->setData('wheel_id', $id);
        return $this;
    }
    
    public function getTitle(): string
    {
        return (string)$this->getData('title');
    }
    
    public function setTitle(string $title): self
    {
        $this->setData('title', $title);
        return $this;
    }
    
    public function getAllowedCustomerGroups(): ?string
    {
        return $this->getData('allowed_customer_groups');
    }
    
    public function setAllowedCustomerGroups(?string $groups): self
    {
        $this->setData('allowed_customer_groups', $groups);
        return $this;
    }
    
    public function getWinMessage(): ?string
    {
        return $this->getData('win_message');
    }
    
    public function setWinMessage(?string $message): self
    {
        $this->setData('win_message', $message);
        return $this;
    }
    
    public function getNoWinMessage(): ?string
    {
        return $this->getData('no_win_message');
    }
    
    public function setNoWinMessage(?string $message): self
    {
        $this->setData('no_win_message', $message);
        return $this;
    }
    
    public function getStartDate(): ?string
    {
        return $this->getData('start_date');
    }
    
    public function setStartDate(?string $date): self
    {
        $this->setData('start_date', $date);
        return $this;
    }
    
    public function getEndDate(): ?string
    {
        return $this->getData('end_date');
    }
    
    public function setEndDate(?string $date): self
    {
        $this->setData('end_date', $date);
        return $this;
    }
    
    public function isActive(): bool
    {
        return (bool)$this->getData('is_active');
    }
    
    public function setIsActive(bool $active): self
    {
        $this->setData('is_active', $active);
        return $this;
    }
    
    public function getStoreviews(): ?string
    {
        return $this->getData('storeviews');
    }
    
    public function setStoreviews(?string $storeviews): self
    {
        $this->setData('storeviews', $storeviews);
        return $this;
    }
    
    // CTA Methods
    public function getIsCtaEnabled(): bool
    {
        return (bool)$this->getData('is_cta_enabled');
    }
    
    public function setIsCtaEnabled(bool $enabled): self
    {
        $this->setData('is_cta_enabled', $enabled);
        return $this;
    }
    
    public function getCtaLabel(): ?string
    {
        return $this->getData('cta_label');
    }
    
    public function setCtaLabel(?string $label): self
    {
        $this->setData('cta_label', $label);
        return $this;
    }
    
    public function getCtaButtonText(): ?string
    {
        return $this->getData('cta_button_text');
    }
    
    public function setCtaButtonText(?string $text): self
    {
        $this->setData('cta_button_text', $text);
        return $this;
    }
    
    public function getCtaImage(): ?string
    {
        return $this->getData('cta_image');
    }
    
    public function setCtaImage(?string $image): self
    {
        $this->setData('cta_image', $image);
        return $this;
    }
    
    public function getCtaPosition(): ?string
    {
        return $this->getData('cta_position');
    }
    
    public function setCtaPosition(?string $position): self
    {
        $this->setData('cta_position', $position);
        return $this;
    }
    
    public function getCtaCustomCss(): ?string
    {
        return $this->getData('cta_custom_css');
    }
    
    public function setCtaCustomCss(?string $css): self
    {
        $this->setData('cta_custom_css', $css);
        return $this;
    }
    
    // Popup Methods
    public function getPopupTitle(): ?string
    {
        return $this->getData('popup_title');
    }
    
    public function setPopupTitle(?string $title): self
    {
        $this->setData('popup_title', $title);
        return $this;
    }
    
    public function getPopupDescription(): ?string
    {
        return $this->getData('popup_description');
    }
    
    public function setPopupDescription(?string $description): self
    {
        $this->setData('popup_description', $description);
        return $this;
    }
    
    public function getIsWishAreaEnabled(): bool
    {
        return (bool)$this->getData('is_wish_area_enabled');
    }
    
    public function setIsWishAreaEnabled(bool $enabled): self
    {
        $this->setData('is_wish_area_enabled', $enabled);
        return $this;
    }
    
    public function getIsEmailInputEnabled(): bool
    {
        return (bool)$this->getData('is_email_input_enabled');
    }
    
    public function setIsEmailInputEnabled(bool $enabled): self
    {
        $this->setData('is_email_input_enabled', $enabled);
        return $this;
    }
    
    // Wheel Configuration
    public function getWheelConfig(): ?string
    {
        return $this->getData('wheel_config');
    }
    
    public function setWheelConfig(?string $config): self
    {
        $this->setData('wheel_config', $config);
        return $this;
    }
    
    // Display Configuration
    public function getDisplayOnPages(): ?string
    {
        return $this->getData('display_on_pages');
    }
    
    public function setDisplayOnPages(?string $pages): self
    {
        $this->setData('display_on_pages', $pages);
        return $this;
    }
    
    // Visual settings for the wheel
    public function getRotationDuration(): ?int
    {
        return $this->getData('rotation_duration') !== null ? (int)$this->getData('rotation_duration') : null;
    }
    
    public function setRotationDuration(?int $duration): self
    {
        $this->setData('rotation_duration', $duration);
        return $this;
    }
    
    public function getWheelRadius(): ?int
    {
        return $this->getData('wheel_radius') !== null ? (int)$this->getData('wheel_radius') : null;
    }
    
    public function setWheelRadius(?int $radius): self
    {
        $this->setData('wheel_radius', $radius);
        return $this;
    }
    
    public function getWheelPosition(): ?string
    {
        return $this->getData('wheel_position');
    }
    
    public function setWheelPosition(?string $position): self
    {
        $this->setData('wheel_position', $position);
        return $this;
    }
    
    // Popup trigger settings
    public function getPopupDelay(): ?int
    {
        return $this->getData('popup_delay') !== null ? (int)$this->getData('popup_delay') : null;
    }
    
    public function setPopupDelay(?int $delay): self
    {
        $this->setData('popup_delay', $delay);
        return $this;
    }
    
    public function getPopupScrollTrigger(): ?string
    {
        return $this->getData('popup_scroll_trigger');
    }
    
    public function setPopupScrollTrigger(?string $trigger): self
    {
        $this->setData('popup_scroll_trigger', $trigger);
        return $this;
    }
    
    public function getPopupOncePerSession(): bool
    {
        return (bool)$this->getData('popup_once_per_session');
    }
    
    public function setPopupOncePerSession(bool $once): self
    {
        $this->setData('popup_once_per_session', $once);
        return $this;
    }
}
