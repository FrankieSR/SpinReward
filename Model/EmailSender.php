<?php
declare(strict_types=1);

namespace Doroshko\SpinReward\Model;

use Doroshko\SpinReward\Api\EmailSenderInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\MailException;

class EmailSender implements EmailSenderInterface
{
    private const XML_PATH_EMAIL_TEMPLATE = 'wishreward_settings/general/winning_coupon_email_template';

    private TransportBuilder $transportBuilder;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function sendWinningCouponEmail(
        string $email,
        ?string $customerName,
        string $couponCode,
        string $rewardDescription,
        string $promotionName
    ): void {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $templateId = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'customer_name' => $customerName ?: 'Guest',
                    'coupon_code' => $couponCode,
                    'reward_description' => $rewardDescription,
                    'promotion_name' => $promotionName,
                ])
                ->setFromByScope('general', $storeId)
                ->addTo($email, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->logger->info('Winning coupon email sent to: ' . $email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send winning coupon email to ' . $email . ': ' . $e->getMessage());
            throw new MailException(__($e->getMessage()), $e);
        }
    }
}
