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
use Doroshko\WishReward\Model\Config\Source\CartPriceRules;

class FormDataProvider extends AbstractDataProvider
{
    protected array $loadedData = [];
    protected RequestInterface $request;
    protected Filesystem $filesystem;
    protected StoreManagerInterface $storeManager;
    protected CartPriceRules $cartPriceRules;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        CartPriceRules $cartPriceRules,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->cartPriceRules = $cartPriceRules;
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

                $data['storeviews'] = !empty($data['storeviews'])
                    ? explode(',', $data['storeviews'])
                    : ['0'];

                $data['allowed_customer_groups'] = $data['allowed_customer_groups'] !== null && $data['allowed_customer_groups'] !== ''
                    ? array_map('strval', explode(',', $data['allowed_customer_groups']))
                    : [];

                $data['wheel_config'] = !empty($data['wheel_config']) ? $data['wheel_config'] : '[]';

                $data['rotation_duration'] = $data['rotation_duration'] ?? 6000;

                $data['popup_delay'] = isset($data['popup_delay']) ? (int)$data['popup_delay'] : 0;
                $data['popup_scroll_trigger'] = $data['popup_scroll_trigger'] ?? 'none';

                $data['time_of_day_start'] = $data['time_of_day_start'] ?? null;
                $data['time_of_day_end'] = $data['time_of_day_end'] ?? null;

                $data['trigger_action'] = $data['trigger_action'] ?? null;

                $data['popup_theme'] = $data['popup_theme'] ?? 'light';

                if (!empty($data['cta_image'])) {
                    $data['cta_image'] = $this->prepareImageData($data['cta_image']);
                }

                if (!empty($data['popup_company_logo'])) {
                    $data['popup_company_logo'] = $this->prepareImageData($data['popup_company_logo']);
                }

                $this->loadedData[$wheelId] = $data;
            }
        }

        return $this->loadedData;
    }

    private function prepareImageData(string $filePath): array
    {
        $filePath = ltrim($filePath, '/');

        if (str_starts_with($filePath, 'media/')) {
            $filePath = substr($filePath, strlen('media/'));
        }

        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $fullPath = $mediaDir->getAbsolutePath($filePath);
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        if (!file_exists($fullPath)) {
            return [];
        }

        return [[
            'name' => basename($filePath),
            'file' => $filePath,
            'url' => $baseUrl . $filePath,
            'size' => filesize($fullPath),
            'type' => mime_content_type($fullPath),
        ]];
    }

    public function getMeta(): array
    {
        $meta = parent::getMeta();

        $meta['wheel_configuration']['children']['wheel_config']['arguments']['data']['config']['priceRuleOptions'] = $this->cartPriceRules->toOptionArray();

        return $meta;
    }
}
