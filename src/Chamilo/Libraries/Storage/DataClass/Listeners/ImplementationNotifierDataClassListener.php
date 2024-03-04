<?php
namespace Chamilo\Libraries\Storage\DataClass\Listeners;

use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\RetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\EndsWithCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use InvalidArgumentException;

/**
 * Dataclass listener which manipulates the crud methods to notify the implementation packages
 *
 * @package Chamilo\Libraries\Storage\DataClass\Listeners
 * @author  Sven Vanpoucke - Hogeschool Gent
 */
class ImplementationNotifierDataClassListener extends DataClassListener
{

    private string $context;

    private DataClass $dataClass;

    /**
     * @var string[]
     */
    private array $implementationPackages;

    /**
     * The mapping between the methods of the data class listener and the methods of the datamanager, at least one
     * method mapping is required
     *
     * @var string[]
     */
    private array $methodMapping;

    /**
     * @param string[] $methodMapping
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(DataClass $dataClass, string $context, array $methodMapping)
    {
        $this->setDataClass($dataClass);
        $this->setContext($context);
        $this->setMethodMapping($methodMapping);
    }

    /**
     * @return string[]
     */
    protected function getImplementationPackages(): array
    {
        if (!isset($this->implementationPackages))
        {
            $pattern = '\\\Integration\\' . $this->context;

            $condition = new EndsWithCondition(
                new PropertyConditionVariable(Registration::class, Registration::PROPERTY_CONTEXT), $pattern
            );

            $packages = [];

            $package_registrations = DataManager::retrieves(
                Registration::class, new RetrievesParameters($condition)
            );
            foreach ($package_registrations as $package_registration)
            {
                $packages[] = $package_registration->get_context();
            }

            $this->implementationPackages = $packages;
        }

        return $this->implementationPackages;
    }

    protected function notifyImplementationPackages(string $dataClassListenerMethod, array $parameters = []): bool
    {
        if (!array_key_exists($dataClassListenerMethod, $this->methodMapping))
        {
            return true;
        }

        array_unshift($parameters, $this->dataClass);

        $method = $this->methodMapping[$dataClassListenerMethod];

        $packages = $this->getImplementationPackages();

        foreach ($packages as $package)
        {
            $className = $package . '\DataManager';

            if (!method_exists($className, $method))
            {
                continue;
            }

            if (!call_user_func_array([$className, $method], $parameters))
            {
                return false;
            }
        }

        return true;
    }

    public function onAfterCreate(bool $success): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    public function onAfterDelete(bool $success): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    public function onAfterSetProperty(string $name, string $value): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    public function onAfterUpdate(bool $success): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    public function onBeforeCreate(): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    public function onBeforeDelete(): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    public function onBeforeSetProperty(string $name, string $value): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    public function onBeforeUpdate(): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, func_get_args());
    }

    /**
     * @param string[] $dependencies
     */
    public function onGetDependencies(array &$dependencies = []): bool
    {
        return $this->notifyImplementationPackages(__FUNCTION__, [&$dependencies]);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setContext(string $context): void
    {
        if (empty($context))
        {
            throw new InvalidArgumentException('The context should not be empty');
        }

        $this->context = $context;
    }

    public function setDataClass(DataClass $dataClass): void
    {
        $this->dataClass = $dataClass;
    }

    /**
     * Sets the method mapping
     *
     * @param string[] $methodMapping
     *
     * @throws \InvalidArgumentException
     */
    public function setMethodMapping(array $methodMapping): void
    {
        if (count($methodMapping) == 0)
        {
            throw new InvalidArgumentException('The method mapping should at least contain 1 method');
        }

        foreach ($methodMapping as $method => $data_manager_method)
        {
            if (!method_exists($this, $method))
            {
                throw new InvalidArgumentException(
                    'The method ' . $method . ' does not exist in the data class listener'
                );
            }
        }

        $this->methodMapping = $methodMapping;
    }
}