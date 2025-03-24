<?php
namespace Doroshko\WishReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Doroshko\WishReward\Api\WheelRepositoryInterface;

class Delete extends Action
{
    protected $wheelRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        WheelRepositoryInterface $wheelRepository
    ) {
        parent::__construct($context);
        $this->wheelRepository = $wheelRepository;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('wheel_id');
        if ($id) {
            try {
                $this->wheelRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('Wheel deleted successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $this->_redirect('wishreward/wheel/index');
    }
}
