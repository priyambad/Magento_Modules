<?php

namespace Redington\AddressApproval\Block\Adminhtml\AddressApproval\Edit;

class Renderer extends \Magento\Framework\Data\Form\Element\AbstractElement {
    public function __construct(
            \Magento\Framework\Data\Form\Element\Factory $factoryElement,
            \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
            \Magento\Framework\Escaper $escaper,
            \Magento\Framework\Registry $registry,
            \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
            \Magento\Framework\Serialize\Serializer\Serialize $serialize,
            $data = array()) {
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->_coreRegistry = $registry;
        $this->serialize = $serialize;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }
    public function getApprovalData() {
        $model = $this->_coreRegistry->registry('address_approval');
        $addressId = $model->getId();
        $addressApproval = $this->addressApprovalFactory->create();
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id',$addressId);
        if($existInQueue->count() > 0) {
            $addressApproval = $existInQueue->getFirstItem();
            return $addressApproval->getPendingDocuments();
        }
        return false;
    }
    public function getAfterElementHtml()
    {
        $customHtml = '';
        if($this->getApprovalData()){
            $documents = $this->serialize->unserialize($this->getApprovalData());
            foreach ($documents as $key => $document) {
                
                if($document['docUrl'] !='') {
                    $replaceString=" ";
                    $replacedString="%20";
                    $url=$document['docUrl'];
                    $finalurl=str_replace($replaceString,$replacedString,$url);
                    $customHtml .= '<div class="address-docpreview">
                            <div class="doc-name">'.$document['documentName'].'</div>
                            <div class="doc-imgpreview"><a href ='.$finalurl.' target="blank"><img height="70" width="55" src="https://blobarmstrongdev01.blob.core.windows.net/magento/admin-file-icon.png"></a></div>
                        </div>';
                }
            }
        }else {
            $customHtml .='<div class="address-docpreview"><p class="no-docs">No Documents Uploaded</p></div>';
        }
        
        return $customHtml;
    }
}

