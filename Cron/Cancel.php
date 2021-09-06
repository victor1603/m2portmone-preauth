<?php

namespace CodeCustom\PortmonePreAuthorization\Cron;

use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePreAuthorization;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use CodeCustom\PortmonePreAuthorization\Model\Response\PostAuthorization\Cancellation;

class Cancel
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
     * @var Cancellation
     */
    protected $cancellationResponse;

    /**
     * Cancel constructor.
     * @param PortmonePreAuthorizationConfig $configHelper
     * @param OrderCollectionFactory $collectionFactory
     * @param Cancellation $cancellationResponse
     */
    public function __construct(
        PortmonePreAuthorizationConfig $configHelper,
        OrderCollectionFactory $collectionFactory,
        Cancellation $cancellationResponse
    )
    {
        $this->configHelper = $configHelper;
        $this->orderCollectionFactory = $collectionFactory;
        $this->cancellationResponse = $cancellationResponse;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        if (!$this->configHelper->isCronCancelEnable()) {
            return true;
        }

        $orderCollection = $this->getOrderCollection();
        if ($orderCollection->getSize()) {
            /**
             * @var Order $order
             */
            foreach ($orderCollection as $order) {
                $result = $this->cancellationResponse->cancel($order->getIncrementId());
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
                    'eq' => $this->configHelper->getPostAuthCancelStatus()
                ]);
            $collection->getSelect()
                ->join(
                    ["s" => "sales_order_payment"],
                    'main_table.entity_id = s.parent_id',
                    array('method')
                )
                ->where('s.method = ?',PortmonePreAuthorization::METHOD_CODE );
            $collection->setOrder(
                'created_at',
                'desc'
            );
            $collection
                ->setPageSize(self::MAX_ORDER_COUNT)
                ->setCurPage(1);
        }

        return $collection;
    }
}
