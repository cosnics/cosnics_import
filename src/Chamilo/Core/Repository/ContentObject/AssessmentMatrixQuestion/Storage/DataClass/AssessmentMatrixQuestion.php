<?php
namespace Chamilo\Core\Repository\ContentObject\AssessmentMatrixQuestion\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;

/**
 *
 * @package repository.lib.content_object.matrix_question
 */
class AssessmentMatrixQuestion extends ContentObject implements Versionable
{
    const MATRIX_TYPE_CHECKBOX = 2;
    const MATRIX_TYPE_RADIO = 1;

    const PROPERTY_MATCHES = 'matches';
    const PROPERTY_MATRIX_TYPE = 'matrix_type';
    const PROPERTY_OPTIONS = 'options';

    public function add_match($match)
    {
        $matches = $this->get_matches();
        $matches[] = $match;

        return $this->set_additional_property(self::PROPERTY_MATCHES, serialize($matches));
    }

    public function add_option($option)
    {
        $options = $this->get_options();
        $options[] = $option;

        return $this->set_additional_property(self::PROPERTY_OPTIONS, serialize($options));
    }

    public static function get_additional_property_names()
    {
        return array(self::PROPERTY_MATCHES, self::PROPERTY_OPTIONS, self::PROPERTY_MATRIX_TYPE);
    }

    public function get_default_weight()
    {
        return $this->get_maximum_score();
    }

    public function get_matches()
    {
        if ($result = unserialize($this->get_additional_property(self::PROPERTY_MATCHES)))
        {
            return $result;
        }

        return array();
    }

    public function get_matrix_type()
    {
        return $this->get_additional_property(self::PROPERTY_MATRIX_TYPE);
    }

    /**
     * Returns the maximum weight/score a user can receive.
     */
    public function get_maximum_score()
    {
        $max = 0;
        $options = $this->get_options();
        foreach ($options as $option)
        {
            $max += $option->get_score();
        }

        return $max;
    }

    public function get_number_of_matches()
    {
        return count($this->get_matches());
    }

    public function get_number_of_options()
    {
        return count($this->get_options());
    }

    /**
     * @return AssessmentMatrixQuestionOption[]
     */
    public function get_options()
    {
        if ($result = unserialize($this->get_additional_property(self::PROPERTY_OPTIONS)))
        {
            return $result;
        }

        return array();
    }

    /**
     * @return string
     */
    public static function get_table_name()
    {
        return 'repository_assessment_matrix_question';
    }

    public static function get_type_name()
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class, true);
    }

    public function set_matches($matches)
    {
        return $this->set_additional_property(self::PROPERTY_MATCHES, serialize($matches));
    }

    public function set_matrix_type($matrix_type)
    {
        $this->set_additional_property(self::PROPERTY_MATRIX_TYPE, $matrix_type);
    }

    // TODO: should be moved to an additional parent layer "question" which offers a default implementation.

    public function set_options($options)
    {
        return $this->set_additional_property(self::PROPERTY_OPTIONS, serialize($options));
    }
}
