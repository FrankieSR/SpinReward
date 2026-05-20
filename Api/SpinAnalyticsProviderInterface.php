<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Api;

interface SpinAnalyticsProviderInterface
{
    /**
     * Returns the number of spins for the given email on the specified date.
     *
     * @param string $email
     * @param string|null $date
     * @return int
     */
    public function getSpinCountByEmail(string $email, ?string $date = null): int;

    /**
     * Returns the number of spins for the given email and wheel within the specified date range.
     *
     * @param string $email
     * @param int $wheelId
     * @param string $startDate
     * @param string $endDate
     * @return int
     */
    public function getSpinCountByEmailAndWheel(string $email, int $wheelId, string $startDate, string $endDate): int;

    /**
     * Returns the number of spins for the given customer and wheel within the specified date range.
     *
     * @param int $customerId
     * @param int $wheelId
     * @param string $startDate
     * @param string $endDate
     * @return int
     */
    public function getSpinCountByCustomerAndWheel(int $customerId, int $wheelId, string $startDate, string $endDate): int;

    /**
     * Returns all spins for the given email.
     *
     * @param string $email
     * @return array
     */
    public function getSpinsByEmail(string $email): array;
}
