<?php


namespace Redington\Quotation\Model\History;

use Magento\NegotiableQuote\Api\Data\HistoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class CriteriaBuilder
 */
class CriteriaBuilder extends \Magento\NegotiableQuote\Model\History\CriteriaBuilder
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * CriteriaBuilder constructor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * Get search criteria for quote history
     *
     * @param int $quoteId
     * @return SearchCriteriaInterface
     */
    public function getQuoteHistoryCriteria($quoteId)
    {
       $createdAtSort = $this->sortOrderBuilder
            ->setField(HistoryInterface::HISTORY_ID)
            ->setDirection(\Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->create();

        foreach ($this->getQuoteFilter($quoteId) as $filter) {
            $this->searchCriteriaBuilder->addFilters([$filter]);
        }

        return $this->searchCriteriaBuilder->addSortOrder($createdAtSort)->create();
    }

    /**
     * Get search criteria for quote history
     *
     * @param int $quoteId
     * @return SearchCriteriaInterface
     */
    public function getSystemHistoryCriteria($quoteId)
    {
        $filters = $this->getQuoteFilter($quoteId);
        $filters[] = $this->filterBuilder
            ->setField(HistoryInterface::STATUS)
            ->setConditionType('eq')
            ->setValue(HistoryInterface::STATUS_UPDATED_BY_SYSTEM)
            ->create();

        foreach ($filters as $filter) {
            $this->searchCriteriaBuilder->addFilters([$filter]);
        }

        return $this->searchCriteriaBuilder->create();
    }

    /**
     * Get quote
     *
     * @param int $quoteId
     * @return \Magento\Framework\Api\SearchCriteria
     */
    public function getQuoteSearchCriteria($quoteId)
    {
        $filters = [];
        $filters[] = $this->filterBuilder
            ->setField('main_table.' . CartInterface::KEY_ENTITY_ID)
            ->setConditionType('eq')
            ->setValue((int)$quoteId)
            ->create();

        foreach ($filters as $filter) {
            $this->searchCriteriaBuilder->addFilters([$filter]);
        }

        return $this->searchCriteriaBuilder->create();
    }

    /**
     * Get filter by quote id
     *
     * @param int $quoteId
     * @return array
     */
    private function getQuoteFilter($quoteId)
    {
        $filters = [];
        $filters[] = $this->filterBuilder
            ->setField(HistoryInterface::QUOTE_ID)
            ->setConditionType('eq')
            ->setValue((int)$quoteId)
            ->create();

        return $filters;
    }
}
