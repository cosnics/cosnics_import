<?php
namespace Chamilo\Application\Calendar\Extension\Google\Component;

use Chamilo\Application\Calendar\Extension\Google\Manager;
use Chamilo\Application\Calendar\Extension\Google\Repository\CalendarRepository;
use Chamilo\Application\Calendar\Extension\Google\Service\CalendarService;
use Chamilo\Application\Calendar\Service\AvailabilityService;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @package Chamilo\Application\Calendar\Extension\Google\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class LogoutComponent extends Manager implements DelegateComponent
{

    public function run()
    {
        $calendarService = new CalendarService(CalendarRepository::getInstance());
        $isSuccessful = $calendarService->logout();

        if ($isSuccessful)
        {
            $this->getAvailabilityService()->deleteAvailabilityByCalendarType(Manager::CONTEXT);
        }

        return new RedirectResponse(
            $this->getUrlGenerator()->fromParameters(
                [Application::PARAM_CONTEXT => \Chamilo\Application\Calendar\Manager::CONTEXT]
            )
        );
    }

    /**
     * @return \Chamilo\Application\Calendar\Service\AvailabilityService
     */
    protected function getAvailabilityService()
    {
        return $this->getService(AvailabilityService::class);
    }
}
