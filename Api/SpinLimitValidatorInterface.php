<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Api;

interface SpinLimitValidatorInterface
{
    /**
     * Determines if the user is allowed to spin for a specific wheel.
     *
     * @param string $email
     * @param int $wheelId
     * @return bool
     */
    public function canSpin(string $email, int $wheelId): bool;

    /**
     * Checks whether the user has exceeded the spin limit for a specific wheel.
     *
     * @param string $email
     * @param int $wheelId
     * @return bool
     */
    public function hasExceededLimit(string $email, int $wheelId): bool;

    /**
     * Returns the number of remaining spins for a specific wheel.
     *
     * @param string $email
     * @param int $wheelId
     * @return int
     */
    public function getRemainingSpins(string $email, int $wheelId): int;
}
