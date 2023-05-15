<?php
namespace Chamilo\Core\Menu\Storage\DataClass;

use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;

/**
 * @package Chamilo\Core\Menu\Storage\DataClass
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class LinkApplicationItem extends Item
{
    public const PROPERTY_SECTION = 'section';

    public const PROPERTY_TARGET = 'target';

    public const PROPERTY_URL = 'url';

    public const TARGET_BLANK = 0;

    public const TARGET_PARENT = 2;

    public const TARGET_SELF = 1;

    public const TARGET_TOP = 3;

    /**
     * @param string[] $defaultProperties
     * @param string[] $additionalProperties
     *
     * @throws \Exception
     */
    public function __construct($defaultProperties = [], $additionalProperties = [])
    {
        parent::__construct($defaultProperties, $additionalProperties);
        $this->setType(__CLASS__);
    }

    /**
     * @return string[]
     */
    public static function getAdditionalPropertyNames(): array
    {
        return [self::PROPERTY_SECTION, self::PROPERTY_URL, self::PROPERTY_TARGET];
    }

    /**
     * @return \Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph
     */
    public function getGlyph()
    {
        return new FontAwesomeGlyph('link', [], null, 'fas');
    }

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->getAdditionalProperty(self::PROPERTY_SECTION);
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'menu_link_application_item';
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->getAdditionalProperty(self::PROPERTY_TARGET);
    }

    /**
     * @return string
     */
    public function getTargetString()
    {
        return self::targetString($this->getTarget());
    }

    /**
     * @param bool $typesOnly
     *
     * @return string[]
     */
    public function getTargetTypes($typesOnly = false)
    {
        $types = [];

        $types[self::TARGET_BLANK] = self::targetString(self::TARGET_BLANK);
        $types[self::TARGET_SELF] = self::targetString(self::TARGET_SELF);
        $types[self::TARGET_PARENT] = self::targetString(self::TARGET_PARENT);
        $types[self::TARGET_TOP] = self::targetString(self::TARGET_TOP);

        return ($typesOnly ? array_keys($types) : $types);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->getAdditionalProperty(self::PROPERTY_URL);
    }

    /**
     * @return string
     * @deprecated Use LinkApplicationItem::getSection()
     */
    public function get_section()
    {
        return $this->getSection();
    }

    /**
     * @return string
     * @deprecated Use LinkApplicationItem::getTarget()
     */
    public function get_target()
    {
        return $this->getTarget();
    }

    /**
     * @return string
     * @deprecated Use LinkApplicationItem::getUrl()
     */
    public function get_url()
    {
        return $this->getUrl();
    }

    /**
     * @param string $section
     */
    public function setSection($section)
    {
        return $this->setAdditionalProperty(self::PROPERTY_SECTION, $section);
    }

    /**
     * @param string $target
     */
    public function setTarget($target)
    {
        return $this->setAdditionalProperty(self::PROPERTY_TARGET, $target);
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        return $this->setAdditionalProperty(self::PROPERTY_URL, $url);
    }

    /**
     * @param string $section
     *
     * @deprecated Use LinkApplicationItem::setSection()
     */
    public function set_section($section)
    {
        return $this->setSection($section);
    }

    /**
     * @param string $target
     *
     * @deprecated Use LinkApplicationItem::setTarget()
     */
    public function set_target($target)
    {
        return $this->setTarget($target);
    }

    /**
     * @param string $url
     *
     * @deprecated Use LinkApplicationItem::setUrl()
     */
    public function set_url($url)
    {
        return $this->setUrl($url);
    }

    /**
     * @param int $target
     *
     * @return string
     */
    public static function targetString(int $target)
    {
        switch ($target)
        {
            case self::TARGET_BLANK :
                return '_blank';
                break;
            case self::TARGET_SELF :
                return '_self';
                break;
            case self::TARGET_PARENT :
                return '_parent';
                break;
            case self::TARGET_TOP :
                return '_top';
                break;
            default:
                return '_blank';
        }
    }
}
