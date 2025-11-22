<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\ResourceModel\SpinAnalytics;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Doroshko\WishReward\Model\Analytics\SpinAnalytics::class,
            \Doroshko\WishReward\Model\ResourceModel\SpinAnalytics::class
        );
    }
}

