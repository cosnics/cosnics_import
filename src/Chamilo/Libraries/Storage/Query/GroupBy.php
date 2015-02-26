<?php
namespace Chamilo\Libraries\Storage\Query;

use Chamilo\Libraries\Architecture\Interfaces\Hashable;

/**
 * Describes the group by functionality of a query.
 * Uses ConditionVariable to define the group_by's
 *
 * @package Chamilo\Libraries\Storage\Query
 * @author Sven Vanpoucke <sven.vanpoucke@hogent.be>
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class GroupBy implements Hashable
{

    /**
     * List of ConditionVariables to group by
     *
     * @var \Chamilo\Libraries\Storage\Query\Variable\ConditionVariable[]
     */
    private $group_by;

    /**
     *
     * @param \Chamilo\Libraries\Storage\Query\Variable\ConditionVariable[] $group_by
     */
    public function __construct($group_by = array())
    {
        $this->group_by = (is_array($group_by) ? $group_by : func_get_args());
    }

    /**
     *
     * @return \Chamilo\Libraries\Storage\Query\Variable\ConditionVariable[]
     */
    public function get()
    {
        return $this->group_by;
    }

    /**
     * Adds a group by
     *
     * @param \Chamilo\Libraries\Storage\Query\Variable\ConditionVariable $group_by
     */
    public function add($group_by)
    {
        $this->group_by[] = $group_by;
    }

    /**
     *
     * @return string
     */
    public function hash()
    {
        $hashes = array();

        foreach ($this->group_by as $group_by)
        {
            $hashes[] = $group_by->hash();
        }

        sort($hashes);

        return md5(serialize($hashes));
    }
}
