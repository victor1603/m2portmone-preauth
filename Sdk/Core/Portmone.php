<?php

namespace CodeCustom\PortmonePreAuthorization\Sdk\Core;

class Portmone
{
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_UAH = 'UAH';
    const CURRENCY_RUB = 'RUB';
    const CURRENCY_RUR = 'RUR';

    protected $_confirm_url = '';
    protected $_check_status_url = '';

    protected $_checkout_fail_redirect_url = 'checkout/onepage/success/';

    protected $_supportedCurrencies = array(
        self::CURRENCY_EUR,
        self::CURRENCY_USD,
        self::CURRENCY_UAH,
        self::CURRENCY_RUB,
        self::CURRENCY_RUR,
    );
    private $_public_key;
    private $_private_key;
    private $_server_response_code = null;

    /**
     * Portmone constructor.
     * @param $public_key
     * @param $private_key
     */
    public function __construct($public_key, $private_key)
    {
        if (empty($public_key)) {
            throw new InvalidArgumentException('public_key is empty');
        }

        if (empty($private_key)) {
            throw new InvalidArgumentException('private_key is empty');
        }

        $this->_public_key = $public_key;
        $this->_private_key = $private_key;
    }

    /**
     * Return last api response http code
     *
     * @return string|null
     */
    public function get_response_code()
    {
        return $this->_server_response_code;
    }


    /**
     * cnb_signature
     *
     * @param array $params
     *
     * @return string
     */
    public function cnb_signature($params)
    {
        $params      = $this->cnb_params($params);
        $private_key = $this->_private_key;

        $json      = $this->encode_params($params);
        $signature = $this->str_to_sign($private_key . $json . $private_key);

        return $signature;
    }

    /**
     * cnb_params
     *
     * @param array $params
     *
     * @return array $params
     */
    private function cnb_params($params)
    {
        $params['public_key'] = $this->_public_key;

        if (!isset($params['version'])) {
            throw new InvalidArgumentException('version is null');
        }
        if (!isset($params['amount'])) {
            throw new InvalidArgumentException('amount is null');
        }
        if (!isset($params['currency'])) {
            throw new InvalidArgumentException('currency is null');
        }
        if (!in_array($params['currency'], $this->_supportedCurrencies)) {
            throw new InvalidArgumentException('currency is not supported');
        }
        if ($params['currency'] == self::CURRENCY_RUR) {
            $params['currency'] = self::CURRENCY_RUB;
        }
        if (!isset($params['description'])) {
            throw new InvalidArgumentException('description is null');
        }

        return $params;
    }

    /**
     * @param $data
     * @return string
     */
    public function formData($data)
    {
        if (isset($data['isGuest']) && $data['isGuest'] == '1') {

            $result = sprintf("
                        <input id='payee_id' name='payee_id' value='%s' type='hidden'/>
                        <input id='shop_order_number' name='shop_order_number' value='%s' type='hidden'/>
                        <input id='bill_amount' name='bill_amount' value='%s' type='hidden'/>
                        <input id='description' name='description' value='%s' type='hidden'/>
                        <input id='success_url' name='success_url' value='%s' type='hidden'/>
                        <input id='failure_url' name='failure_url' value='%s' type='hidden'/>
                        <input id='lang' name='lang' value='%s' type='hidden'/>",
                            $data['payeeId'],
                            $data['orderId'],
                            $data['grandTotal'],
                            $data['description'],
                            $data['successUrl'],
                            $data['failureUrl'],
                            $data['lang']
                        );
        } else {
            $dataJson = isset($data['params']) ? json_encode($data['params']) : null;
            $result = "<input type='hidden' name='bodyRequest' value='$dataJson' />
                       <input type='hidden' name='typeRequest' value='json' />";
        }

        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function postData($data)
    {
        if (isset($data['isGuest']) && $data['isGuest'] == '1') {
            $result = [
                'payee_id' => $data['payeeId'],
                'shop_order_number' => $data['orderId'],
                'bill_amount' => $data['grandTotal'],
                'description' => $data['description'],
                'success_url' => $data['successUrl'],
                'failure_url' => $data['failureUrl'],
                'lang' => $data['lang']
            ];
        } else {
            $dataJson = isset($data['params']) ? json_encode($data['params']) : null;
            $result = [
                'bodyRequest' => $dataJson,
                'typeRequest' => 'json'
            ];
        }

        return $result;
    }

    /**
     * @param $data
     * @return string
     */
    public function form($data)
    {
        return '';
    }

    /**
     * encode_params
     *
     * @param array $params
     * @return string
     */
    private function encode_params($params)
    {
        return base64_encode(json_encode($params));
    }

    /**
     * decode_params
     *
     * @param string $params
     * @return array
     */
    public function decode_params($params)
    {
        return json_decode(base64_decode($params), true);
    }

    /**
     * str_to_sign
     *
     * @param string $str
     *
     * @return string
     */
    public function str_to_sign($str)
    {
        $signature = base64_encode(sha1($str, 1));

        return $signature;
    }
}
