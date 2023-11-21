<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Paytrail\PaymentServiceGraphQl\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Set additional data for payflow link payment
 */
class PaytrailSetAdditionalData extends AbstractDataAssignObserver
{
    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (isset($additionalData['additional_information']) and isset($additionalData['additional_information']['provider'])) {
            $additionalData = $additionalData['additional_information'];
        }
        if (!is_array($additionalData)) {
            return;
        }

        $paymentModel = $this->readPaymentModelArgument($observer);
        $paymentModel->setAdditionalInformation($additionalData);
    }
}
