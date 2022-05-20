<?php
namespace Chamilo\Libraries\Storage\DataManager\AdoDb\ConditionPart;

use Chamilo\Libraries\Storage\Query\ConditionVariableTranslator;
use Chamilo\Libraries\Storage\Query\Variable\OperationConditionVariable;

/**
 *
 * @package Chamilo\Libraries\Storage\DataManager\AdoDb\ConditionPart
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class OperationConditionVariableTranslator extends ConditionVariableTranslator
{

    /**
     * @return \Chamilo\Libraries\Storage\Query\Variable\OperationConditionVariable
     */
    public function getConditionVariable()
    {
        return parent::getConditionVariable();
    }

    /**
     * @param boolean $enableAliasing
     *
     * @return string
     */
    public function translate(bool $enableAliasing = true)
    {
        $strings = [];

        $strings[] = '(';
        $strings[] = $this->getConditionPartTranslatorService()->translate(
            $this->getDataClassDatabase(), $this->getConditionVariable()->getLeftConditionVariable(), $enableAliasing
        );

        switch ($this->getConditionVariable()->get_operator())
        {
            case OperationConditionVariable::ADDITION :
                $strings[] = '+';
                break;
            case OperationConditionVariable::DIVISION :
                $strings[] = '/';
                break;
            case OperationConditionVariable::MINUS :
                $strings[] = '-';
                break;
            case OperationConditionVariable::MULTIPLICATION :
                $strings[] = '*';
                break;
            case OperationConditionVariable::BITWISE_AND :
                $strings[] = '&';
                break;
            case OperationConditionVariable::BITWISE_OR :
                $strings[] = '|';
                break;
        }

        $strings[] = $this->getConditionPartTranslatorService()->translate(
            $this->getDataClassDatabase(), $this->getConditionVariable()->getRightConditionVariable(), $enableAliasing
        );
        $strings[] = ')';

        return implode(' ', $strings);
    }
}
