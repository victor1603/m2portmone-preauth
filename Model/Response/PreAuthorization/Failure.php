<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Response\PreAuthorization;

use CodeCustom\PortmonePreAuthorization\Api\Response\PreAuthorization\FailureInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Logger;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class Failure implements FailureInterface
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
     * @var PortmonePreAuthorizationConfig
     */
    protected $configHelper;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var null
     */
    public $history = null;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Failure constructor.
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param PortmonePreAuthorizationConfig $configHelper
     * @param OrderRepositoryInterface $_orderRepository
     * @param Order $orderModel
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PortmonePreAuthorizationConfig $configHelper,
        OrderRepositoryInterface $_orderRepository,
        Order $orderModel,
        OrderResource $orderResource,
        StoreManagerInterface $storeManager,
        Logger $logger,
        TimezoneInterface $timezone
    )
    {
        $this->response = $response;
        $this->request = $request;
        $this->configHelper = $configHelper;
        $this->_orderRepository = $_orderRepository;
        $this->orderModel = $orderModel;
        $this->orderResource = $orderResource;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->timezone = $timezone;
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function failure()
    {
        $error = false;
        $params = $this->request->getParams();
        $orderId = isset($params['SHOPORDERNUMBER']) ? $this->configHelper->parseOrderId($params['SHOPORDERNUMBER']) : null;
        $storeId = null;

        try {
            if ($orderId) {
                $order = $this->orderModel->loadByIncrementId($orderId);
                $storeId = $order->getStoreId();
                $this->history[] = __("Payment from Portmane pre-authorized, order: %1 status: %2", $order->getIncrementId(), 'failure');
                $this->changeOrder($this->configHelper->getHoldCancelStatus(), $order);
                $this->createLogger($order, $params);
            }
        } catch (\Exception $exception) {
            $error = true;
        }

        $redirectPage = $this->configHelper->getFrontRedirectUrl($storeId);
        if (strpos($redirectPage, 'order_id=') !== false && !$error) {
            $redirectPage .= $order->getIncrementId();
        }

        if ($redirectPage) {
            $this->response->setRedirect($redirectPage)->sendResponse();
        } else {
            $this->response->setRedirect($this->storeManager->getStore()->getBaseUrl() . 'checkout/onepage/success/')->sendResponse();
        }
        return true;
    }

    /**
     * @param $state
     * @param Order $order
     * @param array $history
     * @return bool
     * @throws \Exception
     */
    public function changeOrder($state, Order $order, $history = [])
    {
        if ($this->history) {
            $history += $this->history;
        }

        if ($state) {
            //$order->setState($state);
            $order->setStatus($state);

        }

        if (count($history)) {
            $order->addStatusHistoryComment(implode(' ', $history))
                ->setIsCustomerNotified(true);
        }

        $this->orderResource->save($order);
        return true;
    }

    /**
     * @var Order $order
     * @param null $order
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function createLogger($order = null, $data = [])
    {
        $this->logger->create($order->getIncrementId()
            . '_pre_fail_' . $this->getCreatedAt($order->getCreatedAt()),
            'portmone');
        $this->logger->log([
            'order_id' => $order->getIncrementId(),
            'grand_total' => $order->getGrandTotal(),
            'status' => 'Failure preauthorization',
            'time' => $this->getCreatedAt(date('Y-m-d H:i:s')),
            'transaction_id' => isset($data['SHOPBILLID']) ? $data['SHOPBILLID'] : '',
            'SHOPBILLID' => isset($data['SHOPBILLID']) ? $data['SHOPBILLID'] : '',
            'APPROVALCODE' => isset($data['APPROVALCODE']) ? $data['APPROVALCODE'] : '',
            'PORTMONE_RESULT' => isset($data['RESULT']) ? $data['RESULT'] : '',
            'CARD_MASK' => isset($data['CARD_MASK']) ? $data['CARD_MASK'] : '',
            'BILL_AMOUNT' => isset($data['BILL_AMOUNT']) ? $data['BILL_AMOUNT'] : '',
        ]);
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
