<?php

namespace Redington\Checkout\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendMailOnOrderSuccess implements ObserverInterface
{
	
	protected $orderModel;
	protected $orderSender;
    protected $checkoutSession;
	protected $repositoryAddress;
	
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderModel,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Sales\Model\Order\AddressRepository $repositoryAddress
    )
    {
        $this->orderModel = $orderModel;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
		$this->repositoryAddress= $repositoryAddress;
	}
	/**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
	public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
		$order = $observer->getEvent()->getOrder();
		try{
			$shipAddress =$this->repositoryAddress->get($order->getShippingAddressId());
			if($order->getSapReferenceNumber())
			{
			  $this->checkoutSession->setForceOrderMailSentOnSuccess(true);
			  $order = $this->orderModel->create()->load($orderIds[0]);
			  $this->orderSender->send($order, true);
			}
			if($order->getWarehouseCode()!=''){
				$shipAddress->setCompany('');
				$this->repositoryAddress->save($shipAddress);
			}
		}catch(\Exception $e){
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_createEmail.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$logger->info($order->getSapReferenceNumber());
		}
        
    }
}