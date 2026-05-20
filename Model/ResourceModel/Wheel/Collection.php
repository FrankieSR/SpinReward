<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\ResourceModel\Wheel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Doroshko\SpinReward\Api\Data\WheelInterface;
use Doroshko\SpinReward\Model\Wheel as WheelModel;
use Doroshko\SpinReward\Model\ResourceModel\Wheel as WheelResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(WheelModel::class, WheelResource::class);
    }
}
