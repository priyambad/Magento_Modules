<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Quotation\Model\ResourceModel;

use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Psr\Log\LoggerInterface;
use Magento\NegotiableQuote\Model\Restriction\Admin as GridRestriction;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\App\State;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;

/**
 * Negotiable quote grid resource model.
 */
class QuoteGrid extends \Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid
{
    /**#@+
     * Quote grid table data
     */
    const QUOTE_GRID_TABLE = 'negotiable_quote_grid';
    const QUOTE_ID = 'entity_id';
    const CREATED_AT = 'created_at';
    const COMPANY_ID = 'company_id';
    const COMPANY_NAME = 'company_name';
    const CUSTOMER_ID = 'customer_id';
    const SUBMITTED_BY = 'submitted_by';
    const UPDATED_AT = 'updated_at';
    const SALES_REP_ID = 'sales_rep_id';
    const SALES_REP = 'sales_rep';
    const BASE_GRAND_TOTAL = 'base_grand_total';
    const GRAND_TOTAL = 'grand_total';
    const QUOTE_NAME = 'quote_name';
    const QUOTE_STATUS = 'status';
    const BASE_NEGOTIATED_GRAND_TOTAL = 'base_negotiated_grand_total';
    const NEGOTIATED_GRAND_TOTAL = 'negotiated_grand_total';
    const BASE_CURRENCY = 'base_currency_code';
    const CURRENCY = 'quote_currency_code';
    const STORE_ID = 'store_id';
    const RATE = 'rate';
	const QUOTATION_ID = 'quotation_id';
    /**#@-*/

    /**
     * @var \Magento\Customer\Api\CustomerNameGenerationInterface
     */
    private $customerNameGeneration;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Company\Api\CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\TotalsFactory
     */
    private $quoteTotalsFactory;

    /**
     * @var \Magento\NegotiableQuote\Model\Restriction\Admin
     */
    private $restriction;

    /**
     * Negotiable quote manager
     *
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface
     */
    private $negotiableQuoteManagement;

    /**
     * Negotiable quote manager
     *
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * Quote grid table fields
     *
     * @var array
     */
    private $quoteGridFields = [
        self::QUOTE_ID,
        self::CREATED_AT,
        self::COMPANY_ID,
        self::COMPANY_NAME,
        self::CUSTOMER_ID,
        self::SUBMITTED_BY,
        self::UPDATED_AT,
        self::SALES_REP_ID,
        self::SALES_REP,
        self::BASE_GRAND_TOTAL,
        self::QUOTE_NAME,
        self::QUOTE_STATUS,
        self::NEGOTIATED_GRAND_TOTAL,
        self::BASE_CURRENCY,
        self::CURRENCY,
        self::STORE_ID,
        self::RATE,
		self::QUOTATION_ID,
    ];

    /**
     * @param Context $context
     * @param CustomerNameGenerationInterface $customerNameGeneration
     * @param LoggerInterface $logger
     * @param CompanyManagementInterface $companyManagement
     * @param TotalsFactory $quoteTotalsFactory
     * @param GridRestriction $restriction
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\Framework\App\State $appState
     * @param null $connectionName [optional]
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Customer\Api\CustomerNameGenerationInterface $customerNameGeneration,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        TotalsFactory $quoteTotalsFactory,
        GridRestriction $restriction,
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        State $appState,
        $connectionName = null
    ) {
        parent::__construct($context,$customerNameGeneration,$logger,$companyManagement,$quoteTotalsFactory,$restriction,$negotiableQuoteManagement,$appState,$connectionName);
		$this->customerNameGeneration = $customerNameGeneration;
        $this->logger = $logger;
        $this->companyManagement = $companyManagement;
        $this->quoteTotalsFactory = $quoteTotalsFactory;
        $this->restriction = $restriction;
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->appState = $appState;
		
     }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('negotiable_quote_grid', 'entity_id');
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(CartInterface $quoteData)
    {
        $populatedQuoteData = $this->getPopulatedQuoteGridData($quoteData);
        try {
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(self::QUOTE_GRID_TABLE),
                $populatedQuoteData,
                array_keys($populatedQuoteData)
            );
        } catch (\Exception $e) {
            //$this->logger->critical($e);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshValue($updateWhereField, $updatedWhereValue, $field, $value)
    {
        if (in_array($field, $this->quoteGridFields) && in_array($updateWhereField, $this->quoteGridFields)) {
            try {
                $this->getConnection()->update(
                    $this->getTable(self::QUOTE_GRID_TABLE),
                    [$field => $value],
                    [$updateWhereField . ' = ? ' => $updatedWhereValue]
                );
            } catch (\Exception $e) {
                //$this->logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(CartInterface $object)
    {
        return parent::delete($object);
    }

    /**
     * Populate quote grid data.
     *
     * @param CartInterface $quote
     * @return array
     */
    private function getPopulatedQuoteGridData(CartInterface $quote)
    {
        $this->restriction->setQuote($quote);
        $negotiableQuote = $this->getQuoteExtensionAttributes($quote);
        $quoteCurrency = $quote->getCurrency();

        $populatedQuoteGridData = [
            self::QUOTE_ID => $quote->getId(),
            self::CREATED_AT => $quote->getCreatedAt(),
            self::UPDATED_AT => $quote->getUpdatedAt(),
            self::BASE_GRAND_TOTAL => null,
            self::GRAND_TOTAL => null,
            self::BASE_NEGOTIATED_GRAND_TOTAL => null,
            self::NEGOTIATED_GRAND_TOTAL => null,
            self::BASE_CURRENCY => $quoteCurrency->getBaseCurrencyCode(),
            self::CURRENCY => $quoteCurrency->getQuoteCurrencyCode(),
            self::STORE_ID => $quote->getStoreId(),
            self::RATE => $quoteCurrency->getBaseToQuoteRate(),
			self::QUOTATION_ID => $quote->getQuotationId(),
        ];
        if ($negotiableQuote && $negotiableQuote->hasData(NegotiableQuoteInterface::QUOTE_STATUS)) {
            $populatedQuoteGridData[self::QUOTE_STATUS] = $negotiableQuote->getStatus();
        }
        if ($negotiableQuote && $negotiableQuote->hasData(NegotiableQuoteInterface::QUOTE_NAME)) {
            $populatedQuoteGridData[self::QUOTE_NAME] = $negotiableQuote->getQuoteName();
        }

        $populatedQuoteGridData = array_merge(
            $populatedQuoteGridData,
            $this->getCompanyFields($quote->getCustomer()->getId())
        );
        $populatedQuoteGridData = array_merge(
            $populatedQuoteGridData,
            $this->getQuoteTotals($quote, $negotiableQuote)
        );

        if ($quote->getCustomer()->getId()) {
            $populatedQuoteGridData[self::CUSTOMER_ID] = $quote->getCustomer()->getId();
            $populatedQuoteGridData[self::SUBMITTED_BY] = $this->customerNameGeneration->getCustomerName(
                $quote->getCustomer()
            );
        }

        return $populatedQuoteGridData;
    }

