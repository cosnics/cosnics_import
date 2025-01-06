<?php
namespace Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Variable;

use Chamilo\Libraries\Storage\Architecture\Interfaces\DataClassDatabaseInterface;
use Chamilo\Libraries\Storage\Query\ConditionVariableTranslator;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * @package Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Variable
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class StaticConditionVariableTranslator extends ConditionVariableTranslator
{
    public const CONDITION_CLASS = StaticConditionVariable::class;

    public function translate(
        DataClassDatabaseInterface $dataClassDatabase, StaticConditionVariable $staticConditionVariable
    ): string
    {
        $value = $staticConditionVariable->getValue();

        if ($staticConditionVariable->getQuote())
        {
            $value = $dataClassDatabase->quote($value);
        }

        return $value;
    }
}