<?php

namespace Redington\Transactions\Ui\Component\Transaction\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class CompanyUser extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        array $components = [],
        array $data = []
    ) {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['action_taken_by'] is column name
                $customerId = $items['action_taken_by'];
                try{
                    $customer = $this->_customerRepositoryInterface->getById($customerId);
                    $items['action_taken_by'] = $customer->getFirstName().' '.$customer->getLastName(); 
                }catch(\Exception $e){
                    $items['action_taken_by'] = '';
                }
            }
        }
        return $dataSource;
    }
}