<?php
namespace Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger;

use Chamilo\Libraries\Format\Structure\PageConfiguration;
use Chamilo\Libraries\Platform\Session\SessionUtilities;
use Exception;
use Throwable;

/**
 * Logs errors to New Relic
 *
 * @package Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class NewRelicExceptionLogger implements ExceptionLoggerInterface
{

    protected SessionUtilities $sessionUtilities;

    /**
     * @throws \Exception
     */
    public function __construct(SessionUtilities $sessionUtilities)
    {
        if (!extension_loaded('newrelic'))
        {
            throw new Exception('Can not use the NewRelicExceptionLogger when the newrelic extension is not loaded');
        }

        $this->configureChamiloParameters();
    }

    public function addJavascriptExceptionLogger(PageConfiguration $pageConfiguration)
    {
    }

    /**
     * Configures additional chamilo parameters in New Relic
     */
    protected function configureChamiloParameters()
    {
        $prefix = 'chamilo_';

        newrelic_add_custom_parameter($prefix . 'url', $_SERVER['REQUEST_URI']);
        newrelic_add_custom_parameter($prefix . 'http_method', $_SERVER['REQUEST_METHOD']);

        $user_id = $this->getSessionUtilities()->getUserId();
        if (!empty($user_id))
        {
            newrelic_add_custom_parameter($prefix . 'user_id', $user_id);
        }
    }

    public function getSessionUtilities(): SessionUtilities
    {
        return $this->sessionUtilities;
    }

    public function logException(
        Throwable $exception, int $exceptionLevel = self::EXCEPTION_LEVEL_ERROR, ?string $file = null, int $line = 0
    )
    {
        if ($exceptionLevel == self::EXCEPTION_LEVEL_WARNING)
        {
            return;
        }

        newrelic_notice_error('chamilo_exception', $exception);
    }
}