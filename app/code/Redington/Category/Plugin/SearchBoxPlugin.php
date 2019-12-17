<?php

namespace Redington\Category\Plugin;

class SearchBoxPlugin
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
     * afterGetCategories function
     *
     * @param \Sm\SearchBox\Block\SearchBox $subject
     * @param [type] $result
     * @return void
     */
    public function afterGetCategories(\Sm\SearchBox\Block\SearchBox $subject, $result)
    {
        try {
            if ($this->customerSession->isLoggedIn()) {
                $allowedCategories = $this->customerSession->getAllowedCategories();
            } else {
                return $result;
            }
            foreach ($result as $key => $value) {
                if (!(in_array($value['value'], $this->customerSession->getAllowedCategories()))) {
                    unset($result[$key]);
                }
            }
            return $result;
        } catch (\Exception $e) {
            return $result;
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/searchbox_filter.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info("error".$e->getMessage());
        }
    }
}
