<?php
namespace Chamilo\Application\Weblcms\Component;

use Chamilo\Application\Weblcms\Manager;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbLessComponentInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 *
 * @package Chamilo\Application\Weblcms\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class HomeComponent extends Manager implements BreadcrumbLessComponentInterface
{

    /**
     * Runs this component and returns it's output
     */
    public function run()
    {
        $component =
            $this->isAuthorized(Manager::CONTEXT, 'ViewPersonalCourses') ? 'CourseList' : 'OpenCoursesBrowser';

        return new RedirectResponse(
            $this->getUrlGenerator()->fromParameters(
                [self::PARAM_CONTEXT => Manager::CONTEXT, self::PARAM_ACTION => $component]
            )
        );
    }
}
