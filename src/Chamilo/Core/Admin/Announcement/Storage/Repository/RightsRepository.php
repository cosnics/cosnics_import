<?php
namespace Chamilo\Core\Admin\Announcement\Storage\Repository;

use Chamilo\Core\Admin\Announcement\Storage\DataClass\RightsLocation;
use Chamilo\Core\Admin\Announcement\Storage\DataClass\RightsLocationEntityRight;

/**
 * @package Chamilo\Core\Admin\Announcement\Storage\Repository
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class RightsRepository extends \Chamilo\Core\Rights\Storage\Repository\RightsRepository
{
    /**
     * @return string
     */
    public function getRightsLocationClassName(): string
    {
        return RightsLocation::class;
    }

    /**
     * @return string
     */
    public function getRightsLocationEntityRightClassName(): string
    {
        return RightsLocationEntityRight::class;
    }
}