<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class CartRule implements OptionSourceInterface
{
    /**
     * @var RuleCollectionFactory
     */
    private RuleCollectionFactory $ruleCollectionFactory;

    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->ruleCollectionFactory->create();
        // If you only want active rules:
        $collection->addFieldToFilter('is_active', 1);

        foreach ($collection as $rule) {
            $options[] = [
                'value' => (string)$rule->getId(),
                'label' => $rule->getName()
            ];
        }

        return $options;
    }
}
