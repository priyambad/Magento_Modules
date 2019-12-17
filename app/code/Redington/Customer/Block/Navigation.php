<?php
/**
 * @author SpadeWorx Team
 * @copyright (c) 2019 Redington
 * @package Redington_Customer
 * Created @ 28-04-2019
 */

namespace Redington\Customer\Block;

use Magento\Customer\Model\Session;

/**
 * Class created for navigation menu show by User role
 */
class Navigation extends \Magento\Framework\View\Element\Template
{
    /**
     * Customer Session
     *
     * @var Magento\Customer\Model\Session
     */
    protected $customersession;

    /**
     * User Role Collection 
     *
     * @var CollectionFactory $collectionFactory 
     */
    protected $collectionFactory;
   
    /**
     * User Role  
     *
     * @var \Magento\Company\Model\RoleFactory $roleFactory 
     */
    protected $roleFactory;

    /**
     * Function for dependancy
     *
     * @param Session $customersession
     * @param CollectionFactory $CollectionFactory 
     * @param Redington\Company\Helper\Data $helper  
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Company\Model\ResourceModel\UserRole\CollectionFactory $collectionFactory,
        \Magento\Company\Model\RoleFactory $roleFactory, 
		\Redington\Company\Helper\Data $companyHelper,
		\Magento\Company\Model\CompanyFactory $companyFactory,
		\Magento\NegotiableQuote\Model\CompanyQuoteConfigFactory $companyQuoteConfigFactory,
        Session $customersession

    ) {
        $this->customersession = $customersession;
        $this->collectionFactory = $collectionFactory;
        $this->roleFactory = $roleFactory;
		$this->companyFactory = $companyFactory;
		$this->companyHelper = $companyHelper;
		$this->companyQuoteConfigFactory = $companyQuoteConfigFactory;
        parent::__construct($context);
    }
    /**
     * Used for get particular user role
     *
     * @return string
     */
    public function getUserRole()
    {
        $userRole = '';
        $customerId = $this->customersession->getCustomerId();
        $collection =$this->collectionFactory->create()
                    ->addFieldToFilter('user_id',array('eq'=> $customerId)); 
        $data = $collection->getData();
       
        try{
            if(!empty($data[0]['role_id'])) :
                $roleId = $data[0]['role_id'];
                $role = $this->roleFactory->create()->load($roleId);
                $userRole = $role->getRoleName();
            endif;
        }catch(\Exception $e){
         
        }

        return $userRole;
    }
	public function getQuotePermission(){
		$customerId = $this->customersession->getCustomerId();
		$companyId = $this->companyHelper->retrieveCompanyId();
		$companyData = $this->companyQuoteConfigFactory->create()->load($companyId);
		return $companyData->getIsQuoteEnabled();
		 
		//echo $companyData->getExtensionAttributes()->getExtendedQuoteConfig()->getIsQuoteEnabled();
		//print_r(get_class_methods($companyData->getExtensionAttributes()->getExtendedQuoteConfig()));
		//return $companyData->getExtensionAttributes()->getExtendedQuoteConfig()->getIsQuoteEnabled();
	}
}
