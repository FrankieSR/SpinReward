<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class MaskedIp extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = (string)$this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$fieldName])) {
                continue;
            }

            $item[$fieldName] = $this->maskIp((string)$item[$fieldName]);
        }

        return $dataSource;
    }

    private function maskIp(string $ip): string
    {
        $ip = trim($ip);

        if ($ip === '') {
            return $ip;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.***.***';
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, 8) . ':****:****:****';
        }

        return $ip;
    }
}
