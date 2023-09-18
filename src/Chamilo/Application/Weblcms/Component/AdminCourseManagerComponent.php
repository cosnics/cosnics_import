<?php
namespace Chamilo\Application\Weblcms\Component;

use Chamilo\Core\Admin\Service\BreadcrumbGenerator;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbGeneratorInterface;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbLessComponentInterface;

/**
 * This class represents a component that runs the course submanager.
 * It's an extension from the normal launcher
 * component to support components runnable as administrator
 *
 * @package \application\weblcms\course
 * @author  Sven Vanpoucke - Hogeschool Gent - Refactoring
 */
class AdminCourseManagerComponent extends CourseManagerComponent implements BreadcrumbLessComponentInterface
{

    public function getBreadcrumbGenerator(): BreadcrumbGeneratorInterface
    {
        return $this->getService(BreadcrumbGenerator::class);
    }
}
