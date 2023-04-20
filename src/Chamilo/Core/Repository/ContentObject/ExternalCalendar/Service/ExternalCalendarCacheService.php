<?php
namespace Chamilo\Core\Repository\ContentObject\ExternalCalendar\Service;

use Chamilo\Libraries\Cache\Traits\CacheAdapterHandlerTrait;
use Psr\Cache\InvalidArgumentException;
use Sabre\VObject;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @package Chamilo\Core\Repository\ContentObject\ExternalCalendar\Service
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class ExternalCalendarCacheService
{
    use CacheAdapterHandlerTrait;

    public const PARAM_LIFETIME = 'lifetime';
    public const PARAM_PATH = 'path';

    public function __construct(AdapterInterface $cacheAdapter)
    {
        $this->cacheAdapter = $cacheAdapter;
    }

    public function getCalendarForPath(string $path): VObject\Component\VCalendar
    {
        $cacheAdapter = $this->getCacheAdapter();
        $cacheIdentifier = md5(serialize([$path]));

        try
        {
            $cacheItem = $cacheAdapter->getItem($cacheIdentifier);

            if (!$cacheItem->isHit())
            {
                $calendarData = '';

                if (!file_exists($path))
                {
                    if ($f = fopen($path, 'r'))
                    {

                        while (!feof($f))
                        {
                            $calendarData .= fgets($f, 4096);
                        }
                        fclose($f);
                    }
                }
                else
                {
                    $calendarData = file_get_contents($path);
                }

                $calendar = VObject\Reader::read($calendarData, VObject\Reader::OPTION_FORGIVING);

                $cacheItem->set($calendar);
                $cacheAdapter->save($cacheItem);
            }

            return $cacheItem->get();
        }
        catch (InvalidArgumentException $e)
        {
            return new VObject\Component\VCalendar();
        }
    }
}