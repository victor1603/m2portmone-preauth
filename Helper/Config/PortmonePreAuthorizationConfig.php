<?php

namespace CodeCustom\PortmonePreAuthorization\Helper\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class PortmonePreAuthorizationConfig extends AbstractHelper
{
    const XML_PATH_IS_ENABLED                       = 'payment/portmone_pre_authorization_payment/active';
    const XML_PATH_TITLE                            = 'payment/portmone_pre_authorization_payment/title';
    const XML_PATH_PAYEE_ID                         = 'payment/portmone_pre_authorization_payment/payee_id';
    const XML_PATH_LOGIN                            = 'payment/portmone_pre_authorization_payment/login';
    const XML_PATH_PASSWORD                         = 'payment/portmone_pre_authorization_payment/password';
    const XML_PATH_PAYMENT_NEW_STATUS               = 'payment/portmone_pre_authorization_payment/order_status';
    const XML_PATH_SUCCESS_STATUS                   = 'payment/portmone_pre_authorization_payment/order_status_success_hold';
    const XML_PATH_CANCEL_STATUS                    = 'payment/portmone_pre_authorization_payment/order_status_cancel_hold';
    const XML_PATH_PAYMENT_SUCCESS_STATUS           = 'payment/portmone_pre_authorization_payment/payment_success_order_status';
    const XML_PATH_PAYMENT_ERROR_STATUS             = 'payment/portmone_pre_authorization_payment/payment_error_order_status';
    const XML_PATH_PAYMENT_SHIPPENT                 = 'payment/portmone_pre_authorization_payment/allowed_carrier';
    const XML_PATH_DESCRIPTION                      = 'payment/portmone_pre_authorization_payment/description';
    const XML_PATH_SUBMIT_URL                       = 'payment/portmone_pre_authorization_payment/submit_url';
    const XML_PATH_LANGUAGE                         = 'payment/portmone_pre_authorization_payment/language';
    const XML_PATH_SUCCESS_URL                      = 'payment/portmone_pre_authorization_payment/success_url';
    const XML_PATH_FAILURE_URL                      = 'payment/portmone_pre_authorization_payment/failure_url';
    const XML_PATH_FRONT_URL                        = 'payment/portmone_pre_authorization_payment/front_url';
    const XML_PATH_FRONT_URL_CANCEL                 = 'payment/portmone_pre_authorization_payment/front_url_cancel';
    const XML_PATH_ALLOWED_CARRIERS                 = 'payment/portmone_pre_authorization_payment/allowed_carrier';
    const XML_PATH_ORDER_PREFIX                     = 'payment/portmone_pre_authorization_payment/order_prefix';
    const XML_PATH_POSTAUTH_CONFIRM_STATUS          = 'payment/portmone_pre_authorization_payment/payment_hold_complete_status';
    const XML_PATH_POSTAUTH_CANCEL_STATUS           = 'payment/portmone_pre_authorization_payment/payment_hold_cancel_status';

    const XML_PATH_CONFIRM_CRON_ENABLE              = 'portmone_post_auth/cron/portmone_cron_confirm_enable';
    const XML_PATH_CANCEL_CRON_ENABLE               = 'portmone_post_auth/cron/portmone_cron_cancel_enable';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /** Getting system configuration by field path
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        $storeId = $storeId ? $storeId : $this->getSiteStoreId();
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /** Current store id
     * @return int|null
     */
    public function getSiteStoreId()
    {
        try {
            $storeId = $this->_storeManager->getStore()->getId();
            return $storeId;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->scopeConfig->getValue(
            static::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_STORE
        )
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getSubmitUrl()
    {
        return $this->getConfigValue(self::XML_PATH_SUBMIT_URL);
    }

    /**
     * @return mixed
     */
    public function getSuccessUrl()
    {
        return $this->getBaseurl() . 'rest/V1/portmonepreauth/success'; //$this->getBaseurl() . $this->getConfigValue(self::XML_PATH_SUCCESS_URL);
    }

    /**
     * @return mixed
     */
    public function getFailureUrl()
    {
        return $this->getBaseurl() . 'rest/V1/portmonepreauth/failure';//$this->getBaseurl() . $this->getConfigValue(self::XML_PATH_FAILURE_URL);
    }

    /**
     * @return mixed
     */
    public function getPayeeId()
    {
        return $this->getConfigValue(self::XML_PATH_PAYEE_ID);
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->getConfigValue(self::XML_PATH_LOGIN);
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->getConfigValue(self::XML_PATH_PASSWORD);
    }

    /**
     * @return mixed
     */
    public function getDescription($orderIncrementId = null)
    {
        return $this->getConfigValue(self::XML_PATH_DESCRIPTION);
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->getConfigValue(self::XML_PATH_LANGUAGE);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getFrontRedirectUrl($storeId = nul)
    {
        return $this->getConfigValue(self::XML_PATH_FRONT_URL, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getFrontRedirectCancelUrl($storeId = nul)
    {
        return $this->getConfigValue(self::XML_PATH_FRONT_URL_CANCEL, $storeId);
    }

    /**
     * @return mixed
     */
    public function getNewOrderStatus()
    {
        return $this->getConfigValue(self::XML_PATH_PAYMENT_NEW_STATUS);
    }

    /**
     * @return mixed
     */
    public function getHoldSuccessStatus()
    {
        return $this->getConfigValue(self::XML_PATH_SUCCESS_STATUS);
    }

    /**
     * @return mixed
     */
    public function getHoldCancelStatus()
    {
        return $this->getConfigValue(self::XML_PATH_CANCEL_STATUS);
    }

    /**
     * @return mixed
     */
    public function getOrderSuccessStatus()
    {
        return $this->getConfigValue(self::XML_PATH_PAYMENT_SUCCESS_STATUS);
    }

    /**
     * @return mixed
     */
    public function getOrderFailureStatus()
    {
        return $this->getConfigValue(self::XML_PATH_PAYMENT_ERROR_STATUS);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getBaseurl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return mixed
     */
    public function getAllowedCarriers()
    {
        return $this->getConfigValue(self::XML_PATH_ALLOWED_CARRIERS);
    }

    /**
     * @return mixed|string
     */
    public function getOrderPrefix()
    {
        $value = $this->getConfigValue(self::XML_PATH_ORDER_PREFIX);
        return $value ? $value : "";
    }

    /**
     * @return mixed
     */
    public function getPostAuthConfirmStatus()
    {
        return $this->getConfigValue(self::XML_PATH_POSTAUTH_CONFIRM_STATUS);
    }

    /**
     * @return mixed
     */
    public function getPostAuthCancelStatus()
    {
        return $this->getConfigValue(self::XML_PATH_POSTAUTH_CANCEL_STATUS);
    }

    /**
     * @param null $orderId
     * @return mixed|string|string[]|null
     */
    public function parseOrderId($orderId = null)
    {
        $prefix = $this->getOrderPrefix();
        if ($orderId && $prefix) {
            return str_replace($prefix, '', $orderId);
        }
        return $orderId;
    }

    /**
     * @return mixed
     */
    public function isCronConfirmEnable()
    {
        return $this->getConfigValue(self::XML_PATH_CONFIRM_CRON_ENABLE);
    }

    /**
     * @return mixed
     */
    public function isCronCancelEnable()
    {
        return $this->getConfigValue(self::XML_PATH_CANCEL_CRON_ENABLE);
    }

}
