<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Response\PreAuthorization;

use CodeCustom\PortmonePreAuthorization\Api\Response\PreAuthorization\SuccessInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Sales\Model\Order;
use Magento\Setup\Exception;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;

class Success implements SuccessInterface
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var Transaction
     */
    protected $_transaction;

    /**
     * @var Invoice
     */
    protected $_invoice;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var null
     */
    public $history = null;

    /**
     * Success constructor.
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param PortmonePreAuthorizationConfig $configHelper
     * @param StoreManagerInterface $storeManager
     * @param InvoiceService $_invoiceService
     * @param Transaction $_transaction
     * @param Invoice $_invoice
     * @param OrderRepositoryInterface $_orderRepository
     * @param BuilderInterface $_transactionBuilder
     * @param Order $order
     */
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PortmonePreAuthorizationConfig $configHelper,
        StoreManagerInterface $storeManager,
        InvoiceService $_invoiceService,
        Transaction $_transaction,
        Invoice $_invoice,
        OrderRepositoryInterface $_orderRepository,
        BuilderInterface $_transactionBuilder,
        Order $order,
        TransactionRepositoryInterface $transactionRepository
    )
    {
        $this->request = $request;
        $this->response = $response;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->_invoiceService = $_invoiceService;
        $this->_transaction = $_transaction;
        $this->_invoice = $_invoice;
        $this->_orderRepository = $_orderRepository;
        $this->_transactionBuilder = $_transactionBuilder;
        $this->orderModel = $order;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function success()
    {
        $params = $this->request->getParams();
        $orderId = isset($params['SHOPORDERNUMBER']) ? $this->configHelper->parseOrderId($params['SHOPORDERNUMBER']) : null;
        $result = isset($params['RESULT']) ? $params['RESULT'] : null;

        /**
         * if we have != 0 result on sucess page
         * we send redirect to failure page
         */
        if ($result != 0) {
            $this->response->setRedirect($this->configHelper->getFailureUrl())->sendResponse();
        }

        /**
         * We work with order status
         * Order transaction
         * and order invoice
         */
        $storeId = null;
        try {
            if ($orderId) {
                $invoiceState = 1;
                $order = $this->orderModel->loadByIncrementId($orderId);
                $storeId = $order->getStoreId();
                $transactionId = isset($params['SHOPBILLID']) ? $params['SHOPBILLID'] : null;
                $state = $order->getState();
                $this->history[] = __("Payment from Portmane pre-authorized, order: %1 status: %2", $order->getIncrementId(), 'success');
                $invoice = $this->createInvoice($order, $transactionId, $invoiceState);
                if ($invoice && $invoiceState == 2) {
                    $this->createTransaction(
                        $order,
                        [
                            'id' => $transactionId,
                            'order_id' => $order->getIncrementId()
                        ]
                    );
                }
                $this->changeOrder($this->configHelper->getNewOrderStatus(), $order);
            }

        } catch (\Exception $exception) {
            $error = true;
        }

        /**
         * Make redirect to front page
         */
        if ($this->configHelper->getFrontRedirectUrl($storeId)) {
            $this->response->setRedirect($this->configHelper->getFrontRedirectUrl($storeId))->sendResponse();
        } else {
            $this->response->setRedirect($this->storeManager->getStore()->getBaseUrl() . 'checkout/onepage/success/')->sendResponse();
        }
        return $params;
    }

    /**
     * @param Order|null $order
     * @param array $paymentData
     * @return bool
     * @throws \Exception
     */
    public function createTransaction(Order $order = null, $paymentData = array())
    {
        try {

            /**
             * @var $transactionData Order\Payment\Transaction
             */
            if (isset($paymentData['id']) && $paymentData['id'] && $order->getPayment()->getEntityId()) {
                $transactionData = $this->transactionRepository
                    ->getByTransactionId($paymentData['id'], $order->getPayment()->getEntityId(), $order->getId());

                if ($transactionData && $transactionData->getTxnId()) {
                    return $transactionData->getTransactionId();
                }
            }

            $this->history[] = __("Creating transaction with ID: %1", $paymentData['id']);
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setMethod($order->getPayment()->getMethod());
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __('The authorized amount is %1.', $formatedPrice);
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
                )
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            $this->history[] = __("Error transaction with ID: %1 not created", $paymentData['id']);
        }

        return true;
    }

    /**
     * @param Order $order
     * @param $transactionId
     * @param $invoiceState
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createInvoice(Order $order, $transactionId, $invoiceState)
    {
        $this->history[] = __("Creating invoice with transaction ID: %1 and state: %2", $transactionId, $invoiceState);

        try {
            if ($order->canInvoice()) {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->register()->pay();
                $invoice->setState($invoiceState);
                if ($transactionId && !is_null($transactionId)) {
                    $invoice->setTransactionId($transactionId);
                }
                $transactionSave = $this->_transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder()
                    );
                $transactionSave->save();
            } else {
                $invoiceCollection = $order->getInvoiceCollection();
                if (isset($invoiceCollection->getData()[0])) {
                    $invoice = $invoiceCollection->getData()[0];
                }
                if (isset($invoice['increment_id'])) {
                    $invoiceData = $this->_invoice->loadByIncrementId($invoice['increment_id']);
                    $invoiceData->setState($invoiceState);
                    if ($transactionId && !is_null($transactionId)) {
                        $invoiceData->setTransactionId($transactionId);
                    }
                    $invoiceData->save();
                }
            }
        } catch (\Exception $exception) {
            $this->history[] = __("Error creating incoice with order ID: %1 ", $order->getIncrementId());
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