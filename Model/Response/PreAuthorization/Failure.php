<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Response\PreAuthorization;

use CodeCustom\PortmonePreAuthorization\Api\Response\PreAuthorization\FailureInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

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
        StoreManagerInterface $storeManager
    )
    {
        $this->response = $response;
        $this->request = $request;
        $this->configHelper = $configHelper;
        $this->_orderRepository = $_orderRepository;
        $this->orderModel = $orderModel;
        $this->storeManager = $storeManager;
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function failure()
    {
        $params = $this->request->getParams();
        $orderId = isset($params['SHOPORDERNUMBER']) ? $params['SHOPORDERNUMBER'] : null;
        $storeId = null;

        try {
            if ($orderId) {
                $order = $this->orderModel->loadByIncrementId($orderId);
                $storeId = $order->getStoreId();
                $this->history[] = __("Payment from Portmane pre-authorized, order: %1 status: %2", $order->getIncrementId(), 'failure');
                $this->changeOrder($this->configHelper->getOrderFailureStatus(), $order);
            }
        } catch (\Exception $exception) {
            $error = true;
        }

        if ($this->configHelper->getFrontRedirectUrl($storeId)) {
            $this->response->setRedirect($this->configHelper->getFrontRedirectUrl($storeId))->sendResponse();
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

        if (count($history)) {
            $order->addStatusHistoryComment(implode(' ', $history))
                ->setIsCustomerNotified(true);
        }

        if ($state) {
            //$order->setState($state);
            $order->setStatus($state);
            $order->save();
        }
        $this->_orderRepository->save($order);
        return true;
    }

}
