<?php

declare(strict_types=1);

namespace Doroshko\WishReward\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Doroshko\WishReward\Api\SpinLimitValidatorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Doroshko\WishReward\Api\Data\WheelInterface;

class InitViewModel implements ArgumentInterface
{
    private ?WheelInterface $wheel = null;
    private SpinLimitValidatorInterface $spinLimitValidator;
    private StoreManagerInterface $storeManager;
    private CustomerSession $customerSession;
    private UrlInterface $urlBuilder;
    private LoggerInterface $logger;

    public function __construct(
        SpinLimitValidatorInterface $spinLimitValidator,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
    ) {
        $this->spinLimitValidator = $spinLimitValidator;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    public function setWheel(WheelInterface $wheel): void
    {
        $this->wheel = $wheel;
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

        return [
            'isScrollEnabled'  => $this->wheel->getIsScrollEnabled(),
            'scrollPercentage' => (int) $this->wheel->getScrollPercentage(),
            'isTimeoutEnabled' => $this->wheel->getIsTimeoutEnabled(),
            'timeoutDuration'  => (int) $this->wheel->getTimeoutDuration(),
            'isExitEnabled'    => $this->wheel->getIsExitEnabled(),
            'attemptsPerUser'  => (int) $this->wheel->getAttemptsPerUser(),
            'isCtaEnabled'     => $this->wheel->getIsCtaEnabled(),
        ];
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
