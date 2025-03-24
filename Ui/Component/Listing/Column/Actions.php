<?php

namespace Doroshko\WishReward\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['wheel_id']) && !empty($item['wheel_id'])) {
                    $item[$name] = []; // Инициализируем массив действий
                    $item[$name][] = [
                        'href' => $this->urlBuilder->getUrl(
                            'wishreward/wheel/edit',
                            ['wheel_id' => $item['wheel_id']]
                        ),
                        'label' => __('Edit'),
                        'hidden' => false,
                    ];
                    $item[$name][] = [
                        'href' => $this->urlBuilder->getUrl(
                            'wishreward/wheel/delete',
                            ['wheel_id' => $item['wheel_id']]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete Wheel'),
                            'message' => __('Are you sure you want to delete this wheel?')
                        ],
                        'hidden' => false,
                    ];
                }
            }
        }

        return $dataSource;
    }
}
