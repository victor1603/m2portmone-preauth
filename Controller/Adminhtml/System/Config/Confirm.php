<?php

namespace CodeCustom\PortmonePreAuthorization\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization\Confirmation;

class Confirm extends Action
{

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Confirmation
     */
    protected $confirmation;

    /**
     * Confirm constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Confirmation $confirmation
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Confirmation $confirmation
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->confirmation = $confirmation;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $data = $this->_request->getParams();

        try {
            if (isset($data['increment_id'])) {
                $data = $this->confirmation->confirm($data['increment_id']);
                $result->setData($data);
            }

        } catch (\Exception $exception) {
            $result->setData(
                [
                    'error_code' => $exception->getCode(),
                    'error_message' => $exception->getMessage()
                ]
            );
        }

        return $result;
    }

}
