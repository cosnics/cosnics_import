<?php
namespace Chamilo\Core\User\Component;

use Chamilo\Core\User\Form\TermsConditionsForm;
use Chamilo\Core\User\Manager;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Format\Structure\BreadcrumbGenerator;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;

class TermsConditionsViewerComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        \Chamilo\Libraries\Platform\Session\Session :: unregister('terms_and_conditions_viewed');
        $user = $this->get_user();

        $form = new TermsConditionsForm($user, $this->get_url(), TermsconditionsForm :: TYPE_VIEW);

        if ($form->validate())
        {
            $success = $form->register_terms_user();
            if ($success == 1)
            {
                Redirect :: link();
            }
            else
            {
                $html = array();

                $html[] = $this->render_header();
                $html[] = $form->toHtml();
                $html[] = $this->render_footer();

                return implode(PHP_EOL, $html);
            }
        }
        else
        {
            $html = array();

            $html[] = $this->render_header();
            $html[] = $form->toHtml();
            $html[] = $this->render_footer();

            return implode(PHP_EOL, $html);
        }
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail = new BreadCrumbTrail(false);
        $breadcrumbtrail->add_help('terms_conditions');
    }

    /**
     * Returns the admin breadcrumb generator
     *
     * @return \libraries\format\BreadcrumbGeneratorInterface
     */
    public function get_breadcrumb_generator()
    {
        return new BreadcrumbGenerator($this, BreadcrumbTrail :: get_instance());
    }
}
