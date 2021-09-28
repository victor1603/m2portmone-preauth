<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Curl;

use Magento\Framework\HTTP\Client\Curl;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Sales\Model\Order;

class Transport
{
    const HEADER                    = 'content-type: application/json';
    const CONFIRM_METHOD            = 'confirmPreauth';
    const REJECT_METHOD             = 'rejectPreauth';

    const CONFIRM_STATUS            = 'PAYED';
    const REJECT_STATUS             = 'REJECTED';

    const NOT_FOUNT_ERROR_CODE      = 12;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $configHelper;

    /**
     * Transport constructor.
     * @param Curl $curl
     * @param PortmonePreAuthorizationConfig $config
     */
    public function __construct(
        Curl $curl,
        PortmonePreAuthorizationConfig $config
    )
    {
        $this->curl = $curl;
        $this->configHelper = $config;
    }

    /**
     * @param null $order
     * @param null $method
     * @param bool $useOrderPrefix
     * @return false|mixed
     * @throws \Exception
     */
    public function sendRequest($order = null, $method = null, $useOrderPrefix = true)
    {
        if (!$order) {
            return false;
        }

        try {
            $this->curl->setHeaders([self::HEADER]);
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->post(
                $this->configHelper->getSubmitUrl(),
                json_encode($this->getRequestBody($order, $method, $useOrderPrefix)));
            $result = json_decode($this->curl->getBody(), true);
            if (isset($result['error']) && $result['error'] && $result['errorCode']) {
                $result['error_code'] = $result['errorCode'];
                $result['error_message'] = $result['error'];
                unset($result['error']);
                unset($result['errorCode']);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }

        if (isset($result['error_code']) && $result['error_code'] == self::NOT_FOUNT_ERROR_CODE && $useOrderPrefix) {
            $result = $this->sendRequest($order, $method, false);
        }

        return isset($result[0]) && !isset($result['status']) ? $result[0] : $result;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getRequestBody($order = null, $method = null, $useOrderPrefix = true)
    {
        if (!$order) {
            return [];
        }
        if (!$method) {
            $method = self::REJECT_METHOD;
        }

        $prefix = $useOrderPrefix ? $this->configHelper->getOrderPrefix() : "";

        return [
            'id' => $order->getId(),
            'method' => $method,
            'params' => [
                'data' => [
                    'login' => $this->configHelper->getLogin($order->getStoreId()),
                    'password' => $this->configHelper->getPassword($order->getStoreId()),
                    'payeeId' => $this->configHelper->getPayeeId($order->getStoreId()),
                    'shopOrderNumber' => $prefix . $order->getIncrementId(),
                    'postauthAmount' => $order->getGrandTotal()
                ]
            ]
        ];
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function getRequestUrl($data)
    {
        $redirectUrl = null;
        try {
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->post(
                $this->configHelper->getSubmitUrl(),
                $data);
            $result = is_array($this->curl->getHeaders())
                ? $this->curl->getHeaders()
                : json_decode($this->curl->getHeaders(), true);
        } catch (\Exception $exception) {
            $redirectUrl = null;
        }

        if ($result && isset($result['location'])) {
            $redirectUrl = $result['location'];
        }

        return $redirectUrl;
    }

    /**
     * Get bit.ly short URL
     * we return long url if bitly service forbiden
     *
     * @param null $longUrl
     * @return mixed|null
     */
    public function getShortenUrl($longUrl = null)
    {
        if (!$longUrl) {
            return null;
        }

        try {
            $data = array('long_url' => $longUrl);
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', 'Bearer ' . $this->configHelper->getBitlyToken());
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->post(
                $this->configHelper->getBitlyUrl(),
                json_encode($data));
            $body = json_decode($this->curl->getBody(), true);
            $result = $body && isset($body['link']) ? $body['link'] : $longUrl;
        } catch (\Exception $exception) {
            $result = null;
        }

        return $result;
    }

}
