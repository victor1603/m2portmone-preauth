<?php

namespace CodeCustom\PortmonePreAuthorization\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization\Cancellation;

class Cancel extends Action
{

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Cancellation
     */
    protected $canellation;

    /**
     * Cancel constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Cancellation $cancellation
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Cancellation $cancellation
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->canellation = $cancellation;
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
                $data = $this->canellation->cancel($data['increment_id']);
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
