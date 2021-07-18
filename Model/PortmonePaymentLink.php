<?php

namespace CodeCustom\PortmonePreAuthorization\Model;

use Magento\Sales\Model\Order;
use Magento\Customer\Api\CustomerRepositoryInterface;
use CodeCustom\PortmonePreAuthorization\Model\Curl\Transport;
use CodeCustom\PortmonePreAuthorization\Model\Data as TransportData;
use Magento\Store\Model\StoreManagerInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;

class PortmonePaymentLink
{
    const URL_PATH          = 'portmone/pay/order';

    const PWA_URL_PATH      = '/portmone';

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Transport
     */
    protected $curlTransport;

    /**
     * @var Data
     */
    protected $transportData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $portmoneConfig;

    /**
     * PortmonePaymentLink constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param Transport $curlTransport
     * @param Data $transportData
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Transport $curlTransport,
        TransportData $transportData,
        StoreManagerInterface $storeManager,
        PortmonePreAuthorizationConfig $portmoneConfig
    )
    {
        $this->customerRepository = $customerRepository;
        $this->curlTransport = $curlTransport;
        $this->transportData = $transportData;
        $this->storeManager = $storeManager;
        $this->portmoneConfig = $portmoneConfig;
    }

    /**
     * @param Order $order
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInternalPaymentUrl(Order $order)
    {
        return $this->storeManager->getStore()->getBaseUrl()
            . self::URL_PATH
            . '?o=' . base64_encode($order->getIncrementId())
            . '&c=' . base64_encode($order->getCustomerId());
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getPWAPaymentUrl(Order $order)
    {
        return self::PWA_URL_PATH
            . '?o=' . base64_encode($order->getIncrementId())
            . '&c=' . base64_encode($order->getCustomerId());
    }

    /**
     * This method ganerate unique encripted link from PORTMONE
     * @param Order $order
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentLink(Order $order)
    {
        try {
            $customer = null;
            if ($order->getCustomerId()) {
                $customer = $this->customerRepository->getById($order->getCustomerId());
            }
            $result = $this->curlTransport->getRequestUrl(
                $this->transportData->getData($order, $customer)
            );
        } catch (\Exception $exception) {
            $result = null;
        }

        return $result;
    }

    /**
     * @param Order $order
     * @return string|null
     */
    public function getFullPaymentLink(Order $order)
    {
        try {
            if (
                $order->getPayment()->getMethod() == PortmonePreAuthorization::METHOD_CODE
                && $order->getStatus() == $this->portmoneConfig->getNewOrderStatus()
            ) {
                $frontBaseUrl = $this->portmoneConfig->getFrontBaseUrl($order->getStoreId());
                $result = $frontBaseUrl ? $frontBaseUrl . $this->getPWAPaymentUrl($order) : '';
                $result = $this->portmoneConfig->isBitlyPaymentLinkEnabled()
                    ? $this->curlTransport->getShortenUrl($result)
                    : $result;
            }
        } catch (\Exception $exception) {
            $result = null;
        }

        return $result;
    }

}
