<?php

namespace Redington\SapIntegration\Observer\Checkout\Controller;

use Magento\Checkout\Model\Cart as CustomerCart;

class ActionPredispatchCheckoutIndexIndex implements \Magento\Framework\Event\ObserverInterface
{
	/**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\CompanyCredit\Model\ResourceModel\CreditLimit\CollectionFactory $creditLimitCollectionFactory,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Magento\Inventory\Model\SourceItemFactory $sourceItemFactory,
        \Magento\Checkout\Model\Cart $cartManager,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
		CustomerCart $cart,
		\Magento\Quote\Model\Quote\ItemFactory $itemFactory
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->companyHelper = $companyHelper;
        $this->creditDataProvider = $creditDataProvider;
        $this->creditLimitCollectionFactory = $creditLimitCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->cartManager = $cartManager;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->_messageManager = $messageManager;
        $this->productFactory = $productFactory;
		$this->cart = $cart;
		$this->itemFactory = $itemFactory;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $this->updateStock();
        $this->checkCreditLimit();
        $this->checkSalableQty();
    }
    public function checkSalableQty()
    {
        $updated = false;
        $cart = [];
        $plantCode = $this->getPlantData()->getSourceCode();
        $quote = $this->checkoutSession->getQuote();
        $this->logMessage('Quote id ' . $quote->getId());
        $availableItems = $quote->getAllVisibleItems();
        foreach ($availableItems as $key => $item) {
            $productId = $item->getId();
            $productSku = $item->getSku();
            $this->logMessage('product ' . $productSku);
            $requestedQty = $item->getQty();
            $this->logMessage('requested Qty ' . $requestedQty);
            $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku', $productSku)->addFieldToFilter('source_code', $plantCode);
            $sourceData = $sourceCollection->getFirstItem();
            $qtyInPlant = $sourceData->getQuantity();
            $this->logMessage('available qty ' . $qtyInPlant);
			if($qtyInPlant==0){
				$this->cart->removeItem($productId)->save();
			}
            if ($qtyInPlant < $requestedQty) {
                $requestedQty = $qtyInPlant;
                $updated = true;
            }
			
									
            $cart[$productId]['qty'] = $requestedQty;

        }
        if ($updated) {
            $this->logMessage('updating cart qty');
            $this->logMessage($cart);
            $this->cartManager->updateItems($cart)->save();
            $redirectionUrl = $this->url->getUrl() . 'checkout/cart/';
            $this->_messageManager->addNotice('We adjusted product quantities to fit the required increments.');
            $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
        }
    }
    public function updateStock()
    {
        $quote = $this->checkoutSession->getQuote();
        $availableItems = $quote->getAllVisibleItems();
        // Item array
        $items = [];
        foreach ($availableItems as $key => $item) {
            $sku = $item->getSku();
            array_push($items, ["MATNR" => $sku]);
        }
        // get Plant Data
        $plant = $this->getPlantData();

        $requestBody = [
            "IM_MATNR" => [
                "ZTS_MATNR" => $items,
            ],
            "IM_WERKS" => [
                "ZTS_WERKS" => [
                    ["WERKS" => $plant->getSourceCode()],
                ],
            ],
            "IM_VTWEG" => [
                "ZTS_VTWEG" => [
                    ["VTWEG" => $plant->getDistribution()],
                ],
            ],
            "IM_BUKRS" => [
                "ZTS_BUKRS" => [
                    ["BUKRS" => $plant->getSapAccountCode()],
                ],
            ],
        ];
        $requestBody = json_encode($requestBody);
        $this->logMessage('request body ' . $requestBody);
        $stockUrl = $this->scopeConfig->getValue('redington_sap/general/plant_data');
        $this->logMessage('url : ' . $stockUrl);

        $stockData = $this->getStockData($requestBody, $stockUrl);
        
        try {
            
            if (is_array($stockData['RETURN']['BAPIRET2'])) {
				
                $redirectionUrl = $this->url->getUrl() . 'checkout/cart/';
                //get first element of array
                $firstElement = reset($stockData['RETURN']['BAPIRET2']);
                //if first element is not an array convert whole array as element of that array itself.
                if (!(is_array($firstElement))) {
                    $stockData['RETURN']['BAPIRET2'] = [$stockData['RETURN']['BAPIRET2']];
                }
				
                foreach ($stockData['RETURN']['BAPIRET2'] as $stock) {
					 
						$this->logMessage('warehouse : ' . $plant->getSourceCode() . ' sku ' . $stock['MESSAGE_V1']);
						if ($stock['TYPE'] == 'E' && $stock['MESSAGE_V1'] == null) {
							$this->logMessage('in no data found');
							$this->_messageManager->addError('Requested products are no longer available in stock, hence removed from Cart');
							$this->checkoutSession->clearQuote();
							$this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
							return $this->responseFactory;
						}
						else if ($stock['TYPE'] == 'E' && $stock['NUMBER'] == 1) {
							
								$sourceCollection = $this->sourceItemCollectionFactory->create()
									->addFieldToFilter('sku', $stock['MESSAGE_V1'])->addFieldToFilter('source_code',$plant->getSourceCode());
									if (sizeof($sourceCollection) > 0) {
										$this->logMessage('assigning qty 0 .....');
										$sourceId = $sourceCollection->getFirstItem()->getId();
										$source = $this->sourceItemFactory->create()->load($sourceId);
										$source->setQuantity(0);
										$source->setStatus(1);
										$source->save();
										$this->logMessage('assigned qty 0 .....');
									} 
								$itemModel = $this->itemFactory->create()->getCollection()
									->addFieldToFilter('sku',$stock['MESSAGE_V1']);
									foreach($itemModel->getData() as $item){
										$itemId = $item['item_id'];
										$this->cart->removeItem($itemId)->save();
										$this->logMessage('Removed Item:'.$itemId);
									}
									
							$this->logMessage('Product Sku:' . $stock['MESSAGE_V1']);
							$this->_messageManager->addError('Requested products are no longer available in stock, hence removed from Cart');
							$this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
							
						}
						
					}
             
               return $this->responseFactory; 	
            }

            if (is_array($stockData['STOCK_DETAILS']['ZRFC_WAREHOUSE_STOCK2'])) {
                $this->logMessage('Testing : ');
                //get first element of array
                $firstElement = reset($stockData['STOCK_DETAILS']['ZRFC_WAREHOUSE_STOCK2']);
                //if first element is not an array convert whole array as element of that array itself.
                if (!(is_array($firstElement))) {
                    $stockData['STOCK_DETAILS']['ZRFC_WAREHOUSE_STOCK2'] = [$stockData['STOCK_DETAILS']['ZRFC_WAREHOUSE_STOCK2']];
                }
                foreach ($stockData['STOCK_DETAILS']['ZRFC_WAREHOUSE_STOCK2'] as $stock) {
					
						foreach ($items as $key => $value) {
							$this->logMessage('MATNR'.$value['MATNR']);
							if ($value['MATNR'] == $stock['EX_MATNR']) {
								unset($items[$key]);
							}
						}
						$this->logMessage('warehouse : ' . $stock['EX_WERKS'] . ' sku ' . $stock['EX_MATNR'] . ' qty ' . $stock['EX_LABST']);
						$sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku', $stock['EX_MATNR'])->addFieldToFilter('source_code', $stock['EX_WERKS']);
					   
						if (sizeof($sourceCollection) > 0) {
							$this->logMessage('assigning.....');
							$sourceId = $sourceCollection->getFirstItem()->getId();
							$source = $this->sourceItemFactory->create()->load($sourceId);
							$source->setQuantity($stock['EX_LABST']);
							$source->setStatus(1);
							$source->save();
							if ($stock['EX_LABST'] < 1) {
								$redirectionUrl = $this->url->getUrl() . 'checkout/cart/';
								$this->_messageManager->addError('Requested products are no longer available in stock, hence removed from Cart');
								$this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
							}
							$this->logMessage('assigned.....');
						} else {
							try {
								if ($this->_product->getIdBySku($stock['EX_MATNR'])) {
									$this->logMessage('assigning.....');
									$source = $this->sourceItemFactory->create();
									$source->setSourceCode($stock['EX_WERKS']);
									$source->setSku($stock['EX_MATNR']);
									$source->setQuantity($stock['EX_LABST']);
									$source->save();
									if ($stock['EX_LABST'] < 1) {
										$redirectionUrl = $this->url->getUrl() . 'checkout/cart/';
										$this->_messageManager->addError('Requested products are no longer available in stock, hence removed from Cart');
										$this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
									}
									$this->logMessage('assigned.....');
								}
							} catch (\Exception $e) {
								continue;
							}
						}
						
                }
                $this->logMessage('remining items are');
                $this->logMessage($items);
                foreach ($items as $item) {
                    $sourceCollection = $this->sourceItemCollectionFactory->create()->addFieldToFilter('sku', $item['MATNR'])->addFieldToFilter('source_code', $plant->getSourceCode());
                    if (sizeof($sourceCollection) > 0) {
                        $this->logMessage('assigning qty 0 .....');
                        $sourceId = $sourceCollection->getFirstItem()->getId();
                        $source = $this->sourceItemFactory->create()->load($sourceId);
                        $source->setQuantity(0);
                        $source->setStatus(1);
                        $source->save();
					    $this->logMessage('assigned qty 0 .....');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logMessage($e->getMessage());
            return;
        }
    }
    private function getStockData($body, $url)
    {
        $this->logMessage('Request ' . $body);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $sapResponce = curl_exec($ch);
        curl_close($ch);
        try {
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($responseXml);
            $json = str_replace("{}", "null", $json);
            $responseArray = json_decode($json, true);
            $this->logMessage('response ' . $json);

            if ($responseArray) {
                return $responseArray;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    public function getPlantData()
    {
        $salesOrg = $this->getSalesOrg();
        $distribution = $this->getDistributionChannel();
        $sourceCollection = $this->sourceCollectionFactory->create()
            ->addFieldToFilter('enabled', 1)
            ->addFieldToFilter('distribution', $distribution)
            ->addFieldToFilter('sap_account_code', $salesOrg);
        return $sourceCollection->getFirstItem();
    }
    public function getSalesOrg()
    {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $sapAccountNumber = $adminUser->getCustomAttribute('z_sap_code')->getValue();
        return $sapAccountNumber;
    }
    public function getDistributionChannel()
    {
        $storeCode = $this->_storeManager->getGroup()->getCode();
        $distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code', $storeCode)->getFirstItem();
        return $distribution->getDistributionChannel();
    }
    public function checkCreditLimit()
    {
        $adminId = $this->companyHelper->getCompanyAdminId();
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $sapAccountNumber = $adminUser->getCustomAttribute('z_sap_account_number');
        $sapAccountNumber = $sapAccountNumber ? $sapAccountNumber->getValue() : '';
        if ($sapAccountNumber) {
            $sapResponse = $this->getAvaliableLimitInSap($sapAccountNumber);
            if ($sapResponse) {
                $companyId = $this->companyHelper->retrieveCompanyId();
                $availableCreditLimit = $this->creditDataProvider->get($companyId)->getAvailableLimit();
                if ($sapResponse != $availableCreditLimit) {
                    $balance = $this->creditDataProvider->get($companyId)->getBalance();
                    $creditLimit = $sapResponse - $balance;
                    $creditCollection = $this->creditLimitCollectionFactory->create()->addFieldToFilter('company_id', $companyId)->getFirstItem();
                    $creditCollection->setCreditLimit($creditLimit)->save();
                }
            }
        }
    }

    public function getAvaliableLimitInSap($sapAccountNumber)
    {
//        $creditLimitUrl = 'https://prod-33.westeurope.logic.azure.com/workflows/34e4c19da4ce442785d184a12d72f9ae/triggers/manual/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=7IAqTnUIRRy-wQoABbgOtsfbpFJk2UjPCYnn1TjDjlk';
        $creditLimitUrl = $this->scopeConfig->getValue('redington_sap/general/credit_check');
        $data = json_encode(
            [
                "IM_KUNNR" => $sapAccountNumber,
            ]
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $creditLimitUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $sapResponce = curl_exec($ch);
        curl_close($ch);
        try {
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($responseXml);
            $responseArray = json_decode($json, true);
            if ($responseArray['E_WAERS']) {
                return $responseArray['ET_CREDIT']['ZSCUST_CREDIT2']['AMOUNT'];
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    private function logMessage($message)
    {
        $filePath = '/var/log/Redington_stockCheck.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }

}
