<?php

declare(strict_types=1);

namespace Doroshko\WishReward\Model;

use Magento\Framework\Model\AbstractModel;
use Doroshko\WishReward\Api\Data\WheelInterface;

/**
 * Wheel model representing a WishReward wheel entity.
 *
 * Implements WheelInterface to provide access to wheel configuration and settings.
 */
class Wheel extends AbstractModel implements WheelInterface
{
    protected function _construct()
    {
        $this->_init(\Doroshko\WishReward\Model\ResourceModel\Wheel::class);
    }

    /**
     * Get the wheel ID.
     *
     * @return int|null The wheel ID, or null if not set.
     */
    public function getWheelId(): ?int
    {
        return $this->getData(self::WHEEL_ID) ? (int)$this->getData(self::WHEEL_ID) : null;
    }

    /**
     * Set the wheel ID.
     *
     * @param int $id The wheel ID.
     * @return self
     */
    public function setWheelId(int $id): self
    {
        $this->setData(self::WHEEL_ID, $id);
        return $this;
    }

    /**
     * Get the wheel title.
     *
     * @return string The wheel title.
     */
    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    /**
     * Set the wheel title.
     *
     * @param string $title The wheel title.
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->setData(self::TITLE, $title);
        return $this;
    }

    /**
     * Get allowed customer groups.
     *
     * @return string|null Comma-separated list of customer group IDs, or null if not set.
     */
    public function getAllowedCustomerGroups(): ?string
    {
        return $this->getData(self::ALLOWED_CUSTOMER_GROUPS);
    }

    /**
     * Set allowed customer groups.
     *
     * @param string|null $groups Comma-separated list of customer group IDs.
     * @return self
     */
    public function setAllowedCustomerGroups(?string $groups): self
    {
        $this->setData(self::ALLOWED_CUSTOMER_GROUPS, $groups);
        return $this;
    }

    /**
     * Get the win message.
     *
     * @return string|null The message displayed on win, or null if not set.
     */
    public function getWinMessage(): ?string
    {
        return $this->getData(self::WIN_MESSAGE);
    }

    /**
     * Set the win message.
     *
     * @param string|null $message The win message.
     * @return self
     */
    public function setWinMessage(?string $message): self
    {
        $this->setData(self::WIN_MESSAGE, $message);
        return $this;
    }

    /**
     * Get the no-win message.
     *
     * @return string|null The message displayed on no win, or null if not set.
     */
    public function getNoWinMessage(): ?string
    {
        return $this->getData(self::NO_WIN_MESSAGE);
    }

    /**
     * Set the no-win message.
     *
     * @param string|null $message The no-win message.
     * @return self
     */
    public function setNoWinMessage(?string $message): self
    {
        $this->setData(self::NO_WIN_MESSAGE, $message);
        return $this;
    }

    /**
     * Get the start date.
     *
     * @return string|null The start date in Y-m-d H:i:s format, or null if not set.
     */
    public function getStartDate(): ?string
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * Set the start date.
     *
     * @param string|null $date The start date in Y-m-d H:i:s format.
     * @return self
     */
    public function setStartDate(?string $date): self
    {
        $this->setData(self::START_DATE, $date);
        return $this;
    }

    /**
     * Get the end date.
     *
     * @return string|null The end date in Y-m-d H:i:s format, or null if not set.
     */
    public function getEndDate(): ?string
    {
        return $this->getData(self::END_DATE);
    }

    /**
     * Set the end date.
     *
     * @param string|null $date The end date in Y-m-d H:i:s format.
     * @return self
     */
    public function setEndDate(?string $date): self
    {
        $this->setData(self::END_DATE, $date);
        return $this;
    }

    /**
     * Check if the wheel is active.
     *
     * @return bool True if the wheel is active, false otherwise.
     */
    public function isActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * Set the active status of the wheel.
     *
     * @param bool $active The active status.
     * @return self
     */
    public function setIsActive(bool $active): self
    {
        $this->setData(self::IS_ACTIVE, $active);
        return $this;
    }

    /**
     * Get the store views.
     *
     * @return string|null Comma-separated list of store view IDs, or null if not set.
     */
    public function getStoreviews(): ?string
    {
        return $this->getData(self::STOREVIEWS);
    }

