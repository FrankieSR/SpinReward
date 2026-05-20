<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\Wheel;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Doroshko\SpinReward\Model\ResourceModel\SpinAnalytics\CollectionFactory;
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
            $fullCollection = clone $collection;
            $fullCollection->getSelect()->reset(\Zend_Db_Select::LIMIT_COUNT);
            $fullCollection->getSelect()->reset(\Zend_Db_Select::LIMIT_OFFSET);
            $fullCollection->clear();

            $sameEmailWheelCounts = [];
            foreach ($fullCollection as $item) {
                $data = $item->getData();
                $spinStatus = (string)($data['spin_status'] ?? '');
                if ($spinStatus !== '' && $spinStatus !== 'completed') {
                    continue;
                }

                $email = strtolower(trim((string)($data['email'] ?? '')));
                $wheelId = (int)($data['wheel_id'] ?? 0);
                $key = $email . '|' . $wheelId;
                $sameEmailWheelCounts[$key] = ($sameEmailWheelCounts[$key] ?? 0) + 1;
            }

            $items = [];
            foreach ($collection as $item) {
                $data = $item->getData();
                $email = strtolower(trim((string)($data['email'] ?? '')));
                $wheelId = (int)($data['wheel_id'] ?? 0);
                $key = $email . '|' . $wheelId;

                $data['email_wheel_spin_count'] = $sameEmailWheelCounts[$key] ?? 1;
                $items[] = $data;
            }

            $result = [
                'totalRecords' => (int)$collection->getSize(),
                'items' => $items
            ];

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('SpinAnalyticsDataProvider error:', ['exception' => $e->getMessage()]);
            throw $e;
        }
    }

    public function addFilter(Filter $filter)
    {
        $field = (string)$filter->getField();
        $condition = $filter->getConditionType() ?: 'eq';
        $value = $filter->getValue();

        if ($value === null || $value === '') {
            return;
        }

        if (is_array($value)) {
            if (!empty($value['from'])) {
                $this->collection->addFieldToFilter($field, ['gteq' => $value['from']]);
            }

            if (!empty($value['to'])) {
                $this->collection->addFieldToFilter($field, ['lteq' => $value['to']]);
            }

            return;
        }

        if ($condition === 'like') {
            $value = '%' . $value . '%';
        }

        $this->collection->addFieldToFilter($field, [$condition => $value]);
    }

    public function addOrder($field, $direction)
    {
        $this->collection->setOrder((string)$field, (string)$direction);
    }

    public function setLimit($offset, $size)
    {
        $this->collection->setPageSize((int)$size);
        $this->collection->setCurPage((int)floor(((int)$offset / max(1, (int)$size)) + 1));
    }
}
