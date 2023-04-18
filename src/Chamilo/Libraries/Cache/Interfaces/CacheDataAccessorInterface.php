<?php
namespace Chamilo\Libraries\Cache\Interfaces;

/**
 * @package Chamilo\Libraries\Cache
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
interface CacheDataAccessorInterface extends DataAccessorInterface
{
    public function clearCacheData(): bool;

    public function getCacheKey(): string;

    public function reloadCacheData();

    public function saveCacheData(): bool;
}