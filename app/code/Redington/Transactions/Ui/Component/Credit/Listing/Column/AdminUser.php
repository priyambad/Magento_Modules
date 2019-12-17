<?php

namespace Redington\Transactions\Ui\Component\Credit\Listing\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class AdminUser extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\User\Model\UserFactory $userFactory,
        array $components = [],
        array $data = []
    ) {
        $this->userFactory = $userFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {
                // $items['action_taken_by'] is column name
                $adminId = $items['action_taken_by'];
                if($adminId) {
                    $adminUser = $this->userFactory->create()->load($adminId);
                    $items['action_taken_by'] = $adminUser->getFirstName().' '.$adminUser->getLastName();
                }
            }
        }
        return $dataSource;
    }
}