<?php

namespace Redington\SapIntegration\Cron;

class Overdue
{
	protected $_customerFactory;
	protected $_helper;

	public function __construct(
       \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
       \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
       \Redington\SapIntegration\Helper\Data $helper,
       \Magento\Customer\Model\ResourceModel\Customer\Collection $custCollection
    ) {
        $this->_customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->_helper = $helper;
        $this->custCollection = $custCollection;
    }

   public function updateOverdueDetails()
   {

   	$processedCustomerData = [];

   	 $sapDataCollectionArray['LstZBAPI3007_1'] = [];
   	 $customerCollection = $this->_customerFactory->create();
   	 foreach ($customerCollection as $customer) {

   	 	$customerId = $customer->getId();
		
		$customerLoad = $this->customerRepository->getById($customerId);
		$sapAccNoObj = $customerLoad->getCustomAttribute('z_sap_account_number');
		$sapCodeObj = $customerLoad->getCustomAttribute('z_sap_code');

		if(!empty($sapAccNoObj))
		{
			$sapAccNo = $sapAccNoObj->getValue();
		}
		if(!empty($sapCodeObj))
		{
			$sapCode = $sapCodeObj->getValue();
		}

		$processedCustomerData[$sapAccNo] = $customerId;

		
		$sapDataCollection['COMP_CODE'] = $sapCode;
		$sapDataCollection['CUSTOMER'] = $sapAccNo;
		array_push($sapDataCollectionArray['LstZBAPI3007_1'],$sapDataCollection);
		$sapDataCollectionJson = json_encode($sapDataCollectionArray);
	}
		
		$overDueArray = $this->_helper->getOverdueAmount($sapDataCollectionJson);
		
       
   	 	foreach ($overDueArray as $key => $value) {
   	 		$customerId = $processedCustomerData[$value['CUSTOMER']];
            $customerLoad = $this->customerRepository->getById($customerId);
			

   	 		if($value['LC_AMOUNT'] < 1)
   	 		{
   	 			$customerLoad->setCustomAttribute('is_overdue',0);
   	 			
   	 		}
   	 		else{
   	 			$customerLoad->setCustomAttribute('is_overdue',1);
   	 			
   	 		}
   	 		
   	 		$this->customerRepository->save($customerLoad);
   	 	}	 
   }

}
