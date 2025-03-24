<?php

declare(strict_types=1);

namespace Doroshko\WishReward\Model\Wheel;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Doroshko\WishReward\Model\ResourceModel\Wheel\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class FormDataProvider extends AbstractDataProvider
{
    protected array $loadedData = [];
    protected RequestInterface $request;
    protected LoggerInterface $logger;
    protected Filesystem $filesystem;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        LoggerInterface $logger,
        Filesystem $filesystem,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $wheelId = (int)$this->request->getParam($this->requestFieldName);

        if ($wheelId) {
            $wheel = $this->collection->getItemById($wheelId);
            if ($wheel && $wheel->getId()) {
                $data = $wheel->getData();

                // Преобразование строк с запятыми в массивы для мультиселектов
                $data['storeviews'] = !empty($data['storeviews']) ? explode(',', $data['storeviews']) : ['0'];
                $data['allowed_customer_groups'] = !empty($data['allowed_customer_groups']) ? explode(',', $data['allowed_customer_groups']) : [];
                if (!empty($data['display_on_pages'])) {
                    $data['display_on_pages'] = explode(',', $data['display_on_pages']);
                } else {
                    $data['display_on_pages'] = [];
                }

                // Для wheel_config возвращаем сырую JSON-строку (или значение по умолчанию)
                $data['wheel_config'] = !empty($data['wheel_config']) ? $data['wheel_config'] : '[]';

                // Обработка изображения CTA
                if (!empty($data['cta_image'])) {
                    $filePath = $data['cta_image'];
                    $fullPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($filePath);
                    $baseUrl = $this->getStoreManager()->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $data['cta_image'] = [
                        [
                            'name' => basename($filePath),
                            'file' => $filePath,
                            'url' => $baseUrl . $filePath,
                            'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                            'type' => file_exists($fullPath) ? mime_content_type($fullPath) : 'image/jpeg'
                        ]
                    ];
                }

                $this->loadedData[$wheelId] = $data;
            }
        }

        $this->logger->debug('FormDataProvider Loaded Data: ' . json_encode($this->loadedData));
        return $this->loadedData;
    }

    private function getStoreManager()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Store\Model\StoreManagerInterface::class);
    }
}
