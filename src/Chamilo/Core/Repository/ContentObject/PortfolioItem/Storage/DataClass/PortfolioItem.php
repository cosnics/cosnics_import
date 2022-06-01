<?php
namespace Chamilo\Core\Repository\ContentObject\PortfolioItem\Storage\DataClass;

use Chamilo\Core\Repository\ContentObject\Portfolio\Storage\DataClass\Portfolio;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\HelperContentObjectSupport;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;

/**
 *
 * @package repository.lib.content_object.portfolio_item
 */
class PortfolioItem extends ContentObject implements Versionable, HelperContentObjectSupport
{
    const PROPERTY_REFERENCE = 'reference_id';

    /**
     *
     * @var Portfolio
     */
    private $reference_object;

    public static function getAdditionalPropertyNames(): array
    {
        return array(self::PROPERTY_REFERENCE);
    }

    public function get_reference()
    {
        return $this->getAdditionalProperty(self::PROPERTY_REFERENCE);
    }

    public function get_reference_object()
    {
        if (!$this->reference_object instanceof Portfolio)
        {
            $this->reference_object = DataManager::retrieve_by_id(
                ContentObject::class, $this->get_reference()
            );
        }

        return $this->reference_object;
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'repository_portfolio_item';
    }

    public static function getTypeName(): string
    {
        return ClassnameUtilities::getInstance()->getClassNameFromNamespace(self::class, true);
    }

    public function set_reference($reference)
    {
        $this->setAdditionalProperty(self::PROPERTY_REFERENCE, $reference);
    }
}
