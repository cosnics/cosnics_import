<?php
namespace Chamilo\Libraries\Storage\Parameters;

use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Chamilo\Libraries\Storage\Query\GroupBy;
use Chamilo\Libraries\Storage\Query\Joins;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\RetrieveProperties;

/**
 *
 * @package Chamilo\Libraries\Storage\Parameters
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class RetrieveParameters extends DataClassParameters
{

    public function __construct(
        ?Condition $condition = null, OrderBy $orderBy = new OrderBy(), Joins $joins = new Joins(),
        RetrieveProperties $retrieveProperties = new RetrieveProperties(), GroupBy $groupBy = new GroupBy()
    )
    {
        parent::__construct(
            condition: $condition, joins: $joins, retrieveProperties: $retrieveProperties, orderBy: $orderBy,
            groupBy: $groupBy, count: 1, offset: 0
        );
    }
}