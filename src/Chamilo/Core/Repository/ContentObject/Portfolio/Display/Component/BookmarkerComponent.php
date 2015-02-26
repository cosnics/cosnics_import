<?php
namespace Chamilo\Core\Repository\ContentObject\Portfolio\Display\Component;

use Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioBookmarkSupport;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Display;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Translation;

/**
 * Component that allows the user to create bookmarks to specific portfolio item
 *
 * @package repository\content_object\portfolio\display
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class BookmarkerComponent extends Manager implements DelegateComponent
{

    /**
     * Executes this component
     */
    public function run()
    {
        parent :: run();

        if (! $this->get_parent() instanceof PortfolioBookmarkSupport)
        {
            $message = Display :: error_message(Translation :: get('BookmarksNotSupported'), true);
            $this->get_tabs_renderer()->set_content($message);

            $html = array();

            $html[] = $this->render_header();
            $html[] = $this->get_tabs_renderer()->render();
            $html[] = $this->render_footer();

            return implode("\n", $html);
        }

        BreadcrumbTrail :: get_instance()->add(
            new Breadcrumb($this->get_url(), Translation :: get('BookmarkerComponent')));

        $form = ContentObjectForm :: factory(
            ContentObjectForm :: TYPE_CREATE,
            $this->get_parent()->get_portfolio_bookmark($this->get_current_step()),
            'create',
            'post',
            $this->get_url());

        if ($form->validate())
        {
            if ($form->create_content_object())
            {
                $success = true;
            }
            else
            {
                $success = false;
            }

            $this->redirect(
                $success ? Translation :: get('BookmarkCreated') : Translation :: get('BookmarkNotCreated'),
                ! $success,
                array(self :: PARAM_ACTION => self :: ACTION_VIEW_COMPLEX_CONTENT_OBJECT));
        }
        else
        {
            $this->get_tabs_renderer()->set_content($form->toHtml());

            $html = array();

            $html[] = $this->render_header();
            $html[] = $this->get_tabs_renderer()->render();
            $html[] = $this->render_footer();

            return implode("\n", $html);
        }
    }
}
