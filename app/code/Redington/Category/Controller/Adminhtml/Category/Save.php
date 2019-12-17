<?php 
namespace Redington\Category\Controller\Adminhtml\Category;
class Save extends \Magento\Backend\App\Action
{

     /**
     * Undocumented function
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection
     * @param \Redington\Category\Model\CategoryFactory $redingtonCategoryFactory
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     */
	  public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
        \Redington\Category\Model\CategoryFactory $redingtonCategoryFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize
        
    ) {

        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_request = $request;
        $this->categoryCollection = $categoryCollection;
        $this->redingtonCategoryFactory = $redingtonCategoryFactory;
        $this->serialize = $serialize;
    }
     public function execute() {

        $categories = $this->categoryCollection->create();   
        $categories->addAttributeToSelect('*');     
        $categories->addAttributeToFilter('level' , 3); 
        $this->_request->getParams();

        if(!empty($this->_request->getParam('categories'))){
            $CategoryIds = $result=array_intersect($categories->getColumnValues('entity_id'),$this->_request->getParam('categories'));
            $brands = array_diff($this->_request->getParam('categories'),$CategoryIds);
            $currentCustomerEntityId = $this->_request->getParam('entity_id');

            $redingtonCategory = $this->redingtonCategoryFactory->create()->load($currentCustomerEntityId);
            $redingtonCategory->setCategories($this->serialize->serialize($CategoryIds));
            $redingtonCategory->setBrands($this->serialize->serialize($brands));
            $redingtonCategory->save();
        }

        $this->_redirect('category/category/index');
    
    }
}
?>