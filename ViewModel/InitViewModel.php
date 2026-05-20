<?php

declare(strict_types=1);

namespace Doroshko\SpinReward\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Doroshko\SpinReward\Api\SpinLimitValidatorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Doroshko\SpinReward\Api\Data\WheelInterface;
use Doroshko\SpinReward\Model\SpinCompletionState;

class InitViewModel implements ArgumentInterface
{
    private ?WheelInterface $wheel = null;
    private ?bool $spinCompleted = null;
    private SpinLimitValidatorInterface $spinLimitValidator;
    private StoreManagerInterface $storeManager;
    private CustomerSession $customerSession;
    private UrlInterface $urlBuilder;
    private LoggerInterface $logger;
    private SpinCompletionState $spinCompletionState;

    public function __construct(
        SpinLimitValidatorInterface $spinLimitValidator,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        SpinCompletionState $spinCompletionState,
    ) {
        $this->spinLimitValidator = $spinLimitValidator;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->spinCompletionState = $spinCompletionState;
    }

    public function setWheel(WheelInterface $wheel): void
    {
        $this->wheel = $wheel;
        $this->spinCompleted = null;
    }

    public function getWheel(): ?WheelInterface
    {
        return $this->wheel;
    }

    public function canSpin(): bool
    {
        try {
            if ($this->wheel === null || !$this->wheel->getWheelId()) {
                $this->logger->error('Cannot check canSpin: Wheel is not set or has no ID');
                return false;
            }

            $customer = $this->customerSession->getCustomer();
            $email = $customer ? $customer->getEmail() : null;

            if (!$email) {
                return false;
            }

            return $this->spinLimitValidator->canSpin($email, (int)$this->wheel->getWheelId());

        } catch (\Throwable $e) {
            $this->logger->error('Error checking canSpin: ' . $e->getMessage(), [
                'wheel_id' => $this->wheel ? $this->wheel->getWheelId() : null,
                'email'    => $email ?? null,
            ]);

            return false;
        }
    }

    public function getTriggerConfig(): array
    {
        if ($this->wheel === null) {
            return [];
        }

        $isTimeoutEnabled = (bool)$this->wheel->getIsTimeoutEnabled();
        $timeoutDuration = max(0, (int)$this->wheel->getTimeoutDuration());

        return [
            'isScrollEnabled' => (bool)$this->wheel->getIsScrollEnabled(),
            'scrollPercentage' => (int)$this->wheel->getScrollPercentage(),
            'isTimeoutEnabled' => $isTimeoutEnabled,
            'timeoutDuration' => $isTimeoutEnabled ? $timeoutDuration : 0,
            'isExitEnabled' => (bool)$this->wheel->getIsExitEnabled(),
            'attemptsPerUser' => (int)$this->wheel->getAttemptsPerUser(),
            'isCtaEnabled' => (bool)$this->wheel->getIsCtaEnabled(),
            'isSpinCompleted' => $this->isSpinCompleted(),
        ];
    }

    public function shouldRenderTrigger(): bool
    {
        return $this->wheel !== null && !$this->isSpinCompleted();
    }

    public function isSpinCompleted(): bool
    {
        if ($this->spinCompleted !== null) {
            return $this->spinCompleted;
        }

        if ($this->wheel === null || !$this->wheel->getWheelId()) {
            return $this->spinCompleted = false;
        }

        try {
            return $this->spinCompleted = $this->spinCompletionState->isCompleted($this->wheel);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to resolve trigger suppression state', [
                'wheel_id' => $this->wheel->getWheelId(),
                'error' => $e->getMessage(),
            ]);

            return $this->spinCompleted = false;
        }
    }

    public function getAjaxUrl(): string
    {
        return $this->urlBuilder->getUrl('wishreward/wheel/popup');
    }

    public function getCtaPosition(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaPosition() : null;
    }

    public function getWheelId(): ?string
    {
        return $this->wheel ? (string)$this->wheel->getWheelId() : null;
    }

    public function getCompletionKey(): ?string
    {
        if (!$this->wheel || !$this->wheel->getWheelId()) {
            return null;
        }

        return $this->spinCompletionState->getCompletionKey((int)$this->wheel->getWheelId());
    }

    public function getResultKey(): ?string
    {
        if (!$this->wheel || !$this->wheel->getWheelId()) {
            return null;
        }

        return $this->spinCompletionState->getResultKey((int)$this->wheel->getWheelId());
    }

    public function getCompletionBannerState(): array
    {
        if ($this->wheel === null || !$this->wheel->getWheelId()) {
            return [];
        }

        return [
            'completed' => $this->isSpinCompleted(),
            'result' => $this->spinCompletionState->getResultState($this->wheel),
        ];
    }

    public function getPopupTheme(): string
    {
        return $this->wheel->getPopupTheme();
    }

    
    public function getIsCtaEnabled(): bool
    {
        return $this->wheel->getIsCtaEnabled();
    }

    public function getCtaImage(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaImage() : null;
    }

    public function getCtaLabel(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaLabel() : null;
    }

    public function getCtaButtonText(): ?string
    {
        return $this->wheel ? $this->wheel->getCtaButtonText() : null;
    }
}
