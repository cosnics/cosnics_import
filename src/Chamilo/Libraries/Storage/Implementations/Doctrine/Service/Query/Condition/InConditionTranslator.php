<?php
namespace Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Condition;

use Chamilo\Libraries\Storage\Architecture\Interfaces\DataClassDatabaseInterface;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\ConditionTranslator;

/**
 * @package Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Condition
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 */
class InConditionTranslator extends ConditionTranslator
{
    public const CONDITION_CLASS = InCondition::class;

    public function translate(
        DataClassDatabaseInterface $dataClassDatabase, InCondition $inCondition, ?bool $enableAliasing = true
    ): string
    {
        $values = $inCondition->getValues();

        if (count($values) > 0)
        {
            $where_clause = [];

            $where_clause[] = $this->getConditionPartTranslatorService()->translate(
                    $dataClassDatabase, $inCondition->getConditionVariable(), $enableAliasing
                ) . ' IN (';

            $placeholders = [];

            foreach ($values as $value)
            {
                $placeholders[] = $dataClassDatabase->quote($value);
            }

            $where_clause[] = implode(',', $placeholders);
            $where_clause[] = ')';

            $value = implode('', $where_clause);
        }
        else
        {
            $value = '1 = 0';
        }

        return $value;
    }
}