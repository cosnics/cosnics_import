<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Announcement\Component;

use Chamilo\Application\Weblcms\Tool\Implementation\Announcement\Manager;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbLessComponentInterface;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbTrail;

class ViewerComponent extends Manager implements BreadcrumbLessComponentInterface
{

    /**
     *
     * @param BreadcrumbTrail $breadcrumbtrail
     */
    public function addAdditionalBreadcrumbs(BreadcrumbTrail $breadcrumbtrail): void
    {
        $this->addBrowserBreadcrumb($breadcrumbtrail);
    }

    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = \Chamilo\Application\Weblcms\Tool\Manager::PARAM_PUBLICATION_ID;

        return parent::getAdditionalParameters($additionalParameters);
    }
}
