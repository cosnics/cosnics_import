<?php
namespace Chamilo\Core\Repository\ContentObject\Assessment\Display\Component\Viewer;

use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\ContentObject\Assessment\Display\Component\AssessmentViewerComponent;
use Chamilo\Core\Repository\ContentObject\Assessment\Display\Component\Viewer\Wizard\Inc\QuestionDisplay;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;

class AssessmentViewerForm extends FormValidator
{
    const FORM_NAME = 'assessment_viewer_form';
    const PAGE_NUMBER = 'assessment_page_number';

    /**
     *
     * @var AssessmentViewerComponent
     */
    private $assessment_viewer;

    private $questions;

    public function __construct(AssessmentViewerComponent $assessment_viewer, $method = 'post', $action = null)
    {
        parent :: __construct(self :: FORM_NAME, $method, $action);

        $this->assessment_viewer = $assessment_viewer;

        $this->add_general();
        $this->add_buttons();
        $this->add_questions();
        $this->add_answers_from_question_attempts();
        $this->add_buttons();
    }

    public function get_page_number()
    {
        return $this->assessment_viewer->get_questions_page();
    }

    public function get_total_pages()
    {
        return $this->assessment_viewer->get_total_pages();
    }

    public function get_assessment_viewer()
    {
        return $this->assessment_viewer;
    }

    public function add_general()
    {
        $current_page = self :: PAGE_NUMBER . '-' . $this->get_page_number();
        $this->addElement('hidden', $current_page, $this->get_page_number());

        if ($this->get_page_number() == 1)
        {
            $assessment = $this->assessment_viewer->get_assessment();

            $display = ContentObjectRenditionImplementation :: factory(
                $this->assessment_viewer->get_assessment(),
                ContentObjectRendition :: FORMAT_HTML,
                ContentObjectRendition :: VIEW_DESCRIPTION,
                $this->assessment_viewer);
            $this->add_information_message(null, null, $display->render(), true);
        }

        $this->addElement('hidden', 'start_time', '', array('id' => 'start_time'));
        $this->addElement('hidden', 'max_time', '', array('id' => 'max_time'));
        $this->addElement(
            'html',
            ResourceManager :: get_instance()->get_resource_html(
                Path :: getInstance()->getJavascriptPath('Chamilo\Core\Repository\ContentObject\Assessment', true) .
                     'Assessment.js'));

        $start_time = Request :: post('start_time');
        $start_time = $start_time ? $start_time : 0;

        $defaults['start_time'] = $start_time;
        $defaults['max_time'] = ($this->assessment_viewer->get_assessment()->get_maximum_time() * 60);
        $this->setDefaults($defaults);

        $current_time = $defaults['max_time'] - $defaults['start_time'];

        if ($defaults['max_time'] > 0)
        {
            $this->addElement(
                'html',
                ' <br /><div class="time_left">' . Translation :: get('TimeLeft') . '<br /><div class="time">' .
                     $current_time . '</div>' . Translation :: get('SecondsShort') . '</div><br /><br />');
        }
    }

    public function add_buttons()
    {
        // $progress = round(($this->get_page_number() / $this->get_total_pages()) * 100);
        // Display::get_progress_bar($progress)
        // TODO: Temporary fix
        $this->get_page_number();
        $this->get_total_pages();
        // $this->addElement('html', '<div style="float: left; padding: 7px; font-weight: bold; line-height: 100%;">' .
        // Translation :: get('PageNumberOfTotal', array(
        // 'CURRENT' => $this->get_page_number(), 'TOTAL' => $this->get_total_pages())) . '</div>');

        // Add submit button if there is at least one question
        if (count($this->questions) > 0)
        {
            $submit_button = $this->createElement(
                'style_submit_button',
                'submit',
                Translation :: get('Submit', null, Utilities :: COMMON_LIBRARIES),
                array('class' => 'positive submit', 'style' => 'display: none;'));
        }

        if ($this->assessment_viewer->get_configuration()->show_feedback_after_every_page())
        {
            // if (($this->get_page_number() <= $this->assessment_viewer->get_total_pages()))
            // {
            $buttons[] = $this->createElement(
                'style_submit_button',
                'next',
                Translation :: get('Check', null, Utilities :: COMMON_LIBRARIES),
                array('class' => 'normal next'));
            // }
            // else
            // {
            // $buttons[] = $this->createElement('style_submit_button', 'submit', Translation :: get('Finish', null,
            // Utilities :: COMMON_LIBRARIES), array(
            // 'class' => 'positive finish'));
            // }
        }
        else
        {
            if ($this->get_page_number() > 1)
            {
                $buttons[] = $this->createElement(
                    'style_submit_button',
                    'back',
                    Translation :: get('Previous', null, Utilities :: COMMON_LIBRARIES),
                    array('class' => 'previous'));
            }

            if ($this->get_page_number() < $this->get_total_pages())
            {
                $buttons[] = $this->createElement(
                    'style_submit_button',
                    'next',
                    Translation :: get('Next', null, Utilities :: COMMON_LIBRARIES),
                    array('class' => 'next'));
            }
            elseif ($submit_button)
            {
                $submit_button->_attributes['style'] = '';
            }
        }

        if ($submit_button)
        {
            $buttons[] = $submit_button;
        }

        if (count($buttons) > 0)
        {
            $this->addGroup($buttons, 'buttons', null, '&nbsp;', false);
        }

        $renderer = $this->defaultRenderer();
        $renderer->setElementTemplate('<div style="float: right;">{element}</div><br /><br />', 'buttons');
        $renderer->setGroupElementTemplate('{element}', 'buttons');
    }

    public function add_questions()
    {
        $i = (($this->get_page_number() - 1) * $this->assessment_viewer->get_assessment()->get_questions_per_page()) + 1;

        $this->questions = $this->assessment_viewer->get_questions_for_page($this->get_page_number());

        foreach ($this->questions as $question)
        {
            $question_display = QuestionDisplay :: factory($this, $question, $i);
            $question_display->render();

            $i ++;
        }
    }

    /**
     * Adds the answers from the question attempts to the default values of this form so that the form remembers what
     * you have filled in when you navigate through a multi page assessment
     */
    public function add_answers_from_question_attempts()
    {
        $defaults = array();

        $answers = $this->get_assessment_viewer()->get_question_answers();
        foreach ($answers as $question_cid => $answer)
        {
            $this->add_answer_to_form_defaults($question_cid, $answer, $defaults);
        }

        $this->setConstants($defaults);
    }

    /**
     * Formats and adds a single answer to the form defaults
     *
     * @param int $question_cid
     * @param mixed[] $answer
     * @param mixed[] $defaults
     */
    public function add_answer_to_form_defaults($question_cid, $answer, &$defaults)
    {
        $answer = $this->multi_dimensional_array_to_single_dimensional_array($answer);

        foreach ($answer as $option_index => $option_answer)
        {
            $defaults[$question_cid . '_' . $option_index] = $option_answer;
        }
    }
}