    /**
     * Set the store views.
     *
     * @param string|null $storeviews Comma-separated list of store view IDs.
     * @return self
     */
    public function setStoreviews(?string $storeviews): self
    {
        $this->setData(self::STOREVIEWS, $storeviews);
        return $this;
    }

    /**
     * Check if the CTA is enabled.
     *
     * @return bool True if the CTA is enabled, false otherwise.
     */
    public function getIsCtaEnabled(): bool
    {
        return (bool)$this->getData(self::IS_CTA_ENABLED);
    }

    /**
     * Set whether the CTA is enabled.
     *
     * @param bool $enabled The CTA enabled status.
     * @return self
     */
    public function setIsCtaEnabled(bool $enabled): self
    {
        $this->setData(self::IS_CTA_ENABLED, $enabled);
        return $this;
    }

    /**
     * Get the CTA label.
     *
     * @return string|null The CTA label, or null if not set.
     */
    public function getCtaLabel(): ?string
    {
        return $this->getData(self::CTA_LABEL);
    }

    /**
     * Set the CTA label.
     *
     * @param string|null $label The CTA label.
     * @return self
     */
    public function setCtaLabel(?string $label): self
    {
        $this->setData(self::CTA_LABEL, $label);
        return $this;
    }

    /**
     * Get the CTA button text.
     *
     * @return string|null The CTA button text, or null if not set.
     */
    public function getCtaButtonText(): ?string
    {
        return $this->getData(self::CTA_BUTTON_TEXT);
    }

    /**
     * Set the CTA button text.
     *
     * @param string|null $text The CTA button text.
     * @return self
     */
    public function setCtaButtonText(?string $text): self
    {
        $this->setData(self::CTA_BUTTON_TEXT, $text);
        return $this;
    }

    /**
     * Get the CTA image URL.
     *
     * @return string|null The CTA image URL, or null if not set.
     */
    public function getCtaImage(): ?string
    {
        return $this->getData(self::CTA_IMAGE);
    }

    /**
     * Set the CTA image URL.
     *
     * @param string|null $image The CTA image URL.
     * @return self
     */
    public function setCtaImage(?string $image): self
    {
        $this->setData(self::CTA_IMAGE, $image);
        return $this;
    }

    /**
     * Get the CTA position.
     *
     * @return string|null The CTA position (e.g., 'center'), or null if not set.
     */
    public function getCtaPosition(): ?string
    {
        return $this->getData(self::CTA_POSITION);
    }

    /**
     * Set the CTA position.
     *
     * @param string|null $position The CTA position.
     * @return self
     */
    public function setCtaPosition(?string $position): self
    {
        $this->setData(self::CTA_POSITION, $position);
        return $this;
    }

    /**
     * Get the CTA custom CSS.
     *
     * @return string|null The CTA custom CSS, or null if not set.
     */
    public function getCtaCustomCss(): ?string
    {
        return $this->getData(self::CTA_CUSTOM_CSS);
    }

    /**
     * Set the CTA custom CSS.
     *
     * @param string|null $css The CTA custom CSS.
     * @return self
     */
    public function setCtaCustomCss(?string $css): self
    {
        $this->setData(self::CTA_CUSTOM_CSS, $css);
        return $this;
    }

    /**
     * Get the popup title.
     *
     * @return string|null The popup title, or null if not set.
     */
    public function getPopupTitle(): ?string
    {
        return $this->getData(self::POPUP_TITLE);
    }

    /**
     * Set the popup title.
     *
     * @param string|null $title The popup title.
     * @return self
     */
    public function setPopupTitle(?string $title): self
    {
        $this->setData(self::POPUP_TITLE, $title);
        return $this;
    }

    /**
     * Get the popup description.
     *
     * @return string|null The popup description, or null if not set.
     */
    public function getPopupDescription(): ?string
    {
        return $this->getData(self::POPUP_DESCRIPTION);
    }

    /**
     * Set the popup description.
     *
     * @param string|null $description The popup description.
     * @return self
     */
    public function setPopupDescription(?string $description): self
    {
        $this->setData(self::POPUP_DESCRIPTION, $description);
        return $this;
    }

