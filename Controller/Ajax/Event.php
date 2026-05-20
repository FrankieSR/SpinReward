<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Ajax;

use Doroshko\SpinReward\Model\Analytics\EventLogger;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;

class Event implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly EventLogger $eventLogger
    ) {
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $data = $this->request->getPostValue();
        $eventType = (string)($data['event_type'] ?? '');

        if ($eventType === '') {
            return $result->setData(['success' => false, 'message' => 'Missing event type.']);
        }

        $metadata = [];
        if (!empty($data['metadata']) && is_string($data['metadata'])) {
            $decoded = json_decode($data['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $this->eventLogger->recordEvent($eventType, [
            'spin_id' => $data['spin_id'] ?? null,
            'wheel_id' => $data['wheel_id'] ?? null,
            'store_id' => $data['store_id'] ?? null,
            'website_id' => $data['website_id'] ?? null,
            'email' => $data['email'] ?? null,
            'ip_address' => $this->request->getClientIp(),
            'session_id' => $data['session_id'] ?? null,
            'sector_id' => $data['sector_id'] ?? null,
            'sector_label' => $data['sector_label'] ?? null,
            'coupon_code' => $data['coupon_code'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'order_increment_id' => $data['order_increment_id'] ?? null,
            'block_reason' => $data['block_reason'] ?? null,
            'ml_status' => $data['ml_status'] ?? null,
            'ml_score' => $data['ml_score'] ?? null,
            'ml_category' => $data['ml_category'] ?? null,
            'ml_duration_ms' => $data['ml_duration_ms'] ?? null,
            'is_win' => (bool)($data['is_win'] ?? false),
            'revenue' => $data['revenue'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? null,
            'metadata' => $metadata,
        ]);

        return $result->setData(['success' => true]);
    }
}
