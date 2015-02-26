<?php
namespace Chamilo\Libraries\Format\Form;

use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Utilities\ResourceManager;

/**
 * Specific setting / additions for the CKEditor HTML editor All CKEditor settings:
 * http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
 *
 * @author Scaramanga
 */
class FormValidatorCkeditorHtmlEditor extends FormValidatorHtmlEditor
{

    public function create()
    {
        $form = $this->get_form();

        $form->addElement('html', implode("\n", $this->add_pre_javascript_config()));

        $scripts = $this->get_includes();

        foreach ($scripts as $script)
        {
            if (! empty($script))
            {
                $form->addElement('html', $script);
            }
        }

        $form->addElement('html', implode("\n", $this->get_javascript()));

        return parent :: create();
    }

    public function render()
    {
        $html = array();
        $html[] = parent :: render();
        // $html[] = implode("\n", $this->get_includes());
        $html[] = implode("\n", $this->get_javascript());

        return implode("\n", $html);
    }

    public function add_pre_javascript_config()
    {
        $javascript = array();

        $javascript[] = '<script type="text/javascript">';
        $javascript[] = 'window.CKEDITOR_BASEPATH = "' . Path :: getInstance()->getPluginPath('Chamilo\Configuration', true) .
             '" + "html_editor/ckeditor/release/ckeditor/"';
        $javascript[] = '</script>';

        return $javascript;
    }

    public function get_includes()
    {
        $scripts = array();
        $scripts[] = ResourceManager :: get_instance()->get_resource_html(
            Path :: getInstance()->getPluginPath('Chamilo\Configuration', true) . 'html_editor/ckeditor/release/ckeditor/ckeditor.js');
        $scripts[] = ResourceManager :: get_instance()->get_resource_html(
            Path :: getInstance()->getPluginPath('Chamilo\Configuration', true) . 'html_editor/ckeditor/release/ckeditor/adapters/jquery.js');

        return $scripts;
    }

    public function get_javascript()
    {
        $javascript = array();
        $javascript[] = '<script type="text/javascript">';
        $javascript[] = 'var web_path = \'' . Path :: getInstance()->getBasePath(true) . '\'';
        $javascript[] = '$(function ()';
        $javascript[] = '{';
        $javascript[] = '	$(document).ready(function ()';
        $javascript[] = '	{';
        $javascript[] = '         if(typeof $el == \'undefined\'){';
        $javascript[] = '           $el = new Array()';
        $javascript[] = '         }';
        $javascript[] = '	  $el.push($("textarea.html_editor[name=\'' . $this->get_name() . '\']").ckeditor({';
        $javascript[] = $this->get_options()->render_options();
        $javascript[] = '		}, function(){ $(document).trigger(\'ckeditor_loaded\'); }));';
        $javascript[] = '	}); ';
        $javascript[] = '});';
        $javascript[] = '</script>';

        return $javascript;
    }
}
