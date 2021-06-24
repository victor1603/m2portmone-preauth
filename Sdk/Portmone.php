<?php

namespace CodeCustom\PortmonePreAuthorization\Sdk;

use CodeCustom\PortmonePreAuthorization\Sdk\Core\Portmone as PortmoneCoreSdk;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;

class Portmone extends PortmoneCoreSdk
{

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $_helper;

    /**
     * Portmone constructor.
     * @param PortmonePreAuthorizationConfig $_helper
     */
    public function __construct(
        PortmonePreAuthorizationConfig $_helper
    )
    {
        $this->_helper = $_helper;
    }

    /**
     * @return PortmoneConfig
     */
    public function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return string[]
     */
    public function getSupportedCurrencies()
    {
        return $this->_supportedCurrencies;
    }

    /**
     * @param $data
     * @return array
     */
    protected function prepareParams($data)
    {
        $params = [];
        $params['paymentTypes'] = $this->getPaymentTypes();
        $params['priorityPaymentTypes'] = $this->getPriorityPaymentTypes();
        $params['payee'] = $this->getPayee();
        $params['order'] = $this->getOrder($data['orderId'], $data['grandTotal']);
        $params['token'] = $this->getToken($data['pToken'], $data['cardMask']);
        $params['payer'] = $this->getPayer($data['customerEmail']);
        return $params;
    }

    /**
     * @param $data
     * @return string
     */
    public function formData($data)
    {
        $params['params'] = $this->prepareParams($data);
        $params['isGuest'] = $data['isGuest'];
        $params['payeeId'] = $this->_helper->getPayeeId();
        $params['orderId'] = $data['orderId'];
        $params['grandTotal'] = $data['grandTotal'];
        $params['description'] = $this->_helper->getDescription();
        $params['successUrl'] = $this->_helper->getSuccessUrl();
        $params['failureUrl'] = $this->_helper->getFailureUrl();
        $params['lang'] = $this->_helper->getLanguage();
        return parent::formData($params);
    }

    /**
     * @param $data
     * @return array
     */
    public function postData($data)
    {
        $params['params'] = $this->prepareParams($data);
        $params['isGuest'] = $data['isGuest'];
        $params['payeeId'] = $this->_helper->getPayeeId();
        $params['orderId'] = $data['orderId'];
        $params['grandTotal'] = $data['grandTotal'];
        $params['description'] = $this->_helper->getDescription();
        $params['successUrl'] = $this->_helper->getSuccessUrl();
        $params['failureUrl'] = $this->_helper->getFailureUrl();
        $params['lang'] = $this->_helper->getLanguage();
        return parent::postData($params);
    }

    /**
     * @param $data
     * @return string|void
     */
    public function form($data)
    {
        parent::form($data);
    }

    /**
     * @return string[]
     */
    private function getPaymentTypes()
    {
        return [
            'card' => 'Y',
            'portmone' => 'Y',
            'token' => 'Y',
            'masterpass' => 'Y',
            'visacheckout' => 'Y',
        ];
    }

    /**
     * @return string[]
     */
    private function getPriorityPaymentTypes()
    {
        return [
            'card' => '1',
            'portmone' => '2',
            'masterpass' => '0',
            'token' => '2'
        ];
    }

    /**
     * @return array
     */
    private function getPayee()
    {
        return [
            'payeeId' => $this->_helper->getPayeeId(),
            'login' => '',
            'signature' => '',
            'shopSiteId' => '',
        ];
    }

    /**
     * @var Magento\Sales\Model\Order $order
     * @param $order
     * @return array
     */
    private function getOrder($orderIncrementId, $orderGrandTotal)
    {
        return [
            'description' => $this->_helper->getDescription($orderIncrementId),
            'shopOrderNumber' => $orderIncrementId,
            'billAmount' => floor($orderGrandTotal),
            'successUrl' => $this->_helper->getSuccessUrl(),
            'failureUrl' => $this->_helper->getFailureUrl(),
            'preauthFlag' => 'Y',
            'billCurrency' => 'UAH',
            'encoding' => ''
        ];
    }

    /**
     * @param null $pToekn
     * @param null $cardMask
     * @return array
     */
    private function getToken($pToekn = null, $cardMask = null)
    {
        return [
            'tokenFlag' => $pToekn ? 'Y' : 'N',
            'returnToken' => 'Y',
            'token' => $pToekn,
            'cardMask' => $cardMask ? $cardMask : '',
            'otherPaymentMethods' => 'Y',
            'sellerToken' => '',
        ];
    }

    /**
     * @param $customerEmail
     * @return array
     */
    private function getPayer($customerEmail)
    {
        return [
            'lang' => $this->_helper->getLanguage(),
            'emailAddress' => $customerEmail
        ];
    }
}
