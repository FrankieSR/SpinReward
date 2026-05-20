<?php

declare(strict_types=1);

namespace Doroshko\SpinReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Upload extends Action
{
    private const IMAGE_UPLOAD_PATH = 'wysiwyg/wishreward/';

    private JsonFactory $jsonFactory;
    private UploaderFactory $uploaderFactory;
    private Filesystem $filesystem;
    private LoggerInterface $logger;
    private UrlInterface $urlBuilder;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        LoggerInterface $logger,
        UrlInterface $urlBuilder
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $fileId = $this->getRequest()->getParam('param_name', 'cta_image');

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $uploadPath = self::IMAGE_UPLOAD_PATH;

            $result = $uploader->save($mediaDirectory->getAbsolutePath($uploadPath));
            if (!$result || empty($result['file'])) {
                throw new LocalizedException(__('File cannot be saved to the destination folder.'));
            }

            $fileUrl = $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) . $uploadPath . ltrim($result['file'], '/');

            return $resultJson->setData([
                'name' => $result['file'],
                'url' => $fileUrl,
                'size' => isset($result['size']) ? $result['size'] : null,
                'type' => mime_content_type($mediaDirectory->getAbsolutePath($uploadPath . $result['file'])),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Image upload error: ' . $e->getMessage());
            return $resultJson->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Doroshko_SpinReward::wheel_edit');
    }
}
