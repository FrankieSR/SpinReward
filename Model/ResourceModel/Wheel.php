<?php
namespace Doroshko\WishReward\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Wheel extends AbstractDb
{
    protected function _construct()
    {
        // Указываем имя таблицы и поле первичного ключа
        $this->_init('wishreward_wheel', 'wheel_id');
    }
}
