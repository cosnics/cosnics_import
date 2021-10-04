<?php

namespace Chamilo\Core\Repository\ContentObject\Presence\Display\Ajax\Component;

use Chamilo\Core\Repository\ContentObject\Presence\Display\Ajax\Manager;
use Chamilo\Core\Repository\ContentObject\Presence\Storage\DataClass\Presence;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\File\Redirect;
use Chamilo\Libraries\Storage\FilterParameters\FilterParameters;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @package Chamilo\Core\Repository\ContentObject\Presence\Display\Ajax\Component
 *
 * @author Stefan Gabriëls - Hogeschool Gent
 */
class LoadPresenceEntriesComponent extends Manager
{
    /**
     */
    function run()
    {
        try
        {
            $presence = $this->getPresence();

            $canUserEditPresence = $this->canUserEditPresence();

            if ($canUserEditPresence)
            {
                $userIds = $this->getPresenceServiceBridge()->getTargetUserIds($this->createFilterParameters());
            }
            elseif ($this->canUserViewPresence())
            {
                $userIds = [$this->getUser()->getId()];
            }
            else
            {
                throw new NotAllowedException();
            }

            $contextIdentifier = $this->getPresenceServiceBridge()->getContextIdentifier();

            $filterParameters = $this->createFilterParameters()->setCount(null)->setOffset(null);
            $userService = $this->getUserService();
            $users = $userService->getUsersFromIds($userIds, $contextIdentifier, $filterParameters);

            $presenceService = $this->getPresenceService();
            $periods = $presenceService->getResultPeriodsForPresence($presence->getId(), $contextIdentifier);

            if ($canUserEditPresence && count($periods) == 0)
            {
                $period = $presenceService->createPresenceResultPeriod($presence, $contextIdentifier);
                $periods = [['date' => (int) $period->getDate(), 'id' => (int) $period->getId()]];
            }

            foreach ($periods as $period)
            {
                foreach ($users as $index => $user)
                {
                    $changed = false;
                    if (! array_key_exists('period#' . $period['id'] . '-status', $user))
                    {
                        $user['period#' . $period['id'] . '-status'] = NULL;
                        $changed = true;
                    }
                    if (array_key_exists('period#' . $period['id'] . '-checked_in_date', $user))
                    {
                        $user['period#' . $period['id'] . '-checked_in_date'] = (int) $user['period#' . $period['id'] . '-checked_in_date'];
                        $changed = true;
                    }
                    if (array_key_exists('period#' . $period['id'] . '-checked_out_date', $user))
                    {
                        $user['period#' . $period['id'] . '-checked_out_date'] = (int) $user['period#' . $period['id'] . '-checked_out_date'];
                        $changed = true;
                    }
                    if ($changed)
                    {
                        $users[$index] = $user;
                    }
                }
            }

            foreach ($users as $index => $user)
            {
                $profilePhotoUrl = new Redirect(
                    array(
                        Application::PARAM_CONTEXT => \Chamilo\Core\User\Ajax\Manager::context(),
                        Application::PARAM_ACTION => \Chamilo\Core\User\Ajax\Manager::ACTION_USER_PICTURE,
                        \Chamilo\Core\User\Manager::PARAM_USER_USER_ID => $user['id']
                    )
                );
                $user['photo'] = $profilePhotoUrl->getUrl();
                $users[$index] = $user;
            }

            $resultData = ['students' => $users, 'periods' => $periods, 'last' => (int) end($periods)['id']];

            if ($canUserEditPresence && $this->getRequest()->getFromPostOrUrl('request_count') == 'true')
            {
                $resultData['count'] = count($this->getPresenceServiceBridge()->getTargetUserIds($filterParameters));
            }

            return new JsonResponse($this->serialize($resultData), 200, [], true);
        }
        catch (\Exception $ex)
        {
            return new JsonResponse(['error' => ['code' => 500, 'message' => $ex->getMessage()]], 500);
        }
    }

    /**
     * @return FilterParameters
     */
    protected function createFilterParameters(): FilterParameters
    {
        $userService = $this->getUserService();
        $filterParametersBuilder = $this->getFilterParametersBuilder();
        $fieldMapper = $userService->getFieldMapper();
        return $filterParametersBuilder->buildFilterParametersFromRequest($this->getRequest(), $fieldMapper);
    }
}
