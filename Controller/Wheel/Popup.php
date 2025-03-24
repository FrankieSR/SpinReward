<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Wheel;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Doroshko\WishReward\Api\WheelRepositoryInterface;

class Popup extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    protected $jsonFactory;
    protected $pageFactory;
    protected $wheelRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        JsonFactory $jsonFactory,
        PageFactory $pageFactory,
        WheelRepositoryInterface $wheelRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->pageFactory = $pageFactory;
        $this->wheelRepository = $wheelRepository;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $wheelId = (int)$this->getRequest()->getParam('wheel_id');

        try {
            $wheel = $this->wheelRepository->getById($wheelId);
            if (!$wheel->isActive()) {
                return $result->setData(['success' => false, 'message' => 'Wheel is not active']);
            }

            $block = $this->pageFactory->create()->getLayout()
                ->createBlock(\Doroshko\WishReward\Block\WheelPopup::class)
                ->setWheel($wheel)
                ->toHtml();

            return $result->setData([
                'success' => true,
                'html' => $block,
                'title' => $wheel->getTitle()
            ]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}