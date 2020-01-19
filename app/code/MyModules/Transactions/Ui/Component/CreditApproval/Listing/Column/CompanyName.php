<?php

namespace Redington\Transactions\Ui\Component\CreditApproval\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class CompanyName extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        array $components = [],
        array $data = []
    ) {
        $this->companyFactory = $companyFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['company_id'] is column name
                $companyId = $items['company_id'];
                try{
                    $company = $this->companyFactory->create()->load($companyId);
                    $items['company_id'] = $company->getCompanyName();
                } catch (Exception $ex) {
                    $items['company_id'] = '';
                }
            }
        }
        return $dataSource;
    }
}