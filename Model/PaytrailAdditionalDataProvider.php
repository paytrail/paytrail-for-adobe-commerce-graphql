<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Paytrail\PaymentServiceGraphQl\Model;

use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;

/**
 * Get payment additional data for Payflow pro payment
 */
class PaytrailAdditionalDataProvider implements AdditionalDataProviderInterface
{
    private const PATH_ADDITIONAL_DATA = 'paytrail';

    /**
     * Returns additional data
     *
     * @param array $args
     *
     * @return array
     */
    public function getData(array $args): array
    {
        return $args[self::PATH_ADDITIONAL_DATA] ?? [];
    }
}
