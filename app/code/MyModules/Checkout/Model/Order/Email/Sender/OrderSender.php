<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Redington\Checkout\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

/**
 * Class OrderSender
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    public function send(Order $order, $forceSyncMode = false)
    {
        $this->checkoutSession->unsForceOrderMailSentOnSuccess();
        if ($order->getSapReferenceNumber() != '') {
            $this->checkoutSession->setForceOrderMailSentOnSuccess(true);
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_orderEmail.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($order->getSapReferenceNumber());

            //$order->setSendEmail(true);

            //if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {

                if ($this->checkAndSend($order)) {
                    $order->setEmailSent(true);
                    $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);
                    return true;
                }
            //} else {
            //    $order->setEmailSent(null);
            //    $this->orderResource->saveAttribute($order, 'email_sent');
            //}
        }

        $this->orderResource->saveAttribute($order, 'send_email');

        return false;
    }

}
