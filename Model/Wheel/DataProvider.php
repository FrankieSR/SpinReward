<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model\Wheel;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;
use Doroshko\SpinReward\Model\ResourceModel\Wheel\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array|null
     */
    protected ?array $loadedData = null;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = [
            'items' => [],
            'totalRecords' => (int)$this->collection->getSize()
        ];

        foreach ($items as $wheel) {
            $this->loadedData['items'][] = $wheel->getData();
        }

        return $this->loadedData;
    }
}
