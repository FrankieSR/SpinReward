<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Doroshko\WishReward\Api\Data\WheelInterfaceFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends Action
{
    /**
     * @var WheelRepositoryInterface
     */
    protected $wheelRepository;

    /**
     * @var WheelInterfaceFactory
     */
    protected $wheelFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(
        Action\Context $context,
        WheelRepositoryInterface $wheelRepository,
        WheelInterfaceFactory $wheelFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->wheelRepository = $wheelRepository;
        $this->wheelFactory    = $wheelFactory;
        $this->filesystem      = $filesystem;
    }

    public function execute(): ResultInterface
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->messageManager->addErrorMessage(__('No data to save.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        try {
            $id = $data['wheel_id'] ?? null;
            if ($id) {
                $wheel = $this->wheelRepository->getById((int)$id);
            } else {
                $wheel = $this->wheelFactory->create();
            }

            // General settings
            $wheel->setTitle((string)($data['title'] ?? ''));
            $wheel->setIsActive((bool)($data['is_active'] ?? false));
            $wheel->setWinMessage((string)($data['win_message'] ?? ''));
            $wheel->setNoWinMessage((string)($data['no_win_message'] ?? ''));
            $wheel->setStartDate($data['start_date'] ?? null);
            $wheel->setEndDate($data['end_date'] ?? null);

            if (!empty($data['storeviews']) && is_array($data['storeviews'])) {
                $wheel->setStoreviews(implode(',', $data['storeviews']));
            } else {
                $wheel->setStoreviews('0');
            }

            if (!empty($data['allowed_customer_groups']) && is_array($data['allowed_customer_groups'])) {
                $wheel->setAllowedCustomerGroups(implode(',', $data['allowed_customer_groups']));
            } else {
                $wheel->setAllowedCustomerGroups('');
            }

            // Wheel Configuration: if array, encode as JSON; if string, use as is.
            if (!empty($data['wheel_config'])) {
                if (is_array($data['wheel_config'])) {
                    $wheel->setWheelConfig(json_encode($data['wheel_config']));
                } elseif (is_string($data['wheel_config'])) {
                    $wheel->setWheelConfig($data['wheel_config']);
                }
            } else {
                $wheel->setWheelConfig('[]');
            }

            // Display Configuration
            if (!empty($data['display_on_pages']) && is_array($data['display_on_pages'])) {
                $wheel->setDisplayOnPages(implode(',', $data['display_on_pages']));
            } else {
                $wheel->setDisplayOnPages('');
            }

            // CTA settings
            $wheel->setIsCtaEnabled((bool)($data['is_cta_enabled'] ?? false));
            $wheel->setCtaLabel((string)($data['cta_label'] ?? ''));
            $wheel->setCtaButtonText((string)($data['cta_button_text'] ?? ''));
            $wheel->setCtaPosition((string)($data['cta_position'] ?? 'bottom-right'));
            $wheel->setCtaCustomCss((string)($data['cta_custom_css'] ?? ''));

            // Process CTA Image upload
            if (!empty($data['cta_image']) && is_array($data['cta_image']) && !empty($data['cta_image'][0]['file'])) {
                $imageFile = $data['cta_image'][0]['file'];
                if (strpos($imageFile, 'tmp') !== false) {
                    $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                    $tmpPath = 'catalog/tmp/wishreward/wheel/' . $imageFile;
                    $newPath = 'wishreward/wheel/cta/' . $imageFile;
                    if ($mediaDir->isFile($tmpPath)) {
                        $mediaDir->renameFile($tmpPath, $newPath);
                    }
                    $imageFile = $newPath;
                }
                $wheel->setCtaImage($imageFile);
            } elseif (empty($data['cta_image']) && $id) {
                // Retain existing image if editing
            } else {
                $wheel->setCtaImage(null);
            }

            // Popup settings: Added popup_title saving and others.
            $wheel->setPopupTitle((string)($data['popup_title'] ?? ''));
            $wheel->setPopupDescription((string)($data['popup_description'] ?? ''));
            $wheel->setIsWishAreaEnabled((bool)($data['is_wish_area_enabled'] ?? false));
            $wheel->setIsEmailInputEnabled((bool)($data['is_email_input_enabled'] ?? false));

            $this->wheelRepository->save($wheel);
            $this->messageManager->addSuccessMessage(__('Wheel saved successfully.'));

            if (isset($data['back']) && $data['back'] === 'edit') {
                return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['wheel_id' => $wheel->getId()]);
            }
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the Wheel: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
