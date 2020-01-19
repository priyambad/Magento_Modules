<?php
 

namespace Redington\Quotation\Plugin;



/**
 * Plugin for changing current quote id in checkout session.
 */
class Decline
{
	public function __construct(
     	\Redington\Quotation\Helper\Reservation $reservedHelper,
		\Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
		$this->reservedHelper = $reservedHelper;
		$this->quoteRepository = $quoteRepository;
    }


    public function afterExecute(\Magento\NegotiableQuote\Controller\Adminhtml\Quote $subject, $result)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_QuotePlugin.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		//$logger->info('QuoteId'.print_r(get_class_methods($subject),true));
		$quoteId = $subject->getRequest()->getParam('quote_id');
		$this->reservedHelper->removeStock($quoteId,false,false);
		//$quote = $this->quoteRepository->get($quoteId, ['*']);
        return $result;
    }
}
