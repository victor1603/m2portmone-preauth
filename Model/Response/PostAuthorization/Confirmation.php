<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization;

use CodeCustom\PortmonePreAuthorization\Api\Response\PostAuthorization\ConfirmationInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use CodeCustom\PortmonePreAuthorization\Model\Curl\Transport;
use Magento\Sales\Model\Order;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use CodeCustom\PortmonePreAuthorization\Model\Response\PreAuthorization\Success;
use CodeCustom\PortmonePreAuthorization\Helper\Logger;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

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
     * @var Logger
     */
    protected $logger;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

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
        Success $successResponse,
        Logger $logger,
        TimezoneInterface $timezone
    )
    {
        $this->request = $request;
        $this->response = $response;
        $this->transport = $transport;
        $this->orderModel = $orderModel;
        $this->configHelper = $configHelper;
        $this->successResponse = $successResponse;
        $this->logger = $logger;
        $this->timezone = $timezone;
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
            $this->logger->create($order->getIncrementId()
                . '_post_success_' . $this->getCreatedAt($order->getCreatedAt()),
                'portmone');
            if ($order->getStatus() == $this->configHelper->getPostAuthConfirmStatus()) {
                $result = $this->transport->sendRequest($order, Transport::CONFIRM_METHOD);
                $this->logger->log(['order_id' => $order->getIncrementId()]);
                $this->logger->log(['result_from_PORTMONE' => isset($result['status']) ? $result['status'] : '']);
                $this->logger->log($result);
                if ($result && isset($result['status']) && $result['status'] == Transport::CONFIRM_STATUS) {
                    $transactionId = isset($result['shop_bill_id']) ? $result['shop_bill_id'] : null;
                    $this->logger->log(['trans_id' => $transactionId]);
                    $invoice = $this->successResponse->createInvoice($order, $transactionId, 2);
                    if ($invoice) {
                        $this->successResponse->createTransaction(
                            $order,
                            [
                                'id' => $transactionId,
                                'order_id' => $order->getIncrementId()
                            ]
                        );
                        $this->logger->log(['invoice_status' => 'PAID']);
                    }
                    $this->successResponse->changeOrder($this->configHelper->getOrderSuccessStatus(), $order);
                    $this->logger->log(['order_change_status_to' => $this->configHelper->getOrderSuccessStatus()]);
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
