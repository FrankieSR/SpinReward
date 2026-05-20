<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Api;

use Doroshko\SpinReward\Api\Data\WheelInterface;

interface WheelRepositoryInterface
{
    public function save(WheelInterface $wheel): WheelInterface;
    public function getById(int $wheelId): WheelInterface;
    public function isEligibleWheel(WheelInterface $wheel): bool;
    public function getEligibleWheelById(int $wheelId): ?WheelInterface;
    public function delete(WheelInterface $wheel): bool;
    public function deleteById(int $wheelId): bool;
    public function getEligiblePopup(): ?WheelInterface;
}
