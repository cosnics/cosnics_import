<?php
namespace Chamilo\Libraries\Storage\DataManager\Doctrine\ConditionPart;

use Chamilo\Libraries\Storage\Query\ConditionPart;
use Chamilo\Libraries\Storage\Query\ConditionTranslator;

/**
 *
 * @package Chamilo\Libraries\Storage\DataManager\Doctrine\ConditionPart
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 */
class AggregateConditionTranslator extends ConditionTranslator
{

    /**
     * @return \Chamilo\Libraries\Storage\Query\Condition\MultipleAggregateCondition
     */
    public function getCondition(): ConditionPart
    {
        return parent::getCondition();
    }

    public function translate(?bool $enableAliasing = true): string
    {
        $string = '';

        $conditionTranslations = [];
        $count = 0;

        foreach ($this->getCondition()->getConditions() as $key => $condition)
        {
            $count ++;
            $translation = $this->getConditionPartTranslatorService()->translate(
                $this->getDataClassDatabase(), $condition, $enableAliasing
            );

            if (!empty($translation))
            {
                $conditionTranslations[] = $translation;
            }
        }

        if (count($conditionTranslations) > 0)
        {
            $string = '(' . implode($this->getCondition()->getOperator(), $conditionTranslations) . ')';
        }

        return $string;
    }
}
