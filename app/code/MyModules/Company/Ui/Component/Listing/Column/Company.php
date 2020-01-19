<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
/**
 * Company Class for show the column name
 */
class Company extends Column
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
     *
     * @var \Magento\Company\Api\Data\RoleInterface
     */
    private $requestedRole;
     /**
     *
     * @var \Magento\Company\Model\CompanyUser
     */
    private $companyUser;
    /**
     *
     * @var \Magento\Company\Model\CompanyRepository
     */
    private $companyRepository;
    /**
     * @param ContextInterface                            $context
     * @param UiComponentFactory                          $uiComponentFactory
     * @param UrlInterface                                $urlBuilder
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param \Magento\Company\Api\Data\RoleInterface     $requestedRole
     * @param \Magento\Company\Model\CompanyUser          $companyUser
     * @param \Magento\Company\Model\CompanyRepository    $companyRepository
     * @param array                                       $components
     * @param array                                       $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        \Magento\Company\Api\Data\RoleInterface $requestedRole,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Magento\Company\Model\CompanyRepository $companyRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->authorization = $authorization;
        $this->requestedRole = $requestedRole;
        $this->companyUser = $companyUser;
        $this->companyRepository = $companyRepository;
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
               $data = $this->companyRepository->get($items['company_id']);
                $items['company_name'] = $data->getCompanyName();

            }
           
        }
        return $dataSource;

    }
}
