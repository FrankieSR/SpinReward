<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\ResourceModel\SpinLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Doroshko\WishReward\Model\SpinLog::class,
            \Doroshko\WishReward\Model\ResourceModel\SpinLog::class
        );
    }
}
