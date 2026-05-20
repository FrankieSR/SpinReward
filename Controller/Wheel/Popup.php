<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Wheel;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\LayoutFactory;
use Doroshko\SpinReward\Api\WheelRepositoryInterface;
use Doroshko\SpinReward\Model\SpinCompletionState;
use Psr\Log\LoggerInterface;

class Popup implements HttpPostActionInterface
{
    private const ERROR_WHEEL_NOT_AVAILABLE = 'Wheel is not available.';

    private JsonFactory $jsonFactory;
    private LayoutFactory $layoutFactory;
    private WheelRepositoryInterface $wheelRepository;
    private LoggerInterface $logger;
    private Context $context;
    private SpinCompletionState $spinCompletionState;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory,
        WheelRepositoryInterface $wheelRepository,
        LoggerInterface $logger,
        SpinCompletionState $spinCompletionState
    ) {
        $this->context = $context;
        $this->jsonFactory = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->wheelRepository = $wheelRepository;
        $this->logger = $logger;
        $this->spinCompletionState = $spinCompletionState;
    }

    public function execute(): Json
    {
        $resultJson = $this->jsonFactory->create();
        $wheelId = (int)$this->context->getRequest()->getParam('wheel_id', 0);

        if ($wheelId <= 0) {
            $this->logger->warning('Invalid wheel_id provided: ' . $wheelId);
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid Wheel ID provided.')
            ]);
        }

        try {
            $wheel = $this->wheelRepository->getEligibleWheelById($wheelId);
            if (!$wheel) {
                $this->logger->info('Wheel ID ' . $wheelId . ' is not eligible for current context.');
                return $resultJson->setData([
                    'success' => false,
                    'message' => __(self::ERROR_WHEEL_NOT_AVAILABLE)
                ])->setHttpResponseCode(404);
            }

            if ($this->spinCompletionState->isCompleted($wheel)) {
                return $resultJson->setData([
                    'success' => true,
                    'already_completed' => true,
                    'result' => $this->spinCompletionState->getResultState($wheel)
                ]);
            }

            $resultLayout = $this->layoutFactory->create();
            $resultLayout->addHandle('wishreward_wheel_popup');
            $layout = $resultLayout->getLayout();

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
