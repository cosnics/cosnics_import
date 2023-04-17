<?php
namespace Chamilo\Configuration\Service\DataLoader;

use Chamilo\Configuration\Storage\DataClass\Language;
use Chamilo\Configuration\Storage\Repository\LanguageRepository;
use Chamilo\Libraries\Cache\CacheDataLoaderTrait;
use Chamilo\Libraries\Cache\Interfaces\CacheDataLoaderInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @package Chamilo\Configuration\Service
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 */
class LanguageCacheDataLoader implements CacheDataLoaderInterface
{
    use CacheDataLoaderTrait
    {
        clearCache as protected clearAdapterCache;
    }

    protected LanguageRepository $languageRepository;

    public function __construct(AdapterInterface $cacheAdapter, LanguageRepository $languageRepository)
    {
        $this->cacheAdapter = $cacheAdapter;
        $this->languageRepository = $languageRepository;
    }

    public function clearCache(): bool
    {
        if ($this->getLanguageRepository()->clearLanguageCache())
        {
            return $this->clearAdapterCache();
        }

        return false;
    }

    /**
     * @return string[]
     * @throws \Chamilo\Libraries\Storage\Exception\DataClassNoResultException
     */
    protected function getDataForCache(): array
    {
        $languages = [];
        $languageRecords = $this->getLanguageRepository()->findLanguagesAsRecords();

        foreach ($languageRecords as $languageRecord)
        {
            $languages[$languageRecord[Language::PROPERTY_ISOCODE]] = $languageRecord[Language::PROPERTY_ORIGINAL_NAME];
        }

        return $languages;
    }

    public function getLanguageRepository(): LanguageRepository
    {
        return $this->languageRepository;
    }
}
