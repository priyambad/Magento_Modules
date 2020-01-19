<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Quotation
 */
namespace Redington\Quotation\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\Data\CompanyInterface;
//use Magento\NegotiableQuote\Model\Status\
/**
 * Class Status
 */
class Actions extends \Magento\NegotiableQuote\Ui\Component\Listing\Column\Actions
{   
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    
    /**
     * @var RestrictionInterface
     */
    private $restriction;
    
    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var UserContextInterface
     */
    private $userContext;
    
    /**
     * @var \Magento\Company\Api\AuthorizationInterface
     */
    private $authorization;
    
    /**
     * 
     * @param CompanyManagementInterface $companyManagement
     * @param UserContextInterface $userContext
     * @param RestrictionInterface $restriction
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        UserContextInterface $userContext,
        RestrictionInterface $restriction,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->companyManagement = $companyManagement;
        $this->userContext = $userContext;
        $this->restriction = $restriction;
        $this->authorization = $authorization;
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $components, $data);
    }
    
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['view'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'negotiable_quote/quote/view',
                        ['quote_id' => $item['entity_id']]
                    ),
                    'label' => __('View'),
                    'hidden' => false,
                ];
		
                if($item['status'] == "Updated" && $this->isProceedToCheckoutAvailable() && $this->isCheckoutLinkVisible()) {
                    $item[$this->getData('name')]['placeorder'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'negotiable_quote/quote/checkout',
                            ['negotiableQuoteId' => $item['entity_id']]
                        ),
                        'label' => __('Place Order'),
                        'hidden' => false,                  
                    ];
                }           
            }
        }
        return $dataSource;
    }
    
    /**
     * Is proceed to checkout enabled.
     *
     * @return bool
     */
    public function isProceedToCheckoutAvailable()
    {
        return $this->restriction->canProceedToCheckout();
    }
    
    /**
     * Check company status.
     *
     * @return bool
     */
    public function isCheckoutLinkVisible()
    {
        $isVisible = true;
        $customerId = $this->userContext->getUserId();
        if ($customerId) {
            $company = $this->companyManagement->getByCustomerId($customerId);
            if ($company && $company->getStatus() == CompanyInterface::STATUS_BLOCKED) {
                $isVisible = false;
            }
        }
        if (!$this->authorization->isAllowed('Magento_NegotiableQuote::checkout')) {
            $isVisible = false;
        }

        return $isVisible;
    }
}
