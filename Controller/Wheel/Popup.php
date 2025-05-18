<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Wheel;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\LayoutFactory;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Psr\Log\LoggerInterface;

class Popup implements HttpPostActionInterface
{
    private JsonFactory $jsonFactory;
    private LayoutFactory $layoutFactory;
    private WheelRepositoryInterface $wheelRepository;
    private LoggerInterface $logger;
    private Context $context;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory,
        WheelRepositoryInterface $wheelRepository,
        LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->jsonFactory = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->wheelRepository = $wheelRepository;
        $this->logger = $logger;
    }

    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();
        $wheelId = (int)$this->context->getRequest()->getParam('wheel_id', 0);

        $this->logger->debug('Popup request received with params: ' . json_encode($this->context->getRequest()->getParams()));

        if ($wheelId <= 0) {
            $this->logger->warning('Invalid wheel_id provided: ' . $wheelId);
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid Wheel ID provided.')
            ]);
        }

        try {
            $wheel = $this->wheelRepository->getById($wheelId);

            if (!$wheel->isActive()) {
                $this->logger->info('Wheel ID ' . $wheelId . ' is not active.');
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Wheel is not active.')
                ]);
            }

            $resultLayout = $this->layoutFactory->create();
            $resultLayout->addHandle('wishreward_wheel_popup');
            $layout = $resultLayout->getLayout();

            $this->logger->debug('Loaded layout blocks: ' . json_encode(array_keys($layout->getAllBlocks())));

            $block = $layout->getBlock('wishreward.wheel.popup');
            if (!$block) {
                $this->logger->error('Block "wishreward.wheel.popup" not found in layout.');
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Popup block not found.')
                ]);
            }

            $block->setData('wheel', $wheel);

            try {
                $blockHtml = $block->toHtml();
            } catch (\Exception $e) {
                $this->logger->error('Error rendering wheel_popup.phtml: ' . $e->getMessage());
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Error rendering popup template.')
                ]);
            }

            if (empty($blockHtml)) {
                $this->logger->warning('Popup HTML is empty for wheel_id: ' . $wheelId);
            }

            return $resultJson->setData([
                'success' => true,
                'html' => $blockHtml,
                'title' => $wheel->getTitle()
            ]);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Wheel not found for ID: ' . $wheelId . '. Error: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => __('The requested Wheel does not exist.')
            ]);
        } catch (LocalizedException $e) {
            $this->logger->error('Localized exception in Popup controller: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error in Popup controller: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while loading the popup.')
            ]);
        }
    }
}