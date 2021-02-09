<?php

namespace CodeCustom\PortmonePreAuthorization\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

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
