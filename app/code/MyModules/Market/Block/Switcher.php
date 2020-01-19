<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Redington_Market
 */

/**
 * Store and language switcher block
 */
namespace Redington\Market\Block;

use Magento\Directory\Helper\Data;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Url\Helper\Data as UrlHelper;

/**
 * Switcher block
 *
 * @api
 * @since 100.0.2
 */
class Switcher extends \Magento\Store\Block\Switcher
{
    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var UrlHelper
     */
    private $urlHelper;
	
	/**
     * @var \Redington\Market\Helper\Data
     */
    private $redingtonMarketData;
	
	/**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param array $data
     * @param UrlHelper $urlHelper
	 * @param \Redington\Market\Helper\Data $redingtonMarketData
	 * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        array $data = [],
        UrlHelper $urlHelper = null,
		\Redington\Market\Helper\Data $redingtonMarketData,
		\Magento\Framework\App\Http\Context $httpContext
    ) {
        $this->_postDataHelper = $postDataHelper;
		$this->redingtonMarketData = $redingtonMarketData;
		$this->httpContext = $httpContext;
        parent::__construct($context, $postDataHelper, $data, $urlHelper);
    }
	public function getStoreDependsOnCompanyAdmin(){
		$companyAdminId = $this->redingtonMarketData->getCompanyAdminId();
	}
	/**
     * Get raw stores.
     *
     * @return array
     */
    public function getRawStores()
    {
        if (!$this->hasData('raw_stores')) {
            $websiteStores = $this->_storeManager->getWebsite()->getStores();
            $stores = [];
            foreach ($websiteStores as $store) {
				
                /* @var $store \Magento\Store\Model\Store */
                if (!$store->isActive()) {
                    continue;
                }
                $localeCode = $this->_scopeConfig->getValue(
                    Data::XML_PATH_DEFAULT_LOCALE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $store
                );
                $store->setLocaleCode($localeCode);
                $params = ['_query' => []];
                if (!$this->isStoreInUrl()) {
                    $params['_query']['___store'] = $store->getCode();
                }
                $baseUrl = $store->getUrl('', $params);

                $store->setHomeUrl($baseUrl);
                $stores[$store->getGroupId()][$store->getId()] = $store;
            }
            $this->setData('raw_stores', $stores);
        }
		
        return $this->getData('raw_stores');
    }

    /**
     * Retrieve list of store groups with default urls set
     *
     * @return Group[]
     */
    public function getGroups()
    {
        if (!$this->hasData('groups')) {
            $rawGroups = $this->getRawGroups();
            $rawStores = $this->getRawStores();

            $groups = [];
            $localeCode = $this->_scopeConfig->getValue(
                Data::XML_PATH_DEFAULT_LOCALE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            foreach ($rawGroups as $group) {
                /* @var $group Group */
                if (!isset($rawStores[$group->getId()])) {
                    continue;
                }
                if ($group->getId() == $this->getCurrentGroupId()) {
                    $groups[] = $group;
                    continue;
                }

                $store = $group->getDefaultStoreByLocale($localeCode);

                if ($store) {
                    $group->setHomeUrl($store->getHomeUrl());
                    $groups[] = $group;
                }
            }
            $this->setData('groups', $groups);
        }
        return $this->getData('groups');
    }
	/**
     * Checking customer login status
     *
     * @return bool
     */
    public function customerLoggedIn()
    {
		$is_login = (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return $is_login;
    }
}
