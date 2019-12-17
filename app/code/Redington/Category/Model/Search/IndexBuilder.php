<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Category\Model\Search;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Adapter\Mysql\ConditionManager;
use Magento\Framework\Search\Adapter\Mysql\IndexBuilderInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\ObjectManager;
use Magento\CatalogSearch\Model\Search\FilterMapper\DimensionsProcessor;
use Magento\CatalogSearch\Model\Search\SelectContainer\SelectContainer;
use Magento\CatalogSearch\Model\Search\SelectContainer\SelectContainerBuilder;
use Magento\CatalogSearch\Model\Search\BaseSelectStrategy\StrategyMapper as BaseSelectStrategyMapper;
use Magento\CatalogSearch\Model\Search\BaseSelectStrategy\BaseSelectStrategyInterface;
use Magento\CatalogSearch\Model\Search\FilterMapper\FilterMapper;

/**
 * Build base Query for Index
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @deprecated 101.0.0
 * @see \Magento\ElasticSearch
 */
class IndexBuilder extends \Magento\CatalogSearch\Model\Search\IndexBuilder
{

	/**
	  * @var \Magento\Framework\App\Config\ScopeConfigInterface
	  */
	 protected $scopeConfig;

	/**
	* @var \Magento\Store\Model\StoreManagerInterface
	*/
	protected $storeManager;
    
    /**
     * @var DimensionsProcessor
     */
    private $dimensionsProcessor;

    /**
     * @var SelectContainerBuilder
     */
    private $selectContainerBuilder;

    /**
     * @var BaseSelectStrategyMapper
     */
    private $baseSelectStrategyMapper;

    /**
     * @var FilterMapper
     */
    private $filterMapper; 

	public function __construct(
	\Magento\Store\Model\StoreManagerInterface $storeManager,
	\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
	\Magento\Catalog\Model\Product\Visibility $productVisibility,
	\Magento\Catalog\Helper\Category $categoryHelper,
	\Magento\Framework\Registry $registry,
	\Magento\Framework\App\ResourceConnection $resource,
	\Redington\Configuration\Helper\Data $helperData,
	DimensionsProcessor $dimensionsProcessor = null,
    SelectContainerBuilder $selectContainerBuilder = null,
    BaseSelectStrategyMapper $baseSelectStrategyMapper = null,
    FilterMapper $filterMapper = null
	) {
	$this->storeManager = $storeManager;
	$this->_productCollectionFactory = $productCollectionFactory; 
	$this->_productVisibility = $productVisibility;
	$this->categoryHelper = $categoryHelper;
	$this->registry = $registry;
	$this->_resource = $resource;
	$this->helperData = $helperData;
	$this->dimensionsProcessor = $dimensionsProcessor ?: ObjectManager::getInstance()
            ->get(DimensionsProcessor::class);

    $this->selectContainerBuilder = $selectContainerBuilder ?: ObjectManager::getInstance()
            ->get(SelectContainerBuilder::class);

    $this->baseSelectStrategyMapper = $baseSelectStrategyMapper ?: ObjectManager::getInstance()
            ->get(BaseSelectStrategyMapper::class);

    $this->filterMapper = $filterMapper ?: ObjectManager::getInstance()
            ->get(FilterMapper::class);

	
	}

    /**
     * Build index query
     *
     * @param RequestInterface $request
     * @return Select
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(RequestInterface $request)
    {
     
         /** @var SelectContainer $selectContainer */
        $storeId = $this->storeManager->getStore()->getStoreId();
		$rootCatId = $this->storeManager->getStore($storeId)->getRootCategoryId();
		$productUniqueIds = $this->getCustomCollectionQuery();

		
        $selectContainer = $this->selectContainerBuilder->buildByRequest($request);
        
        /** @var BaseSelectStrategyInterface $baseSelectStrategy */
        $baseSelectStrategy = $this->baseSelectStrategyMapper->mapSelectContainerToStrategy($selectContainer);
		
         
        $selectContainer = $baseSelectStrategy->createBaseSelect($selectContainer);

        
        $selectContainer = $this->filterMapper->applyFilters($selectContainer);

        $selectContainer = $this->dimensionsProcessor->processDimensions($selectContainer);
      
        
        return $selectContainer->getSelect();
    }
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
