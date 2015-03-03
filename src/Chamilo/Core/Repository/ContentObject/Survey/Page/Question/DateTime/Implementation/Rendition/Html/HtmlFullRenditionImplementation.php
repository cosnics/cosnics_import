<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Page\Question\DateTime\Implementation\Rendition\Html;

use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\ContentObject\Survey\Page\Question\DateTime\Implementation\Rendition\HtmlRenditionImplementation;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Platform\Translation;

class HtmlFullRenditionImplementation extends HtmlRenditionImplementation
{

    function render()
    {
        $html[] = ContentObjectRendition :: launch($this);
        $html[] = '<h4>' . Translation :: get('QuestionPreview') . '</h4>';
        $html[] = '<div style="border: 1px solid whitesmoke; padding: 10px; margin-bottom: 10px;">';
        $html[] = $this->get_question_preview();
        $html[] = '</div>';
        return implode(PHP_EOL, $html);
    }

    function get_question_preview($nr = null, $complex_question_id = null)
    {
        $content_object = $this->get_content_object();
        
        if ($complex_question_id)
        {
            $question_id = $complex_question_id;
        }
        else
        {
            $question_id = $content_object->get_id();
        }
        
        $html = array();
        $html[] = $this->get_includes();
        $html[] = '<div class="question" >';
        $html[] = '<div class="title">';
        $html[] = '<div class="number">';
        $html[] = '<div class="bevel">';
        $html[] = $nr != null ? $nr : 'nr.';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<div class="text">';
        $html[] = '<div class="bevel">';
        $title = $content_object->get_question();
        $html[] = $title;
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<div class="clear"></div>';
        $html[] = '</div>';
        
        $html[] = '<div class="instruction">';
        if ($content_object->has_instruction())
        {
            $html[] = $content_object->get_instruction();
        }
        $html[] = '<div class="clear"></div>';
        $html[] = '</div>';
        
        $html[] = '<div class="answer">';
        
        $html[] = '<div class="clear"></div>';
        
        $table_header = array();
        $table_header[] = '<table class="data_table take_survey">';
        $table_header[] = '<thead>';
        $table_header[] = '<tr>';
        $html[] = implode(PHP_EOL, $table_header);
        
        if ($content_object->get_date() == 1)
        {
            $html[] = '<th class="info" >' . Translation :: get('EnterDate') . '</th>';
            $html[] = '</tr>';
            $html[] = '</thead>';
            $html[] = '<tbody>';
            
            $html[] = '<tr>';
            $html[] = '<td>';
            
            $html[] = '<div id="datepicker"></div>';
            $html[] = '<script type="text/javascript" src="' .
                 Path :: getInstance()->namespaceToFullPath(__NAMESPACE__, true) . 'resources/javascript/date.js' .
                 '"></script>';
            $html[] = '</td>';
            $html[] = '</tr>';
            $html[] = '</tbody>';
        }
        
        if ($content_object->get_time() == 1)
        {
            $html[] = '<th class="info" >' . Translation :: get('EnterTime') . '</th>';
            $html[] = '</tr>';
            $html[] = '</thead>';
            $html[] = '<tbody>';
            
            $html[] = '<tr>';
            $html[] = '<td>';
            $html[] = '<div id="timepicker"></div>';
            $html[] = '<script type="text/javascript" src="' .
                 Path :: getInstance()->namespaceToFullPath(__NAMESPACE__, true) . 'resources/javascript/time.js' .
                 '"></script>';
            $html[] = '</td>';
            $html[] = '</tr>';
            $html[] = '</tbody>';
        }
        
        $html[] = '</table>';
        
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<div class="clear"></div>';
        // $html[] = $this->get_javascript($question_id);
        return implode(PHP_EOL, $html);
    }

    function get_includes()
    {
        $scripts = array();
        $scripts[] = ResourceManager :: get_instance()->get_resource_html(
            Path :: getInstance()->getPluginPath('Chamilo\Configuration', true) . 'html_editor/ckeditor/ckeditor.js');
        $scripts[] = ResourceManager :: get_instance()->get_resource_html(
            Path :: getInstance()->getPluginPath('Chamilo\Configuration', true) . 'html_editor/ckeditor/adapters/jquery.js');
        
        return implode(PHP_EOL, $scripts);
    }
    
    // function get_javascript($question_id)
    // {
    // $html_editor_options = array();
    // $html_editor_options['width'] = '100%';
    // $html_editor_options['toolbar'] = 'Assessment';
    // $options = FormValidatorHtmlEditorOptions :: factory(LocalSetting :: get('html_editor'), $html_editor_options);
    
    // $javascript = array();
    // $javascript[] = '<script type="text/javascript">';
    // $javascript[] = 'var web_path = \'' . Path :: getInstance()->getBasePath(true) . '\'';
    // $javascript[] = '$(function ()';
    // $javascript[] = '{';
    // $javascript[] = ' $(document).ready(function ()';
    // $javascript[] = ' {';
    // $javascript[] = ' $("textarea.html_editor[name=\'' . $question_id . '\
    
    // ']").ckeditor({';
    // $javascript[] = $options->render_options();
    // $javascript[] = ' });';
    // $javascript[] = ' });';
    // $javascript[] = '});';
    // $javascript[] = '</script>';
    
    // return implode(PHP_EOL, $javascript);
    // }
}
?>