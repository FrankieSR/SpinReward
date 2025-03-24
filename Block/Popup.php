<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Block;

use Magento\Framework\View\Element\Template;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Popup extends Template
{
    /**
     * @var WheelRepositoryInterface
     */
    protected WheelRepositoryInterface $wheelRepository;
    
    public function __construct(
        Template\Context $context,
        WheelRepositoryInterface $wheelRepository,
        array $data = []
    ) {
        $this->wheelRepository = $wheelRepository;
        parent::__construct($context, $data);
    }
    
    /**
     * Retrieve eligible popup data.
     *
     * @return array|null
     */
    public function getPopupData(): ?array
    {
        try {
            $popup = $this->wheelRepository->getEligiblePopup();
            if ($popup && $popup->getId()) {
                return $popup->getData();
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return null;
    }
}
