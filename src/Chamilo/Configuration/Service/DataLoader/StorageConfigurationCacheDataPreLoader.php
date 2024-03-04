<?php
namespace Chamilo\Configuration\Service\DataLoader;

use Chamilo\Configuration\Storage\DataClass\Setting;
use Chamilo\Configuration\Storage\Repository\ConfigurationRepository;
use Chamilo\Libraries\Cache\Interfaces\CacheDataPreLoaderInterface;
use Chamilo\Libraries\Cache\Traits\SimpleCacheAdapterHandlerTrait;
use Chamilo\Libraries\Cache\Traits\SimpleCacheDataPreLoaderTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @package Chamilo\Configuration\Service\DataLoader
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 */
class StorageConfigurationCacheDataPreLoader implements CacheDataPreLoaderInterface
{
    use SimpleCacheAdapterHandlerTrait;
    use SimpleCacheDataPreLoaderTrait;

    protected ConfigurationRepository $configurationRepository;

    public function __construct(AdapterInterface $cacheAdapter, ConfigurationRepository $configurationRepository)
    {
        $this->cacheAdapter = $cacheAdapter;
        $this->configurationRepository = $configurationRepository;
    }

    public function getConfigurationRepository(): ConfigurationRepository
    {
        return $this->configurationRepository;
    }

    /**
     * @return string[][]
     */
    public function getDataForCache(): array
    {
        $settings = [];
        $settingRecords = $this->getConfigurationRepository()->findSettingsAsRecords();

        foreach ($settingRecords as $settingRecord)
        {
            $settings[$settingRecord[Setting::PROPERTY_CONTEXT]][$settingRecord[Setting::PROPERTY_VARIABLE]] =
                $settingRecord[Setting::PROPERTY_VALUE];
        }

        return $settings;
    }
}
