<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization;

use CodeCustom\PortmonePreAuthorization\Api\Response\PostAuthorization\CancellationInterface;
use CodeCustom\PortmonePreAuthorization\Model\Curl\Transport;
use Magento\Sales\Model\Order;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use CodeCustom\PortmonePreAuthorization\Model\Response\PreAuthorization\Failure;
use CodeCustom\PortmonePreAuthorization\Helper\Logger;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

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

    /**
     * @var Failure
     */
    protected $failureResponse;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Cancellation constructor.
     * @param Transport $transport
     * @param Order $orderModel
     * @param PortmonePreAuthorizationConfig $configHelper
     * @param Failure $failureResponse
     */
    public function __construct(
        Transport $transport,
        Order $orderModel,
        PortmonePreAuthorizationConfig $configHelper,
        Failure $failureResponse,
        Logger $logger,
        TimezoneInterface $timezone
    )
    {
        $this->transport = $transport;
        $this->orderModel = $orderModel;
        $this->configHelper = $configHelper;
        $this->failureResponse = $failureResponse;
        $this->logger = $logger;
        $this->timezone = $timezone;
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
            $this->logger->create($order->getIncrementId()
                . '_post_cancel_' . $this->getCreatedAt($order->getCreatedAt()),
                'portmone');
            if ($order->getStatus() == $this->configHelper->getPostAuthCancelStatus()) {
                $result = $this->transport->sendRequest($order, Transport::REJECT_METHOD);
                $this->logger->log(['order_id' => $order->getIncrementId()]);
                $this->logger->log(['result_from_PORTMONE' => isset($result['status']) ? $result['status'] : '']);
                $this->logger->log($result);
                if ($result && isset($result['status']) && $result['status'] == Transport::REJECT_STATUS) {
                    $this->failureResponse->changeOrder(
                        $this->configHelper->getOrderFailureStatus(),
                        $order,
                        [__("Payment from Portmane post-authorized, order: %1 status: %2", $order->getIncrementId(), 'canceled')]
                    );
                    $this->logger->log(['order_change_status_to' => $this->configHelper->getOrderFailureStatus()]);
                }
            } else {
                $result['error_code'] = 1;
                $result['error_message'] = __('Error status on this order already changed');
                $this->logger->log(['error_msg' => __('Error status on this order already changed')]);
            }

        } catch (\Exception $exception) {
            $result['error_code'] = $exception->getCode();
            $result['error_message'] = $exception->getMessage();
            $this->logger->log(['error_msg' => $exception->getMessage()]);
        }

        return $result;
    }

    /**
     * Convert time to needed timezone
     * @param null $date
     * @return string|null
     */
    public function getCreatedAt($date = null)
    {
        if (!$date) {
            return null;
        }
        return $this->timezone->date(new \DateTime($date))->format('Y-m-d H:i:s');
    }
}
