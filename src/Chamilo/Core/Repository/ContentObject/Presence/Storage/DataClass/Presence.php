<?php
namespace Chamilo\Core\Repository\ContentObject\Presence\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;

/**
 *
 * @package repository.lib.content_object.presence
 */
/**
 * This class represents a presence
 */
class Presence extends ContentObject implements Versionable
{
    const PROPERTY_OPTIONS = 'options';
    const PROPERTY_HAS_CHECKOUT = 'has_checkout';

    const FIXED_STATUS_DEFAULTS_EN = '[{"id": 1, "type": "fixed", "title": "Absent"},{"id": 2, "type": "fixed", "title": "Authorized absent"},{"id": 3, "type": "fixed", "title": "Present"},{"id": 4, "type": "semifixed", "title": "Online present", "aliasses": 3}]';
    const FIXED_STATUS_DEFAULTS_NL = '[{"id": 1, "type": "fixed", "title": "Afwezig"},{"id": 2, "type": "fixed", "title": "Gewettigd afwezig"},{"id": 3, "type": "fixed", "title": "Aanwezig"},{"id": 4, "type": "semifixed", "title": "Online aanwezig", "aliasses": 3}]';

    const OPTIONS_DEFAULTS_EN = '[{"id": 1, "type": "fixed", "code": "abs", "color": "deep-orange-500"},{"id": 2, "type": "fixed", "code": "aabs", "color": "amber-700"},{"id": 3, "type": "fixed", "code": "pres", "color": "lime-500"},{"id": 4, "type": "semifixed", "code": "online", "color": "green-300"}]';
    const OPTIONS_DEFAULTS_NL = '[{"id": 1, "type": "fixed", "code": "afw", "color": "deep-orange-500"},{"id": 2, "type": "fixed", "code": "gafw", "color": "amber-700"},{"id": 3, "type": "fixed", "code": "aanw", "color": "lime-500"},{"id": 4, "type": "semifixed", "code": "online", "color": "green-300"}]';

    const COLORS = ['pink', 'blue', 'cyan', 'teal', 'green', 'light-green', 'lime', 'yellow', 'amber', 'deep-orange', 'grey'];
    const VALUES = ['-100', '-300', '-500', '-700', '-900'];

    const ABSENT_STATUS_ID = 1;
    const AUTHORIZED_ABSENT_STATUS_ID = 2;
    const PRESENT_STATUS_ID = 3;
    const ONLINE_PRESENT_STATUS_ID = 4;

    const FIXED_STATUS_IDS = [1, 2, 3];
    const STATUS_TYPE_FIXED = 'fixed';
    const STATUS_TYPE_SEMIFIXED = 'semifixed';
    const STATUS_TYPE_CUSTOM = 'custom';

    public static function get_type_name()
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class_name(), true);
    }

    public static function get_additional_property_names()
    {
        $propertyNames = parent::get_additional_property_names();
        $propertyNames[] = self::PROPERTY_OPTIONS;
        $propertyNames[] = self::PROPERTY_HAS_CHECKOUT;
        return $propertyNames;
    }

    /**
     * @return string
     */
    public function getOptions(): string
    {
        return $this->get_additional_property(self::PROPERTY_OPTIONS);
    }

    /**
     * @param string $options
     *
     * @return $this
     */
    public function setOptions(string $options): Presence
    {
        $this->set_additional_property(self::PROPERTY_OPTIONS, $options);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasCheckout(): bool
    {
        return $this->get_additional_property(self::PROPERTY_HAS_CHECKOUT);
    }

    /**
     * @param bool $hasCheckout
     *
     * @return $this
     */
    public function setHasCheckout(bool $hasCheckout): Presence
    {
        $this->set_additional_property(self::PROPERTY_HAS_CHECKOUT, $hasCheckout);
        return $this;
    }
}