<?php

declare(strict_types=1);

namespace Doroshko\WishReward\Api\Data;

interface WheelInterface
{
    public function getWheelId(): ?int;
    public function setWheelId(int $id): self;

    public function getTitle(): string;
    public function setTitle(string $title): self;

    public function getAllowedCustomerGroups(): ?string;
    public function setAllowedCustomerGroups(?string $groups): self;

    public function getWinMessage(): ?string;
    public function setWinMessage(?string $message): self;

    public function getNoWinMessage(): ?string;
    public function setNoWinMessage(?string $message): self;

    public function getStartDate(): ?string;
    public function setStartDate(?string $date): self;

    public function getEndDate(): ?string;
    public function setEndDate(?string $date): self;

    public function isActive(): bool;
    public function setIsActive(bool $active): self;

    public function getStoreviews(): ?string;
    public function setStoreviews(?string $storeviews): self;

    public function getRotationDuration(): ?int;
    public function setRotationDuration(?int $duration): self;

    public function getWheelRadius(): ?int;
    public function setWheelRadius(?int $radius): self;

    public function getWheelPosition(): ?string;
    public function setWheelPosition(?string $position): self;

    public function getIsCtaEnabled(): bool;
    public function setIsCtaEnabled(bool $enabled): self;

    public function getCtaLabel(): ?string;
    public function setCtaLabel(?string $label): self;

    public function getCtaButtonText(): ?string;
    public function setCtaButtonText(?string $text): self;

    public function getCtaImage(): ?string;
    public function setCtaImage(?string $image): self;

    public function getCtaPosition(): ?string;
    public function setCtaPosition(?string $position): self;

    public function getCtaCustomCss(): ?string;
    public function setCtaCustomCss(?string $css): self;

    public function getPopupTitle(): ?string;
    public function setPopupTitle(?string $title): self;

    public function getPopupDescription(): ?string;
    public function setPopupDescription(?string $description): self;

    public function getIsWishAreaEnabled(): bool;
    public function setIsWishAreaEnabled(bool $enabled): self;

    public function getIsEmailInputEnabled(): bool;
    public function setIsEmailInputEnabled(bool $enabled): self;

    public function getPopupDelay(): ?int;
    public function setPopupDelay(?int $delay): self;

    public function getPopupScrollTrigger(): ?string;
    public function setPopupScrollTrigger(?string $trigger): self;

    public function getPopupOncePerSession(): bool;
    public function setPopupOncePerSession(bool $once): self;

    public function getWheelConfig(): ?string;
    public function setWheelConfig(?string $config): self;

    public function getDisplayOnPages(): ?string;
    public function setDisplayOnPages(?string $pages): self;
}
