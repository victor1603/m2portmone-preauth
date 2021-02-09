<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization;

use CodeCustom\PortmonePreAuthorization\Api\Response\PostAuthorization\ConfirmationInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use CodeCustom\PortmonePreAuthorization\Model\Curl\Transport;
use Magento\Sales\Model\Order;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use CodeCustom\PortmonePreAuthorization\Model\Response\PreAuthorization\Success;

class Confirmation implements ConfirmationInterface
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $configHelper;

    /**
     * @var Success
     */
    protected $successResponse;

    /**
     * Confirmation constructor.
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        Transport $transport,
        Order $orderModel,
        PortmonePreAuthorizationConfig $configHelper,
        Success $successResponse
    )
    {
        $this->request = $request;
        $this->response = $response;
        $this->transport = $transport;
        $this->orderModel = $orderModel;
        $this->configHelper = $configHelper;
        $this->successResponse = $successResponse;
    }

    /**
     * @param string $orderIncrementId
     * @return array|false|mixed
     */
    public function confirm($orderIncrementId = '')
    {
        try {
            $result = null;
            $order = $this->orderModel->loadByIncrementId($orderIncrementId);
            if ($order->getStatus() == $this->configHelper->getPostAuthConfirmStatus()) {
                $result = $this->transport->sendRequest($order, Transport::CONFIRM_METHOD);
                $transactionId = isset($result['shop_bill_id']) ? $result['shop_bill_id'] : null;
                $invoice = $this->successResponse->createInvoice($order, $transactionId, 2);
                if ($invoice) {
                    $this->successResponse->createTransaction(
                        $order,
                        [
                            'id' => $transactionId,
                            'order_id' => $order->getIncrementId()
                        ]
                    );
                }
                $this->successResponse->changeOrder($this->configHelper->getOrderSuccessStatus(), $order);
            } else {
                $result['error_code'] = 1;
                $result['error_message'] = __('Error status on this order already changed');
            }

        } catch (\Exception $exception) {
            $result['error_code'] = $exception->getCode();
            $result['error_message'] = $exception->getMessage();
        }

        return $result;
    }
}
