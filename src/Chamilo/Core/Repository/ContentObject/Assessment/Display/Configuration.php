<?php
namespace Chamilo\Core\Repository\ContentObject\Assessment\Display;

use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Translation\Translation;
use Exception;

/**
 *
 * @package Chamilo\Core\Repository\ContentObject\Assessment\Display
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class Configuration
{
    const ANSWER_FEEDBACK_TYPE_ALL = 7;
    const ANSWER_FEEDBACK_TYPE_CORRECT = 5;
    const ANSWER_FEEDBACK_TYPE_GIVEN = 2;
    const ANSWER_FEEDBACK_TYPE_GIVEN_CORRECT = 3;
    const ANSWER_FEEDBACK_TYPE_GIVEN_WRONG = 4;
    const ANSWER_FEEDBACK_TYPE_NONE = 0;
    const ANSWER_FEEDBACK_TYPE_QUESTION = 1;
    const ANSWER_FEEDBACK_TYPE_WRONG = 6;

    const FEEDBACK_LOCATION_TYPE_BOTH = 3;
    const FEEDBACK_LOCATION_TYPE_NONE = 0;
    const FEEDBACK_LOCATION_TYPE_PAGE = 1;
    const FEEDBACK_LOCATION_TYPE_SUMMARY = 2;

    const PROPERTY_ALLOW_HINTS = 'allow_hints';
    const PROPERTY_FEEDBACK_LOCATION = 'feedback_location';
    const PROPERTY_SHOW_ANSWER_FEEDBACK = 'show_answer_feedback';
    const PROPERTY_SHOW_CORRECTION = 'show_correction';
    const PROPERTY_SHOW_SCORE = 'show_score';
    const PROPERTY_SHOW_SOLUTION = 'show_solution';

    /**
     *
     * @var boolean
     */
    private $allow_hints;

    /**
     *
     * @var boolean
     */
    private $show_score;

    /**
     *
     * @var boolean
     */
    private $show_correction;

    /**
     *
     * @var boolean
     */
    private $show_solution;

    /**
     *
     * @var int
     */
    private $show_answer_feedback;

    /**
     *
     * @var int
     */
    private $feedback_location;

    /**
     *
     * @param boolean $allow_hints
     * @param boolean $show_score
     * @param boolean $show_correction
     * @param boolean $show_solution
     * @param int $show_answer_feedback
     * @param int $feedback_location
     */
    public function __construct(
        $allow_hints = true, $show_score = true, $show_correction = true, $show_solution = true,
        $show_answer_feedback = self::ANSWER_FEEDBACK_TYPE_ALL, $feedback_location = self::FEEDBACK_LOCATION_TYPE_BOTH
    )
    {
        $this->allow_hints = $allow_hints;
        $this->show_score = $show_score;
        $this->show_correction = $show_correction;
        $this->show_solution = $show_solution;
        $this->show_answer_feedback = $show_answer_feedback;
        $this->feedback_location = $feedback_location;
    }

    /**
     *
     * @return boolean
     */
    public function allow_hints()
    {
        return (bool) $this->get_allow_hints();
    }

    /**
     * @param integer $answerFeedbackType
     * @param boolean $isDisabled
     *
     * @return \Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph
     * @throws \Exception
     */
    static public function answerFeedbackGlyph($answerFeedbackType, $isDisabled = false)
    {
        $extraClasses = array();

        switch ($answerFeedbackType)
        {
            case Configuration::ANSWER_FEEDBACK_TYPE_NONE :
                $extraClasses[] = 'text-danger';
                $glyphName = 'times-circle';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_QUESTION :
                $glyphName = 'arrow-alt-circle-down';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_GIVEN :
                $extraClasses[] = 'text-primary';
                $glyphName = 'check-square';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_GIVEN_CORRECT :
                $extraClasses[] = 'text-success';
                $glyphName = 'check-square';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_GIVEN_WRONG :
                $extraClasses[] = 'text-danger';
                $glyphName = 'check-square';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_CORRECT :
                $extraClasses[] = 'text-success';
                $glyphName = 'check';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_WRONG :
                $extraClasses[] = 'text-danger';
                $glyphName = 'times';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_ALL :
                $glyphName = 'copy';
                break;
            default :
                throw new Exception(Translation::get('NoSuchAnswerFeedbackType'));
                break;
        }

        if ($isDisabled)
        {
            $extraClasses[] = 'text-muted';
        }

        return new FontAwesomeGlyph(
            $glyphName, $extraClasses, self::answer_feedback_string($answerFeedbackType), 'fas'
        );
    }

    /**
     *
     * @param int $answer_feedback_type
     *
     * @return string
     * @throws \Exception
     */
    static public function answer_feedback_string($answer_feedback_type)
    {
        switch ($answer_feedback_type)
        {
            case Configuration::ANSWER_FEEDBACK_TYPE_QUESTION :
                $variable = 'AnswerFeedbackQuestion';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_GIVEN :
                $variable = 'AnswerFeedbackOnGivenAnswers';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_GIVEN_CORRECT :
                $variable = 'AnswerFeedbackOnGivenAnswersWhenCorrect';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_GIVEN_WRONG :
                $variable = 'AnswerFeedbackOnGivenAnswersWhenWrong';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_CORRECT :
                $variable = 'AnswerFeedbackOnCorrectAnswers';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_WRONG :
                $variable = 'AnswerFeedbackOnWrongAnswers';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_ALL :
                $variable = 'AnswerFeedbackOnAllAnswers';
                break;
            case Configuration::ANSWER_FEEDBACK_TYPE_NONE :
                $variable = 'AnswerFeedbackNone';
                break;
            default :
                throw new Exception(Translation::get('NoSuchAnswerFeedbackType'));
                break;
        }

        return Translation::get($variable);
    }

    /**
     * Disable feedback per page
     */
    public function disable_feedback_per_page()
    {
        switch ($this->get_feedback_location())
        {
            case self::FEEDBACK_LOCATION_TYPE_BOTH :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_SUMMARY;
                break;
            case self::FEEDBACK_LOCATION_TYPE_PAGE :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_NONE;
                break;
        }
    }

    /**
     * Disable feedback at the end of the assessment
     */
    public function disable_feedback_summary()
    {
        switch ($this->get_feedback_location())
        {
            case self::FEEDBACK_LOCATION_TYPE_BOTH :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_PAGE;
                break;
            case self::FEEDBACK_LOCATION_TYPE_SUMMARY :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_NONE;
                break;
        }
    }

    /**
     * Disable hints
     */
    public function disable_hints()
    {
        $this->allow_hints = false;
    }

    /**
     * Enable feedback per page
     */
    public function enable_feedback_per_page()
    {
        switch ($this->get_feedback_location())
        {
            case self::FEEDBACK_LOCATION_TYPE_NONE :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_PAGE;
                break;
            case self::FEEDBACK_LOCATION_TYPE_SUMMARY :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_BOTH;
                break;
        }
    }

    /**
     * Enable feedback at the end of the assessment
     */
    public function enable_feedback_summary()
    {
        switch ($this->get_feedback_location())
        {
            case self::FEEDBACK_LOCATION_TYPE_NONE :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_SUMMARY;
                break;
            case self::FEEDBACK_LOCATION_TYPE_PAGE :
                $this->feedback_location = self::FEEDBACK_LOCATION_TYPE_BOTH;
                break;
        }
    }

    /**
     * Enable hints
     */
    public function enable_hints()
    {
        $this->allow_hints = true;
    }

    /**
     * @param boolean $isDisabled
     *
     * @return \Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph
     * @throws \Exception
     */
    public function getAnwerFeedbackGlyph($isDisabled = false)
    {
        return self::answerFeedbackGlyph($this->get_show_answer_feedback(), $isDisabled);
    }

    /**
     *
     * @return boolean
     */
    public function get_allow_hints()
    {
        return $this->allow_hints;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function get_answer_feedback_string()
    {
        return self::answer_feedback_string($this->get_show_answer_feedback());
    }

    /**
     *
     * @return int[]
     */
    static public function get_answer_feedback_types()
    {
        return array(
            self::ANSWER_FEEDBACK_TYPE_NONE, self::ANSWER_FEEDBACK_TYPE_QUESTION, self::ANSWER_FEEDBACK_TYPE_GIVEN,
            self::ANSWER_FEEDBACK_TYPE_GIVEN_CORRECT, self::ANSWER_FEEDBACK_TYPE_GIVEN_WRONG,
            self::ANSWER_FEEDBACK_TYPE_CORRECT, self::ANSWER_FEEDBACK_TYPE_WRONG, self::ANSWER_FEEDBACK_TYPE_ALL
        );
    }

    /**
     *
     * @return int
     */
    public function get_feedback_location()
    {
        return $this->feedback_location;
    }

    /**
     *
     * @return int
     */
    public function get_show_answer_feedback()
    {
        return $this->show_answer_feedback;
    }

    /**
     *
     * @return boolean
     */
    public function get_show_correction()
    {
        return $this->show_correction;
    }

    /**
     *
     * @return boolean
     */
    public function get_show_score()
    {
        return $this->show_score;
    }

    /**
     *
     * @return boolean
     */
    public function get_show_solution()
    {
        return $this->show_solution;
    }

    /**
     *
     * @return boolean
     */
    public function show_answer_feedback()
    {
        return $this->get_show_answer_feedback() != self::ANSWER_FEEDBACK_TYPE_NONE;
    }

    /**
     *
     * @return boolean
     */
    public function show_correction()
    {
        return $this->get_show_correction();
    }

    /**
     * Returns whether or not any kind of "feedback" is enabled
     *
     * @return boolean
     */
    public function show_feedback()
    {
        return $this->show_score() || $this->show_correction();
    }

    /**
     * Should feedback be displayed after every page
     *
     * @return boolean
     */
    public function show_feedback_after_every_page()
    {
        return $this->get_feedback_location() == self::FEEDBACK_LOCATION_TYPE_PAGE ||
            $this->get_feedback_location() == self::FEEDBACK_LOCATION_TYPE_BOTH;
    }

    /**
     * Should feedback be displayed as a summary at the end of an assessment
     *
     * @return boolean
     */
    public function show_feedback_summary()
    {
        return $this->get_feedback_location() == self::FEEDBACK_LOCATION_TYPE_SUMMARY ||
            $this->get_feedback_location() == self::FEEDBACK_LOCATION_TYPE_BOTH;
    }

    /**
     *
     * @return boolean
     */
    public function show_score()
    {
        return $this->get_show_score();
    }

    /**
     *
     * @return boolean
     */
    public function show_solution()
    {
        return $this->get_show_solution();
    }
}
