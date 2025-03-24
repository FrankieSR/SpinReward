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

    /**
     * Returns the eligible popup according to business rules.
     *
     * @return WheelInterface|null
     */
    public function getEligiblePopup(): ?WheelInterface;
}
