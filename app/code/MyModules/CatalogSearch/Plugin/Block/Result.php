<?php

namespace Redington\CatalogSearch\Plugin\Block;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;

class Result
{
    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $catalogLayer; 
    /**
     * Get Layered Resolver 
     *
     * @param LayerResolver $layerResolver
     */
    public function __construct(
      LayerResolver $layerResolver      
    ) {

        $this->catalogLayer = $layerResolver->get();             
    }
    /**
     * Set List Order
     *
     * @param \Magento\CatalogSearch\Block\Result $subject
     * @param \Closure $proceed
     * @return void
     */
    public function aroundSetListOrders(
        \Magento\CatalogSearch\Block\Result $subject,
        \Closure $proceed       
    )
    {
        try {
            $category = $this->catalogLayer->getCurrentCategory();
            /* @var $category \Magento\Catalog\Model\Category */
            $availableOrders = $category->getAvailableSortByOptions();
            unset($availableOrders['relevance']);

            $subject->getListBlock()->setAvailableOrders(
                $availableOrders
        )->setDefaultDirection(
            'desc'
        )->setDefaultSortBy(
            $category->getDefaultSortBy()
        );

            return $subject;
        }catch(\Exception $e)   {
            $filePath = '/var/log/Redington_Catalog.log';
            $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }
    }

}
