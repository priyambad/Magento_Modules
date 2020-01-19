<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */

namespace Redington\Quotation\Cron\CartReservation;

use Plumrocket\CartReservation\Model\Config\Source\EndAction;

class QuoteRemoveItem extends \Plumrocket\CartReservation\Cron\QuoteRemoveItem
{    
    /**
     * @param \Plumrocket\CartReservation\Helper\Data                    $dataHelper
     * @param \Plumrocket\CartReservation\Helper\Config                  $configHelper
     * @param \Plumrocket\CartReservation\Helper\Item                    $itemHelper
     * @param \Magento\Framework\App\ResourceConnection                  $resource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                $dateTime
     * @param \Magento\Checkout\Model\CartFactory                        $cartFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     */
    public function __construct(
        \Plumrocket\CartReservation\Helper\Data $dataHelper,
        \Plumrocket\CartReservation\Helper\Config $configHelper,
        \Plumrocket\CartReservation\Helper\Item $itemHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Checkout\Model\CartFactory $cartFactory,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory $negotiableQuoteCollection
    ) {
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->itemHelper = $itemHelper;
        $this->resource = $resource;
        $this->dateTime = $dateTime;
        $this->cartFactory = $cartFactory;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->negotiableQuoteCollection = $negotiableQuoteCollection;
        parent::__construct($dataHelper, $configHelper, $itemHelper, $resource, $dateTime, $cartFactory, $quoteCollectionFactory);
        }

    /**
     * Remove quote items
     *
     * @return void
     */
    public function execute()
    {   $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_Quote.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('In overrided file');
        if (! $quoteIds = $this->getExpiredQuotes()) {
            return;
        }

        if ($this->configHelper->getCartEndAction() != EndAction::REMOVE_ITEM) {
            return $result->setData($data);
        }

        $quotes = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $quoteIds]);
        /*Redington Quotation - redington quotation and prepare quuotes array */
        $nQuotes = $this->negotiableQuoteCollection->create()
                  ->addFieldToFilter('entity_id', ['in' => $quoteIds]);        
        $nQuoteArray = [];
        foreach ($nQuotes as $nQuote) {
            $nQuoteArray[] = $nQuote->getId();
        }
        
        foreach ($quotes as $quote) {
            /*Redington Quotation - stop if quote is in redington quotation */
            if(in_array($quote->getId(), $nQuoteArray)) {
                continue;                
            }
            
            $hasRemoved = false;
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                // Stop if item is not expired.
                if ($item->getData('timer_expire_at') > $this->dateTime->gmtTimestamp()
                    || $this->itemHelper->getReservationStatus($item)
                ) {
                    continue;
                }

                $quote->removeItem($item->getId());
                $hasRemoved = true;
            }

            if ($hasRemoved) {
                $quote->getBillingAddress();
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->collectTotals();
                $quote->save();
            }
        }
    }    
}
