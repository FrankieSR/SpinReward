<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class MaskedEmail extends Column
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

            $item[$fieldName] = $this->maskEmail((string)$item[$fieldName]);
        }

        return $dataSource;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);

        if ($email === '' || strpos($email, '@') === false) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $localLength = strlen($localPart);

        if ($localLength <= 2) {
            $maskedLocal = str_repeat('*', $localLength);
        } else {
            $maskedLocal = substr($localPart, 0, 1) . str_repeat('*', max(2, $localLength - 2)) . substr($localPart, -1);
        }

        return $maskedLocal . '@' . $domain;
    }
}
