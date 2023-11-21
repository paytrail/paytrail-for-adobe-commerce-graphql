<?php

namespace Paytrail\PaymentServiceGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Paytrail\PaymentService\Model\ConfigProvider;

class PaytrailConfig implements ResolverInterface
{

    /**
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        private readonly MaskedQuoteIdToQuoteIdInterface                  $maskedQuoteIdToQuoteId,
        private readonly CartRepositoryInterface                          $cartRepository,
        private readonly \Paytrail\PaymentService\Model\Ui\ConfigProvider $configProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        $cartId = $this->maskedQuoteIdToQuoteId->execute($args['cart_id']);
        $config = $this->configProvider->getConfig();

        $methodGroups = $config['payment']['paytrail']['method_groups'];


        return [
            'groups' => $methodGroups
        ];
    }
}
