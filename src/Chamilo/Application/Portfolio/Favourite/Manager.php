<?php
namespace Chamilo\Application\Portfolio\Favourite;

use Chamilo\Application\Portfolio\Favourite\Infrastructure\Service\FavouriteService;
use Chamilo\Libraries\Architecture\Application\Application;

/**
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
abstract class Manager extends Application
{
    // Parameters
    const PARAM_FAVOURITE_ID = 'favourite_id';
    const PARAM_FAVOURITE_USER_ID = 'favourite_user_id';
    const PARAM_ACTION = 'favourite_action';
    const PARAM_SOURCE = 'source';

    // Actions
    const ACTION_BROWSE = 'Browser';
    const ACTION_DELETE = 'Deleter';
    const ACTION_CREATE = 'Creator';

    // Default action
    const DEFAULT_ACTION = self::ACTION_BROWSE;
    const SOURCE_USER_BROWSER = 'user_browser';
    const SOURCE_FAVOURITES_BROWSER = 'favourites_browser';

    /**
     *
     * @return \Chamilo\Application\Portfolio\Favourite\Infrastructure\Service\FavouriteService
     */
    public function getFavouriteService()
    {
        return $this->getService(FavouriteService::class);
    }
}
