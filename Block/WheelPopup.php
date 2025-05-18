<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Doroshko\WishReward\ViewModel\WheelPopupViewModel;
use Magento\Framework\Escaper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class WheelPopup extends Template
{
    private WheelPopupViewModel $viewModel;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        Template\Context $context,
        WheelPopupViewModel $viewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->viewModel    = $viewModel;
        $this->storeManager = $context->getStoreManager();
        $this->logger       = $context->getLogger();
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
            $this->logger->warning('Wheel data not set in WheelPopup block.');
        }
        return parent::_beforeToHtml();
    }

    /**
     * Build full media URL from file path.
     *
     * @param string $filePath Relative path to the media file
     * @return string Full URL to the media file
     */
    public function getMediaUrl(string $filePath): string
    {
        try {
            $baseMediaUrl = $this->storeManager
                ->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $baseMediaUrl . ltrim($filePath, '/');
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(sprintf(
                'Store not found while generating media URL for path "%s": %s',
                $filePath,
                $e->getMessage()
            ));
            return '';
        }
    }
}
