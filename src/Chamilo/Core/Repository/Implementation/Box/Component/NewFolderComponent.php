<?php
namespace Chamilo\Core\Repository\Implementation\Box\Component;

use Chamilo\Core\Repository\Implementation\Box\Form\ExternalObjectForm;
use Chamilo\Core\Repository\Implementation\Box\Manager;

class NewFolderComponent extends Manager
{

    public function run()
    {
        $form = new ExternalObjectForm(ExternalObjectForm :: TYPE_NEW_FOLDER, $this->get_url(), $this);
        if ($form->validate())
        {
            $id = $form->create_folder();
            if (! is_null($id))
            {
                $parameters = $this->get_parameters();
                $parameters[Manager :: PARAM_ACTION] = Manager :: ACTION_BROWSE_EXTERNAL_REPOSITORY;
                $this->redirect('Folder is created', false, $parameters);
            }
            else
            {
                $parameters = $this->get_parameters();
                $parameters[Manager :: PARAM_ACTION] = Manager :: ACTION_NEW_FOLDER_EXTERNAL_REPOSITORY;
                $this->redirect('Folder is not created', true, $parameters);
            }
        }
        else
        {
            $html = array();

            $html[] = $this->render_header();
            $html[] = $form->toHtml();
            $html[] = $this->render_footer();

            return implode("\n", $html);
        }
    }
}
