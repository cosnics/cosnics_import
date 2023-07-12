<?php
namespace Chamilo\Core\Repository\ContentObject\AssessmentMultipleChoiceQuestion\Form;

use Chamilo\Core\Repository\ContentObject\AssessmentMultipleChoiceQuestion\Storage\DataClass\AssessmentMultipleChoiceQuestion;
use Chamilo\Core\Repository\ContentObject\AssessmentMultipleChoiceQuestion\Storage\DataClass\AssessmentMultipleChoiceQuestionOption;
use Chamilo\Core\Repository\Form\ContentObjectForm;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\ActionBar\ButtonToolBar;
use Chamilo\Libraries\Format\Structure\ActionBar\Renderer\ButtonToolBarRenderer;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Format\Tabs\Form\FormTab;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package repository.lib.content_object.multiple_choice_question
 */
class AssessmentMultipleChoiceQuestionForm extends ContentObjectForm
{

    public function addHintTab()
    {
        $this->getTabsCollection()->add(
            new FormTab(
                'add-hint', Translation::get('AddHint'), new FontAwesomeGlyph('magic', ['fa-sm']), 'buildHintForm'
            )
        );
    }

    protected function addOptionsButtons()
    {
        $answerType = $this->getSession()->get('mc_answer_type');

        if ($answerType == AssessmentMultipleChoiceQuestion::ANSWER_TYPE_RADIO)
        {
            $switchLabel = Translation::get('SwitchToCheckboxes');
        }
        else
        {
            $switchLabel = Translation::get('SwitchToRadioButtons');
        }

        $buttonToolBar = new ButtonToolBar();

        $buttonToolBar->addItem(
            new Button(
                Translation::get('AddMultipleChoiceOption'), new FontAwesomeGlyph('plus'), ' ',
                Button::DISPLAY_ICON_AND_LABEL, null, ['btn-primary', 'add-option']
            )
        );
        $buttonToolBar->addItem(
            new Button(
                $switchLabel, new FontAwesomeGlyph('retweet'), ' ', Button::DISPLAY_ICON_AND_LABEL, null,
                ['change-answer-type']
            )
        );

        $buttonToolBarRenderer = new ButtonToolBarRenderer($buttonToolBar);

        $this->addElement('html', $buttonToolBarRenderer->render());
    }

    /**
     * Adds the form-fields to the form to provide the possible options for this multiple choice question
     */
    public function add_options()
    {
        $renderer = $this->get_renderer();
        $this->setOptionsSessionValues();

        $number_of_options = (int) $this->getSession()->get('mc_number_of_options');
        $skippedOptions = $this->getSession()->get('mc_skip_options');
        $answerType = $this->getSession()->get('mc_answer_type');

        $this->addElement('category', Translation::get('Options'));

        $this->addElement('hidden', 'mc_answer_type', $answerType, ['id' => 'mc_answer_type']);
        $this->addElement('hidden', 'mc_number_of_options', $number_of_options, ['id' => 'mc_number_of_options']);

        $htmlEditorOptions = [];
        $htmlEditorOptions['width'] = '100%';
        $htmlEditorOptions['height'] = '65';
        $htmlEditorOptions['collapse_toolbar'] = true;
        $htmlEditorOptions['show_tags'] = false;

        $this->addElement('html', '<table class="table table-assessment-question-form"><tbody>');

        for ($option_number = 0; $option_number < $number_of_options; $option_number ++)
        {
            if (!in_array($option_number, $skippedOptions))
            {
                $this->addElement('html', '<tr data-option-id="' . $option_number . '">');

                // Checkbox or radio button
                $attributes = ['class' => 'option-value', 'data-option-id' => $option_number];

                if ($answerType == AssessmentMultipleChoiceQuestion::ANSWER_TYPE_CHECKBOX)
                {
                    $selectionName =
                        AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT . '[' . $option_number . ']';
                    $this->addElement('checkbox', $selectionName, Translation::get('Correct'), '', $attributes);
                }
                else
                {
                    $selectionName = AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT;
                    $this->addElement(
                        'radio', $selectionName, Translation::get('Correct'), '', $option_number, $attributes
                    );
                }

                $renderer->setElementTemplate(
                    '<td class="table-cell-selection cell-stat-x2">{element}</td>', $selectionName
                );

                $this->addElement('html', '<td>');

                // Answer
                $this->add_html_editor(
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_VALUE . '[' . $option_number . ']',
                    Translation::get('Answer'), false, $htmlEditorOptions
                );

                $renderer->setElementTemplate(
                    '<div class="option-answer-field" data-element="' .
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_VALUE . '[' . $option_number . ']' .
                    '">{element}</div>',
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_VALUE . '[' . $option_number . ']'
                );

                // Feedback
                $this->add_html_editor(
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_FEEDBACK . '[' . $option_number . ']',
                    Translation::get('Feedback'), false, $htmlEditorOptions
                );

                $renderer->setElementTemplate(
                    '<div class="option-feedback-field form-assessment-extra-container" data-element="' .
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_FEEDBACK . '[' . $option_number . ']' .
                    '"><label>{label}</label>{element}</div>',
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_FEEDBACK . '[' . $option_number . ']'
                );

                // Score
                $this->addElement(
                    'text', AssessmentMultipleChoiceQuestionOption::PROPERTY_SCORE . '[' . $option_number . ']',
                    Translation::get('Score'), 'size="2"  class="input_numeric form-control"'
                );

                $renderer->setElementTemplate(
                    '<div class="option-score-field form-assessment-extra-container form-inline" data-element="' .
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_SCORE . '[' . $option_number . ']' .
                    '"><label>{label}:</label> {element}</div>',
                    AssessmentMultipleChoiceQuestionOption::PROPERTY_SCORE . '[' . $option_number . ']'
                );

                $this->addElement('html', '</td>');

                $this->addElement('html', '<td class="table-cell-action cell-stat-x2 text-right">');

                $actionButtons = [];

                if ($number_of_options - count($skippedOptions) > 2)
                {
                    $removeClass = 'text-danger';
                }
                else
                {
                    $removeClass = 'text-muted';
                }

                $translator = Translation::getInstance();

                $actionButtons[] =
                    '<span title="' . $translator->getTranslation('Feedback') . '" data-option-id="' . $option_number .
                    '" class="option-action option-feedback fas fa-comment text-primary"></span>';
                $actionButtons[] =
                    '<span title="' . $translator->getTranslation('Score') . '" data-option-id="' . $option_number .
                    '" class="option-action option-score fas fa-percent text-primary"></span>';
                $actionButtons[] =
                    '<span title="' . $translator->getTranslation('Delete', null, StringUtilities::LIBRARIES) .
                    '" data-option-id="' . $option_number . '" class="option-action option-remove fas fa-trash-alt ' .
                    $removeClass . '"></span>';

                $this->addElement('html', implode('<br />' . PHP_EOL, $actionButtons));

                $this->addElement('html', '</td>');
                $this->addElement('html', '</tr>');
            }
        }

        $this->addElement('html', '</tbody></table>');

        $this->addOptionsButtons();
    }

