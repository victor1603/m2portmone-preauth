<?php

namespace CodeCustom\PortmonePreAuthorization\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CodeCustom\PortmonePreAuthorization\Helper\Config\PortmonePreAuthorizationConfig;

class Language implements OptionSourceInterface
{

    /**
     * @var PortmonePreAuthorizationConfig
     */
    protected $config;

    /**
     * Language constructor.
     * @param PortmonePreAuthorizationConfig $config
     */
    public function __construct(
        PortmonePreAuthorizationConfig $config
    )
    {
        $this->config = $config;
    }

    /**
     * Available languages
     *
     * @return \string[][]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'ru', 'label' => 'ru'],
            ['value' => 'uk', 'label' => 'uk'],
            ['value' => 'en', 'label' => 'en']
        ];
    }

}
