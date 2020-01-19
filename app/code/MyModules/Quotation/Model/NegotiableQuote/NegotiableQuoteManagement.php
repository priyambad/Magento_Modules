<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */

namespace Redington\Quotation\Model\NegotiableQuote;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Model\Email\Sender;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;

class NegotiableQuoteManagement extends \Magento\NegotiableQuote\Model\NegotiableQuoteManagement
{
    /**
     * 
     * @param CartRepositoryInterface $quoteRepository
     * @param Sender $emailSender
     * @param \Magento\NegotiableQuote\Model\CommentManagementInterface $commentManagement
     * @param NegotiableQuoteItemManagementInterface $quoteItemManagement
     * @param \Magento\NegotiableQuote\Model\NegotiableQuoteConverter $negotiableQuoteConverter
     * @param \Magento\NegotiableQuote\Model\QuoteUpdater $quoteUpdater
     * @param \Magento\NegotiableQuote\Model\Quote\History $quoteHistory
     * @param ValidatorInterfaceFactory $validatorFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        Sender $emailSender,
        \Magento\NegotiableQuote\Model\CommentManagementInterface $commentManagement,
        NegotiableQuoteItemManagementInterface $quoteItemManagement,
        \Magento\NegotiableQuote\Model\NegotiableQuoteConverter $negotiableQuoteConverter,
        \Magento\NegotiableQuote\Model\QuoteUpdater $quoteUpdater,
        \Magento\NegotiableQuote\Model\Quote\History $quoteHistory,
        ValidatorInterfaceFactory $validatorFactory,
		\Redington\Quotation\Helper\Reservation $reservedHelper
    ) {
        $this->quoteRepository = $quoteRepository;
		$this->emailSender = $emailSender;
		$this->commentManagement = $commentManagement;
		$this->quoteItemManagement = $quoteItemManagement;
		$this->negotiableQuoteConverter = $negotiableQuoteConverter;
		$this->quoteUpdater = $quoteUpdater;
		$this->quoteHistory= $quoteHistory;
		$this->validatorFactory = $validatorFactory;
		$this->reservedHelper = $reservedHelper;
    }
    
   /**
     * {@inheritdoc}
     */
    public function close($quoteId, $force = false)
    {
        $quote = $this->getNegotiableQuote($quoteId);

        $validator = $this->validatorFactory->create(['action' => 'close']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if ((!$validateResult->hasMessages() || $force)
            && !in_array(
                $quote->getExtensionAttributes()->getNegotiableQuote()->getStatus(),
                [NegotiableQuoteInterface::STATUS_CLOSED, NegotiableQuoteInterface::STATUS_ORDERED]
            )
        ) {
            $quote->getExtensionAttributes()
                ->getNegotiableQuote()
                ->setStatus(NegotiableQuoteInterface::STATUS_CLOSED);
            $this->quoteHistory->closeLog($quoteId);
            $this->updateSnapshotQuote($quoteId);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function openByMerchant($quoteId)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        $validator = $this->validatorFactory->create(['action' => 'edit']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if ($validateResult->hasMessages()) {
            return false;
        }
        $oldData = $this->quoteHistory->collectOldDataFromQuote($quote);
        if (in_array(
            $negotiableQuote->getStatus(),
            [NegotiableQuoteInterface::STATUS_CREATED, NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER]
        )) {
            $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN);
            $quote->getExtensionAttributes()
                ->setNegotiableQuote($negotiableQuote);
            $quote->collectTotals();
            $this->quoteHistory->updateStatusLog($quoteId, true);
            $this->updateSnapshotQuote($quoteId);
        }
        $updatePrice = $negotiableQuote->getNegotiatedPriceValue() === null;
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, $updatePrice, $updatePrice);
        $quote = $this->getNegotiableQuote($quoteId);
        $this->quoteHistory->checkPricesAndDiscounts($quote, $oldData);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function send($quoteId, $commentText = '', array $files = [])
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'send']);
        $validateResult = $validator->validate(['quote' => $quote, 'files' => $files]);
        if ($validateResult->hasMessages()) {
            return false;
        }
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($negotiableQuote->getStatus() == NegotiableQuoteInterface::STATUS_EXPIRED) {
            $negotiableQuote->setExpirationPeriod(null);
        }
        $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
        $this->commentManagement->update(
            $quoteId,
            $commentText,
            $files
        );
        //$this->quoteHistory->updateLog($quoteId);
        $this->emailSender->sendChangeQuoteEmailToMerchant(
            $quote,
            Sender::XML_PATH_SELLER_QUOTE_UPDATED_BY_BUYER_TEMPLATE
        );
        $this->updateSnapshotQuote($quoteId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function adminSend($quoteId, $commentText = '', array $files = [])
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'send']);
        $validateResult = $validator->validate(['quote' => $quote, 'files' => $files]);
        if ($validateResult->hasMessages()) {
            $exception = new InputException(__('Cannot send a B2B quote.'));
            foreach ($validateResult->getMessages() as $message) {
                $exception->addError($message);
            }
            throw $exception;
        }
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        $negotiableQuote->setHasUnconfirmedChanges(false)
            ->setIsCustomerPriceChanged(false)
            ->setIsShippingTaxChanged(false);
        $result = $this->save($quoteId, [], NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
        $this->commentManagement->update(
            $quoteId,
            $commentText,
            $files
        );
        //$this->quoteHistory->updateLog($quoteId, true);
        $quote = $this->getNegotiableQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        if ($negotiableQuote->getNegotiatedPriceValue() !== null) {
            $this->quoteHistory->removeFrontMessage($negotiableQuote);
        }
        $this->updateSnapshotQuote($quoteId);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function updateProcessingByCustomerQuoteStatus($quoteId, $needSave = true)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        $quoteStatus = $negotiableQuote->getStatus();
        $validator = $this->validatorFactory->create(['action' => 'edit']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if (!$validateResult->hasMessages()) {
            if ($quoteStatus !== NegotiableQuoteInterface::STATUS_CREATED) {
                $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER);
                $this->quoteHistory->updateStatusLog($quoteId, false);
                $this->updateSnapshotQuoteStatus($quoteId, NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER);
            }

            if ($needSave) {
                $this->quoteRepository->save($quote);
            }
        }

        return $negotiableQuote->getStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function saveAsDraft($quoteId, array $quoteData, array $commentData = [])
    {
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_orderQuote.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer);
				$logger->info('QuoteData'.print_r($quoteData,true));
        $this->save($quoteId, $quoteData);

        if (!empty($commentData) && $this->getNegotiableQuote($quoteId)) {
            $this->commentManagement->update(
                $quoteId,
                $commentData['message'] ?? null,
                $this->commentManagement->getFilesNamesList(
                    $commentData['files'] ?? []
                ),
                false,
                true
            );
        }

        return $this;
    }

    /**
     * Save quote id with provided data.
     *
     * @param int $quoteId
     * @param array $data [
     *      'items' => [] array of quote items,
     *      'addItems' => [] add new items to quote,
     *      'configuredSkus' => [] configured products,
     *      'recalcPrice' => bool flag that triggers quote recalculation
     *  ]
     * @param string $status [optional]
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function save($quoteId, array $data, $status = '')
    {
        $quote = $this->getNegotiableQuote($quoteId);
		$quotedata = $this->quoteRepository->get($quoteId);
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_orderQuote.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer);
				//$logger->info('QuoteData'.print_r(get_class_methods($quotedata),true));
		$quoteItems = $quotedata->getAllVisibleItems();
		$proposed = array();
		foreach($quoteItems as $item):
			$proposed[$item->getId()] = $item->getProposedPrice();	
			$logger->info('Item Price'.print_r($item->getProposedPrice(),true));
		endforeach;
		$this->quoteUpdater->updateQuote($quoteId, $data);
        if ($status) {
            $negotiableQuote = $this->retrieveNegotiableQuote($quote);
            $negotiableQuote->setHasUnconfirmedChanges(true);
            $negotiableQuote->setIsCustomerPriceChanged(true);
            $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
        }
        $this->quoteRepository->save($quote);
        if ($status == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN) {
            $this->emailSender->sendChangeQuoteEmailToBuyer(
                $quote,
                Sender::XML_PATH_BUYER_QUOTE_UPDATED_BY_SELLER_TEMPLATE
            );
        }
		foreach($quoteItems as $item):
			$item->setProposedPrice($proposed[$item->getId()]);	
			$item->setCustomPrice($proposed[$item->getId()]);
			$item->save();
			$logger->info('itemData'.print_r($proposed,true));
			$logger->info('Item Methods'.print_r(get_class_methods($item),true));
		endforeach;
		$quote->getItemsCollection();
		$quote->collectTotals();
        $quote->setTotalsCollectedFlag(false);
        $quote->save();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create($quoteId, $quoteName, $commentText = '', array $files = [])
    {
        $quote = $this->retrieveQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'create']);
        $validateResult = $validator->validate(['quote' => $quote, 'files' => $files]);
        if ($validateResult->hasMessages()) {
            $exception = new InputException(__('Cannot create a B2B quote.'));
            foreach ($validateResult->getMessages() as $message) {
                $exception->addError($message);
            }
            throw $exception;
        }
        $this->removeCartDiscounts($quote);
        $quote->collectTotals();

        $this->quoteUpdater->updateCurrentDate($quote);
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $negotiableQuote->setQuoteId($quote->getId())
            ->setIsRegularQuote(true)
            ->setAppliedRuleIds($quote->getAppliedRuleIds())
            ->setStatus(NegotiableQuoteInterface::STATUS_CREATED)
            ->setQuoteName($quoteName);
        $this->quoteRepository->save($quote);
        $this->quoteItemManagement->updateQuoteItemsCustomPrices($quoteId);
        $this->commentManagement->update(
            $quoteId,
            $commentText,
            $files
        );
        $this->quoteHistory->createLog($quoteId);
        $this->emailSender->sendChangeQuoteEmailToMerchant(
            $quote,
            Sender::XML_PATH_SELLER_NEW_QUOTE_CREATED_BY_BUYER_TEMPLATE
        );
        return true;
    }

    /**
     * Remove cart discounts on negotiable quote.
     *
     * @param CartInterface $quote
     * @return $this
     */
    private function removeCartDiscounts(CartInterface $quote)
    {
        if ($quote->getGiftCards() !== null) {
            $quote->setGiftCards(null);
        }

        if ($quote->getCouponCode() !== null) {
            $quote->setCouponCode(null);
        }

        return $this;
    }

    /**
     * Updates data of snapshot quote.
     *
     * @param int $quoteId
     * @return $this
     */
    private function updateSnapshotQuote($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId, ['*']);
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $negotiableQuote->setSnapshot(json_encode($this->negotiableQuoteConverter->quoteToArray($quote)));
        $this->quoteRepository->save($quote);

        return $this;
    }

    /**
     * Updates status in quote snapshot.
     *
     * @param int $quoteId
     * @param string $status
     * @return $this
     */
    private function updateSnapshotQuoteStatus($quoteId, $status)
    {
        $quote = $this->quoteRepository->get($quoteId, ['*']);
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $snapshot = json_decode($negotiableQuote->getSnapshot(), true);
        $snapshot['negotiable_quote'][NegotiableQuoteInterface::QUOTE_STATUS] = $status;
        $negotiableQuote->setSnapshot(json_encode($snapshot));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSnapshotQuote($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId, ['*']);
        $quoteExtensionAttributes = $quote->getExtensionAttributes();
        $negotiableQuote = $quoteExtensionAttributes->getNegotiableQuote();

        $snapshot = json_decode($negotiableQuote->getSnapshot(), true);
        if (is_array($snapshot)) {
            $quote = $this->negotiableQuoteConverter->arrayToQuote($snapshot);
        }

        return $quote;
    }

    /**
     * {@inheritdoc}
     */
    public function decline($quoteId, $reason)
    {
		$this->reservedHelper->removeStock($quoteId,false,false);
        $quote = $this->getNegotiableQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'decline']);
        $validateResult = $validator->validate(['quote' => $quote]);
		
       /* if ($validateResult->hasMessages()) {
            $exception = new InputException();
            foreach ($validateResult->getMessages() as $message) {
                $exception->addError($message);
            }
            throw $exception;
        }*/

        $oldData = $this->quoteHistory->collectOldDataFromQuote($quote);
        $quote->getExtensionAttributes()
            ->getNegotiableQuote()
            ->setStatus(NegotiableQuoteInterface::STATUS_DECLINED)
            ->setIsCustomerPriceChanged(false)
            ->setHasUnconfirmedChanges(false)
            ->setIsShippingTaxChanged(false);
        $this->resetCustomPrice($quote);
        $quote->getShippingAddress()
            ->setShippingMethod(null)
            ->setShippingDescription(null);
        if ($quote->getExtensionAttributes()->getShippingAssignments()) {
            foreach ($quote->getExtensionAttributes()->getShippingAssignments() as $shippingAssignment) {
                $shippingAssignment->getShipping()->setMethod(null);
            }
        }
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true);
        $this->commentManagement->update(
            $quoteId,
            $reason,
            [],
            true
        );
        //$this->quoteHistory->updateLog($quoteId, true, NegotiableQuoteInterface::STATUS_DECLINED);
        $this->emailSender->sendChangeQuoteEmailToBuyer(
            $quote,
            Sender::XML_PATH_BUYER_QUOTE_DECLINED_BY_SELLER_TEMPLATE,
            $reason
        );
        $this->updateSnapshotQuote($quoteId);
        $this->quoteHistory->checkPricesAndDiscounts($quote, $oldData);
        $this->quoteHistory->removeAdminMessage($quote->getExtensionAttributes()->getNegotiableQuote());

        return true;
    }
	
	

    /**
     * {@inheritdoc}
     */
    public function order($quoteId)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'checkout']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if ($validateResult->hasMessages()) {
            return false;
        }

        $quote->getExtensionAttributes()
            ->getNegotiableQuote()
            ->setStatus(NegotiableQuoteInterface::STATUS_ORDERED);
        $this->updateSnapshotQuoteStatus($quoteId, NegotiableQuoteInterface::STATUS_ORDERED);
        $this->quoteRepository->save($quote);
        //$this->quoteHistory->updateLog($quoteId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeQuoteItem($quoteId, $itemId)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $oldData = $this->quoteHistory->collectOldDataFromQuote($quote);

        $validator = $this->validatorFactory->create(['action' => 'edit']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if ($validateResult->hasMessages()) {
            return false;
        }

        $quote->removeItem($itemId);
        $this->setIsCustomerPriceChanged($quote);
        $this->setHasChangesInNegotiableQuote($quote);
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true);
        $this->quoteHistory->checkPricesAndDiscounts($quote, $oldData);
        $this->updateProcessingByCustomerQuoteStatus($quoteId);

        return true;
    }

    /**
     * Retrieve negotiable quote from regular quote.
     *
     * @param CartInterface $quote
     * @return NegotiableQuoteInterface|null
     */
    private function retrieveNegotiableQuote(CartInterface $quote)
    {
        $negotiableQuote = null;

        if ($quote->getExtensionAttributes() && $quote->getExtensionAttributes()->getNegotiableQuote()) {
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        }

        return $negotiableQuote;
    }

    /**
     * @inheritdoc
     */
    public function getNegotiableQuote($quoteId)
    {
        $quote = $this->retrieveQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        if ($negotiableQuote === null
            || !$negotiableQuote->getIsRegularQuote()
        ) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __(
                    'Requested quote is not found. Row ID: %fieldName = %fieldValue',
                    ['fieldName' => 'quoteId', 'fieldValue' => $quoteId]
                )
            );
        }

        return $quote;
    }

    /**
     * {@inheritdoc}
     */
    public function setHasChangesInNegotiableQuote(CartInterface $quote)
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $value = $negotiableQuote->getNegotiatedPriceValue();
        if ($value !== null) {
            $negotiableQuote->setHasUnconfirmedChanges(true);
        }
    }

    /**
     * Set customer price changed flag in negotiable quote.
     *
     * @param CartInterface $quote
     * @return void
     */
    private function setIsCustomerPriceChanged(CartInterface $quote)
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($negotiableQuote->getNegotiatedPriceValue() !== null) {
            $negotiableQuote->setIsCustomerPriceChanged(true);
        }
        $this->quoteRepository->save($quote);
    }

    /**
     * {@inheritdoc}
     */
    private function resetCustomPrice(CartInterface $quote)
    {
        if ($quote->getExtensionAttributes() !== null
            && $quote->getExtensionAttributes()->getNegotiableQuote() !== null) {
            $quote->getExtensionAttributes()
                ->getNegotiableQuote()
                ->setNegotiatedPriceType(null)
                ->setNegotiatedPriceValue(null)
                ->setShippingPrice(null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeNegotiation($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId);
        $oldData = $this->quoteHistory->collectOldDataFromQuote($quote);
        $this->resetCustomPrice($quote);
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true, false);
        $this->quoteHistory->checkPricesAndDiscounts($quote, $oldData);
        //$this->quoteHistory->updateLog($quoteId, true);
        $this->updateSnapshotQuote($quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function recalculateQuote($quoteId, $updatePrice = true)
    {
        $quote = $this->quoteRepository->get($quoteId);
        $oldQuoteData = $this->quoteHistory->collectOldDataFromQuote($quote);
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, $updatePrice, $updatePrice, false);
        $checkData = $this->quoteHistory->checkPricesAndDiscounts($quote, $oldQuoteData);
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if (($checkData->getIsTaxChanged() || $checkData->getIsPriceChanged()
                || $checkData->getIsDiscountChanged())
            && $negotiableQuote->getStatus() != NegotiableQuoteInterface::STATUS_CREATED
            && $negotiableQuote->getNegotiatedPriceValue() !== null
        ) {
            $negotiableQuote->setIsCustomerPriceChanged(true);
        }
        $negotiableQuote->setIsAddressDraft(false);
        $this->quoteRepository->save($quote);
    }

    /**
     * {@inheritdoc}
     */
    public function updateQuoteItems($quoteId, array $cartData = [])
    {
        $quote = $this->quoteRepository->get($quoteId);
        if (is_array($cartData)) {
            $oldQuoteData = $this->quoteHistory->collectOldDataFromQuote($quote);

            $this->quoteUpdater->updateQuoteItemsByCartData($quote, $cartData);

            $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true);
            $result = $this->quoteHistory->checkPricesAndDiscounts($quote, $oldQuoteData);
            if ($result->getIsChanged()
                || $quote->getExtensionAttributes()->getNegotiableQuote()->getIsCustomerPriceChanged()) {
                $this->quoteRepository->save($quote);
            }
        }
    }

    /**
     * Retrieve quote from repository.
     *
     * @param int $quoteId
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    private function retrieveQuote($quoteId)
    {
        try {
            return $this->quoteRepository->get($quoteId, ['*']);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __(
                    'Requested quote is not found. Row ID: %fieldName = %fieldValue',
                    ['fieldName' => 'quoteId', 'fieldValue' => $quoteId]
                )
            );
        }
    }
    
}

?>