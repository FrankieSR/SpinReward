<?php

declare(strict_types=1);

namespace Doroshko\WishReward\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Escaper;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Doroshko\WishReward\Api\Data\WheelInterface;
use Magento\Framework\Data\Form\FormKey;

class WheelPopup extends Template
{
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;
    private WheelRepositoryInterface $wheelRepository;
    private Escaper $escaper;
    private FormKey $formKey;

    public function __construct(
        Template\Context $context,
        WheelRepositoryInterface $wheelRepository,
        Escaper $escaper,
        FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager    = $context->getStoreManager();
        $this->logger          = $context->getLogger();
        $this->wheelRepository = $wheelRepository;
        $this->escaper = $escaper;
        $this->formKey = $formKey;
    }

    /**
     * Get eligible wheel
     *
     * @return WheelInterface|null
     */
    public function getEligibleWheel(): ?WheelInterface
    {
        try {
            return $this->wheelRepository->getEligiblePopup();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching wheel: ' . $e->getMessage());
            return null;
        }
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

    public function getEscaper(): Escaper
    {
        return $this->escaper;
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
