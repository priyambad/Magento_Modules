<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */
namespace Redington\Quotation\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\NegotiableQuote\Model\Status\LabelProviderInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

/**
 * Class Status
 */
class Status extends \Magento\NegotiableQuote\Ui\Component\Listing\Column\Status
{    
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['status']) && isset($item['status'])) {
                    $orginalValue = $item[$fieldName];
                    $item[$fieldName . '_original'] = $item[$fieldName];
                    $item[$fieldName] = $this->setStatusLabel($item[$fieldName]);
                    if(true) {
                       $item[$fieldName] = html_entity_decode('<span class="quote-status order-width-status order-status-'.$item[$fieldName].'">'.$item[$fieldName].'</span>'); 
                    } else if($orginalValue == NegotiableQuoteInterface::STATUS_EXPIRED || $orginalValue == NegotiableQuoteInterface::STATUS_DECLINED || $orginalValue == NegotiableQuoteInterface::STATUS_CLOSED) {
                       $item[$fieldName] = html_entity_decode('<span class="red-status">'.$item[$fieldName].'</span>'); 
                    } else if($orginalValue == NegotiableQuoteInterface::STATUS_ORDERED) {
                       $item[$fieldName] = html_entity_decode('<span class="green-status">'.$item[$fieldName].'</span>'); 
                    } else if($orginalValue == NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN || $orginalValue == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN || $orginalValue == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER) {
                       $item[$fieldName] = html_entity_decode('<span class="orange-status">'.$item[$fieldName].'</span>'); 
                    }
                }
            }
        }

        return $dataSource;
    }
}
