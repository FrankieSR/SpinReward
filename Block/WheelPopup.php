<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Block;

use Magento\Framework\View\Element\Template;
use Doroshko\WishReward\ViewModel\WheelPopupViewModel;
use Magento\Framework\Escaper;

class WheelPopup extends Template
{
    private WheelPopupViewModel $viewModel;

    public function __construct(
        Template\Context $context,
        WheelPopupViewModel $viewModel,
        array $data = []
    ) {
        $this->viewModel = $viewModel;
        parent::__construct($context, $data);
    }

    /**
     * Set Wheel object
     *
     * @param \Doroshko\WishReward\Api\Data\WheelInterface $wheel
     * @return $this
     */
    public function setWheel(\Doroshko\WishReward\Api\Data\WheelInterface $wheel): self
    {
        $this->viewModel->setWheel($wheel);
        return $this;
    }

    /**
     * Get ViewModel
     *
     * @return WheelPopupViewModel
     */
    public function getViewModel(): WheelPopupViewModel
    {
        return $this->viewModel;
    }

    /**
     * Get Escaper instance
     *
     * @return Escaper
     */
    public function getEscaper(): Escaper
    {
        return $this->_escaper;
    }

    /**
     * Prepare data before rendering
     *
     * @return $this
     */
    protected function _beforeToHtml(): self
    {
        $wheel = $this->getData('wheel');
        if ($wheel instanceof \Doroshko\WishReward\Api\Data\WheelInterface) {
            $this->viewModel->setWheel($wheel);
        } else {
            $this->_logger->warning('Wheel data not set in WheelPopup block.');
        }
        return parent::_beforeToHtml();
    }
}