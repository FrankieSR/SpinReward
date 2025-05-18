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

    public function getRotationDuration(): ?int
    {
        return $this->getData('rotation_duration') !== null ? (int)$this->getData('rotation_duration') : null;
    }
    
    public function setRotationDuration(?int $duration): self
    {
        $this->setData('rotation_duration', $duration);
        return $this;
    }

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

    public function getIsDelayEnabled(): bool
    {
        return (bool)$this->getData('is_delay_enabled');
    }

    public function setIsDelayEnabled(bool $enabled): self
    {
        $this->setData('is_delay_enabled', $enabled);
        return $this;
    }

    public function getTimeOfDayStart(): ?string
    {
        return $this->getData('time_of_day_start');
    }

    public function setTimeOfDayStart(?string $time): self
    {
        $this->setData('time_of_day_start', $time);
        return $this;
    }

    public function getTimeOfDayEnd(): ?string
    {
        return $this->getData('time_of_day_end');
    }

    public function setTimeOfDayEnd(?string $time): self
    {
        $this->setData('time_of_day_end', $time);
        return $this;
    }

    public function getTriggerAction(): ?string
    {
        return $this->getData('trigger_action');
    }

    public function setTriggerAction(?string $action): self
    {
        $this->setData('trigger_action', $action);
        return $this;
    }

    public function getConditionsSerialized(): ?string
    {
        return $this->getData('conditions_serialized');
    }

    public function setConditionsSerialized(?string $conditions): self
    {
        $this->setData('conditions_serialized', $conditions);
        return $this;
    }

    public function getOncePerUser(): bool
    {
        return (bool)$this->getData('once_per_user');
    }

    public function setOncePerUser(bool $once): self
    {
        $this->setData('once_per_user', $once);
        return $this;
    }

    // New Popup Methods
    public function getPopupButtonText(): ?string
    {
        return $this->getData('popup_button_text');
    }
    
    public function setPopupButtonText(?string $text): self
    {
        $this->setData('popup_button_text', $text);
        return $this;
    }
    
    public function getPopupCompanyText(): ?string
    {
        return $this->getData('popup_company_text');
    }
    
    public function setPopupCompanyText(?string $text): self
    {
        $this->setData('popup_company_text', $text);
        return $this;
    }
    
    public function getPopupCompanyLogo(): ?string
    {
        return $this->getData('popup_company_logo');
    }
    
    public function setPopupCompanyLogo(?string $logo): self
    {
        $this->setData('popup_company_logo', $logo);
        return $this;
    }
    
    public function getPopupDeclineText(): ?string
    {
        return $this->getData('popup_decline_text');
    }
    
    public function setPopupDeclineText(?string $text): self
    {
        $this->setData('popup_decline_text', $text);
        return $this;
    }
    
    public function getPopupCloseText(): ?string
    {
        return $this->getData('popup_close_text');
    }
    
    public function setPopupCloseText(?string $text): self
    {
        $this->setData('popup_close_text', $text);
        return $this;
    }
    
    public function getPopupTermsText(): ?string
    {
        return $this->getData('popup_terms_text');
    }
    
    public function setPopupTermsText(?string $text): self
    {
        $this->setData('popup_terms_text', $text);
        return $this;
    }

    // New Trigger Methods
    public function getIsScrollEnabled(): bool
    {
        return (bool)$this->getData(self::IS_SCROLL_ENABLED);
    }

    public function setIsScrollEnabled(bool $enabled): self
    {
        $this->setData(self::IS_SCROLL_ENABLED, $enabled);
        return $this;
    }

    public function getScrollPercentage(): ?int
    {
        return $this->getData(self::SCROLL_PERCENTAGE) !== null ? (int)$this->getData(self::SCROLL_PERCENTAGE) : null;
    }

    public function setScrollPercentage(?int $percentage): self
    {
        $this->setData(self::SCROLL_PERCENTAGE, $percentage);
        return $this;
    }

    public function getIsTimeoutEnabled(): bool
    {
        return (bool)$this->getData(self::IS_TIMEOUT_ENABLED);
    }

    public function setIsTimeoutEnabled(bool $enabled): self
    {
        $this->setData(self::IS_TIMEOUT_ENABLED, $enabled);
        return $this;
    }

    public function getTimeoutDuration(): ?int
    {
        return $this->getData(self::TIMEOUT_DURATION) !== null ? (int)$this->getData(self::TIMEOUT_DURATION) : null;
    }

    public function setTimeoutDuration(?int $duration): self
    {
        $this->setData(self::TIMEOUT_DURATION, $duration);
        return $this;
    }

    public function getIsExitEnabled(): bool
    {
        return (bool)$this->getData(self::IS_EXIT_ENABLED);
    }

    public function setIsExitEnabled(bool $enabled): self
    {
        $this->setData(self::IS_EXIT_ENABLED, $enabled);
        return $this;
    }

    // Timestamp Methods
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Get popup theme
     *
     * @return string
     */
    public function getPopupTheme(): string
    {
        return $this->getData('popup_theme') ?: 'light';
    }

    /**
     * Set popup theme
     *
     * @param string $theme
     * @return $this
     */
    public function setPopupTheme(string $theme): self
    {
        return $this->setData('popup_theme', $theme);
    }
}