    /**
     * Check if the wish area is enabled.
     *
     * @return bool True if the wish area is enabled, false otherwise.
     */
    public function getIsWishAreaEnabled(): bool
    {
        return (bool)$this->getData(self::IS_WISH_AREA_ENABLED);
    }

    /**
     * Set whether the wish area is enabled.
     *
     * @param bool $enabled The wish area enabled status.
     * @return self
     */
    public function setIsWishAreaEnabled(bool $enabled): self
    {
        $this->setData(self::IS_WISH_AREA_ENABLED, $enabled);
        return $this;
    }

    /**
     * Get the wheel configuration.
     *
     * @return string|null The wheel configuration (e.g., JSON), or null if not set.
     */
    public function getWheelConfig(): ?string
    {
        return $this->getData(self::WHEEL_CONFIG);
    }

    /**
     * Set the wheel configuration.
     *
     * @param string|null $config The wheel configuration.
     * @return self
     */
    public function setWheelConfig(?string $config): self
    {
        $this->setData(self::WHEEL_CONFIG, $config);
        return $this;
    }

    /**
     * Get the rotation duration.
     *
     * @return int|null The rotation duration in milliseconds, or null if not set.
     */
    public function getRotationDuration(): ?int
    {
        return $this->getData(self::ROTATION_DURATION) !== null ? (int)$this->getData(self::ROTATION_DURATION) : null;
    }

    /**
     * Set the rotation duration.
     *
     * @param int|null $duration The rotation duration in milliseconds.
     * @return self
     */
    public function setRotationDuration(?int $duration): self
    {
        $this->setData(self::ROTATION_DURATION, $duration);
        return $this;
    }

    /**
     * Get the popup delay.
     *
     * @return int|null The popup delay in milliseconds, or null if not set.
     */
    public function getPopupDelay(): ?int
    {
        return $this->getData(self::POPUP_DELAY) !== null ? (int)$this->getData(self::POPUP_DELAY) : null;
    }

    /**
     * Set the popup delay.
     *
     * @param int|null $delay The popup delay in milliseconds.
     * @return self
     */
    public function setPopupDelay(?int $delay): self
    {
        $this->setData(self::POPUP_DELAY, $delay);
        return $this;
    }

    /**
     * Get the popup scroll trigger.
     *
     * @return string|null The scroll trigger configuration, or null if not set.
     */
    public function getPopupScrollTrigger(): ?string
    {
        return $this->getData(self::POPUP_SCROLL_TRIGGER);
    }

    /**
     * Set the popup scroll trigger.
     *
     * @param string|null $trigger The scroll trigger configuration.
     * @return self
     */
    public function setPopupScrollTrigger(?string $trigger): self
    {
        $this->setData(self::POPUP_SCROLL_TRIGGER, $trigger);
        return $this;
    }

    /**
     * Check if the delay is enabled.
     *
     * @return bool True if the delay is enabled, false otherwise.
     */
    public function getIsDelayEnabled(): bool
    {
        return (bool)$this->getData('is_delay_enabled');
    }

    /**
     * Set whether the delay is enabled.
     *
     * @param bool $enabled The delay enabled status.
     * @return self
     */
    public function setIsDelayEnabled(bool $enabled): self
    {
        $this->setData('is_delay_enabled', $enabled);
        return $this;
    }

    /**
     * Get the start time of day.
     *
     * @return string|null The start time of day (e.g., '09:00'), or null if not set.
     */
    public function getTimeOfDayStart(): ?string
    {
        return $this->getData('time_of_day_start');
    }

    /**
     * Set the start time of day.
     *
     * @param string|null $time The start time of day.
     * @return self
     */
    public function setTimeOfDayStart(?string $time): self
    {
        $this->setData('time_of_day_start', $time);
        return $this;
    }

    /**
     * Get the end time of day.
     *
     * @return string|null The end time of day (e.g., '17:00'), or null if not set.
     */
    public function getTimeOfDayEnd(): ?string
    {
        return $this->getData('time_of_day_end');
    }

    /**
     * Set the end time of day.
     *
     * @param string|null $time The end time of day.
     * @return self
     */
    public function setTimeOfDayEnd(?string $time): self
    {
        $this->setData('time_of_day_end', $time);
        return $this;
    }

