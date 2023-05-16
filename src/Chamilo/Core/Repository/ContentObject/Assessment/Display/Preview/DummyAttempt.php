<?php
namespace Chamilo\Core\Repository\ContentObject\Assessment\Display\Preview;

use Chamilo\Core\Repository\ContentObject\Assessment\Display\Attempt\AbstractAttempt;
use Chamilo\Core\Repository\ContentObject\Assessment\Display\Manager;
use Symfony\Component\Uid\Uuid;

/**
 * @package core\repository\content_object\assessment\display
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class DummyAttempt extends AbstractAttempt
{
    public const CONTEXT = Manager::CONTEXT;

    public const PROPERTY_CONTENT_OBJECT_ID = 'content_object_id';

    /**
     * @see \libraries\storage\DataClass::create()
     */
    public function create(): bool
    {
        $this->set_id(Uuid::v4());

        return PreviewStorage::getInstance()->create_assessment_attempt($this);
    }

    /**
     * @see \libraries\storage\DataClass::delete()
     */
    public function delete(): bool
    {
        return PreviewStorage::getInstance()->delete_assessment_attempt($this);
    }

    /**
     * @param string[] $extendedPropertyNames
     *
     * @return string[]
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        $extendedPropertyNames[] = self::PROPERTY_CONTENT_OBJECT_ID;

        return parent::getDefaultPropertyNames($extendedPropertyNames);
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'repository_assessment_preview_assessment_attempt';
    }

    /**
     * @return int
     */
    public function get_content_object_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_CONTENT_OBJECT_ID);
    }

    /**
     * @param int $content_object_id
     */
    public function set_content_object_id($content_object_id)
    {
        $this->setDefaultProperty(self::PROPERTY_CONTENT_OBJECT_ID, $content_object_id);
    }

    /**
     * @see \libraries\storage\DataClass::update()
     */
    public function update(): bool
    {
        return PreviewStorage::getInstance()->update_assessment_attempt($this);
    }
}
