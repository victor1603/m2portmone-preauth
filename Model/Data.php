<?php

namespace CodeCustom\PortmonePreAuthorization\Model;

use Magento\Customer\Model\Customer;
use Magento\Sales\Model\Order;
use CodeCustom\PortmonePreAuthorization\Sdk\Portmone;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Customer\Api\Data\CustomerInterface;

class Data
{

    /**
     * @var Portmone
     */
    protected $portmoneSdk;

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $configHelper;

    /**
     * Data constructor.
     * @param Portmone $portmoneSdk
     * @param PortmonePreAuthorizationConfig $configHelper
     */
    public function __construct(
        Portmone $portmoneSdk,
        PortmonePreAuthorizationConfig $configHelper
    )
    {
        $this->portmoneSdk = $portmoneSdk;
        $this->configHelper = $configHelper;
    }

    /**
     * get post data for portmone response
     * @param Order $order
     * @param CustomerInterface $customer
     * @return array
     */
    public function getData(Order $order, CustomerInterface $customer)
    {
        $prefix = $this->configHelper->getOrderPrefix();
        return $this->portmoneSdk->postData(
            [
                'orderId' => $prefix . $order->getIncrementId(),
                'grandTotal' => $order->getGrandTotal(),
                'pToken' => $this->getPToken($customer),
                'cardMask' => $this->getCardMask($customer),
                'customerEmail' => $order->getCustomerEmail(),
                'isGuest' => $order->getCustomerIsGuest(),
                'storeId' => $order->getStoreId()
            ]
        );
    }

    /**
     * get pToken value
     * @param CustomerInterface $customer
     * @return string
     */
    private function getPToken(CustomerInterface $customer)
    {
        if ($customer && $customer->getCustomAttribute('ptoken')) {
            return $customer->getCustomAttribute('ptoken')->getValue();
        }

        return '';
    }

    /**
     * get card mask value
     * @param CustomerInterface $customer
     * @return string
     */
    private function getCardMask(CustomerInterface $customer)
    {
        if ($customer && $customer->getCustomAttribute('card_mask')) {
            return $customer->getCustomAttribute('card_mask')->getValue();
        }

        return '';
    }

}
