<?php

namespace Redington\AddressApproval\Controller\Country;

class Region extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Redington\AddressApproval\Helper\Region $regionHelper
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->regionHelper = $regionHelper;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        try{
            $regionArray = $this->regionHelper->getRegionData();
            $data = json_encode($regionArray);
            $response = [
                'success' => true,
                'data' => $data
            ];

            return $resultJson->setData($response);
        } catch (\Exception $ex) {
            $response = [
                'error' => true,
                'message' => $ex.message()
            ];

            return $resultJson->setData($response);
        }
    }
}