<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */

namespace Redington\Quotation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Quote\Api\CartRepositoryInterface;

class Reservation extends AbstractHelper
{

    /**
     * default 
     */
    const SCOPE_DEFAULT = 'default';
    
    /**
     * Catalog Module Enable configuration
     */
    const CUSTOMER_MODULE_ENABLED = 'redington_quotation/general/enabled';
       
	protected $_storeManager;	
       
    /**
     * 
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(        
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Redington\Company\Helper\Data $companyHelper,
        \Redington\Quotation\Model\InventoryReservationFactory $inventoryReservationFactory,
        \Redington\Quotation\Model\QuoteReservationFactory $quoteReservationFactory,
        \Redington\Configuration\Helper\Data $configurationHelper,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
		\Magento\Company\Model\CompanyFactory $companyFactory,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
		\Magento\Company\Model\CompanyRepository $companyRepository,
		CartRepositoryInterface $quoteRepository
    ) {        
        $this->scopeConfig = $scopeConfig;
		$this->quoteFactory = $quoteFactory;
        $this->companyHelper = $companyHelper;
        $this->inventoryReservationFactory = $inventoryReservationFactory;
        $this->quoteReservationFactory = $quoteReservationFactory;
        $this->configurationHelper = $configurationHelper;
		$this->customerFactory = $customerFactory;
		$this->sourceCollectionFactory = $sourceCollectionFactory;
		$this->companyFactory = $companyFactory;
		$this->customerRepository = $customerRepository;
		$this->companyRepository = $companyRepository;
		$this->quoteRepository = $quoteRepository;
    }
    
	public function reserveStock($quoteId, $isReview = true, $shouldReserve = true){
        $isReview = $isReview ? 1 : 0;
        $status = $shouldReserve ? 0 : 1;
        $companyId = $this->companyHelper->retrieveCompanyId();
		 $this->logMessage('Company id is '.$companyId);
        $quoteReservation = $this->quoteReservationFactory->create();
        $quoteReservationCollection = $quoteReservation
            ->getCollection()
            ->addFieldToFilter('quote_id',$quoteId);
        if ($quoteReservationCollection->getSize()>0) {
            $reservationId = $quoteReservationCollection->getFirstItem()->getReservationId();
            $quoteReservation
                ->load($reservationId)
                ->setStatus($status)
                ->save();
        }else{
            $quoteReservation
                ->setQuoteId($quoteId)
                ->setCompanyId($companyId)
                ->setIsReview($isReview)
                ->setStatus($status)
                ->save();
        }
        
        $quote = $this->quoteFactory->create()->load($quoteId);
        $this->logMessage($quote->getData());
        $allItems = $quote->getAllItems();
        $plantCode = $this->configurationHelper->getPlantData()->getPlantCode();
        $this->logMessage('quote id is '.$quoteId.' company id is '.$companyId.' plant code is '.$plantCode);
        $this->reservationCollection = $this->inventoryReservationFactory->create()->getCollection();
        foreach ($allItems as $item) {
            $this->updateInventoryReservation($plantCode,$item,$shouldReserve);
        }
    }
	
	public function removeStock($quoteId,$isReview = true, $shouldReserve = true){
        $isReview = $isReview ? 1 : 0;
        $status = $shouldReserve ? 0 : 1;
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_QuoteCompany.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info('Quote Id1'.$quoteId);
		$quote = $this->quoteRepository->get($quoteId);
		$customerId = $quote->getCustomer()->getId();
		$logger->info('Customer Id'.$customerId);
		$customer = $this->customerRepository->getById($customerId);
		$companyId = $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId();
		$logger->info('Company Id1'.$companyId);
		$company =  $this->companyRepository->get($companyId);
		$logger->info('Company Id1'.$companyId);
		$superUserId =  $company->getSuperUserId();
		$logger->info('Super User Id'.$companyId);
		$customerModel = $this->customerFactory->create()->load($superUserId);
		
		
		$quoteReservation = $this->quoteReservationFactory->create();
        $quoteReservationCollection = $quoteReservation
            ->getCollection()
            ->addFieldToFilter('quote_id',$quoteId);
        if ($quoteReservationCollection->getSize()>0) {
            $reservationId = $quoteReservationCollection->getFirstItem()->getReservationId();
            $quoteReservation
                ->load($reservationId)
                ->setStatus($status)
                ->save();
        }else{
            $quoteReservation
                ->setQuoteId($quoteId)
                ->setCompanyId($companyId)
                ->setIsReview($isReview)
                ->setStatus($status)
                ->save();
        }
        
        $plantCode = '';
        
        $allItems = $quote->getAllItems();
		$sourceCollection = $this->sourceCollectionFactory->create()
                ->addFieldToFilter('enabled', 1)
                ->addFieldToFilter('distribution', $customerModel->getZDistributionChannel())
                ->addFieldToFilter('sap_account_code',  $customerModel->getZSapCode());
			
			$data = $sourceCollection->getData();
			if(isset($data) && $data!=''){
				$plantCode = $data[0]['plant_code'];
			}
       
        $this->logMessage('quote id is '.$quoteId.' company id is '.$customerModel->getCompanyId().' plant code is '.$plantCode);
        $this->reservationCollection = $this->inventoryReservationFactory->create()->getCollection();
        foreach ($allItems as $item) {
            $this->updateInventoryReservation($plantCode,$item,$shouldReserve);
        }
    }
	
	public function removeItemById($quoteId, $itemModel, $isReview = true, $shouldReserve = true){
        $isReview = $isReview ? 1 : 0;
        $status = $shouldReserve ? 0 : 1;
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_QuoteDeleteItem.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info('Quote Id1'.$quoteId);
		$quote = $this->quoteRepository->get($quoteId);
		$customerId = $quote->getCustomer()->getId();
		$logger->info('Customer Id'.$customerId);
		$customer = $this->customerRepository->getById($customerId);
		$companyId = $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId();
		$logger->info('Company Id1'.$companyId);
		$company =  $this->companyRepository->get($companyId);
		$logger->info('Company Id1'.$companyId);
		$superUserId =  $company->getSuperUserId();
		$logger->info('Super User Id'.$companyId);
		$customerModel = $this->customerFactory->create()->load($superUserId);
		
		
		$quoteReservation = $this->quoteReservationFactory->create();
        $quoteReservationCollection = $quoteReservation
            ->getCollection()
            ->addFieldToFilter('quote_id',$quoteId);
        if ($quoteReservationCollection->getSize()>0) {
            $reservationId = $quoteReservationCollection->getFirstItem()->getReservationId();
            $quoteReservation
                ->load($reservationId)
                ->setStatus($status)
                ->save();
        }else{
            $quoteReservation
                ->setQuoteId($quoteId)
                ->setCompanyId($companyId)
                ->setIsReview($isReview)
                ->setStatus($status)
                ->save();
        }
        
        $plantCode = '';
        
        $allItems = $quote->getAllItems();
		$sourceCollection = $this->sourceCollectionFactory->create()
                ->addFieldToFilter('enabled', 1)
                ->addFieldToFilter('distribution', $customerModel->getZDistributionChannel())
                ->addFieldToFilter('sap_account_code',  $customerModel->getZSapCode());
			
			$data = $sourceCollection->getData();
			if(isset($data) && $data!=''){
				$plantCode = $data[0]['plant_code'];
			}
       
        $this->logMessage('quote id is '.$quoteId.' company id is '.$customerModel->getCompanyId().' plant code is '.$plantCode);
        $this->reservationCollection = $this->inventoryReservationFactory->create()->getCollection();
		$this->updateInventoryReservation($plantCode,$itemModel,$shouldReserve);
        
    }
	
    public function updateInventoryReservation($plantCode,$item,$shouldReserve){
        $sku = $item->getSku();
        $this->logMessage('skuis '.$sku);
        $this->logMessage('qty is '.$item->getQty());
        $reservationData = $this->reservationCollection
            ->addFieldToFilter('plant_code',$plantCode)
            ->addFieldToFilter('sku',$sku);
        if($reservationData->getSize()>0){
            $reservationId = $reservationData->getFirstItem()->getReservationId();
            $reservation = $this->inventoryReservationFactory->create()->load($reservationId);
            $initialQty = $reservation->getQuantity();
            $newQty = $item->getQty();
            $totalQty = $shouldReserve ? $initialQty + $newQty : ($initialQty > $newQty ? $initialQty - $newQty : 0);
            $this->logMessage('initial qty is '.$initialQty.' new qty is '.$newQty.' total qty is'.$totalQty);
            $reservation->setQuantity($totalQty)->save();
        }else{
            $this->logMessage('reservation created');
            $this->inventoryReservationFactory
            ->create()
            ->setPlantCode($plantCode)
            ->setSku($sku)
            ->setQuantity($item->getQty())
            ->save();
            $this->logMessage('object saved');
        }
    }

    public function getReservedQty($productSku){
        $plantCode = $this->configurationHelper->getPlantData()->getPlantCode();
        $inventoryReservation = $this->inventoryReservationFactory->create()->getCollection();
        $inventoryReservation
            ->addFieldToFilter('plant_code',$plantCode)
            ->addFieldToFilter('sku',$productSku);
        if($inventoryReservation->getSize()>0){
            return $inventoryReservation->getFirstItem()->getQuantity();
        }else{
            return 0;
        }
    }

    public function logMessage($message, $filePath = '/var/log/Redington_Reservation.log') {        
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}
