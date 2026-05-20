<?php

declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Adminhtml\Wheel;

use Doroshko\SpinReward\Api\WheelRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Doroshko\SpinReward\Model\ResourceModel\Wheel\CollectionFactory;
use Psr\Log\LoggerInterface;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'Doroshko_SpinReward::wheel_delete';

    public function __construct(
        Action\Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly WheelRepositoryInterface $wheelRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deleted = 0;

            foreach ($collection as $wheel) {
                $wheelId = (int)$wheel->getId();
                if ($wheelId <= 0) {
                    continue;
                }

                $this->wheelRepository->deleteById($wheelId);
                $deleted++;
            }

            if ($deleted > 0) {
                $this->messageManager->addSuccessMessage(__('A total of %1 wheel(s) have been deleted.', $deleted));
            } else {
                $this->messageManager->addNoticeMessage(__('No wheels were deleted.'));
            }
        } catch (\Throwable $e) {
            $this->logger->error('Mass delete failed for wishreward wheels', ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('An error occurred while deleting selected wheels.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
