<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Cron;

use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Psr\Log\LoggerInterface;

class CacheWheels
{
    private WheelRepositoryInterface $wheelRepository;
    private LoggerInterface $logger;

    public function __construct(
        WheelRepositoryInterface $wheelRepository,
        LoggerInterface $logger
    ) {
        $this->wheelRepository = $wheelRepository;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            $this->wheelRepository->cacheActiveWheels();
            $this->logger->info('Active wheels cached');
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache active wheels', ['error' => $e->getMessage()]);
        }
    }
}
