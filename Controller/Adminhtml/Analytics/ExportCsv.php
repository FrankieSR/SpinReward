<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Adminhtml\Analytics;

use Doroshko\SpinReward\Model\Analytics\ReportService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;

class ExportCsv extends Action
{
    public const ADMIN_RESOURCE = 'Doroshko_SpinReward::analytics';

    public function __construct(
        Context $context,
        private readonly RequestInterface $request,
        private readonly ReportService $reportService,
        private readonly RawFactory $rawFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $csv = $this->reportService->exportLatestSpinsCsv($this->extractFilters());
        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'text/csv; charset=UTF-8', true);
        $result->setHeader('Content-Disposition', 'attachment; filename="wishreward-latest-spins.csv"', true);
        $result->setContents($csv);

        return $result;
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
