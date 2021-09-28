<?php

namespace CodeCustom\PortmonePreAuthorization\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use CodeCustom\PortmonePreAuthorization\Model\PortmonePreAuthorization;

class ConfirmOrder implements OptionSourceInterface
{

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $configHelper;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * ConfirmOrder constructor.
     * @param PortmonePreAuthorizationConfig $configHelper
     * @param OrderCollectionFactory $collectionFactory
     */
    public function __construct(
        PortmonePreAuthorizationConfig $configHelper,
        OrderCollectionFactory $collectionFactory
    )
    {
        $this->configHelper = $configHelper;
        $this->orderCollectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getOrderCollection();
    }

    /**
     * @return array
     */
    public function getOrderCollection()
    {
        $result[] = ['value' => '', 'label' => __('Choose order')];
        if ($this->configHelper->getPostAuthConfirmStatus()) {
            $collection = $this->orderCollectionFactory->create();
            $collection->addAttributeToSelect('*')
                ->addFieldToFilter('status', [
                    'eq' => $this->configHelper->getPostAuthConfirmStatus()
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

            if ($collection->getSize()) {
                foreach ($collection as $item) {
                    $result[] = [
                        'value' => $item->getIncrementId(),
                        'label' => $item->getIncrementId()
                            . " - " . $item->getCustomerFirstname() . " " . $item->getCustomerLastname()
                            . " (" . $item->getCreatedAt() . ")",
                    ];
                }
            }
        }

        return $result;
    }
}
