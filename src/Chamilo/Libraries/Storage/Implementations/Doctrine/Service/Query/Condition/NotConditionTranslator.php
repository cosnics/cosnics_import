<?php
namespace Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Condition;

use Chamilo\Libraries\Storage\Architecture\Interfaces\DataClassDatabaseInterface;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\ConditionTranslator;

/**
 * @package Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Condition
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 */
class NotConditionTranslator extends ConditionTranslator
{
    public const CONDITION_CLASS = NotCondition::class;

    public function translate(
        DataClassDatabaseInterface $dataClassDatabase, NotCondition $notCondition, ?bool $enableAliasing = true
    ): string
    {
        $string = [];

        $string[] = 'NOT (';
        $string[] = $this->getConditionPartTranslatorService()->translate(
            $dataClassDatabase, $notCondition->getCondition(), $enableAliasing
        );
        $string[] = ')';

        return implode('', $string);
    }
}