<?php
namespace Doroshko\SpinReward\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Wheel extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('wishreward_wheel', 'wheel_id');
    }
}
