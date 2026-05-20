<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\Analytics;

use Magento\Framework\Model\AbstractModel;

class SpinAnalytics extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Doroshko\SpinReward\Model\ResourceModel\SpinAnalytics::class);
    }
}