    public function add_options_to_object()
    {
        $object = $this->get_content_object();
        $values = $this->exportValues();
        $answerType = $this->getSession()->get('mc_answer_type');

        $options = [];

        foreach ($values[AssessmentMultipleChoiceQuestionOption::PROPERTY_VALUE] as $option_id => $value)
        {
            $score = $values[AssessmentMultipleChoiceQuestionOption::PROPERTY_SCORE][$option_id];
            $feedback = $values[AssessmentMultipleChoiceQuestionOption::PROPERTY_FEEDBACK][$option_id];

            if ($answerType == AssessmentMultipleChoiceQuestion::ANSWER_TYPE_RADIO)
            {
                $correct = $values[AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT] == $option_id;
            }
            else
            {
                $correct = $values[AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT][$option_id];
            }

            $options[] = new AssessmentMultipleChoiceQuestionOption($value, $correct, $score, $feedback);
        }

        $object->set_answer_type($answerType);
        $object->set_options($options);
    }

    protected function buildBasicQuestionForm()
    {
        $this->add_options();

        $this->addElement(
            'html', $this->getResourceManager()->getResourceHtml(
            $this->getWebPathBuilder()->getJavascriptPath(
                'Chamilo\Core\Repository\ContentObject\AssessmentMultipleChoiceQuestion'
            ) . 'AssessmentMultipleChoiceQuestion.js'
        )
        );
    }

    public function buildHintForm()
    {
        $htmlEditorOptions = [];
        $htmlEditorOptions['width'] = '100%';
        $htmlEditorOptions['height'] = '100';
        $htmlEditorOptions['collapse_toolbar'] = true;
        $htmlEditorOptions['show_tags'] = false;

        $this->add_html_editor(
            AssessmentMultipleChoiceQuestion::PROPERTY_HINT,
            Translation::get('Hint', [], ClassnameUtilities::getInstance()->getNamespaceFromObject($this)), false,
            $htmlEditorOptions
        );
    }

    protected function build_creation_form($htmleditor_options = [], $in_tab = false)
    {
        parent::build_creation_form($this->getDescriptionHtmlEditorOptions());

        $this->buildBasicQuestionForm();
    }

    protected function build_editing_form($htmleditor_options = [], $in_tab = false)
    {
        parent::build_editing_form($this->getDescriptionHtmlEditorOptions());
        $this->buildBasicQuestionForm();
    }

    public function create_content_object()
    {
        $this->set_content_object(new AssessmentMultipleChoiceQuestion());
        $this->processSubmittedData();

        return parent::create_content_object();
    }

    public function generateTabs()
    {
        $this->addDefaultTab();
        $this->addHintTab();
        $this->addInstructionsTab();
        $this->addMetadataTabs();
    }

    protected function getDescriptionHtmlEditorOptions()
    {
        $htmlEditorOptions = [];
        $htmlEditorOptions['width'] = '100%';
        $htmlEditorOptions['height'] = '100';
        $htmlEditorOptions['collapse_toolbar'] = false;
        $htmlEditorOptions['show_tags'] = false;

        return $htmlEditorOptions;
    }

