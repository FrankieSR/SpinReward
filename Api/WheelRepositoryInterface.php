<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Api;

use Doroshko\WishReward\Api\Data\WheelInterface;

interface WheelRepositoryInterface
{
    public function save(WheelInterface $wheel): WheelInterface;
    public function getById(int $wheelId): WheelInterface;
    public function delete(WheelInterface $wheel): bool;
    public function deleteById(int $wheelId): bool;
    public function getEligiblePopup(): ?WheelInterface;
    public function cacheActiveWheels(): void;
}