    /**
     * Get the trigger action.
     *
     * @return string|null The trigger action (e.g., 'spin'), or null if not set.
     */
    public function getTriggerAction(): ?string
    {
        return $this->getData('trigger_action');
    }

    /**
     * Set the trigger action.
     *
     * @param string|null $action The trigger action.
     * @return self
     */
    public function setTriggerAction(?string $action): self
    {
        $this->setData('trigger_action', $action);
        return $this;
    }

    /**
     * Get the number of attempts per user.
     *
     * @return int The number of allowed attempts per user.
     */
    public function getAttemptsPerUser(): int
    {
        return (int)$this->getData(self::ATTEMPTS_PER_USER);
    }

    /**
     * Set the number of attempts per user.
     *
     * @param int $attempts The number of attempts per user.
     * @return self
     */
    public function setAttemptsPerUser(int $attempts): self
    {
        $this->setData(self::ATTEMPTS_PER_USER, $attempts);
        return $this;
    }

    /**
     * Get the attempts period unit.
     *
     * @return string The period unit (e.g., 'day', 'week').
     */
    public function getAttemptsPeriodUnit(): string
    {
        return (string)$this->getData(self::ATTEMPTS_PERIOD_UNIT);
    }

    /**
     * Set the attempts period unit.
     *
     * @param string $period The period unit.
     * @return self
     */
    public function setAttemptsPeriodUnit(string $period): self
    {
        $this->setData(self::ATTEMPTS_PERIOD_UNIT, $period);
        return $this;
    }

    /**
     * Get the popup button text.
     *
     * @return string|null The popup button text, or null if not set.
     */
    public function getPopupButtonText(): ?string
    {
        return $this->getData(self::POPUP_BUTTON_TEXT);
    }

    /**
     * Set the popup button text.
     *
     * @param string|null $text The popup button text.
     * @return self
     */
    public function setPopupButtonText(?string $text): self
    {
        $this->setData(self::POPUP_BUTTON_TEXT, $text);
        return $this;
    }

    /**
     * Get the popup company text.
     *
     * @return string|null The popup company text, or null if not set.
     */
    public function getPopupCompanyText(): ?string
    {
        return $this->getData(self::POPUP_COMPANY_TEXT);
    }

    /**
     * Set the popup company text.
     *
     * @param string|null $text The popup company text.
     * @return self
     */
    public function setPopupCompanyText(?string $text): self
    {
        $this->setData(self::POPUP_COMPANY_TEXT, $text);
        return $this;
    }

    /**
     * Get the popup company logo URL.
     *
     * @return string|null The popup company logo URL, or null if not set.
     */
    public function getPopupCompanyLogo(): ?string
    {
        return $this->getData(self::POPUP_COMPANY_LOGO);
    }

    /**
     * Set the popup company logo URL.
     *
     * @param string|null $logo The popup company logo URL.
     * @return self
     */
    public function setPopupCompanyLogo(?string $logo): self
    {
        $this->setData(self::POPUP_COMPANY_LOGO, $logo);
        return $this;
    }

    /**
     * Get the popup decline text.
     *
     * @return string|null The popup decline text, or null if not set.
     */
    public function getPopupDeclineText(): ?string
    {
        return $this->getData(self::POPUP_DECLINE_TEXT);
    }

    /**
     * Set the popup decline text.
     *
     * @param string|null $text The popup decline text.
     * @return self
     */
    public function setPopupDeclineText(?string $text): self
    {
        $this->setData(self::POPUP_DECLINE_TEXT, $text);
        return $this;
    }

    /**
     * Get the popup close text.
     *
     * @return string|null The popup close text, or null if not set.
     */
    public function getPopupCloseText(): ?string
    {
        return $this->getData(self::POPUP_CLOSE_TEXT);
    }

    /**
     * Set the popup close text.
     *
     * @param string|null $text The popup close text.
     * @return self
     */
    public function setPopupCloseText(?string $text): self
    {
        $this->setData(self::POPUP_CLOSE_TEXT, $text);
        return $this;
    }

