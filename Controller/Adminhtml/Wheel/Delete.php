<?php
namespace Doroshko\SpinReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Doroshko\SpinReward\Api\WheelRepositoryInterface;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Doroshko_SpinReward::wheel_delete';

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
