<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\ResourceModel\SpinAnalytics;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Doroshko\SpinReward\Model\Analytics\SpinAnalytics::class,
            \Doroshko\SpinReward\Model\ResourceModel\SpinAnalytics::class
        );
    }
}

