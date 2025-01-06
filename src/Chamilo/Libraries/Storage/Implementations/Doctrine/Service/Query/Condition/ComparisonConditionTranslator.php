<?php
namespace Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Condition;

use Chamilo\Libraries\Storage\Architecture\Interfaces\DataClassDatabaseInterface;
use Chamilo\Libraries\Storage\Query\Condition\ComparisonCondition;
use Chamilo\Libraries\Storage\Query\ConditionTranslator;

/**
 * @package Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Condition
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 */
class ComparisonConditionTranslator extends ConditionTranslator
{
    public const CONDITION_CLASS = ComparisonCondition::class;

    public function translate(
        DataClassDatabaseInterface $dataClassDatabase, ComparisonCondition $comparisonCondition,
        ?bool $enableAliasing = true
    ): string
    {
        $translationParts = [];

        $translationParts[] = $this->getConditionPartTranslatorService()->translate(
            $dataClassDatabase, $comparisonCondition->getLeftConditionVariable(), $enableAliasing
        );

        if ($comparisonCondition->getOperator() == ComparisonCondition::EQUAL &&
            is_null($comparisonCondition->getRightConditionVariable()))
        {
            $translationParts[] = 'IS NULL';

            return implode(' ', $translationParts);
        }

        $translationParts[] = $this->translateOperator($comparisonCondition->getOperator());

        $translationParts[] = $this->getConditionPartTranslatorService()->translate(
            $dataClassDatabase, $comparisonCondition->getRightConditionVariable(), $enableAliasing
        );

        return implode(' ', $translationParts);
    }

    private function translateOperator(int $conditionOperator): string
    {
        switch ($conditionOperator)
        {
            case ComparisonCondition::GREATER_THAN :
                $translatedOperator = '>';
                break;
            case ComparisonCondition::GREATER_THAN_OR_EQUAL :
                $translatedOperator = '>=';
                break;
            case ComparisonCondition::LESS_THAN :
                $translatedOperator = '<';
                break;
            case ComparisonCondition::LESS_THAN_OR_EQUAL :
                $translatedOperator = '<=';
                break;
            case ComparisonCondition::EQUAL :
                $translatedOperator = '=';
                break;
            default :
                die('Unknown operator for Comparison condition');
        }

        return $translatedOperator;
    }
}