    public function processSubmittedData()
    {
        $this->get_content_object()->set_hint($this->exportValue(AssessmentMultipleChoiceQuestion::PROPERTY_HINT));
        $this->add_options_to_object();
    }

    public function setDefaults($defaults = [], $filter = null)
    {
        if (!$this->isSubmitted())
        {
            $object = $this->get_content_object();
            $defaults[AssessmentMultipleChoiceQuestion::PROPERTY_HINT] = $object->get_hint();

            if ($object->get_number_of_options() != 0)
            {
                $options = $object->get_options();

                foreach ($options as $index => $option)
                {
                    $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_VALUE][$index] =
                        $option->get_value() ? $option->get_value() : 0;
                    $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_SCORE][$index] =
                        $option->get_score() ? $option->get_score() : 0;
                    $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_FEEDBACK][$index] =
                        $option->get_feedback();

                    if ($object->get_answer_type() == AssessmentMultipleChoiceQuestion::ANSWER_TYPE_CHECKBOX)
                    {
                        $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT][$index] =
                            $option->is_correct();
                    }
                    elseif ($option->is_correct())
                    {
                        $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT] = $index;
                    }
                }
            }
            else
            {
                $number_of_options = (int) $this->getSession()->get('mc_number_of_options');

                for ($option_number = 0; $option_number < $number_of_options; $option_number ++)
                {
                    $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_SCORE][$option_number] = 0;
                }

                $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT] = 0;
                $defaults[AssessmentMultipleChoiceQuestionOption::PROPERTY_SCORE][0] = 1;
            }
        }

        parent::setDefaults($defaults);
    }

    public function setOptionsSessionValues()
    {
        if (!$this->isSubmitted())
        {
            $this->getSession()->remove('mc_number_of_options');
            $this->getSession()->remove('mc_skip_options');
            $this->getSession()->remove('mc_answer_type');
        }

        $session = $this->getSession();

        if (!$session->has('mc_number_of_options'))
        {
            $session->set('mc_number_of_options', 3);
        }

        if (!$session->has('mc_skip_options'))
        {
            $session->set('mc_skip_options', []);
        }

        if (!$session->has('mc_answer_type'))
        {
            $session->set('mc_answer_type', AssessmentMultipleChoiceQuestion::ANSWER_TYPE_RADIO);
        }

        $extraOptionRequested = $this->getRequest()->request->get('add');
        $removedOptions = $this->getRequest()->request->get('remove');
        $answerTypeChanged =$this->getRequest()->request->get('change_answer_type');

        if (isset($extraOptionRequested))
        {
            $this->getSession()->set(
                'mc_number_of_options', ($this->getSession()->get('mc_number_of_options') + 1)
            );
        }

        if (isset($removedOptions))
        {
            $indexes = array_keys($removedOptions);
            $skippedOptions = $this->getSession()->get('mc_skip_options');
            $skippedOptions[] = $indexes[0];

            $this->getSession()->set('mc_skip_options', $skippedOptions);
        }

        if (isset($answerTypeChanged))
        {
            $currentAnswerType = $this->getSession()->get('mc_answer_type');

            if ($currentAnswerType == AssessmentMultipleChoiceQuestion::ANSWER_TYPE_RADIO)
            {
                $newAnswerType = AssessmentMultipleChoiceQuestion::ANSWER_TYPE_CHECKBOX;
            }
            else
            {
                $newAnswerType = AssessmentMultipleChoiceQuestion::ANSWER_TYPE_RADIO;
            }

            $this->getSession()->set('mc_answer_type', $newAnswerType);
        }

        $object = $this->get_content_object();

        if (!$this->isSubmitted() && $object->get_number_of_options() != 0)
        {
            $this->getSession()->set('mc_number_of_options', $object->get_number_of_options());
            $this->getSession()->set('mc_answer_type', $object->get_answer_type());
        }
    }

    public function update_content_object()
    {
        $this->processSubmittedData();

        return parent::update_content_object();
    }

    public function validate(): bool
    {
        $extraOptionRequested = $this->getRequest()->request->get('add');
        $removedOptions = $this->getRequest()->request->get('remove');
        $answerTypeChanged = $this->getRequest()->request->get('change_answer_type');

        if (isset($extraOptionRequested) || isset($removedOptions) || isset($answerTypeChanged))
        {
            return false;
        }

        return parent::validate();
    }

    public function validate_selected_answers($fields)
    {
        $answerType = $this->getSession()->get('mc_answer_type');

        if (!isset($fields[AssessmentMultipleChoiceQuestionOption::PROPERTY_CORRECT]))
        {
            $message = ($answerType == AssessmentMultipleChoiceQuestion::ANSWER_TYPE_CHECKBOX ? Translation::get(
                'SelectAtLeastOneCorrectAnswer'
            ) : Translation::get('SelectACorrectAnswer'));

            return ['change_answer_type' => $message];
        }

        return true;
    }
}