    /**
     * Retrieve quote totals array to fill quote grid data.
     *
     * @param CartInterface $quote
     * @param NegotiableQuoteInterface $negotiableQuote
     * @return array
     */
    private function getQuoteTotals(CartInterface $quote, NegotiableQuoteInterface $negotiableQuote)
    {
        $isLocked = $negotiableQuote->getStatus() && $this->restriction->isLockMessageDisplayed();
        if (($isLocked
                && $this->appState->getAreaCode() != \Magento\Framework\App\Area::AREA_ADMINHTML)
            || ($negotiableQuote !== null && $negotiableQuote->getStatus() == NegotiableQuoteInterface::STATUS_ORDERED)
        ) {
            $snapshot = $this->negotiableQuoteManagement->getSnapshotQuote($quote->getId());
            $totals = $this->quoteTotalsFactory->create(['quote' => $snapshot]);
            if ($negotiableQuote->getStatus() !== NegotiableQuoteInterface::STATUS_CREATED) {
                $quoteTotals[self::NEGOTIATED_GRAND_TOTAL] = $totals->getSubtotal(true);
                $quoteTotals[self::BASE_NEGOTIATED_GRAND_TOTAL] = $totals->getSubtotal();
            } else {
                $quoteTotals[self::NEGOTIATED_GRAND_TOTAL] = $totals->getCatalogTotalPrice(true);
                $quoteTotals[self::BASE_NEGOTIATED_GRAND_TOTAL] = $totals->getCatalogTotalPrice();
            }
            $quoteTotals[self::GRAND_TOTAL] = $totals->getCatalogTotalPrice(true);
            $quoteTotals[self::BASE_GRAND_TOTAL] = $totals->getCatalogTotalPrice();
        } else {
            $quote = $this->negotiableQuoteManagement->getNegotiableQuote($quote->getId());
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
            $totals = $this->quoteTotalsFactory->create(['quote' => $quote]);
            if ($negotiableQuote !== null) {
                $quoteTotals[self::NEGOTIATED_GRAND_TOTAL] = $negotiableQuote->getNegotiatedTotalPrice();
                $quoteTotals[self::BASE_NEGOTIATED_GRAND_TOTAL] = $negotiableQuote->getBaseNegotiatedTotalPrice();
            }
            $quoteTotals[self::GRAND_TOTAL] = $totals->getCatalogTotalPrice(true);
            $quoteTotals[self::BASE_GRAND_TOTAL] = $totals->getCatalogTotalPrice();
        }

        return $quoteTotals;
    }

    /**
     * Get populated company fields.
     *
     * @param int $customerId
     * @return array
     */
    private function getCompanyFields($customerId)
    {
        $populatedCompanyData = [];

        try {
            $company = $this->companyManagement->getByCustomerId($customerId);

            if ($company) {
                $salesRepId = $company->getSalesRepresentativeId() ? $company->getSalesRepresentativeId() : '';
                if ($salesRepId && $this->companyManagement->getSalesRepresentative($salesRepId)) {
                    $populatedCompanyData[self::SALES_REP_ID] = $salesRepId;
                    $populatedCompanyData[self::SALES_REP] = $this->companyManagement->getSalesRepresentative(
                        $salesRepId
                    );
                }

                $populatedCompanyData[self::COMPANY_ID] = $company->getId();
                $populatedCompanyData[self::COMPANY_NAME] = $company->getCompanyName();
            }
        } catch (\Exception $e) {
            //skip populating company fields on error
        }

        return $populatedCompanyData;
    }

    /**
     * Get negotiable quote extension attributes from quote.
     *
     * @param CartInterface $quote
     * @return NegotiableQuoteInterface|null
     */
    private function getQuoteExtensionAttributes(CartInterface $quote)
    {
        $extensionAttributes = null;

        if ($quote->getExtensionAttributes() && $quote->getExtensionAttributes()->getNegotiableQuote()) {
            $extensionAttributes = $quote->getExtensionAttributes()->getNegotiableQuote();
        }

        return $extensionAttributes;
    }
}
