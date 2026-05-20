<?php

namespace Doroshko\SpinReward\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isModuleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'wishreward_settings/general/enable_module',
            ScopeInterface::SCOPE_STORE
        );
    }
}
