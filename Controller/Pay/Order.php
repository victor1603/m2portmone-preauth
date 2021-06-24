<?php

namespace CodeCustom\PortmonePreAuthorization\Controller\Pay;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePaymentLink;
use Magento\Sales\Model\Order as OrderModel;

class Order implements ActionInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var PortmonePaymentLink
     */
    protected $portmoneLink;

    /**
     * @var OrderModel
     */
    protected $orderModel;

    /**
     * Order constructor.
     * @param Context $context
     * @param PortmonePaymentLink $portmoneLink
     * @param OrderModel $orderModel
     */
    public function __construct(
        Context $context,
        PortmonePaymentLink $portmoneLink,
        OrderModel $orderModel
    )
    {
        $this->request = $context->getRequest();
        $this->redirect = $context->getRedirect();
        $this->resultFactory = $context->getResultFactory();
        $this->portmoneLink = $portmoneLink;
        $this->orderModel = $orderModel;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $data = $this->request->getParams();

        try {

            $orderId = isset($data['o']) ? base64_decode($data['o']) : null;
            $customerId = isset($data['c']) ? base64_decode($data['c']) : null;

            if (!$orderId || !$customerId) {
                $result->setUrl($this->redirect->getRefererUrl());
                return $result;
            }

            $order = $this->orderModel->loadByIncrementId($orderId);

            if ($order->getCustomerId() != $customerId) {
                $result->setUrl($this->redirect->getRefererUrl());
                return $result;
            }

            $paymentLink = $this->portmoneLink->getPaymentLink($order);
            $result->setUrl($paymentLink);

        } catch (\Exception $exception) {
            $result->setUrl($this->redirect->getRefererUrl());
        }

        return $result;
    }
}
