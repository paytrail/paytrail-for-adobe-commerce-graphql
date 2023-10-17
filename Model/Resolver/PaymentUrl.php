<?php

namespace Paytrail\PaymentServiceGraphQl\Model\Resolver;

use Magento\Checkout\Model\Session;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order;
use Paytrail\PaymentService\Exceptions\CheckoutException;
use Paytrail\PaymentService\Gateway\Config\Config;
use Paytrail\PaymentService\Helper\ApiData;
use Paytrail\PaymentService\Helper\Data as paytrailHelper;
use Paytrail\SDK\Response\PaymentResponse;
use Psr\Log\LoggerInterface;

class PaymentUrl implements ResolverInterface
{
    public function __construct(
        private readonly Session         $checkoutSession,
        private readonly ApiData         $apiData,
        private readonly paytrailHelper  $paytrailHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return string[]
     * @throws CheckoutException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        $result = [
            'payment_url' => '',
            'error'       => ''
        ];

        try {
            $order = $this->checkoutSession->getLastRealOrder();
            if ($order->getPayment()->getMethod() == Config::CODE) {
                $responseData          = $this->getResponseData($order);
                $result['payment_url'] = $responseData->getHref();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param Order $order
     *
     * @return PaymentResponse
     * @throws CheckoutException
     */
    private function getResponseData(
        Order $order
    ): PaymentResponse {
        $response = $this->apiData->processApiRequest('payment', $order);

        $errorMsg = $response['error'];

        if (isset($errorMsg)) {
            $this->errorMsg = ($errorMsg);
            $this->paytrailHelper->processError($errorMsg);
        }

        return $response["data"];
    }
}
