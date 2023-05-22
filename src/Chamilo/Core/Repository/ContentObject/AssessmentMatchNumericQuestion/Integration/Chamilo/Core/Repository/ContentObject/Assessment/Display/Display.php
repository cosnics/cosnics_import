<?php
namespace Chamilo\Core\Repository\ContentObject\AssessmentMatchNumericQuestion\Integration\Chamilo\Core\Repository\ContentObject\Assessment\Display;

use Chamilo\Core\Repository\ContentObject\Assessment\Display\Component\Viewer\QuestionDisplay;
use Chamilo\Core\Repository\ContentObject\Assessment\Storage\DataClass\Assessment;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Translation\Translation;

/**
 * @package Chamilo\Core\Repository\ContentObject\AssessmentMatchNumericQuestion\Integration\Chamilo\Core\Repository\ContentObject\Assessment\Display
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class Display extends QuestionDisplay
{

    public function add_footer()
    {
        $formvalidator = $this->get_formvalidator();

        if ($this->get_question()->has_hint() && $this->get_configuration()->allow_hints())
        {
            $hint_name = 'hint_' . $this->get_complex_content_object_question()->get_id();
            $glyph = new FontAwesomeGlyph('gift', [], null, 'fas');

            $html[] = '<div class="panel-body panel-body-assessment-hint">';
            $html[] = '<a id="' . $hint_name . '" class="btn btn-default hint_button">' . $glyph->render() . ' ' .
                Translation::get('GetAHint') . '</a>';
            $html[] = '</div>';

            $footer = implode(PHP_EOL, $html);
            $formvalidator->addElement('html', $footer);
        }

        parent::add_footer();
    }

    public function add_question_form()
    {
        $formvalidator = $this->get_formvalidator();
        $renderer = $this->get_renderer();
        $clo_question = $this->get_complex_content_object_question();
        $question = $this->get_question();

        $formvalidator->addElement('html', '<div class="panel-body">');
        $formvalidator->addElement('html', $this->get_instruction());

        $textarea_width = '400px';
        $textarea_height = '50px';
        $textarea_style = 'width: ' . $textarea_width . '; height: ' . $textarea_height . ';';

        $element_template = [];
        $element_template[] =
            '<div><!-- BEGIN error --><small class="text-danger">{error}</small><br /><!-- END error -->	{element}';
        $element_template[] = '<div class="clearfix"></div>';
        $element_template[] = '<div class="form_feedback"></div>';
        $element_template[] = '<div class="clearfix"></div>';
        $element_template[] = '</div>';
        $element_template = implode(PHP_EOL, $element_template);

        $name = $clo_question->get_id() . '_0';
        $formvalidator->addElement('textarea', $name, '', ['style' => $textarea_style]);
        $renderer->setElementTemplate($element_template, $name);

        $formvalidator->addElement('html', '</div>');

        $formvalidator->addElement(
            'html', ResourceManager::getInstance()->getResourceHtml(
            $formvalidator->getWebPathBuilder()->getJavascriptPath(Assessment::CONTEXT) . 'GiveHint.js'
        )
        );
    }

    public function get_instruction()
    {
        $instruction = [];
        $question = $this->get_question();

        if ($question->has_description())
        {
            $instruction[] = '<p>';
            $instruction[] = '<strong>';
            $instruction[] = Translation::get('EnterAnswer');
            $instruction[] = '</strong>';
            $instruction[] = '</p>';
        }
        else
        {
            $instruction = [];
        }

        return implode(PHP_EOL, $instruction);
    }

    public function needsDescriptionBorder()
    {
        return true;
    }
}
