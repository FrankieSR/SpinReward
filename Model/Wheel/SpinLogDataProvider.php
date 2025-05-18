<?php
namespace Doroshko\WishReward\Model\Wheel;

use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Doroshko\WishReward\Model\ResourceModel\SpinLog\CollectionFactory;

class SpinLogDataProvider extends AbstractDataProvider
{
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    public function getData()
    {
        return $this->collection->toArray();
    }

    public function addFilter(Filter $filter)
    {
        $this->collection->addFieldToFilter(
            $filter->getField(),
            [$filter->getConditionType() => $filter->getValue()]
        );
    }
}