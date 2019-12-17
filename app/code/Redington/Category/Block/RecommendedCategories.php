<?php
/*------------------------------------------------------------------------
# SM Categories - Version 3.2.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Redington\Categories\Block;

class RecommendedCategories  extends \Magento\Catalog\Block\Product\AbstractProduct
{
	protected $_config = null;
	
	protected $_categoryFactory;
    protected $_categoryHelper;
    protected $_categoryRepository;
	protected $_storeManager;
    protected $_scopeConfig;
	protected $_storeId;
	protected $_storeCode;
        
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,        
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,        
        array $data = [],
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		$attr = null
    )
    {
		$this->_categoryFactory = $categoryFactory; 
		$this->_categoryHelper = $categoryHelper;
		$this->_categoryRepository = $categoryRepository;
		$this->_storeManager = $context->getStoreManager();
        $this->_scopeConfig = $context->getScopeConfig();
		$this->_storeId=(int)$this->_storeManager->getStore()->getId();
		$this->_storeCode=$this->_storeManager->getStore()->getCode();
			
		$this->_productCollectionFactory=$productCollectionFactory;
        parent::__construct($context, $data);
    }

	

	public function _getList()
	{
		$collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->setPageSize(8); 

        return $collection;
	}
	
}