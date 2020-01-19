<?php

namespace Redington\Warehouse\Controller\Adminhtml\Source;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\InventoryApi\Api\Data\SourceInterface;

class Save extends \Magento\InventoryAdminUi\Controller\Adminhtml\Source\Save {
    public function __construct(
            \Magento\Backend\App\Action\Context $context,
            \Magento\InventoryApi\Api\Data\SourceInterfaceFactory $sourceFactory,
            \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
            \Magento\InventoryAdminUi\Model\Source\SourceHydrator $sourceHydrator,
            \Magento\Inventory\Model\SourceFactory $inventorySourceFactory
        ) {
        $this->sourceFactory = $sourceFactory;
        $this->sourceRepository = $sourceRepository;
        $this->sourceHydrator = $sourceHydrator;
        $this->inventorySourceFactory = $inventorySourceFactory;
        parent::__construct($context, $sourceFactory, $sourceRepository, $sourceHydrator);
    }
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();
        $requestData = $request->getPost()->toArray();
        try{
            $sourceCode = $requestData['general']['source_code'];
        }catch(\Exception $e){
            $currentSize = sizeof($this->inventorySourceFactory->create()->getCollection());
            $sourceCode = $currentSize+1;
            $requestData['general']['source_code'] = $sourceCode;
        }

        $distribution = $requestData['general']['distribution'];
        $sapAccountCode = $requestData['general']['sap_account_code'];
        $plantCode = $requestData['general']['plant_code'];

        unset($requestData['general']['distribution']);
        unset($requestData['general']['sap_account_code']);
        unset($requestData['general']['plant_code']);

        if (!$request->isPost() || empty($requestData['general'])) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
            return $resultRedirect;
        }

        $sourceCodeQueryParam = $request->getQuery(SourceInterface::SOURCE_CODE);
        try {
            $source = (null !== $sourceCodeQueryParam)
                ? $this->sourceRepository->get($sourceCodeQueryParam)
                : $this->sourceFactory->create();

            $this->processSave($source, $requestData);
            //save custom data in source
            $source = $this->inventorySourceFactory->create()->load($sourceCode);
            $source->setDistribution($distribution);
            $source->setSapAccountCode($sapAccountCode);
            $source->setPlantCode($plantCode);
            $source->save();



            $this->messageManager->addSuccessMessage(__('The Source has been saved.'));
            $this->processRedirectAfterSuccessSave($resultRedirect, $source->getSourceCode());
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The Source does not exist.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $localizedError) {
                $this->messageManager->addErrorMessage($localizedError->getMessage());
            }
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect, $sourceCodeQueryParam ?? $sourceCodeQueryParam);
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect, $sourceCodeQueryParam ?? $sourceCodeQueryParam);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save Source.'));
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect, $sourceCodeQueryParam ?? $sourceCodeQueryParam);
        }
        return $resultRedirect;
    }
    /**
     * @param SourceInterface $source
     * @param array $requestData
     * @return void
     */
    private function processSave(SourceInterface $source, array $requestData)
    {
        $source = $this->sourceHydrator->hydrate($source, $requestData);

        $this->_eventManager->dispatch(
            'controller_action_inventory_populate_source_with_data',
            [
                'request' => $this->getRequest(),
                'source' => $source,
            ]
        );

        $this->sourceRepository->save($source);

        $this->_eventManager->dispatch(
            'controller_action_inventory_source_save_after',
            [
                'request' => $this->getRequest(),
                'source' => $source,
            ]
        );
    }

    /**
     * @param Redirect $resultRedirect
     * @param string $sourceCode
     * @return void
     */
    private function processRedirectAfterSuccessSave(Redirect $resultRedirect, string $sourceCode)
    {
        if ($this->getRequest()->getParam('back')) {
            $resultRedirect->setPath('*/*/edit', [
                SourceInterface::SOURCE_CODE => $sourceCode,
                '_current' => true,
            ]);
        } elseif ($this->getRequest()->getParam('redirect_to_new')) {
            $resultRedirect->setPath('*/*/new', [
                '_current' => true,
            ]);
        } else {
            $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * @param Redirect $resultRedirect
     * @param string|null $sourceCode
     * @return void
     */
    private function processRedirectAfterFailureSave(Redirect $resultRedirect, string $sourceCode = null)
    {
        if (null === $sourceCode) {
            $resultRedirect->setPath('*/*/new');
        } else {
            $resultRedirect->setPath('*/*/edit', [
                SourceInterface::SOURCE_CODE => $sourceCode,
                '_current' => true,
            ]);
        }
    }
}