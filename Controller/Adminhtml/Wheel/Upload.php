<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Adminhtml\Wheel;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Upload extends Action
{
    /**
     * @var \Magento\Catalog\Model\ImageUploader
     */
    protected $imageUploader;

    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Model\ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        try {
            $result = $this->imageUploader->saveFileToTmpDir('cta_image');
            $result['url'] = $this->_getUrlForFile($result['file']);
            $result['name'] = $result['file']; // Для отображения имени файла в UI
            $result['size'] = $result['size'] ?? filesize($this->imageUploader->getBaseTmpPath() . '/' . $result['file']);
        } catch (LocalizedException $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        } catch (\Exception $e) {
            $result = [
                'error' => __('Something went wrong while uploading the file: %1', $e->getMessage()),
                'errorcode' => $e->getCode()
            ];
        }

        return $this->resultFactory
            ->create(ResultFactory::TYPE_JSON)
            ->setData($result);
    }

    /**
     * Get URL for the uploaded file
     *
     * @param string $file
     * @return string
     */
    protected function _getUrlForFile($file)
    {
        return $this->getUrl('pub/media/wishreward/wheel/tmp') . '/' . $file;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Doroshko_WishReward::wheel_edit');
    }
}