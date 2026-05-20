<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Adminhtml\Analytics;

use Doroshko\SpinReward\Model\Analytics\ReportService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;

class Payload extends Action
{
    public const ADMIN_RESOURCE = 'Doroshko_SpinReward::analytics';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly ReportService $reportService
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $filters = $this->extractFilters();

        return $result->setData($this->reportService->getDashboardPayload($filters));
    }

    private function extractFilters(): array
    {
        $filters = [];

        foreach (['wheel_id', 'store_id', 'website_id', 'email', 'spin_result', 'spin_status', 'block_reason', 'ml_status', 'device_type', 'utm_source', 'sector_id'] as $field) {
            $value = $this->request->getParam($field);
            if ($value !== null && $value !== '') {
                $filters[$field] = $value;
            }
        }

        $createdAt = $this->request->getParam('created_at');
        if (is_array($createdAt)) {
            $filters['created_at'] = [
                'from' => $createdAt['from'] ?? null,
                'to' => $createdAt['to'] ?? null,
            ];
        }

        return $filters;
    }
}
