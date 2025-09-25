<?php

namespace Paytrail\PaymentServiceGraphQl\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\Manager;
use Paytrail\PaymentService\Controller\Receipt\Index;

class GetFrontendUrl
{
    /**
     * @param QuoteIdMaskFactory $idMaskFactory
     * @param Manager $moduleManager
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly QuoteIdMaskFactory    $idMaskFactory,
        private readonly Manager               $moduleManager,
        private readonly ScopeConfigInterface  $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     *  Get success url based on pwa configuration.
     *
     * @param Index $subject
     * @param string $result
     * @param Order $order
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function afterGetSuccessUrl(Index $subject, string $result, Order $order): string
    {
        if ($this->moduleManager->isEnabled('Magento_UpwardConnector')) {
            $frontendBaseUrl = $this->storeManager->getStore($order->getStoreId())->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB,
                true
            );
        } else {
            $frontendBaseUrl = $this->getBaseUrl();
        }

        $maskedId = $this->getMaskedId($order);
        $result   = trim($frontendBaseUrl, '/') . '/checkout/success/' . $order->getIncrementId()
            . '/maskedId/' . $maskedId;

        return $result;
    }

    /**
     * Get cart url based on pwa configuration.
     *
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
        if ($this->moduleManager->isEnabled('Magento_UpwardConnector')) {
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
     * Get quote masked id.
     *
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
     * Get base url for pwa frontend
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        $baseUrl = '';
        if ($this->config->isSetFlag('payment/paytrail/pwa/use_pwa', ScopeInterface::SCOPE_STORE)) {
            $baseUrl = $this->config->getValue(
                'payment/paytrail/pwa/pwa_frontend_url',
                ScopeInterface::SCOPE_STORE
            );
        }

        return $baseUrl;
    }
}
