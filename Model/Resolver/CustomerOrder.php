<?php

namespace CodeCustom\PortmonePreAuthorization\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePreAuthorization;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\LayoutFactory;
use CodeCustom\PortmonePreAuthorization\Model\Curl\Transport;
use CodeCustom\PortmonePreAuthorization\Sdk\Portmone as PortmoneSdk;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePaymentLink;


class CustomerOrder implements ResolverInterface
{

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $portmonePreAuthorizationConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var PortmoneSdk
     */
    protected $portmoneSdk;

    /**
     * @var PortmonePaymentLink
     */
    protected $portmoneLink;

    /**
     * CustomerOrder constructor.
     * @param PortmonePreAuthorizationConfig $portmonePreAuthorizationConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param LayoutFactory $layoutFactory
     * @param Transport $transport
     * @param PortmoneSdk $portmone
     * @param PortmonePaymentLink $portmoneLink
     */
    public function __construct(
        PortmonePreAuthorizationConfig $portmonePreAuthorizationConfig,
        CustomerRepositoryInterface $customerRepository,
        LayoutFactory $layoutFactory,
        Transport $transport,
        PortmoneSdk $portmone,
        PortmonePaymentLink $portmoneLink
    )
    {
        $this->portmonePreAuthorizationConfig = $portmonePreAuthorizationConfig;
        $this->customerRepository = $customerRepository;
        $this->layoutFactory = $layoutFactory;
        $this->transport = $transport;
        $this->portmoneSdk = $portmone;
        $this->portmoneLink = $portmoneLink;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return null[]
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $order = $value['model'];

        return [
            'payment_url' => $this->getPaymentUrl($order)
        ];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    protected function getPaymentLogic($order)
    {
        if ($order->getStatus() != $this->portmonePreAuthorizationConfig->getNewOrderStatus()
            || $order->getPayment()->getMethod() != PortmonePreAuthorization::METHOD_CODE
        ) {
            return null;
        }

        $customer = null;
        if ($order->getCustomerId()) {
            $customer = $this->customerRepository->getById($order->getCustomerId());
        }

        $formBlock = $this->layoutFactory->create()->createBlock('\CodeCustom\PortmonePreAuthorization\Block\Portmone\RedirectForm');
        $formBlock->setOrder($order);
        $formBlock->setCustomer($customer);
        $html = $formBlock->getHtml();

        return $html;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    protected function getPaymentUrl($order)
    {
        if ($order->getStatus() == $this->portmonePreAuthorizationConfig->getNewOrderStatus()
            && $order->getPayment()->getMethod() == PortmonePreAuthorization::METHOD_CODE
        ) {
            return $this->portmoneLink->getPWAPaymentUrl($order);
        }
        return null;
    }
}
