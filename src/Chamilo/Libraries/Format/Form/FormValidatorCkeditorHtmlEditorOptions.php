<?php
namespace Chamilo\Libraries\Format\Form;

use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Platform\Session\Request;

/**
 * The combination of options available for the FormValidatorCkeditorHtmlEditor
 *
 * @author Scaramanga
 */
class FormValidatorCkeditorHtmlEditorOptions extends FormValidatorHtmlEditorOptions
{

    public function get_mapping()
    {
        $mapping = parent :: get_mapping();

        $mapping[self :: OPTION_THEME] = 'skin';
        $mapping[self :: OPTION_COLLAPSE_TOOLBAR] = 'toolbarStartupExpanded';
        $mapping[self :: OPTION_CONFIGURATION] = 'customConfig';
        $mapping[self :: OPTION_FULL_PAGE] = 'fullPage';
        $mapping[self :: OPTION_TEMPLATES] = 'templates_files';

        return $mapping;
    }

    public function process_collapse_toolbar($value)
    {
        if ($value === true)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function set_defaults()
    {
        parent :: set_defaults();
        $application = Request :: get('application');
        $app_sys_path = Path :: getInstance()->namespaceToFullPath($application) .
             '/Resources/Javascript/HtmlEditor/CkeditorConfiguration.js';
        if (file_exists($app_sys_path))
        {
            $path = Path :: getInstance()->namespaceToFullPath($application, true) .
                 '/Resources/Javascript/HtmlEditor/CkeditorConfiguration.js';
        }
        else
        {
            $path = Path :: getInstance()->namespaceToFullPath('Chamilo\Libraries', true) .
                 'Resources/Javascript/CkeditorConfiguration.js';
        }
        $this->set_option(self :: OPTION_CONFIGURATION, $path);
    }
}
