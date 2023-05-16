<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\LearningPath\Assignment;

/**
 * @package Chamilo\Application\Weblcms\Bridge\Assignment\Storage\DataClass
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class EntryAttachment
    extends \Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\Storage\DataClass\EntryAttachment
{
    public const CONTEXT = 'Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking';

    public static function getStorageUnitName(): string
    {
        return 'tracking_weblcms_learning_path_assignment_entry_attachment';
    }
}
