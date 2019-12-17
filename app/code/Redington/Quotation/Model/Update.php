<?php
/**
 * Copyright � Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;

/**
 * Class is responsible for updating custom price of quote items.
 */
class Update extends \Magento\NegotiableQuote\Model\Action\Item\Price\Update
{
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     */
    public function __construct(
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->localeFormat = $localeFormat;
        $this->serializer = $serializer;
        $this->customerSession = $customerSession;
    }

    /**
     * Update custom price of quote item.
     *
     * @param CartItemInterface $item
     * @param array $priceData
     * @return void
     */
    public function update(CartItemInterface $item, array $priceData)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_ItemUpdate.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
				$logger->info('Update item price'.$item->getProposedPrice());
				$logger->info(print_r($priceData,true));
                
        try{
            $isQuoteCreated = $this->customerSession->getIsNegotiableQuoteCreated();
            if($isQuoteCreated){
                $itemPrice = $priceData['custom_price'];
                $itemPrice = $itemPrice > 0 ? $itemPrice : 0;
                $this->modifyInfoBuyRequest($item, $itemPrice);
                $item->setOriginalCustomPrice($itemPrice);
                $item->setCustomPrice($itemPrice);
                $item->setProposedPrice($itemPrice);
                $logger->info('update model create  ------->');
            }else{
                $itemPrice = $item->getProposedPrice();
                $itemPrice = $itemPrice > 0 ? $itemPrice : 0;
                $this->modifyInfoBuyRequest($item, $itemPrice);
                $item->setOriginalCustomPrice($itemPrice);
                $item->setCustomPrice($itemPrice);
                $item->setProposedPrice($itemPrice);
                $logger->info('update model update ------->');
            }
        }catch(\Exception $e) {
            $itemPrice = $item->getProposedPrice();
            $itemPrice = $itemPrice > 0 ? $itemPrice : 0;
            $this->modifyInfoBuyRequest($item, $itemPrice);
            $item->setOriginalCustomPrice($itemPrice);
            $item->setCustomPrice($itemPrice);
            $item->setProposedPrice($itemPrice);
        }
        $item->setNoDiscount(!isset($priceData['use_discount']));
    }

    /**
     * Change or remove custom price value in infoBuyRequest.
     *
     * @param CartItemInterface $item
     * @param float|int|null $customPrice
     * @return void
     */
    private function modifyInfoBuyRequest(CartItemInterface $item, $customPrice)
    {
        $infoBuyRequest = $item->getBuyRequest();
        if ($infoBuyRequest) {
          
            if ($customPrice !== null) {
                $infoBuyRequest->setCustomPrice($customPrice);
               
            } else {
                $infoBuyRequest->unsetData('custom_price');
                
            }
            $infoBuyRequest->setValue($this->serializer->serialize($infoBuyRequest->getData()));
            $infoBuyRequest->setCode('info_buyRequest');
            $infoBuyRequest->setProduct($item->getProduct());
            $item->addOption($infoBuyRequest);
        }
    }
}
