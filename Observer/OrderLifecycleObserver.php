<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Observer;

use Doroshko\SpinReward\Api\SpinAnalyticsRepositoryInterface;
use Doroshko\SpinReward\Model\Analytics\EventLogger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderLifecycleObserver implements ObserverInterface
{
    public function __construct(
        private readonly SpinAnalyticsRepositoryInterface $spinAnalyticsRepository,
        private readonly EventLogger $eventLogger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getId()) {
            return;
        }

        $couponCode = trim((string)$order->getCouponCode());
        if ($couponCode === '') {
            return;
        }

        $spin = $this->spinAnalyticsRepository->findByCouponCode($couponCode);
        if (!$spin) {
            return;
        }

        $orderStatus = (string)$order->getStatus();
        $eventType = $observer->getEvent()->getName() === 'sales_order_place_after' ? 'order_placed' : null;

        if ($observer->getEvent()->getName() === 'sales_order_save_after') {
            $originalStatus = (string)($order->getOrigData('status') ?? '');
            if ($originalStatus === $orderStatus) {
                return;
            }

            if (in_array($orderStatus, ['canceled', 'closed'], true)) {
                $eventType = 'order_canceled';
            } elseif ($orderStatus === 'refunded') {
                $eventType = 'order_refunded';
            }
        }

        $update = [
            'order_id' => (int)$order->getId(),
            'order_increment_id' => (string)$order->getIncrementId(),
            'order_status' => $orderStatus,
            'base_subtotal' => (float)$order->getBaseSubtotal(),
            'base_discount_amount' => abs((float)$order->getBaseDiscountAmount()),
            'base_grand_total' => (float)$order->getBaseGrandTotal(),
            'coupon_applied_at' => gmdate('Y-m-d H:i:s'),
            'is_redeemed' => 1,
            'redeemed_at' => gmdate('Y-m-d H:i:s'),
        ];

        $this->spinAnalyticsRepository->updateSpin((int)$spin['id'], $update);

        if ($observer->getEvent()->getName() === 'sales_order_place_after') {
            $this->eventLogger->recordEvent('coupon_applied', [
                'spin_id' => (int)$spin['id'],
                'wheel_id' => (int)$spin['wheel_id'],
                'store_id' => (int)($spin['store_id'] ?? 0),
                'website_id' => (int)($spin['website_id'] ?? 0),
                'order_id' => (int)$order->getId(),
                'order_increment_id' => (string)$order->getIncrementId(),
                'coupon_code' => $couponCode,
                'revenue' => (float)$order->getBaseGrandTotal(),
                'discount_amount' => abs((float)$order->getBaseDiscountAmount()),
            ]);
        }

        if ($eventType !== null) {
            $this->eventLogger->recordEvent($eventType, [
                'spin_id' => (int)$spin['id'],
                'wheel_id' => (int)$spin['wheel_id'],
                'store_id' => (int)($spin['store_id'] ?? 0),
                'website_id' => (int)($spin['website_id'] ?? 0),
                'order_id' => (int)$order->getId(),
                'order_increment_id' => (string)$order->getIncrementId(),
                'coupon_code' => $couponCode,
                'revenue' => (float)$order->getBaseGrandTotal(),
                'discount_amount' => abs((float)$order->getBaseDiscountAmount()),
                'metadata' => [
                    'status' => $orderStatus,
                    'state' => (string)$order->getState(),
                ],
            ]);
        }
    }
}
