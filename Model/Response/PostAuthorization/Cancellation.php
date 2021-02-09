<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization;

use CodeCustom\PortmonePreAuthorization\Api\Response\PostAuthorization\CancellationInterface;
use CodeCustom\PortmonePreAuthorization\Model\Curl\Transport;
use Magento\Sales\Model\Order;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use CodeCustom\PortmonePreAuthorization\Model\Response\PreAuthorization\Failure;

class Cancellation implements CancellationInterface
{
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

    protected $failureResponse;

    public function __construct(
        Transport $transport,
        Order $orderModel,
        PortmonePreAuthorizationConfig $configHelper,
        Failure $failureResponse
    )
    {
        $this->transport = $transport;
        $this->orderModel = $orderModel;
        $this->configHelper = $configHelper;
        $this->failureResponse = $failureResponse;
    }

    /**
     * @param string $orderIncrementId
     * @return array|false|mixed
     */
    public function cancel($orderIncrementId = '')
    {
        try {
            $result = null;
            $order = $this->orderModel->loadByIncrementId($orderIncrementId);
            if ($order->getStatus() == $this->configHelper->getPostAuthCancelStatus()) {
                $result = $this->transport->sendRequest($order, Transport::REJECT_METHOD);
                $this->failureResponse->changeOrder(
                    $this->configHelper->getOrderFailureStatus(),
                    $order,
                    [__("Payment from Portmane post-authorized, order: %1 status: %2", $order->getIncrementId(), 'canceled')]
                );
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
