<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Api;

interface SpinAnalyticsRepositoryInterface
{
    /**
     * Save spin data
     *
     * @param array $data Array with keys: wheel_id, customer_id, email, spin_result, spin_date, ip_address,
     *                    is_guest, consent_given, utm_source, utm_medium, utm_campaign, user_agent,
     *                    device_type, session_id, is_redeemed, redeemed_at
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveSpin(array $data): void;

    /**
     * Delete spins by email
     *
     * @param string $email
     */
    public function deleteByEmail(string $email): void;
}
