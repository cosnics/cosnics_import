<?php
namespace Chamilo\Core\Repository\ContentObject\Portfolio\Display\Component;

use Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioBookmarkSupport;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace;
use Chamilo\Libraries\Format\Display;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Translation\Translation;

/**
 * Component that allows the user to create bookmarks to specific portfolio item
 *
 * @package repository\content_object\portfolio\display
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class BookmarkerComponent extends ItemComponent
{
    /**
     * Executes this component
     */
    public function build()
    {
        if (!$this->get_parent() instanceof PortfolioBookmarkSupport)
        {
            $message = Display::error_message(Translation::get('BookmarksNotSupported'), true);

            $html = [];

            $html[] = $this->render_header();
            $html[] = $message;
            $html[] = $this->render_footer();

            return implode(PHP_EOL, $html);
        }

        $this->getBreadcrumbTrail()->add(new Breadcrumb($this->get_url(), Translation::get('BookmarkerComponent')));

        $form = ContentObjectForm::factory(
            ContentObjectForm::TYPE_CREATE, $this->getCurrentWorkspace(),
            $this->get_parent()->get_portfolio_bookmark($this->get_current_step()), 'create',
            FormValidator::FORM_METHOD_POST, $this->get_url()
        );

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

            $this->redirectWithMessage(
                $success ? Translation::get('BookmarkCreated') : Translation::get('BookmarkNotCreated'), !$success, [
                    self::PARAM_ACTION => self::ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                    self::PARAM_STEP => $this->get_current_step()
                ]
            );
        }
        else
        {
            $html = [];

            $html[] = $this->render_header();
            $html[] = $form->toHtml();
            $html[] = $this->render_footer();

            return implode(PHP_EOL, $html);
        }
    }

    protected function getCurrentWorkspace(): Workspace
    {
        return $this->getService('Chamilo\Core\Repository\CurrentWorkspace');
    }
}
