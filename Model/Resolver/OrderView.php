<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Resolver;

use CodeCustom\PortmonePreAuthorization\Model\PortmonePreAuthorization;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePaymentLink;

class OrderView implements ResolverInterface
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    protected $portmoneLink;

    /**
     * OrderView constructor.
     * @param Order $order
     * @param Image $imageHelper
     */
    public function __construct(
        Order $order,
        Image $imageHelper,
        TimezoneInterface $timezone,
        PortmonePaymentLink $portmoneLink
    )
    {
        $this->order = $order;
        $this->imageHelper = $imageHelper;
        $this->timezone = $timezone;
        $this->portmoneLink = $portmoneLink;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        try {
            $order = $this->order->loadByIncrementId($args['order_id']);

            if ($order->getCustomerId() != $context->getUserId()) {
                throw new GraphQlAuthorizationException(__('This user cant view order with id %1.', $args['order_id']));
            }

            $result = $this->getData($order);
        } catch (\Exception $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        }

        return $result;
    }

    /**
     * @param $order Order
     */
    public function getData($order)
    {
        if (!$order->getId()) {
            return [];
        }

        return [
            'increment_id' => $order->getIncrementId(),
            'grand_total' => $order->getGrandTotal(),
            'currency_code' => $order->getOrderCurrencyCode(),
            'created_at' => $this->getCreatedAt($order->getCreatedAt()),
            'shipping_city' => $order->getShippingAddress()->getCity(),
            'shipping_street' => is_array($order->getShippingAddress()->getStreet())
                ? implode(',', $order->getShippingAddress()->getStreet())
                : "",
            'payment' => $order->getPayment()->getMethodInstance()->getTitle(),
            'customer_firstname' => $order->getShippingAddress()->getFirstname(),
            'customer_lastname' => $order->getShippingAddress()->getLastname(),
            'customer_telephone' => $order->getShippingAddress()->getTelephone(),
            'items' => $this->getOrderItems($order),
            'payment_url' => $order->getPayment()->getMethod() == PortmonePreAuthorization::METHOD_CODE
                ? $this->portmoneLink->getPaymentLink($order)
                : null
        ];
    }

    /**
     * @param $order Order
     * @return array
     */
    public function getOrderItems($order)
    {
        if (!$order->getId() && !$order->getItems()) {
            return [];
        }

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $image_url = $this->imageHelper->init($product, 'small_image')
                ->setImageFile($product->getSmallImage())
                ->resize(200)->getUrl();
            $result[] = [
                'sku' => $item->getSku(),
                'image' => $image_url,
                'name' => $item->getName(),
                'qty' => $item->getQtyOrdered(),
                'price' => $item->getPrice()
            ];
        }

        return $result;
    }

    /**
     * Convert order created time to needed timezone
     * @param null $date
     * @return string|null
     */
    public function getCreatedAt($date = null)
    {
        if (!$date) {
            return null;
        }
        return $this->timezone->date(new \DateTime($date))->format('Y-m-d H:i:s');
    }
}
