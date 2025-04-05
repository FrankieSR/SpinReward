<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Doroshko\WishReward\Api\Data\WheelInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Escaper;
use Psr\Log\LoggerInterface;

class CtaButton extends Template
{
    private const AJAX_URL_PATH = 'wishreward/wheel/popup';

    private WheelRepositoryInterface $wheelRepository;
    private StoreManagerInterface $storeManager;
    private CustomerSession $customerSession;
    private Escaper $escaper;
    private LoggerInterface $logger;

    /**
     * @param Template\Context $context
     * @param WheelRepositoryInterface $wheelRepository
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param Escaper $escaper
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        WheelRepositoryInterface $wheelRepository,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        Escaper $escaper,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->wheelRepository = $wheelRepository;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->escaper = $escaper;
        $this->logger = $logger;
    }

    /**
     * Retrieve eligible Wheel model for CTA display.
     *
     * @return WheelInterface|null
     */
    public function getEligibleWheel(): ?WheelInterface
    {
        try {
            return $this->wheelRepository->getEligiblePopup();
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Failed to retrieve eligible wheel: %s',
                $e->getMessage()
            ));
            return null;
        }
    }

    /**
     * Get AJAX URL for loading full popup data.
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl(self::AJAX_URL_PATH);
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
            $baseMediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
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

    /**
     * Get escaper instance.
     *
     * @return Escaper
     */
    public function getEscaper(): Escaper
    {
        return $this->escaper;
    }
}