    /**
     * Get the popup terms text.
     *
     * @return string|null The popup terms text, or null if not set.
     */
    public function getPopupTermsText(): ?string
    {
        return $this->getData(self::POPUP_TERMS_TEXT);
    }

    /**
     * Set the popup terms text.
     *
     * @param string|null $text The popup terms text.
     * @return self
     */
    public function setPopupTermsText(?string $text): self
    {
        $this->setData(self::POPUP_TERMS_TEXT, $text);
        return $this;
    }

    /**
     * Check if the scroll trigger is enabled.
     *
     * @return bool True if the scroll trigger is enabled, false otherwise.
     */
    public function getIsScrollEnabled(): bool
    {
        return (bool)$this->getData(self::IS_SCROLL_ENABLED);
    }

    /**
     * Set whether the scroll trigger is enabled.
     *
     * @param bool $enabled The scroll trigger enabled status.
     * @return self
     */
    public function setIsScrollEnabled(bool $enabled): self
    {
        $this->setData(self::IS_SCROLL_ENABLED, $enabled);
        return $this;
    }

    /**
     * Get the scroll percentage.
     *
     * @return int|null The scroll percentage, or null if not set.
     */
    public function getScrollPercentage(): ?int
    {
        return $this->getData(self::SCROLL_PERCENTAGE) !== null ? (int)$this->getData(self::SCROLL_PERCENTAGE) : null;
    }

    /**
     * Set the scroll percentage.
     *
     * @param int|null $percentage The scroll percentage.
     * @return self
     */
    public function setScrollPercentage(?int $percentage): self
    {
        $this->setData(self::SCROLL_PERCENTAGE, $percentage);
        return $this;
    }

    /**
     * Check if the timeout trigger is enabled.
     *
     * @return bool True if the timeout trigger is enabled, false otherwise.
     */
    public function getIsTimeoutEnabled(): bool
    {
        return (bool)$this->getData(self::IS_TIMEOUT_ENABLED);
    }

    /**
     * Set whether the timeout trigger is enabled.
     *
     * @param bool $enabled The timeout trigger enabled status.
     * @return self
     */
    public function setIsTimeoutEnabled(bool $enabled): self
    {
        $this->setData(self::IS_TIMEOUT_ENABLED, $enabled);
        return $this;
    }

    /**
     * Get the timeout duration.
     *
     * @return int|null The timeout duration in seconds, or null if not set.
     */
    public function getTimeoutDuration(): ?int
    {
        return $this->getData(self::TIMEOUT_DURATION) !== null ? (int)$this->getData(self::TIMEOUT_DURATION) : null;
    }

    /**
     * Set the timeout duration.
     *
     * @param int|null $duration The timeout duration in seconds.
     * @return self
     */
    public function setTimeoutDuration(?int $duration): self
    {
        $this->setData(self::TIMEOUT_DURATION, $duration);
        return $this;
    }

    /**
     * Check if the exit intent trigger is enabled.
     *
     * @return bool True if the exit intent trigger is enabled, false otherwise.
     */
    public function getIsExitEnabled(): bool
    {
        return (bool)$this->getData(self::IS_EXIT_ENABLED);
    }

    /**
     * Set whether the exit intent trigger is enabled.
     *
     * @param bool $enabled The exit intent trigger enabled status.
     * @return self
     */
    public function setIsExitEnabled(bool $enabled): self
    {
        $this->setData(self::IS_EXIT_ENABLED, $enabled);
        return $this;
    }

    /**
     * Get the creation date.
     *
     * @return string The creation date in Y-m-d H:i:s format.
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData('created_at');
    }

    /**
     * Get the update date.
     *
     * @return string|null The update date in Y-m-d H:i:s format, or null if not set.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }

    /**
     * Get the popup theme.
     *
     * @return string The popup theme (e.g., 'light', 'dark'), defaults to 'light'.
     */
    public function getPopupTheme(): string
    {
        return $this->getData(self::POPUP_THEME) ?: 'light';
    }

    /**
     * Set the popup theme.
     *
     * @param string $theme The popup theme.
     * @return self
     */
    public function setPopupTheme(string $theme): self
    {
        $this->setData(self::POPUP_THEME, $theme);
        return $this;
    }
}
