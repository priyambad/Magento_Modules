<?php 
namespace Redington\CompanyLogo\Controller\Adminhtml\Address;
use Magento\Framework\Controller\ResultFactory;

class Upload extends \Magento\Backend\App\Action
{
    public $imageUploader;

    /**
     * Undocumented function
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Redington\CompanyLogo\Model\ImageUploader $imageUploader
     * @param \Redington\CompanyLogo\Model\CompanyLogoFactory $companylogodata
     * @param \Magento\Framework\App\Request\Http $request
     */

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Redington\CompanyLogo\Model\ImageUploader $imageUploader,
        \Redington\CompanyLogo\Model\CompanyLogoFactory $companylogodata,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->companylogodata = $companylogodata;
        $this->request = $request;
    }

    public function _isAllowed() {
        return $this->_authorization->isAllowed('Redington_CompanyLogo::Address');
    }

    public function execute()
    {
    	$params = $this->request->getParams();
    	 
        try {
        	
            $result = $this->imageUploader->saveFileToTmpDir('company[icon]');
          
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {

            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
           
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
?>