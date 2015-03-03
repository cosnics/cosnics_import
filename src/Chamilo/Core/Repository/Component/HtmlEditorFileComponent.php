<?php
namespace Chamilo\Core\Repository\Component;

use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Processor\HtmlEditorProcessor;
use Chamilo\Libraries\Architecture\Application\ApplicationFactory;
use Chamilo\Libraries\Platform\Session\Request;

class HtmlEditorFileComponent extends Manager
{
    const PARAM_PLUGIN = 'plugin';

    public function __construct($user)
    {
        parent :: __construct($user);
        $this->set_parameter('CKEditor', Request :: get('CKEditor'));
        $this->set_parameter('CKEditorFuncNum', Request :: get('CKEditorFuncNum'));
        $this->set_parameter('langCode', Request :: get('langCode'));
    }

    public function run()
    {
        $plugin = $this->get_plugin();
        $this->set_parameter(self :: PARAM_PLUGIN, $plugin);

        if (! \Chamilo\Core\Repository\Viewer\Manager :: is_ready_to_be_published())
        {
            $factory = new ApplicationFactory(
                $this->getRequest(),
                \Chamilo\Core\Repository\Viewer\Manager :: context(),
                $this->get_user(),
                $this);
            $component = $factory->getComponent();
            $component->set_maximum_select(\Chamilo\Core\Repository\Viewer\Manager :: SELECT_SINGLE);
            return $component->run();
        }
        else
        {
            $processor = HtmlEditorProcessor :: factory(
                $plugin,
                $this,
                \Chamilo\Core\Repository\Viewer\Manager :: get_selected_objects());

            $this->small_header();
            $processor->run();
            $this->small_footer();
        }
    }

    public function get_plugin()
    {
        return Request :: get(self :: PARAM_PLUGIN);
    }

    public function get_allowed_content_object_types()
    {
        $types = array();

        $active_types = \Chamilo\Core\Repository\Storage\DataManager :: get_registered_types(true);

        foreach ($active_types as $active_type)
        {
            if (in_array(
                'Chamilo\Libraries\Architecture\Interfaces\Includeable',
                (array) class_implements($active_type)))
            {
                $types[] = $active_type;
            }
        }
        return $types;
    }

    public function render_header()
    {
        return $this->render_small_header();
    }

    public function render_footer()
    {
        return $this->render_small_footer();
    }
}
