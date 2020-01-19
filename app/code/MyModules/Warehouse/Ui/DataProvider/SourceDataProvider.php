<?php

namespace Redington\Warehouse\Ui\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\InventoryApi\Api\Data\SourceInterface;

class SourceDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider {
    const SOURCE_FORM_NAME = 'inventory_source_form_data_source';
    public function __construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            \Magento\Framework\Api\Search\ReportingInterface $reporting,
            \Magento\Framework\Api\Search\SearchCriteriaBuilder $searchCriteriaBuilder,
            \Magento\Framework\App\RequestInterface $request,
            \Magento\Framework\Api\FilterBuilder $filterBuilder,
            \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
            \Magento\Inventory\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
            \Magento\Ui\DataProvider\SearchResultFactory $searchResultFactory,
            \Magento\Backend\Model\Session $session,
            array $meta = array(),
            array $data = array()
        ) {
        $this->sourceRepository = $sourceRepository;
        $this->session = $session;
        $this->collection = $sourceCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
    public function getData()
    {
        $data = parent::getData();
        if (self::SOURCE_FORM_NAME === $this->name) {
            // It is need for support of several fieldsets.
            // For details see \Magento\Ui\Component\Form::getDataSourceData
            if ($data['totalRecords'] > 0) {
                $sourceCode = $data['items'][0][SourceInterface::SOURCE_CODE];
                
                $sourceGeneralData = $data['items'][0];
                $sourceGeneralData['disable_source_code'] = !empty($sourceGeneralData['source_code']);

//                Fetch custom fields data and set in data provider
                $source = $this->sourceRepository->get($sourceCode);
                $sourceGeneralData['sap_account_code'] = $source->getSapAccountCode();
                $sourceGeneralData['distribution'] = $source->getDistribution();
                
                $dataForSingle[$sourceCode] = [
                    'general' => $sourceGeneralData,
                ];
                return $dataForSingle;
            }
            $sessionData = $this->session->getSourceFormData(true);
            if (null !== $sessionData) {
                // For details see \Magento\Ui\Component\Form::getDataSourceData
                $data = [
                    '' => $sessionData,
                ];
            }
        }
        return $data;
    }
}