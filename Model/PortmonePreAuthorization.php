<?php

namespace CodeCustom\PortmonePreAuthorization\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Psr\Log\LoggerInterface;
use Magento\Payment\Model\InfoInterface;
use CodeCustom\PortmonePreAuthorization\Sdk\Portmone as PortmoneSdk;
use Magento\Quote\Api\Data\CartInterface;

class PortmonePreAuthorization extends AbstractMethod
{
    const METHOD_CODE = 'portmone_pre_authorization_payment';

    protected $_code = self::METHOD_CODE;

    protected $_portmone;

    protected $_canCapture = true;
    protected $_canVoid = true;
    protected $_canUseForMultishipping = false;
    protected $_canUseInternal = true;
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_canAuthorize = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canUseCheckout = true;

    protected $_minOrderTotal = 0;
    protected $_supportedCurrencyCodes;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuider,
        PortmoneSdk $_portmone,
        array $data = array()
    )
    {
        $this->_portmone = $_portmone;
        $this->_supportedCurrencyCodes = $_portmone->getSupportedCurrencies();
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
    }


    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|Portmone
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();
        try {
            $payment->setTransactionId('portmone-pre-auth' . $order->getId())->setIsTransactionClosed(0);
            return $this;
        } catch (\Exception $e) {
            $this->debugData(['exception' => $e->getMessage()]);
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->_portmone->getHelper()->isEnabled()) {
            return false;
        }

        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        $allowedCarriers = $this->_portmone->getHelper()->getAllowedCarriers();
        $allowedShippingMethods = $allowedCarriers ? explode(',', $allowedCarriers) : null;

        if (!$allowedShippingMethods || !in_array($shippingMethod, $allowedShippingMethods)) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
