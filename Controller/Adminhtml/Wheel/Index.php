<?php
namespace Doroshko\SpinReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Doroshko_SpinReward::wheels';

    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory
    ){
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(){
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Doroshko_SpinReward::wheels');
        $resultPage->getConfig()->getTitle()->prepend(__('Spin Reward Wheels'));
        return $resultPage;
    }
}
