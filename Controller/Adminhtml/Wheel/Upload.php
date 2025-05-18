<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

class Upload extends Action
{
    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = [];
        try {
            $possibleFileIds = ['cta_image', 'popup_company_logo'];
            $fileId = null;

            foreach ($possibleFileIds as $id) {
                if (isset($_FILES[$id]) && !empty($_FILES[$id]['name'])) {
                    $fileId = $id;
                    break;
                }
            }

            if (!$fileId) {
                throw new LocalizedException(__('No file was uploaded.'));
            }

            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);

            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $result = $uploader->save($mediaDirectory->getAbsolutePath('wishreward/wheel'));

            if (!$result) {
                throw new LocalizedException(__('File cannot be saved to the destination folder.'));
            }

            $result['url'] = $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
                . 'wishreward/wheel' . $result['file'];
            $result['file'] = ltrim($result['file'], '/');

        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}