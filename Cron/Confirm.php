<?php

namespace CodeCustom\PortmonePreAuthorization\Cron;

use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization\Confirmation;

class Confirm
{
    const MAX_ORDER_COUNT = 10;

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $configHelper;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var Confirmation
     */
    protected $confirmationResponse;

    /**
     * Confirm constructor.
     * @param PortmonePreAuthorizationConfig $configHelper
     * @param OrderCollectionFactory $collectionFactory
     * @param Confirmation $confirmationResponse
     */
    public function __construct(
        PortmonePreAuthorizationConfig $configHelper,
        OrderCollectionFactory $collectionFactory,
        Confirmation $confirmationResponse
    )
    {
        $this->configHelper = $configHelper;
        $this->orderCollectionFactory = $collectionFactory;
        $this->confirmationResponse = $confirmationResponse;
    }

    /**
     * @var Order $order
     * @return bool
     */
    public function execute()
    {
        if (!$this->configHelper->isCronConfirmEnable()) {
            return true;
        }

        $orderCollection = $this->getOrderCollection();
        if ($orderCollection->getSize()) {
            /**
             * @var Order $order
             */
            foreach ($orderCollection as $order) {
                $result = $this->confirmationResponse->confirm($order->getIncrementId());
            }
        }
        return true;
    }

    /**
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection|null
     */
    public function getOrderCollection()
    {
        $collection = null;
        if ($this->configHelper->getPostAuthConfirmStatus()) {
            $collection = $this->orderCollectionFactory->create();
            $collection->addAttributeToSelect('*')
                ->addFieldToFilter('status', [
                    'eq' => $this->configHelper->getPostAuthConfirmStatus()
                ]);
            $collection
                ->setPageSize(self::MAX_ORDER_COUNT)
                ->setCurPage(1);
        }

        return $collection;
    }
}
