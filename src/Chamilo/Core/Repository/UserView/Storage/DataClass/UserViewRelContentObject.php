<?php
namespace Chamilo\Core\Repository\UserView\Storage\DataClass;

use Chamilo\Libraries\Storage\DataClass\DataClass;

/**
 *
 * @package core\repository\user_view
 * @author Sven Vanpoucke <sven.vanpoucke@ehb.be>
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class UserViewRelContentObject extends DataClass
{
    
    // Properties
    const PROPERTY_USER_VIEW_ID = 'user_view_id';
    const PROPERTY_CONTENT_OBJECT_TEMPLATE_ID = 'content_object_template_id';

    /**
     *
     * @return string[]
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        return parent::getDefaultPropertyNames(
            array(self::PROPERTY_USER_VIEW_ID, self::PROPERTY_CONTENT_OBJECT_TEMPLATE_ID));
    }

    /**
     *
     * @return int
     */
    public function get_user_view_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_USER_VIEW_ID);
    }

    /**
     *
     * @param int $user_view_id
     */
    public function set_user_view_id($user_view_id)
    {
        $this->setDefaultProperty(self::PROPERTY_USER_VIEW_ID, $user_view_id);
    }

    /**
     *
     * @return string
     */
    public function get_content_object_template_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_CONTENT_OBJECT_TEMPLATE_ID);
    }

    /**
     *
     * @param string $content_object_template_id
     */
    public function set_content_object_template_id($content_object_template_id)
    {
        $this->setDefaultProperty(self::PROPERTY_CONTENT_OBJECT_TEMPLATE_ID, $content_object_template_id);
    }
    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'repository_user_view_rel_content_object';
    }

}
