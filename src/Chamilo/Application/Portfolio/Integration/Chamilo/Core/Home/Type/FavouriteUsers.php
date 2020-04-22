<?php
namespace Chamilo\Application\Portfolio\Integration\Chamilo\Core\Home\Type;

use Chamilo\Application\Portfolio\Favourite\Service\FavouriteService;
use Chamilo\Application\Portfolio\Favourite\Storage\Repository\FavouriteRepository;
use Chamilo\Application\Portfolio\Manager;
use Chamilo\Core\Home\Renderer\Type\Basic\BlockRenderer;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\File\Redirect;

/**
 * Renders the favourite users
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class FavouriteUsers extends BlockRenderer
{

    public function displayContent()
    {
        $html = array();

        $favouriteUsers = $this->getFavouriteService()->findFavouriteUsers($this->getUser());
        if ($favouriteUsers->size() > 0)
        {
            $html[] = '<ul style="list-style: none; margin: 0; padding: 0;">';

            while ($favouriteUser = $favouriteUsers->next_result())
            {
                $redirect = new Redirect(
                    array(
                        Manager::PARAM_CONTEXT => Manager::context(),
                        Manager::PARAM_ACTION => Manager::ACTION_HOME,
                        Manager::PARAM_USER_ID => $favouriteUser[FavouriteRepository::PROPERTY_USER_ID]));

                $portfolioURL = $redirect->getUrl();

                $html[] = '<li style="padding: 3px;">';
                $html[] = '<a href="' . $portfolioURL . '">';
                $html[] = $favouriteUser[User::PROPERTY_FIRSTNAME] . ' ' . $favouriteUser[User::PROPERTY_LASTNAME];
                $html[] = '</a>';
                $html[] = '</li>';
            }

            $html[] = '</ul>';
        }

        return implode(PHP_EOL, $html);
    }

    /**
     *
     * @return \Chamilo\Application\Portfolio\Favourite\Service\FavouriteService
     */
    public function getFavouriteService()
    {
        $container = DependencyInjectionContainerBuilder::getInstance()->createContainer();
        return $container->get(FavouriteService::class);
    }
}
