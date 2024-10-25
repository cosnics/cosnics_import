<?php
namespace Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger;

use Chamilo\Libraries\Architecture\Application\Routing\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Interface for classes that build exception loggers
 *
 * @package Chamilo\Libraries\Architecture\ErrorHandler\ExceptionLogger
 * @author  Sven Vanpoucke - Hogeschool Gent
 */
interface ExceptionLoggerBuilderInterface
{

    public function __construct(SessionInterface $session, UrlGenerator $urlGenerator, array $configuration = []);

    public function createExceptionLogger(): ExceptionLoggerInterface;
}