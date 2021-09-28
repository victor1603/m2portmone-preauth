<?php

namespace CodeCustom\PortmonePreAuthorization\Plugin\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\PlaceOrder as PlaseOrderResolve;
use Magento\Sales\Model\Order;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePreAuthorization;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\LayoutFactory;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use CodeCustom\PortmonePreAuthorization\Helper\Logger;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePaymentLink;

class PlaceOrder
{
    /**
     * @var PlaseOrderResolve
     */
    protected $placeOrderResolve;

    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $portmoneHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    protected $portmoneLink;

    /**
     * PlaceOrder constructor.
     * @param PlaseOrderResolve $placeOrderResolve
     * @param Order $orderModel
     * @param CustomerRepositoryInterface $customerRepository
     * @param LayoutFactory $layoutFactory
     * @param PortmonePreAuthorizationConfig $portmoneHelper
     */
    public function __construct(
        PlaseOrderResolve $placeOrderResolve,
        Order $orderModel,
        CustomerRepositoryInterface $customerRepository,
        LayoutFactory $layoutFactory,
        PortmonePreAuthorizationConfig $portmoneHelper,
        Logger $logger,
        TimezoneInterface $timezone,
        PortmonePaymentLink $portmoneLink
    )
    {
        $this->placeOrderResolve = $placeOrderResolve;
        $this->orderModel = $orderModel;
        $this->customerRepository = $customerRepository;
        $this->layoutFactory = $layoutFactory;
        $this->portmoneHelper = $portmoneHelper;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->portmoneLink = $portmoneLink;
    }

    /**
     * @param ResolverInterface $subject
     * @param $resolvedValue
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @throws \Exception
     */
    public function afterResolve(
        ResolverInterface $subject,
        $resolvedValue,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if (!$resolvedValue['order']) {
            throw new GraphQlInputException(__("Order not created"));
        }

        try {
            $orderId = $resolvedValue['order']['order_number'];
            $order = $this->orderModel->loadByIncrementId($orderId);

            if ($order && $order->getPayment()->getMethod() == PortmonePreAuthorization::METHOD_CODE) {
                $resolvedValue['order']['payment_extension_data']['redirect_url'] = $this->portmoneLink->getPaymentLink($order);
                $resolvedValue['order']['payment_extension_data']['html_data'] = '';
                $resolvedValue['order']['payment_extension_data']['payment_method'] = $order->getPayment()->getMethod();
                $this->createLogger($order);
            }
        } catch (\Exception $e) {
            throw new \Exception(__($e->getMessage()));
        }

        return $resolvedValue;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    protected function getPaymentLogic($order)
    {
        $customer = null;
        if ($order->getCustomerId()) {
            $customer = $this->customerRepository->getById($order->getCustomerId());
        }

        $formBlock = $this->layoutFactory->create()
            ->createBlock('\CodeCustom\PortmonePreAuthorization\Block\Portmone\RedirectForm');
        $formBlock->setOrder($order);
        $formBlock->setCustomer($customer);
        $html = $formBlock->getHtml();

        return $html;
    }

    /**
     * @var Order $order
     * @param null $order
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function createLogger($order = null)
    {
        if ($order && $order->getId()) {
            $this->logger->create($order->getIncrementId()
                . '_create_' . $this->getCreatedAt($order->getCreatedAt()),
                'portmone');
            $this->logger->log([
                'order_id' => $order->getIncrementId(),
                'grand_total' => $order->getGrandTotal(),
                'grand_total' => $order->getGrandTotal(),
                'currency_code' => $order->getOrderCurrencyCode(),
                'shipping_city' => $order->getShippingAddress()->getCity(),
                'shipping_street' => is_array($order->getShippingAddress()->getStreet())
                && isset($order->getShippingAddress()->getStreet()[0])
                    ? $order->getShippingAddress()->getStreet()[0]
                    : "",
                'payment' => $order->getPayment()->getMethodInstance()->getTitle(),
                'customer_firstname' => $order->getShippingAddress()->getFirstname(),
                'customer_lastname' => $order->getShippingAddress()->getLastname(),
                'customer_telephone' => $order->getShippingAddress()->getTelephone(),
                'customer_email' => $order->getShippingAddress()->getEmail(),
            ]);
        }
    }

    /**
     * Convert order created time to needed timezone
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
