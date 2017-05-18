<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\ConfigProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

abstract class AbstractRelatedItemConfigProvider
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    abstract public function isEnabled();

    /**
     * @return int
     */
    abstract public function getLimit();

    /**
     * @return bool
     */
    abstract public function isBidirectional();
}
