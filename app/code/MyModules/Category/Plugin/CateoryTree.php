<?php

namespace Redington\Category\Plugin;

class CateoryTree
{
  

    /**
     * aftertoOptionArray function
     *
     * @param \Magento\Catalog\Ui\Component\Product\Form\Categories\Options $subject
     * @param [array] $result
     * @return void
     */
    public function aftertoOptionArray(\Magento\Catalog\Ui\Component\Product\Form\Categories\Options $subject,
            $result)
    {
   
    	$categoriesArray = array();
    	
    	
    	foreach ($result as $key => $value) {
    
    		if($value['value'] == '2' || $value['value'] == '3')
    		{
    			
    			if(empty($categoriesArray))
    			{
    				$categoriesArray= $value['optgroup'];
    			}
    			else
    			{
    				array_push($categoriesArray,$value['optgroup']);
    				
    			}

    		} 
    		
    	} 
    	
    	return $result;
    }
}