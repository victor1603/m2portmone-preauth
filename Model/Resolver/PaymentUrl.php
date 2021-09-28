<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePaymentLink;
use Magento\Sales\Model\Order as OrderModel;

class PaymentUrl implements ResolverInterface
{

    /**
     * @var PortmonePaymentLink
     */
    protected $portmoneLink;

    /**
     * @var OrderModel
     */
    protected $orderModel;

    /**
     * PaymentUrl constructor.
     * @param PortmonePaymentLink $portmoneLink
     * @param OrderModel $orderModel
     */
    public function __construct(
        PortmonePaymentLink $portmoneLink,
        OrderModel $orderModel
    )
    {
        $this->portmoneLink = $portmoneLink;
        $this->orderModel = $orderModel;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $orderId = isset($args['order']) ? base64_decode($args['order']) : null;
        $customerId = isset($args['customer']) ? base64_decode($args['customer']) : null;

        if (!$orderId || !$customerId) {
            throw new GraphQlInputException(__('Canot find order'));
        }

        $order = $this->orderModel->loadByIncrementId($orderId);

        if ($order->getCustomerId() != $customerId) {
            throw new GraphQlInputException(__('Canot find order'));
        }

        $paymentLink = $this->portmoneLink->getPaymentLink($order);

        return ['payment_url' => $paymentLink];
    }
}
