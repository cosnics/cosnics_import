<?php
namespace Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\Interfaces;

use Chamilo\Application\Weblcms\Tool\Implementation\Ephorus\Storage\DataClass\Request;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\DataClassParameters;

/**
 * Interface AssignmentEphorusSupportInterface
 *
 * @package Chamilo\Core\Repository\ContentObject\Assignment\Display\Interfaces
 */
interface EphorusServiceBridgeInterface
{
    /**
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return int
     */
    public function countAssignmentEntriesWithEphorusRequests(Condition $condition = null);

    /**
     * @param \Chamilo\Libraries\Storage\Query\DataClassParameters $dataClassParameters
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAssignmentEntriesWithEphorusRequests(
        DataClassParameters $dataClassParameters = new DataClassParameters()
    );

    /**
     * @param int[] $entryIds
     *
     * @return Request[]
     */
    public function findEphorusRequestsForAssignmentEntries(array $entryIds = []);

    /**
     * @return bool
     */
    public function isEphorusEnabled();
}