<?php

namespace Redington\Category\Plugin;

class ListingTabsPlugin
{
    /**
     * construct function
     *
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * before_getList function
     *
     * @param \Sm\ListingTabs\Block\ListingTabs $subject
     * @return void
     */
    public function before_getList(\Sm\ListingTabs\Block\ListingTabs $subject)
    {
        try {
            if ($this->customerSession->isLoggedIn()) {
                $category_id = $subject->_getConfig('category_tabs');
                $catids = explode(',', $category_id);
                $allowedCat = $this->customerSession->getAllowedCategories();
                if($allowedCat){
                    $matchCatIds = array_intersect($catids, $allowedCat);
                    $matchcat_Ids = implode(',', $matchCatIds);
                    $subject->_setConfig('category_tabs', $matchcat_Ids);
                }
            } else{
                $matchcat_Ids = '';
                $subject->_setConfig('category_tabs', $matchcat_Ids);
            }
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/category_filter.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info("error".$e->getMessage);
        }
    }
}
