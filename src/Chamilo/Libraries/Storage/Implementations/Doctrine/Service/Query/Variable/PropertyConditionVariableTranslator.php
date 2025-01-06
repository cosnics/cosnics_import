<?php
namespace Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Variable;

use Chamilo\Libraries\Storage\Architecture\Interfaces\DataClassDatabaseInterface;
use Chamilo\Libraries\Storage\Query\ConditionVariableTranslator;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;

/**
 * @package Chamilo\Libraries\Storage\Implementations\Doctrine\Service\Query\Variable
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class PropertyConditionVariableTranslator extends ConditionVariableTranslator
{
    public const CONDITION_CLASS = PropertyConditionVariable::class;

    public function translate(
        DataClassDatabaseInterface $dataClassDatabase, PropertyConditionVariable $propertyConditionVariable,
        ?bool $enableAliasing = true
    ): string
    {
        $className = $propertyConditionVariable->getDataClassName();

        if ($enableAliasing)
        {
            $alias = $this->getStorageAliasGenerator()->getDataClassAlias($className);
        }
        else
        {
            $alias = null;
        }

        $translationParts = [];

        $translationParts[] = $dataClassDatabase->escapeColumnName(
            $propertyConditionVariable->getPropertyName(), $alias
        );

        if ($propertyConditionVariable->getAlias())
        {
            $translationParts[] = 'AS';
            $translationParts[] = $propertyConditionVariable->getAlias();
        }

        return implode(' ', $translationParts);
    }
}