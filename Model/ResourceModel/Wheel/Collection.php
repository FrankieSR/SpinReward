<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\ResourceModel\Wheel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Doroshko\WishReward\Api\Data\WheelInterface;
use Doroshko\WishReward\Model\Wheel as WheelModel;
use Doroshko\WishReward\Model\ResourceModel\Wheel as WheelResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(WheelModel::class, WheelResource::class);
    }
}
