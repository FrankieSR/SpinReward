<?php

declare(strict_types=1);

namespace Doroshko\SpinReward\Service;

use Psr\Log\LoggerInterface;

/**
 * Helper class for calculating winning sectors based on weighted probabilities.
 */
class ProbabilityCalculator
{
    /** Key for probability field in sector data */
    private const PROBABILITY_KEY = 'probability';
    private const PROBABILITY_SCALE = 100;

    private LoggerInterface $logger;

    /**
     * ProbabilityCalculator constructor.
     *
     * @param LoggerInterface $logger Logger for debugging and error handling
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Calculates the winning sector based on weighted probabilities.
     *
     * Accepts an array of sectors, each containing a probability field.
     * Generates a random number and selects the sector where the random number
     * falls within the cumulative probability range.
     *
     * Example sector structure:
     * [
     *     ['id' => 1, 'label' => 'Prize 1', 'probability' => 30],
     *     ['id' => 2, 'label' => 'Prize 2', 'probability' => 50],
     *     ['id' => 3, 'label' => 'Prize 3', 'probability' => 20]
     * ]
     *
     * @param array<int, array<string, mixed>> $sectors Array of sectors, each with a probability field
     * @return array<string, mixed> The winning sector
     * @throws \InvalidArgumentException If sectors array is empty or probabilities are invalid
     */
    public function getWinningSector(array $sectors): array
    {
        if (empty($sectors)) {
            $this->logger->error('Sectors array is empty in ProbabilityCalculator');
            throw new \InvalidArgumentException('Sectors array cannot be empty.');
        }

        $totalProbability = 0;
        $bucketedProbabilities = [];
        foreach ($sectors as $index => $sector) {
            if (!isset($sector[self::PROBABILITY_KEY]) || !is_numeric($sector[self::PROBABILITY_KEY])) {
                $this->logger->error('Invalid or missing probability in sector', [
                    'index' => $index,
                    'sector' => $sector
                ]);
                throw new \InvalidArgumentException('Each sector must have a valid numeric probability.');
            }
            $probability = (float)$sector[self::PROBABILITY_KEY];
            if ($probability < 0) {
                $this->logger->error('Negative probability in sector', [
                    'index' => $index,
                    'probability' => $probability
                ]);
                throw new \InvalidArgumentException('Probabilities must be non-negative.');
            }
            $bucketedProbability = (int)round($probability * self::PROBABILITY_SCALE);
            $bucketedProbabilities[$index] = $bucketedProbability;
            $totalProbability += $bucketedProbability;
        }

        if ($totalProbability <= 0) {
            $this->logger->error('Total probability is zero or negative', [
                'total_probability' => $totalProbability
            ]);
            throw new \InvalidArgumentException('Total probability must be greater than zero.');
        }

        $random = random_int(1, (int)$totalProbability);

        $currentSum = 0;

        foreach ($sectors as $index => $sector) {
            $currentSum += $bucketedProbabilities[$index] ?? 0;

            if ($random <= $currentSum) {
                return $sector;
            }
        }

        $lastSector = end($sectors);

        $this->logger->warning('Fallback to last sector in ProbabilityCalculator', [
            'sector_id' => $lastSector['id'] ?? null,
            'probability' => $lastSector[self::PROBABILITY_KEY]
        ]);
        return $lastSector;
    }
}
