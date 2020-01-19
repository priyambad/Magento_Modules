<?php
namespace Redington\CompanyLogo\Model\Address;

use Redington\CompanyLogo\Model\ResourceModel\CompanyLogo\CollectionFactory;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

	/**
     * @var array
     */
    protected $_loadedData;
    
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $newRecordCollectionFactory
     * @param array $meta
     * @param array $data
     */

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $newRecordCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,   
        array $meta = [],
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->collection = $newRecordCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }
        $items = $this->collection->getItems();

         foreach ($items as $model) {
            $this->loadedData[$model->getId()] = $model->getData();
            if ($model->getCompanyLogo()) {
                $m['icon'][0]['name'] = str_replace("logos/","",$model->getCompanyLogo());
                $m['icon'][0]['url'] = $this->getMediaUrl().$model->getCompanyLogo();

                $fullData = $this->loadedData;
                foreach ($fullData as  $value) {
                    $entityId = $value['entity_id'];
                    $this->loadedData[$model->getId()]['company'] = array_merge($model->getData(), $m);
                }
            }
        }
        return $this->loadedData;
    }
     public function getMediaUrl() {
        $mediaUrl = $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl;
    }
}