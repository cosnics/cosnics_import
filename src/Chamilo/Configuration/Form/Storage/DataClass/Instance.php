<?php
namespace Chamilo\Configuration\Form\Storage\DataClass;

use Chamilo\Configuration\Form\Storage\DataManager;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 *
 * @package configuration\form
 * @author Sven Vanpoucke <sven.vanpoucke@hogent.be>
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class Instance extends DataClass
{
    const PROPERTY_APPLICATION = 'application';

    const PROPERTY_NAME = 'name';

    private $elements;

    public function add_elements($elements)
    {
        if (!is_array($elements))
        {
            $elements = array($elements);
        }

        foreach ($elements as $element)
        {
            $this->elements[] = $element;
        }
    }

    public function get_application()
    {
        return $this->getDefaultProperty(self::PROPERTY_APPLICATION);
    }

    /**
     * Get the default properties of all user course categories.
     *
     * @return array The property names.
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        return parent::getDefaultPropertyNames(array(self::PROPERTY_NAME, self::PROPERTY_APPLICATION));
    }

    public function get_element($index)
    {
        return $this->elements[$index];
    }

    public function get_elements()
    {
        if (!$this->elements)
        {
            $this->load_elements();
        }

        return $this->elements;
    }

    public function set_elements($elements)
    {
        $this->elements = $elements;
    }

    public function get_name()
    {
        return $this->getDefaultProperty(self::PROPERTY_NAME);
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'configuration_form_instance';
    }

    public function load_elements()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(Element::class, Element::PROPERTY_DYNAMIC_FORM_ID),
            new StaticConditionVariable($this->get_id())
        );
        $elements = DataManager::retrieve_dynamic_form_elements($condition);
        $this->set_elements($elements);

        return $this->elements;
    }

    public function set_application($application)
    {
        $this->setDefaultProperty(self::PROPERTY_APPLICATION, $application);
    }

    public function set_name($name)
    {
        $this->setDefaultProperty(self::PROPERTY_NAME, $name);
    }
}
