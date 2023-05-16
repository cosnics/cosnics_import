<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Event;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\LearningPathTreeNodeQuestionAttempt;
use Chamilo\Core\Tracking\Storage\DataClass\Event;

/**
 * @package Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Event
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class AttemptLearningPathQuestion extends Event
{
    public const CONTEXT = 'Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking';

    /**
     * @see \Chamilo\Core\Tracking\Storage\DataClass\Event::getTrackerClasses()
     */
    public function getTrackerClasses()
    {
        return [
            LearningPathTreeNodeQuestionAttempt::class
        ];
    }
}