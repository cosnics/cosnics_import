<?php
namespace Chamilo\Core\Repository\ContentObject\OrderingQuestion\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\Interfaces\VersionableInterface;
use Chamilo\Libraries\Storage\DataClass\Interfaces\DataClassExtensionInterface;
use Chamilo\Libraries\Storage\DataClass\Traits\DataClassExtensionTrait;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package Chamilo\Core\Repository\ContentObject\OrderingQuestion\Storage\DataClass
 */
class OrderingQuestion extends ContentObject implements VersionableInterface, DataClassExtensionInterface
{
    use DataClassExtensionTrait;

    public const CONTEXT = 'Chamilo\Core\Repository\ContentObject\OrderingQuestion';

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
        return [self::PROPERTY_OPTIONS, self::PROPERTY_HINT];
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'repository_ordering_question';
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
        $score = 0;

        foreach ($this->get_options() as $option)
        {
            $score += $option->get_score();
        }

        return $score;
    }

    public function get_number_of_options()
    {
        return count($this->get_options());
    }

    /**
     * @return OrderingQuestionOption[]
     */
    public function get_options()
    {
        if ($result = unserialize($this->getAdditionalProperty(self::PROPERTY_OPTIONS)))
        {
            return $result;
        }

        return [];
    }

    public function has_hint()
    {
        return StringUtilities::getInstance()->hasValue($this->get_hint(), true);
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
