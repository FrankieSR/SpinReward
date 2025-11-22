<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\Analytics;

use Magento\Framework\Model\AbstractModel;

class SpinAnalytics extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Doroshko\WishReward\Model\ResourceModel\SpinAnalytics::class);
    }
}
