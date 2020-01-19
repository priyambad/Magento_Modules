<?php

namespace Redington\Customer\Block\Order;

class Recent extends \Magento\Sales\Block\Order\Recent
{

    protected $customerSession;
    protected $_helper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        array $data = array(),
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\CompanyCredit\Api\CreditDataProviderInterface $creditDataProvider,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceFormatter,
        \Magento\CompanyCredit\Model\WebsiteCurrency $websiteCurrency,
        \Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Redington\SapIntegration\Model\ResourceModel\OrderReference\CollectionFactory $orderReferenceCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository 
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->companyHelper = $companyHelper;
        $this->creditDataProvider = $creditDataProvider;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->priceFormatter = $priceFormatter;
        $this->websiteCurrency = $websiteCurrency;
        $this->orderReferenceCollectionFactory = $orderReferenceCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data, $storeManager);
    }
    public function getOrderCount()
    {
        $customers = $this->companyHelper->retrieveCompanyUsersArray();
        $orders = $this->_orderCollectionFactory->create()->addAttributeToSelect(
            '*'
        )->addFieldToFilter(
            'customer_id',
            ['in' => $customers]
        )->addAttributeToFilter(
            'status',
            ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
        )->load();
        return count($orders);
    }

    public function getOrders()
    {
        $customers = $this->companyHelper->retrieveCompanyUsersArray();
        $orders = $this->_orderCollectionFactory->create()->addAttributeToSelect(
            '*'
        )->addFieldToFilter(
            'customer_id',
            ['in' => $customers]
        )->addAttributeToFilter(
            'store_id',
            $this->storeManager->getStore()->getId()
        )->addAttributeToFilter(
            'status',
            ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
        )->addAttributeToSort(
            'created_at',
            'desc'
        )->setPageSize(
            self::ORDER_LIMIT
        )->load();
        return $orders;
    }

    public function getCredit()
    {
        $companyId = $this->companyHelper->retrieveCompanyId();
        $availableCredit = $this->creditDataProvider->get($companyId)->getAvailableLimit();
        return $this->getFormatedCredit($availableCredit);
    }

    public function getFormatedCredit($credit){
        return $this->priceFormatter->format(
            $credit,
            false,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $this->getCreditCurrency()
        );
    }
    private function getCreditCurrency()
    {
        $creditCurrencyCode = null;
        if ($this->getCreditData()) {
            $creditCurrencyCode = $this->getCreditData()->getCurrencyCode();
        }
        return $this->websiteCurrency->getCurrencyByCode($creditCurrencyCode);
    }
    public function getCreditData()
    {
        $companyId = $this->companyHelper->retrieveCompanyId();
        $this->credit = $this->creditDataProvider->get($companyId);
        return $this->credit;
    }

    public function getQuotesCount()
    {
        $customers = $this->companyHelper->retrieveCompanyUsersArray();
        $customerId = $this->customerSession->getCustomerId();
        $this->negotiable_quote = 'negotiable_quote';
        $quotes = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('customer_id', ['in' => $customers]);
        /*$quotes->getSelect()->join(array('negotiablequote' => $this->negotiable_quote), 'main_table.customer_id= negotiablequote.creator_id');
        $quotes->addFieldToFilter('main_table.customer_id', ['in' => $customers]);*/
        return count($quotes);
    }
    public function getOrderError($orderId){
        try{
            $orderReference = $this->orderReferenceCollectionFactory->create()->addFieldToFilter('order_id',$orderId)->getFirstItem();
            $responseData = $orderReference->getResponseData();
            $response = unserialize($responseData);
            $orderError = $response['RETURN']['BAPIRET2']['MESSAGE'];
            return $orderError;
        }catch(\Exception $e){
            return false;
        }
    }
    public function getOverdueAmount()
    {
     
       $overDueAmount = $this->customerSession->getOverdueAmount();
       $currencysymbol = $this->storeManager->getStore()->getCurrentCurrencyCode();
        
        return $currencysymbol." ".round($overDueAmount,2) ? $currencysymbol." ".round($overDueAmount,2) : 0;

    }
    public function getErrorMessage(){
        return $this->scopeConfig->getValue('redington_sap/general/so_message');
    }
}
