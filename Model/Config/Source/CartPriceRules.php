<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class CartPriceRules implements OptionSourceInterface
{
    private RuleCollectionFactory $ruleCollectionFactory;

    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * @return array<int,array{value:int,label:string}>
     */
    public function toOptionArray(): array
    {
        $collection = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('is_active', 1)
            ->setOrder('rule_id', 'ASC');

        $options = [];
        foreach ($collection as $rule) {
            $options[] = [
                'value' => (int)$rule->getRuleId(),
                'label' => $rule->getName() ?: (string)$rule->getData('rule_id'),
            ];
        }
        return $options;
    }
}
