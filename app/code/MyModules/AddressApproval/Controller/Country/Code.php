<?php

namespace Redington\AddressApproval\Controller\Country;

class Code extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Filesystem\DirectoryList $dir
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->dir = $dir;
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
            $rootPath = $this->dir->getRoot();
            $data = file_get_contents($rootPath.'/app/code/Redington/AddressApproval/code.json');
            $data = json_decode($data, true);
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