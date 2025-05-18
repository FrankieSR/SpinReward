<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Api;

interface SpinLogRepositoryInterface
{
    /**
     * Save spin data
     *
     * @param array $data
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveSpin(array $data): void;

    /**
     * Get spin count by email for a specific date
     *
     * @param string $email
     * @param string|null $date
     * @return int
     */
    public function getSpinCountByEmail(string $email, ?string $date = null): int;

    /**
     * Check if user can spin
     *
     * @param string $email
     * @param int $maxSpins
     * @return bool
     */
    public function canSpin(string $email, int $maxSpins = 1): bool;

    /**
     * Get spins by email
     *
     * @param string $email
     * @return array
     */
    public function getSpinsByEmail(string $email): array;

    /**
     * Delete spins by email
     *
     * @param string $email
     */
    public function deleteByEmail(string $email): void;

    /**
     * Get total spins
     *
     * @return int
     */
    public function getTotalSpins(): int;
}
