<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\AddressApproval\Ui\Component\ForwarderApproval;

use Redington\AddressApproval\Model\ResourceModel\ForwarderApproval\CollectionFactory;
use Redington\Transactions\Model\CreditFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
/**
 * Class DataProvider.
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var RequestInterface
     */
   
    protected $_storeManager; 


    protected $collection;
    /**
     * DataProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Auth\Session $adminSession,
        array $meta = [],
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_adminSession = $adminSession;
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName,$reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder, $meta, $data);

        
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        $adminUserDetails = array();
        $adminUserRole = array();
        $adminUserDetails = $this->_adminSession->getUser()->getData();
        $adminUserRole = $this->_adminSession->getUser()->getRole()->getData();

        $data = parent::getData();
    
       
        if($adminUserRole['role_id'] == "1")
        {
            return $data;
        }
        else
        {
            foreach ($data['items'] as $key => $value) 
            {
                if($data['items'][$key]['customer_store_id']!=$adminUserDetails['store_permission'])
                {
                    unset($data['items'][$key]);
                }
                
            }
            return $data;
        }
       
        return parent::getData();
    }

}
