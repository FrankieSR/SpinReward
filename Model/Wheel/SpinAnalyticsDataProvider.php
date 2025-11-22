<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\Wheel;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Doroshko\WishReward\Model\ResourceModel\SpinAnalytics\CollectionFactory;
use Psr\Log\LoggerInterface;

class SpinAnalyticsDataProvider extends AbstractDataProvider
{
    private RequestInterface $request;
    private LoggerInterface $logger;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        $this->logger = $logger;
    }

    public function getData()
    {
        try {
            $collection = $this->getCollection();

            $items = [];
            foreach ($collection as $item) {
                $items[] = $item->getData();
            }

            $result = [
                'totalRecords' => $collection->getSize(),
                'items' => $items
            ];

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('SpinAnalyticsDataProvider error:', ['exception' => $e->getMessage()]);
            throw $e;
        }
    }
}