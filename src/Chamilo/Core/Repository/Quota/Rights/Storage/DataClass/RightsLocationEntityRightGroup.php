<?php
namespace Chamilo\Core\Repository\Quota\Rights\Storage\DataClass;

use Chamilo\Core\Repository\Quota\Rights\Manager;
use Chamilo\Libraries\Storage\DataClass\DataClass;

/**
 * @package Chamilo\Core\Repository\Quota\Rights\Storage\DataClass
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class RightsLocationEntityRightGroup extends DataClass
{
    public const CONTEXT = Manager::CONTEXT;

    public const PROPERTY_GROUP_ID = 'group_id';
    public const PROPERTY_LOCATION_ENTITY_RIGHT_ID = 'location_entity_right_id';

    /**
     * @param string[] $extendedPropertyNames
     *
     * @return string[]
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        $extendedPropertyNames[] = self::PROPERTY_LOCATION_ENTITY_RIGHT_ID;
        $extendedPropertyNames[] = self::PROPERTY_GROUP_ID;

        return parent::getDefaultPropertyNames($extendedPropertyNames);
    }

    public static function getStorageUnitName(): string
    {
        return 'repository_quote_rights_location_entity_right_group';
    }

    /**
     * @return string
     */
    public function get_group_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_GROUP_ID);
    }

    /**
     * @return string
     */
    public function get_location_entity_right_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_LOCATION_ENTITY_RIGHT_ID);
    }

    public function set_group_id(string $groupId)
    {
        $this->setDefaultProperty(self::PROPERTY_GROUP_ID, $groupId);
    }

    public function set_location_entity_right_id(string $locationEntityRightId)
    {
        $this->setDefaultProperty(self::PROPERTY_LOCATION_ENTITY_RIGHT_ID, $locationEntityRightId);
    }
}
