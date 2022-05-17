<?php
namespace Chamilo\Core\Rights\Structure\Storage\DataClass;

use Chamilo\Libraries\Storage\DataClass\DataClass;

/**
 * Defines a structure location
 * 
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class StructureLocation extends DataClass
{
    const PROPERTY_CONTEXT = 'context';
    const PROPERTY_ACTION = 'action';

    /**
     * Get the default properties of all data classes.
     * 
     * @param string[] $extendedPropertyNames
     *
     * @return string[]
     */
    public static function getDefaultPropertyNames($extendedPropertyNames = []): array
    {
        $extendedPropertyNames[] = self::PROPERTY_CONTEXT;
        $extendedPropertyNames[] = self::PROPERTY_ACTION;
        
        return parent::getDefaultPropertyNames($extendedPropertyNames);
    }

    /**
     *
     * @return string
     */
    public function getContext()
    {
        return $this->get_default_property(self::PROPERTY_CONTEXT);
    }

    /**
     *
     * @param string $context
     *
     * @return $this
     */
    public function setContext($context)
    {
        $this->set_default_property(self::PROPERTY_CONTEXT, $context);
        
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getAction()
    {
        return $this->get_default_property(self::PROPERTY_ACTION);
    }

    /**
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->set_default_property(self::PROPERTY_ACTION, $action);
        
        return $this;
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'rights_structure_location';
    }
}