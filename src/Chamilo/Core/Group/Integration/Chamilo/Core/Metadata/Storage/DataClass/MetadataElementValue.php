<?php
namespace Chamilo\Core\Group\Integration\Chamilo\Core\Metadata\Storage\DataClass;

use Chamilo\Core\Metadata\Value\Storage\DataClass\ElementValue;

/**
 * Class to store the element values for the given group
 * 
 * @package group\integration\core\metadata
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class MetadataElementValue extends ElementValue
{
    const PROPERTY_GROUP_ID = 'group_id';

    /**
     * ***************************************************************************************************************
     * Extended functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Get the default properties
     * 
     * @param array $extended_property_names
     *
     * @return array The property names.
     */
    public static function get_default_property_names($extended_property_names = array())
    {
        $extended_property_names[] = self :: PROPERTY_GROUP_ID;
        
        return parent :: get_default_property_names($extended_property_names);
    }

    /**
     * ***************************************************************************************************************
     * Getters & Setters *
     * **************************************************************************************************************
     */
    
    /**
     * Returns the group_id
     * 
     * @return int
     */
    public function get_group_id()
    {
        return $this->get_default_property(self :: PROPERTY_GROUP_ID);
    }

    /**
     * Sets the group_id
     * 
     * @param int $group_id
     */
    public function set_group_id($group_id)
    {
        $this->set_default_property(self :: PROPERTY_GROUP_ID, $group_id);
    }
}