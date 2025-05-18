<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model;

use Magento\Framework\Model\AbstractModel;

class SpinLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Doroshko\WishReward\Model\ResourceModel\SpinLog::class);
    }
}
