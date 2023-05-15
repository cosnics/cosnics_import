<?php
namespace Chamilo\Core\Repository\ContentObject\AssessmentSelectQuestion\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package Chamilo\Core\Repository\ContentObject\AssessmentSelectQuestion\Storage\DataClass
 */
class AssessmentSelectQuestion extends ContentObject
{
    public const ANSWER_TYPE_CHECKBOX = 'checkbox';

    public const ANSWER_TYPE_RADIO = 'radio';

    public const CONTEXT = 'Chamilo\Core\Repository\ContentObject\AssessmentSelectQuestion';

    public const PROPERTY_ANSWER_TYPE = 'answer_type';
    public const PROPERTY_HINT = 'hint';
    public const PROPERTY_OPTIONS = 'options';

    public function add_option($option)
    {
        $options = $this->get_options();
        $options[] = $option;

        return $this->setAdditionalProperty(self::PROPERTY_OPTIONS, serialize($options));
    }

    public static function getAdditionalPropertyNames(): array
    {
        return [self::PROPERTY_ANSWER_TYPE, self::PROPERTY_OPTIONS, self::PROPERTY_HINT];
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'repository_assessment_select_question';
    }

    public function get_answer_type()
    {
        return $this->getAdditionalProperty(self::PROPERTY_ANSWER_TYPE);
    }

    public function get_default_weight()
    {
        return $this->get_maximum_score();
    }

    public function get_hint()
    {
        return $this->getAdditionalProperty(self::PROPERTY_HINT);
    }

    /**
     * Returns the names of the properties which are UI-wise filled by the integrated html editor
     *
     * @return string[]
     */
    public static function get_html_editors($html_editors = [])
    {
        return parent::get_html_editors([self::PROPERTY_HINT]);
    }

    /**
     * Returns the maximum weight/score a user can receive.
     */
    public function get_maximum_score()
    {
        $max = 0;

        switch ($this->get_answer_type())
        {
            case self::ANSWER_TYPE_CHECKBOX :
                foreach ($this->get_options() as $option)
                {
                    if ($option->is_correct())
                    {
                        $max += $option->get_score();
                    }
                }
                break;
            case self::ANSWER_TYPE_RADIO :
                foreach ($this->get_options() as $option)
                {
                    if ($option->is_correct())
                    {
                        $max = max($max, $option->get_score());
                    }
                }
                break;
        }

        return $max;
    }

    public function get_number_of_options()
    {
        return count($this->get_options());
    }

    /**
     * @return AssessmentSelectQuestionOption[]
     */
    public function get_options()
    {
        if ($result = unserialize($this->getAdditionalProperty(self::PROPERTY_OPTIONS)))
        {
            return $result;
        }

        return [];
    }

    /**
     * @return bool
     */
    public function has_feedback()
    {
        foreach ($this->get_options() as $option)
        {
            if ($option->has_feedback())
            {
                return true;
            }
        }

        return false;
    }

    public function has_hint()
    {
        return StringUtilities::getInstance()->hasValue($this->get_hint(), true);
    }

    public function set_answer_type($answer_type)
    {
        return $this->setAdditionalProperty(self::PROPERTY_ANSWER_TYPE, $answer_type);
    }

    // TODO: should be moved to an additional parent layer "question" which offers a default implementation.

    public function set_hint($hint)
    {
        return $this->setAdditionalProperty(self::PROPERTY_HINT, $hint);
    }

    public function set_options($options)
    {
        return $this->setAdditionalProperty(self::PROPERTY_OPTIONS, serialize($options));
    }
}
