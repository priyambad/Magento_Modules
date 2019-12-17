<?php

namespace Redington\Category\Plugin;

use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Search\Request\Filter\BoolExpression;
use Magento\Framework\Search\Request\Query\Filter;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;
use Magento\Framework\App\ResourceConnection;

class IndexBuilder
{
/**
* @var \Magento\Framework\App\Config\ScopeConfigInterface
*/
protected $scopeConfig;

/**
* @var \Magento\Store\Model\StoreManagerInterface
*/
protected $storeManager;


public function __construct(
\Magento\Store\Model\StoreManagerInterface $storeManager,
\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
\Magento\Catalog\Model\Product\Visibility $productVisibility,
\Magento\Catalog\Helper\Category $categoryHelper,
\Magento\Framework\Registry $registry,
\Magento\Framework\App\ResourceConnection $resource,
\Redington\Configuration\Helper\Data $helperData
) {
$this->storeManager = $storeManager;
$this->_productCollectionFactory = $productCollectionFactory; 
$this->_productVisibility = $productVisibility;
$this->categoryHelper = $categoryHelper;
$this->registry = $registry;
$this->_resource = $resource;
$this->helperData = $helperData;
}

/**
* Build index query
*
* @param $subject
* @param callable $proceed
* @param RequestInterface $request
* @return Select
*/
	public function aroundBuild($subject, callable $proceed, RequestInterface $request)
	{
		
		$select = $proceed($request);
		$storeId = $this->storeManager->getStore()->getStoreId();
		$rootCatId = $this->storeManager->getStore($storeId)->getRootCategoryId();
		$productUniqueIds = $this->getCustomCollectionQuery();
		$select->where('search_index.entity_id IN (' . join(',', $productUniqueIds) . ')');

		return $select;
	}

/**
*
* @return ProductIds[]
*/
	public function getCustomCollectionQuery() {
	
			$currentStoreAllCategories = $this->categoryHelper->getStoreCategories(false,true,true);
			$collection = $this->_productCollectionFactory->create();
			$collection->addAttributeToSelect(array('entity_id','sku'));
			$collection->setVisibility($this->_productVisibility->getVisibleInSiteIds());

			$tableName = 'x_'.uniqid();
			$plantCode = $this->helperData->getPlantData()->getPlantCode();
			  if ($plantCode) {
                $condition = $tableName.'.source_code = '.$plantCode;
            } else {
                $condition = $tableName.'.source_code = NULL';
            }
            $connection  = $this->_resource->getConnection();
            $collection->getSelect()->joinLeft(
            [$tableName => $connection->getTableName($this->_resource->getTableName('inventory_source_item'))],
            $tableName.'.sku = e.sku',
            []
            )->where(
                $condition
            );
			
			$getProductAllIds = $collection->getAllIds();
			$getProductUniqueIds = array_unique($getProductAllIds);
			

			return $getProductUniqueIds;
	}

}