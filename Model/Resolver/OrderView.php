<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Catalog\Helper\Image;

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
     * OrderView constructor.
     * @param Order $order
     * @param Image $imageHelper
     */
    public function __construct(
        Order $order,
        Image $imageHelper
    )
    {
        $this->order = $order;
        $this->imageHelper = $imageHelper;
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
            'created_at' => $order->getCreatedAt(),
            'shipping_city' => $order->getShippingAddress()->getCity(),
            'shipping_street' => is_array($order->getShippingAddress()->getStreet())
                && isset($order->getShippingAddress()->getStreet()[0])
                ? $order->getShippingAddress()->getStreet()[0]
                : "",
            'payment' => $order->getPayment()->getMethodInstance()->getTitle(),
            'customer_firstname' => $order->getShippingAddress()->getFirstname(),
            'customer_lastname' => $order->getShippingAddress()->getLastname(),
            'customer_telephone' => $order->getShippingAddress()->getTelephone(),
            'items' => $this->getOrderItems($order)
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
}
