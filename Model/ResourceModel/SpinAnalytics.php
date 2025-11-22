<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SpinAnalytics extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('wishreward_spin_analytics', 'id');
    }
}
