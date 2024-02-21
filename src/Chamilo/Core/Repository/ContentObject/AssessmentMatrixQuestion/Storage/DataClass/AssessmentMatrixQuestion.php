<?php
namespace Chamilo\Core\Repository\ContentObject\AssessmentMatrixQuestion\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\Interfaces\VersionableInterface;
use Chamilo\Libraries\Storage\DataClass\Interfaces\CompositeDataClassExtensionInterface;

/**
 * @package Chamilo\Core\Repository\ContentObject\AssessmentMatrixQuestion\Storage\DataClass
 */
class AssessmentMatrixQuestion extends ContentObject
    implements VersionableInterface, CompositeDataClassExtensionInterface
{
    public const CONTEXT = 'Chamilo\Core\Repository\ContentObject\AssessmentMatrixQuestion';

    public const MATRIX_TYPE_CHECKBOX = 2;
    public const MATRIX_TYPE_RADIO = 1;

    public const PROPERTY_MATCHES = 'matches';
    public const PROPERTY_MATRIX_TYPE = 'matrix_type';
    public const PROPERTY_OPTIONS = 'options';

    public function add_match($match)
    {
        $matches = $this->get_matches();
        $matches[] = $match;

        return $this->setAdditionalProperty(self::PROPERTY_MATCHES, serialize($matches));
    }

    public function add_option($option)
    {
        $options = $this->get_options();
        $options[] = $option;

        return $this->setAdditionalProperty(self::PROPERTY_OPTIONS, serialize($options));
    }

    public static function getAdditionalPropertyNames(): array
    {
        return parent::getAdditionalPropertyNames(
            [self::PROPERTY_MATCHES, self::PROPERTY_OPTIONS, self::PROPERTY_MATRIX_TYPE]
        );
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'repository_assessment_matrix_question';
    }

    public function get_default_weight()
    {
        return $this->get_maximum_score();
    }

    public function get_matches()
    {
        if ($result = unserialize($this->getAdditionalProperty(self::PROPERTY_MATCHES)))
        {
            return $result;
        }

        return [];
    }

    public function get_matrix_type()
    {
        return $this->getAdditionalProperty(self::PROPERTY_MATRIX_TYPE);
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
        if ($result = unserialize($this->getAdditionalProperty(self::PROPERTY_OPTIONS)))
        {
            return $result;
        }

        return [];
    }

    public function set_matches($matches)
    {
        return $this->setAdditionalProperty(self::PROPERTY_MATCHES, serialize($matches));
    }

    public function set_matrix_type($matrix_type)
    {
        $this->setAdditionalProperty(self::PROPERTY_MATRIX_TYPE, $matrix_type);
    }

    // TODO: should be moved to an additional parent layer "question" which offers a default implementation.

    public function set_options($options)
    {
        return $this->setAdditionalProperty(self::PROPERTY_OPTIONS, serialize($options));
    }
}
