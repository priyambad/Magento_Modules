<?php
namespace Redington\Category\Block\Navigation;
use Magento\Swatches\Block\LayeredNavigation\RenderLayered;

/**
 * Class SwatchRenderer
 * @package Redington\Category\Block\Navigation
 */
class SwatchRenderer extends RenderLayered
{

	  public function getSwatchData()
      {

	        $attributeOptions = [];
	        $colorValueArray = [];
	        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
	        $customerSession = $objectManager->get('Magento\Customer\Model\Session');

	        foreach($customerSession->getEntityId() as $value)
	        {
	            $_product = $objectManager->get('Magento\Catalog\Model\Product')->load($value);
	            $colorValueArray[] = $_product->getData('color');
	        }

	        foreach ($this->eavAttribute->getOptions() as $option) {
	          foreach($option as $value)
	             {
					
					$url = $_SERVER['REQUEST_URI'];
					//echo '<pre>'; print_r($value);exit;
					if (strpos($url,'color=') !== false) {
						 if ($currentOption = $this->getFilterOption($this->filter->getItems(), $option)) {
		                      $attributeOptions[$option->getValue()] = $currentOption;
		                } elseif ($this->isShowEmptyResults()) {
		                    $attributeOptions[$option->getValue()] = '';
		                } 
					}else if(in_array($value['value'], $colorValueArray)){
		                if ($currentOption = $this->getFilterOption($this->filter->getItems(), $option)) {
		                      $attributeOptions[$option->getValue()] = $currentOption;
		                } elseif ($this->isShowEmptyResults()) {
		                    $attributeOptions[$option->getValue()] = '';
		                }  
	                }
	            }
	        }
	        
	        $attributeOptionIds = array_keys($attributeOptions);
	        $swatches = $this->swatchHelper->getSwatchesByOptionsId($attributeOptionIds);

	        $data = [
	            'attribute_id' => $this->eavAttribute->getId(),
	            'attribute_code' => $this->eavAttribute->getAttributeCode(),
	            'attribute_label' => $this->eavAttribute->getStoreLabel(),
	            'options' => $attributeOptions,
	            'swatches' => $swatches,
	        ];
	      
	   
	        return $data;
    }

}