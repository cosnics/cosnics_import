<?php
namespace Chamilo\Core\Repository\ContentObject\AssessmentRatingQuestion\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 *
 * @package repository.lib.content_object.rating_question
 */

/**
 * This class represents an open question
 */
class AssessmentRatingQuestion extends ContentObject implements Versionable
{
    const PROPERTY_CORRECT = 'correct';
    const PROPERTY_FEEDBACK = 'feedback';
    const PROPERTY_HIGH = 'high';
    const PROPERTY_HINT = 'hint';
    const PROPERTY_LOW = 'low';

    public static function getAdditionalPropertyNames(): array
    {
        return array(
            self::PROPERTY_LOW,
            self::PROPERTY_HIGH,
            self::PROPERTY_CORRECT,
            self::PROPERTY_FEEDBACK,
            self::PROPERTY_HINT
        );
    }

    public function get_correct()
    {
        return $this->getAdditionalProperty(self::PROPERTY_CORRECT);
    }

    public function get_feedback()
    {
        return $this->getAdditionalProperty(self::PROPERTY_FEEDBACK);
    }

    public function get_high()
    {
        return $this->getAdditionalProperty(self::PROPERTY_HIGH);
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
        return parent::get_html_editors(array(self::PROPERTY_HINT, self::PROPERTY_FEEDBACK));
    }

    public function get_low()
    {
        return $this->getAdditionalProperty(self::PROPERTY_LOW);
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'repository_assessment_rating_question';
    }

    public static function getTypeName(): string
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class, true);
    }

    public function has_hint()
    {
        return StringUtilities::getInstance()->hasValue($this->get_hint(), true);
    }

    public function set_correct($value)
    {
        $this->setAdditionalProperty(self::PROPERTY_CORRECT, $value);
    }

    public function set_feedback($feedback)
    {
        $this->setAdditionalProperty(self::PROPERTY_FEEDBACK, $feedback);
    }

    public function set_high($value)
    {
        $this->setAdditionalProperty(self::PROPERTY_HIGH, $value);
    }

    public function set_hint($hint)
    {
        return $this->setAdditionalProperty(self::PROPERTY_HINT, $hint);
    }

    public function set_low($value)
    {
        $this->setAdditionalProperty(self::PROPERTY_LOW, $value);
    }
}
