<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace Paytrail\PaymentServiceGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

class RestoreQuote implements ResolverInterface
{
    /**
     * RestoreQuote constructor.
     *
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly CartRepositoryInterface         $cartRepository
    ) {
    }

    /**
     * Resolve masked cart id to quote id
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return string
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(
        Field       $field,
        $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ): string {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
        }

        $maskedCartId = $args['input']['cart_id'];
        $storeId      = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart         = $this->getQuoteByHash($maskedCartId, $context->getUserId(), $storeId);

        if (!$cart->getIsActive()) {
            $this->restoreQuote($cart);
        }

        return $maskedCartId;
    }

    /**
     * Get cart for user
     *
     * @param string $cartHash
     * @param int|null $customerId
     * @param int $storeId
     *
     * @return CartInterface
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    private function getQuoteByHash(string $cartHash, ?int $customerId, int $storeId): CartInterface
    {
        try {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($cartHash);
            $cart   = $this->cartRepository->get($cartId);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%1"', $cartHash)
            );
        }

        if ($cart->getPayment()->getMethod() && !str_contains($cart->getPayment()->getMethod(), 'paytrail')) {
            throw new GraphQlNoSuchEntityException(
                __('This cart "%1" is not using a Paytrail payment method', $cartHash)
            );
        }

        $cartCustomerId = (int)$cart->getCustomerId();

        if ($cartCustomerId === 0 && (null === $customerId || 0 === $customerId)) {
            return $cart;
        }

        if ($cartCustomerId !== $customerId) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on cart "%1"', $cartHash)
            );
        }

        if ((int)$cart->getStoreId() !== $storeId) {
            throw new GraphQlNoSuchEntityException(
                __('Wrong store code specified for cart "%1"', $cartHash)
            );
        }

        return $cart;
    }

    /**
     * Restore Quote
     *
     * @param CartInterface $cart
     *
     * @return void
     */
    private function restoreQuote(CartInterface $cart): void
    {
        $cart->setIsActive(1)->setReservedOrderId(null);
        $this->cartRepository->save($cart);
    }
}
