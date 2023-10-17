<?php

namespace Paytrail\PaymentServiceGraphQl\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Paytrail\PaymentService\Controller\Receipt\Index;

class GetFrontendUrl
{
    public function __construct(
        private readonly QuoteIdMaskFactory $idMaskFactory,
        private readonly \Magento\Support\Model\Report\Group\Modules\Modules $modules,
        private readonly ScopeConfigInterface $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param Index $subject
     * @param string $result
     * @param Order $order
     *
     * @return string
     */
    public function afterGetSuccessUrl(Index $subject, string $result, Order $order): string
    {
        if ($this->modules->isModuleEnabled('Magento_UpwardConnector')) {
            $frontendBaseUrl = $this->storeManager->getStore($order->getStoreId())->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB,
                true
            );
        } else {
            $frontendBaseUrl = $this->getBaseUrl();
        }

        $maskedId = $this->getMaskedId($order);
        $result   = trim($frontendBaseUrl, '/') . '/checkout/success/' . $order->getIncrementId(
            ) . '/maskedId/' . $maskedId;

        return $result;
    }


    /**
     * @param Index $subject
     * @param string $result
     * @param Order $order
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function afterGetCartUrl(Index $subject, string $result, Order $order): string
    {
        if ($this->modules->isModuleEnabled('Magento_UpwardConnector')) {
            $frontendBaseUrl = $this->storeManager->getStore($order->getStoreId())->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB,
                true
            );
        } else {
            $frontendBaseUrl = $this->getBaseUrl();
        }

        $maskedId = $this->getMaskedId($order);
        $result   = trim($frontendBaseUrl, '/') . '/cart/?paytrailRestore=true&maskedId=' . $maskedId;

        return $result;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    private function getMaskedId(Order $order): string
    {
        $quoteIdMask = $this->idMaskFactory->create();
        $quoteIdMask->load($order->getQuoteId(), 'quote_id');
        return $quoteIdMask->getMaskedId();
    }

    /**
     * @return string
     */
    private function getBaseUrl(): string
    {
        $baseUrl = '';
        if ($this->config->isSetFlag('payment/paytrail/pwa/use_pwa')) {
            $baseUrl = $this->config->getValue('payment/paytrail/pwa/pwa_frontend_url');
        }

        return $baseUrl;
    }
}
