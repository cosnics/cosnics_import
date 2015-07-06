<?php
namespace Chamilo\Application\Survey\Component;

use Chamilo\Application\Survey\Form\PublicationForm;
use Chamilo\Application\Survey\Manager;
use Chamilo\Application\Survey\Storage\DataClass\Publication;
use Chamilo\Application\Survey\Storage\DataManager;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;
use Chamilo\Application\Survey\Repository\PublicationRepository;
use Chamilo\Application\Survey\Service\PublicationService;

/**
 *
 * @package Chamilo\Application\Survey\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class UpdaterComponent extends TabComponent
{

    /**
     * Executes this controller
     */
    public function build()
    {
    $publication = DataManager :: retrieve_by_id(
            Publication :: class_name(), 
            Request :: get(self :: PARAM_PUBLICATION_ID));
        
//         if (! Rights :: get_instance()->is_right_granted(
//             Rights :: RIGHT_EDIT, 
//             $publication->getId()))
        
//         {
//            throw new NotAllowedException();
//         }
        
        $form = new PublicationForm(
            PublicationForm :: TYPE_EDIT, 
            $publication, 
            $this->get_user(), 
            $this->get_url(array(self :: PARAM_PUBLICATION_ID => $publication->getId())), 
            $publication);
        
        if ($form->validate())
        {
            $success = $form->update_publication();
            $this->redirect(
                $success ? Translation :: get('PublicationUpdated') : Translation :: get('PublicationNotUpdated'), 
                ! $success, 
                array(self :: PARAM_ACTION => self :: ACTION_BROWSE));
        }
        else
        {
            $html = array();
            
            $html[] = $this->render_header();
            $html[] =$form->toHtml();
            $html[] = $this->render_footer();
            
            return implode(PHP_EOL, $html);
          
        }
    }

    /**
     * Adds additional breadcrumbs
     *
     * @param \libraries\format\BreadcrumbTrail $breadcrumb_trail
     * @param BreadcrumbTrail $breadcrumb_trail
     */
    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumb_trail)
    {
        $breadcrumb_trail->add(
            new Breadcrumb(
                $this->get_url(array(Manager :: PARAM_ACTION => Manager :: ACTION_BROWSE)),
                Translation :: get('BrowserComponent')));
    }
}