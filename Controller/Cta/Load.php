<?php
declare(strict_types=1);

namespace Doroshko\WishReward\Controller\Cta;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Doroshko\WishReward\Api\WheelRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\RequestInterface;

class Load implements HttpGetActionInterface
{
    private JsonFactory $resultJsonFactory;
    private WheelRepositoryInterface $wheelRepository;
    private RequestInterface $request;

    public function __construct(
        JsonFactory $resultJsonFactory,
        WheelRepositoryInterface $wheelRepository,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->wheelRepository = $wheelRepository;
        $this->request = $request;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $popupId = (int)$this->request->getParam('id');
        $pageHandle = $this->request->getParam('handle', 'default');
        $pageUrl = $this->request->getParam('url', '');

        try {
            if ($popupId) {
                $wheel = $this->wheelRepository->getById($popupId);
            } else {
                $wheel = $this->wheelRepository->getEligiblePopupForHandle($pageHandle, $pageUrl);
                if (!$wheel) {
                    return $resultJson->setData([
                        'error' => __('No eligible popup found.'),
                        'enabled' => false
                    ]);
                }
            }

            $data = [
                'enabled' => true,
                'popup_title' => $wheel->getPopupTitle(),
                'popup_description' => $wheel->getPopupDescription(),
                'is_wish_area_enabled' => (bool)$wheel->getIsWishAreaEnabled(),
                'is_email_input_enabled' => (bool)$wheel->getIsEmailInputEnabled(),
                'wheel_config' => $wheel->getWheelConfig() ? json_decode($wheel->getWheelConfig(), true) : [],
                'is_cta_enabled' => (bool)$wheel->getIsCtaEnabled(),
                'cta_label' => $wheel->getCtaLabel(),
                'cta_button_text' => $wheel->getCtaButtonText(),
                'cta_image' => $wheel->getCtaImage(),
                'cta_position' => $wheel->getCtaPosition(),
                'cta_custom_css' => $wheel->getCtaCustomCss(),
                'display_on_pages' => $wheel->getDisplayOnPages(),
            ];

            return $resultJson->setData($data);
        } catch (NoSuchEntityException $e) {
            return $resultJson->setData([
                'error' => __('Popup with id "%1" does not exist.', $popupId),
                'enabled' => false
            ]);
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'error' => $e->getMessage(),
                'enabled' => false
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'error' => __('An unexpected error occurred: %1', $e->getMessage()),
                'enabled' => false
            ]);
        }
    }
}