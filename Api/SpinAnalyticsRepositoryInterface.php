<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Api;

interface SpinAnalyticsRepositoryInterface
{
    /**
     * Save spin data
     *
     * @param array $data Array with keys: wheel_id, customer_id, email, spin_result, spin_prize_label,
     *                    coupon_code, spin_date, ip_address, is_guest, consent_given, utm_source,
     *                    utm_medium, utm_campaign, page_url, referrer_url, user_agent, device_type,
     *                    session_id, is_redeemed, redeemed_at, order_id, spin_count_session
     *                    Unknown keys are stripped.
     * @return int Inserted spin ID
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveSpin(array $data): int;

    /**
     * Update spin data by ID.
     *
     * @param int $spinId
     * @param array $data
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function updateSpin(int $spinId, array $data): void;

    /**
     * Find spin by coupon code.
     *
     * @param string $couponCode
     * @return array|null
     */
    public function findByCouponCode(string $couponCode): ?array;

    /**
     * Delete spins by email
     *
     * @param string $email
     */
    public function deleteByEmail(string $email): void;
}
