<?php

namespace Redington\AddressApproval\Ui\Component\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Partner extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Redington\AddressApproval\Helper\Data $addressHelper,
        array $data = []
    ) {
        $this->companyFactory = $companyFactory;
        $this->addressHelper = $addressHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['company_id'] is column name
                try{
                    $items['company_id'] = $this->companyFactory->create()->load($items['company_id'])->getCompanyName();
                }catch(\Exception $e){
                    $this->addressHelper->logMessage('could not get company name '.$e->getMessage());
                    $items['company_id'] = '';
                }
            }
        }
        return $dataSource;
    }
}