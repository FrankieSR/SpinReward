<?php

declare(strict_types=1);

namespace Doroshko\SpinReward\Model;

use Doroshko\SpinReward\Api\Data\WheelInterface;
use Doroshko\SpinReward\Api\SpinAnalyticsProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class SpinCompletionState
{
    private const COOKIE_PREFIX = 'wishreward_spin_completed_';
    private const RESULT_COOKIE_PREFIX = 'wishreward_spin_result_';
    private const COOKIE_VALUE = '1';

    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly SessionManagerInterface $sessionManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerSession $customerSession,
        private readonly SpinAnalyticsProviderInterface $analyticsProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    public function isCompleted(WheelInterface $wheel): bool
    {
        $wheelId = (int)$wheel->getWheelId();
        if ($wheelId <= 0) {
            return false;
        }

        try {
            if ($this->customerSession->isLoggedIn()) {
                $customerId = (int)$this->customerSession->getCustomerId();
                if ($customerId > 0 && $this->hasCustomerSpin($customerId, $wheel)) {
                    return true;
                }

                $customerEmail = trim((string)$this->customerSession->getCustomer()->getEmail());
                if ($customerEmail !== '' && $this->hasEmailSpin($customerEmail, $wheel)) {
                    return true;
                }
            }

            return $this->hasCompletionCookie($wheelId);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to resolve Spin Reward completion state', [
                'wheel_id' => $wheelId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function markCompleted(WheelInterface $wheel, array $resultState = []): void
    {
        $wheelId = (int)$wheel->getWheelId();
        if ($wheelId <= 0) {
            return;
        }

        try {
            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setDuration($this->getCompletionDurationSeconds((string)$wheel->getAttemptsPeriodUnit()))
                ->setPath($this->sessionManager->getCookiePath() ?: '/')
                ->setHttpOnly(false);

            $cookieDomain = (string)$this->sessionManager->getCookieDomain();
            if ($cookieDomain !== '') {
                $metadata->setDomain($cookieDomain);
            }

            $metadata->setSecure((bool)$this->storeManager->getStore()->isCurrentlySecure());

            $this->cookieManager->setPublicCookie($this->getCookieName($wheelId), self::COOKIE_VALUE, $metadata);

            if (!empty($resultState)) {
                $this->setResultState($wheel, $resultState, $metadata);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to set Spin Reward completion cookie', [
                'wheel_id' => $wheelId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getCookieName(int $wheelId): string
    {
        return self::COOKIE_PREFIX . $this->getScopeKey() . '_' . $wheelId;
    }

    public function getCompletionKey(int $wheelId): string
    {
        return $this->getCookieName($wheelId);
    }

    public function getResultKey(int $wheelId): string
    {
        return $this->getResultCookieName($wheelId);
    }

    public function getResultState(WheelInterface $wheel): array
    {
        $wheelId = (int)$wheel->getWheelId();
        if ($wheelId <= 0) {
            return [];
        }

        try {
            $raw = trim((string)$this->cookieManager->getCookie($this->getResultCookieName($wheelId)));
            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return [];
            }

            return [
                'result' => isset($decoded['result']) ? (string)$decoded['result'] : '',
                'coupon_code' => isset($decoded['coupon_code']) ? (string)$decoded['coupon_code'] : '',
                'message' => isset($decoded['message']) ? (string)$decoded['message'] : '',
                'sector_label' => isset($decoded['sector_label']) ? (string)$decoded['sector_label'] : '',
                'completed_at' => isset($decoded['completed_at']) ? (string)$decoded['completed_at'] : '',
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to read Spin Reward result state', [
                'wheel_id' => $wheelId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function hasCompletionCookie(int $wheelId): bool
    {
        return trim((string)$this->cookieManager->getCookie($this->getCookieName($wheelId))) === self::COOKIE_VALUE;
    }

    private function setResultState(WheelInterface $wheel, array $resultState, $metadata): void
    {
        $wheelId = (int)$wheel->getWheelId();
        if ($wheelId <= 0) {
            return;
        }

        $payload = [
            'result' => isset($resultState['result']) ? (string)$resultState['result'] : '',
            'coupon_code' => isset($resultState['coupon_code']) ? (string)$resultState['coupon_code'] : '',
            'message' => isset($resultState['message']) ? (string)$resultState['message'] : '',
            'sector_label' => isset($resultState['sector_label']) ? (string)$resultState['sector_label'] : '',
            'spin_result' => isset($resultState['spin_result']) ? (string)$resultState['spin_result'] : '',
            'completed_at' => gmdate('Y-m-d H:i:s'),
        ];

        $this->cookieManager->setPublicCookie(
            $this->getResultCookieName($wheelId),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $metadata
        );
    }

    private function getResultCookieName(int $wheelId): string
    {
        return self::RESULT_COOKIE_PREFIX . $this->getScopeKey() . '_' . $wheelId;
    }

    private function hasCustomerSpin(int $customerId, WheelInterface $wheel): bool
    {
        $dateRange = $this->getPeriodRange((string)$wheel->getAttemptsPeriodUnit());

        return $this->analyticsProvider->getSpinCountByCustomerAndWheel(
            $customerId,
            (int)$wheel->getWheelId(),
            $dateRange['start'],
            $dateRange['end']
        ) > 0;
    }

    private function hasEmailSpin(string $email, WheelInterface $wheel): bool
    {
        $dateRange = $this->getPeriodRange((string)$wheel->getAttemptsPeriodUnit());

        return $this->analyticsProvider->getSpinCountByEmailAndWheel(
            $email,
            (int)$wheel->getWheelId(),
            $dateRange['start'],
            $dateRange['end']
        ) > 0;
    }

    private function getCompletionDurationSeconds(string $periodUnit): int
    {
        $range = $this->getPeriodRange($periodUnit);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable($range['end'], new \DateTimeZone('UTC'));

        return max(60, $end->getTimestamp() - $now->getTimestamp());
    }

    private function getPeriodRange(string $periodUnit): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return match (strtolower(trim($periodUnit)) ?: 'day') {
            'week' => [
                'start' => $now->modify('monday this week')->format('Y-m-d 00:00:00'),
                'end' => $now->modify('sunday this week')->format('Y-m-d 23:59:59'),
            ],
            'month' => [
                'start' => $now->modify('first day of this month')->format('Y-m-d 00:00:00'),
                'end' => $now->modify('last day of this month')->format('Y-m-d 23:59:59'),
            ],
            'year' => [
                'start' => $now->modify('first day of january this year')->format('Y-m-d 00:00:00'),
                'end' => $now->modify('last day of december this year')->format('Y-m-d 23:59:59'),
            ],
            'forever' => [
                'start' => '1970-01-01 00:00:00',
                'end' => $now->modify('+10 years')->format('Y-m-d H:i:s'),
            ],
            default => [
                'start' => $now->format('Y-m-d 00:00:00'),
                'end' => $now->format('Y-m-d 23:59:59'),
            ],
        };
    }

    private function getScopeKey(): string
    {
        try {
            $websiteId = (int)$this->storeManager->getStore()->getWebsiteId();
            if ($websiteId > 0) {
                return 'website_' . $websiteId;
            }
        } catch (\Throwable $e) {
            $this->logger->debug('Failed to resolve Spin Reward scope key', [
                'error' => $e->getMessage(),
            ]);
        }

        return 'website_default';
    }
}
