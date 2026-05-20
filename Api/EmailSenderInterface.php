<?php

declare(strict_types=1);

namespace Doroshko\SpinReward\Api;

interface EmailSenderInterface
{
    /**
     * Send winning coupon email
     *
     * @param string $email
     * @param string|null $customerName
     * @param string $couponCode
     * @param string $rewardDescription
     * @param string $promotionName
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendWinningCouponEmail(
        string $email,
        ?string $customerName,
        string $couponCode,
        string $rewardDescription,
        string $promotionName
    ): void;
}
