<?php

declare(strict_types=1);

namespace Doroshko\WishReward\Model\Wheel;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Doroshko\WishReward\Model\ResourceModel\Wheel\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class FormDataProvider extends AbstractDataProvider
{
    protected array $loadedData = [];
    protected RequestInterface $request;
    protected Filesystem $filesystem;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection   = $collectionFactory->create();
        $this->request      = $request;
        $this->filesystem   = $filesystem;
        $this->storeManager = $storeManager;
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

                // Нормализация storeviews (если пусто — возвращаем массив с '0')
                $data['storeviews'] = !empty($data['storeviews'])
                    ? explode(',', $data['storeviews'])
                    : ['0'];

                // Нормализация групп клиентов
                $data['allowed_customer_groups'] = !empty($data['allowed_customer_groups'])
                    ? explode(',', $data['allowed_customer_groups'])
                    : [];

                // Нормализация отображения страниц
                $data['display_on_pages'] = !empty($data['display_on_pages'])
                    ? explode(',', $data['display_on_pages'])
                    : [];

                // Если конфигурация колеса пуста — устанавливаем значение по умолчанию
                $data['wheel_config'] = !empty($data['wheel_config']) ? $data['wheel_config'] : '[]';

                // Визуальные настройки колеса
                $data['rotation_duration'] = $data['rotation_duration'] ?? 6000;
                $data['wheel_radius']      = $data['wheel_radius'] ?? 140;
                $data['wheel_position']    = $data['wheel_position'] ?? 'center';

                // Настройки триггера показа popup
                $data['popup_delay'] = isset($data['popup_delay']) ? (int)$data['popup_delay'] : 0;
                $data['popup_scroll_trigger'] = $data['popup_scroll_trigger'] ?? 'none';
                $data['popup_once_per_session'] = isset($data['popup_once_per_session'])
                    ? (bool)$data['popup_once_per_session']
                    : true;

                // Обработка CTA изображения, если оно есть
                if (!empty($data['cta_image'])) {
                    $filePath = $data['cta_image'];
                    $filePath = ltrim(str_replace(['media/.renditions/', 'media/'], '', $filePath), '/');
                    $fullPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($filePath);
                    $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

                    $data['cta_image'] = [
                        [
                            'name' => basename($filePath),
                            'file' => $filePath,
                            'url'  => $baseUrl . $filePath,
                            'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                            'type' => file_exists($fullPath) ? mime_content_type($fullPath) : 'image/jpeg'
                        ]
                    ];
                }

                $this->loadedData[$wheelId] = $data;
            }
        }

        return $this->loadedData;
    }
}
