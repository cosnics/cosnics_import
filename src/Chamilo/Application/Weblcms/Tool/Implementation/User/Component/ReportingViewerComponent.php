<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\User\Component;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Template\CourseStudentTrackerDetailTemplate;
use Chamilo\Application\Weblcms\Tool\Implementation\User\Manager;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbLessComponentInterface;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Translation\Translation;

/**
 *
 * @package application.lib.weblcms.tool.user.component
 */
class ReportingViewerComponent extends Manager implements BreadcrumbLessComponentInterface
{

    public function run()
    {
        $this->getBreadcrumbTrail()->add(
            new Breadcrumb($this->get_url(), Translation::get('ReportingViewerComponent'))
        );

        $component = $this->getApplicationFactory()->getApplication(
            \Chamilo\Core\Reporting\Viewer\Manager::CONTEXT,
            new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this)
        );
        $component->set_template_by_name(
            CourseStudentTrackerDetailTemplate::class
        );

        return $component->run();
    }

    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID;
        $additionalParameters[] = \Chamilo\Application\Weblcms\Tool\Manager::PARAM_COMPLEX_ID;
        $additionalParameters[] = \Chamilo\Application\Weblcms\Tool\Manager::PARAM_TEMPLATE_NAME;
        $additionalParameters[] = \Chamilo\Application\Weblcms\Manager::PARAM_COURSE;

        return parent::getAdditionalParameters($additionalParameters);
    }
}
