<?php

namespace CodeCustom\PortmonePreAuthorization\Block\Adminhtml\Order\View\Tab;

use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePreAuthorization;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Model\Order;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePaymentLink;

class PaymentLink extends AbstractOrder
{

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $portmoneConfig;

    /**
     * @var PortmonePaymentLink
     */
    protected $portmoneLink;

    /**
     * PaymentLink constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     * @param PortmonePreAuthorizationConfig $portmoneConfig
     * @param PortmonePaymentLink $portmonePaymentLink
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null,
        PortmonePreAuthorizationConfig $portmoneConfig,
        PortmonePaymentLink $portmonePaymentLink
    )
    {
        $this->portmoneConfig = $portmoneConfig;
        $this->portmoneLink = $portmonePaymentLink;
        parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @var Order $order
     * @return false
     */
    public function isPaymentLinkEnabled()
    {
        $order = $this->getOrder();
        if (
            $order->getPayment()->getMethod() == PortmonePreAuthorization::METHOD_CODE
            && $order->getStatus() == $this->portmoneConfig->getNewOrderStatus()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return string|null
     */
    public function getOrderPaymentLink()
    {
        $order = $this->getOrder();
        return $this->portmoneLink->getFullPaymentLink($order);
    }

}
