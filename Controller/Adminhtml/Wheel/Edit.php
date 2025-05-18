<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultFactory;
use Doroshko\WishReward\Model\WheelFactory;

/**
 * Controller for editing a Wheel record without using Registry.
 */
class Edit extends Action
{
    /**
     * @var WheelFactory
     */
    protected $wheelFactory;

    /**
     * @param Action\Context $context
     * @param WheelFactory   $wheelFactory
     */
    public function __construct(
        Action\Context $context,
        WheelFactory $wheelFactory
    ) {
        parent::__construct($context);
        $this->wheelFactory = $wheelFactory;
    }

    /**
     * Execute the edit form.
     *
     * Checks if the requested wheel_id exists. If not, redirects to the index.
     * Otherwise, renders the page. Data loading for the UI form is handled by
     * the DataProvider (no need for registry).
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('wheel_id');

        if ($id) {
            $model = $this->wheelFactory->create()->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Wheel no longer exists.'));
                // Редиректим через resultRedirectFactory
                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        }

        // Создаём PageResult
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Wheel'));

        return $resultPage;
    }
}
