<?php

/**
 * Copyright © Redington. All rights reserved.
 *
 */
namespace Redington\PaymentGroup\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Action class for View & Delete actions
 */
class PartnerName extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Company\Api\AuthorizationInterface
     */
    private $authorization;

    /**
     * @param ContextInterface                            $context
     * @param UiComponentFactory                          $uiComponentFactory
     * @param UrlInterface                                $urlBuilder
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param array                                       $components
     * @param array                                       $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        array $components = [],
        array $data = []
    ) {

        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->authorization = $authorization;
        $this->company = $companyFactory;
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                $customerId = $items['partner_id'];

                $items['partner_id'] = $this->getCompanyName($customerId);
            }
        }
        return $dataSource;
       
    }

    public function getCompanyName($customerId){

        $companyModel =  $this->company->create()->load($customerId);
        return $companyModel->getCompanyName();
    }

}
