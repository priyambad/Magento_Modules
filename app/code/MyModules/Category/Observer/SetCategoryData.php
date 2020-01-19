<?php

namespace Redington\Category\Observer;

class SetCategoryData implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * construct function
     *
     * @param \Redington\Company\Helper\Data $companyHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Redington\Category\Model\CategoryFactory $redingtonCategoryFactory
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Category\Model\CategoryFactory $redingtonCategoryFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->companyHelper = $companyHelper;
        $this->_storeManager = $storeManager;
        $this->redingtonCategoryFactory = $redingtonCategoryFactory;
        $this->serialize = $serialize;
        $this->customerSession = $customerSession;
    }
    /**
     * execute function
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $companyId = $this->companyHelper->retrieveCompanyId();
            $this->logMessage('$companyId '.$companyId);
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            $this->logMessage('$websiteId '.$websiteId);
            $redingtonCategoryCollection =  $this->redingtonCategoryFactory->create()->getCollection()
                                    ->addFieldToFilter('website_id', $websiteId)
                                    ->addFieldToFilter('partner_id', $companyId);
            if (sizeof($redingtonCategoryCollection) > 0) {
                $redingtonCategory = $redingtonCategoryCollection->getFirstItem();
                $allowedCategories = $this->serialize->unserialize($redingtonCategory->getCategories());
                $allowedBrands = $this->serialize->unserialize($redingtonCategory->getBrands());
            }
            $this->customerSession->setAllowedCategories($allowedCategories);
            $this->customerSession->setAllowedBrands($allowedBrands);
        } catch (\Exception $e) {
            $this->logMessage('error '.$e->getMessage());
        }
    }
    /**
     * logMessage function
     *
     * @param [type] $message
     * @return void
     */
    private function logMessage($message)
    {
        $filePath = '/var/log/Redington_CategoryData.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message, true));
    }
}
