<?php
namespace Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger;

use Chamilo\Configuration\Service\ConfigurationConsulter;
use Chamilo\Configuration\Service\FileConfigurationLoader;
use Chamilo\Configuration\Service\FileConfigurationLocator;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\File\ConfigurablePathBuilder;
use Chamilo\Libraries\File\PathBuilder;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * Builds the FileExceptionLogger class
 *
 * @package Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class FileExceptionLoggerBuilder implements ExceptionLoggerBuilderInterface
{

    /**
     *
     * @see \Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger\ExceptionLoggerBuilderInterface::__construct()
     */
    public function __construct(protected ConfigurationConsulter $configurationConsulter)
    {
    }

    /**
     * Creates the exception logger
     *
     * @return \Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger\FileExceptionLogger
     */
    public function createExceptionLogger(): FileExceptionLogger
    {
        $configurablePathBuilder = new ConfigurablePathBuilder(
            $this->configurationConsulter->getSetting(array('Chamilo\Configuration', 'storage')));

        return new FileExceptionLogger($configurablePathBuilder->getLogPath());
    }
}
