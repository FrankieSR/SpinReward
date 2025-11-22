<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model;

use Doroshko\WishReward\Api\SpinLimitValidatorInterface;
use Doroshko\WishReward\Api\SpinAnalyticsProviderInterface;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class SpinLimitValidator implements SpinLimitValidatorInterface
{
    private SpinAnalyticsProviderInterface $analyticsProvider;
    private TimezoneInterface $timezone;
    private WheelRepositoryInterface $wheelRepository;
    private LoggerInterface $logger;

    public function __construct(
        SpinAnalyticsProviderInterface $analyticsProvider,
        TimezoneInterface $timezone,
        WheelRepositoryInterface $wheelRepository,
        LoggerInterface $logger
    ) {
        $this->analyticsProvider = $analyticsProvider;
        $this->timezone = $timezone;
        $this->wheelRepository = $wheelRepository;
        $this->logger = $logger;
    }

    public function canSpin(string $email, int $wheelId): bool
    {
        try {
            $wheel = $this->wheelRepository->getById($wheelId);
            if (!$wheel->isActive()) {
                return false;
            }

            $maxSpins = $wheel->getAttemptsPerUser();
            $periodUnit = $wheel->getAttemptsPeriodUnit();

            if ($maxSpins <= 0) {
                return false;
            }

            $dateRange = $this->getDateRangeForPeriod($periodUnit);
            $count = $this->analyticsProvider->getSpinCountByEmailAndWheel($email, $wheelId, $dateRange['start'], $dateRange['end']);

            return $count < $maxSpins;
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Wheel not found', ['wheel_id' => $wheelId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function hasExceededLimit(string $email, int $wheelId): bool
    {
        return !$this->canSpin($email, $wheelId);
    }

    public function getRemainingSpins(string $email, int $wheelId): int
    {
        try {
            $wheel = $this->wheelRepository->getById($wheelId);
            if (!$wheel->isActive()) {
                return 0;
            }

            $maxSpins = $wheel->getAttemptsPerUser();
            $periodUnit = $wheel->getAttemptsPeriodUnit();

            $dateRange = $this->getDateRangeForPeriod($periodUnit);
            $count = $this->analyticsProvider->getSpinCountByEmailAndWheel($email, $wheelId, $dateRange['start'], $dateRange['end']);

            return max(0, $maxSpins - $count);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Wheel not found', ['wheel_id' => $wheelId, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    private function getDateRangeForPeriod(string $periodUnit): array
    {
        $now = $this->timezone->date();

        switch (strtolower($periodUnit)) {
            case 'week':
                $start = $now->modify('monday this week')->format('Y-m-d 00:00:00');
                $end = $now->modify('sunday this week')->format('Y-m-d 23:59:59');
                break;
            case 'month':
                $start = $now->modify('first day of this month')->format('Y-m-d 00:00:00');
                $end = $now->modify('last day of this month')->format('Y-m-d 23:59:59');
                break;
            case 'day':
            default:
                $start = $now->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                break;
        }

        return ['start' => $start, 'end' => $end];
    }
}
