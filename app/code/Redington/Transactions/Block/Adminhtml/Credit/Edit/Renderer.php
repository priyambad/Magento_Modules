<?php

namespace Redington\Transactions\Block\Adminhtml\Credit\Edit;

class Renderer extends \Magento\Framework\Data\Form\Element\AbstractElement {
    public function __construct(
            \Magento\Framework\Data\Form\Element\Factory $factoryElement,
            \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
            \Magento\Framework\Escaper $escaper,
            \Magento\Framework\Registry $registry,
            \Redington\Transactions\Model\CreditFactory $creditFactory,
            \Magento\Framework\Serialize\Serializer\Serialize $serialize,
            $data = array()) {
        $this->creditFactory = $creditFactory;
        $this->_coreRegistry = $registry;
        $this->serialize = $serialize;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }
    public function getApprovalData() {
        $creditId = $this->_coreRegistry->registry('credit_request_id');
        $creditApproval = $this->creditFactory->create()->load($creditId);
        return $creditApproval->getDocuments();
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
                            <div class="doc-imgpreview"><a href ='.$finalurl.' target="blank"><img height="50" width="50" src="https://blobarmstrongdev01.blob.core.windows.net/magento/admin-file-icon.png"></a></div>
                        </div>';
                }
            }
        }else {
            $customHtml .='<div class="address-docpreview"><p class="no-docs">No Documents Uploaded</p></div>';
        }
        
        return $customHtml;
    }
}

