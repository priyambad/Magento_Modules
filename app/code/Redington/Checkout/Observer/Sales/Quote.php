<?php

namespace Redington\Checkout\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;

class Quote implements ObserverInterface {

    public function __construct(
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ToOrderAddressConverter $quoteAddressToOrderAddress
    ) {
        
        $this->customerRepository = $customerRepositoryInterface;
        $this->addressRepository = $addressRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->objectManager = $objectManager;
        $this->quoteAddressToOrderAddress = $quoteAddressToOrderAddress;
    }
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $quote = $observer->getQuote();
		try{
				$adminId = $this->companyHelper->getCompanyAdminId();
				$admin = $this->customerRepository->getById($adminId);
				$billingId = $admin->getDefaultBilling();
				$billingAddress = $this->addressRepository->getById($billingId);
				$data = [
					'firstname' => $billingAddress->getFirstName(),
					'lastname' => $billingAddress->getLastName(),
					'telephone' => $billingAddress->getTelephone(),
					'street' => $billingAddress->getStreet(),
					'city' => $billingAddress->getCity(),
					'postcode' => $billingAddress->getPostcode(),
					'country_id' => $billingAddress->getCountryId(),
					'company' => $billingAddress->getCompany(),
					'address_type' => 'billing',
				];
				$address = $this->objectManager->create(\Magento\Quote\Model\Quote\Address::class, ['data' => $data]);
				$quote->setBillingAddress($address);
				$order = $observer->getOrder();
				/*if($order->getWarehouseCode()!=''){
					$quote->getShippingAddress()->setCompany('');
					$quote->setShippingAddress();
					$quote->save();
				}*/
				$orderBillingAddress = $this->quoteAddressToOrderAddress->convert(
					$quote->getBillingAddress(),
					[
						'address_type' => 'billing',
						'email' => $quote->getCustomerEmail()
					]
				);
				$orderBillingAddress->setData('quote_address_id', $quote->getBillingAddress()->getId());
				$order->setBillingAddress($orderBillingAddress);
		}catch(\Exception $e){
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_orderAddress.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$logger->info('in Quote Addrees');
		}
    }
}