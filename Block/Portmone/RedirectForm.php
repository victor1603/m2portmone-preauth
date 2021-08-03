<?php

namespace CodeCustom\PortmonePreAuthorization\Block\Portmone;

use Magento\Framework\View\Element\Template;
use CodeCustom\PortmonePreAuthorization\Sdk\Portmone as PortmoneSDK;
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Data\Customer;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;

class RedirectForm extends Template
{
    protected $portmoneSdk;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var Customer
     */
    protected $_customer;

    protected $configHelper;

    public function __construct(
        Template\Context $context,
        PortmoneSDK $portmone,
        PortmonePreAuthorizationConfig $configHelper,
        array $data = []
    )
    {
        $this->portmoneSdk = $portmone;
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        if ($this->_order === null) {
            throw new \Exception('Order is not set');
        }
        return $this->_order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->_order = $order;
    }

    /**
     * @return Order
     */
    public function getCustomer()
    {
        return $this->_order;
    }

    /**
     * @param Customer|null $customer
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->_customer = $customer;
    }

    /**
     * @param $attr
     * @return mixed|string
     */
    public function getAttribute($attr)
    {
        if ($attr && $this->_customer && $this->_customer->getCustomAttribute('ptoken')) {
            return $this->_customer->getCustomAttribute('ptoken')->getValue();
        }
        return '';
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        $prefix = $this->configHelper->getOrderPrefix();
        $html = $this->portmoneSdk->formData(
            [
                'orderId' => $prefix . $this->_order->getIncrementId(),
                'grandTotal' => $this->_order->getGrandTotal(),
                'pToken' => $this->getAttribute('ptoken'),
                'cardMask' => $this->getAttribute('card_mask'),
                'customerEmail' => $this->_order->getCustomerEmail(),
                'isGuest' => $this->_order->getCustomerIsGuest(),
                'storeId' => $this->_order->getStoreId()
            ]
        );
        return $html;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->_toHtml();
    }
}
