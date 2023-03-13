<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Ajax\Component;

use Chamilo\Core\Repository\ContentObject\LearningPath\Ajax\Manager;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns the workspaces for which the user has the right to copy
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class GetWorkspacesWithCopyRightComponent extends Manager
{
    /**
     * Executes this component and return's its response
     */
    public function run()
    {
        try
        {
            $workspacesDataArray = [];

            $workspaces = $this->getWorkspaceService()->getWorkspacesForUser($this->getUser());
            $this->processWorkspaces($workspacesDataArray, $workspaces);

            return new JsonResponse($workspacesDataArray);
        }
        catch (Exception $ex)
        {
            return $this->handleException($ex);
        }
    }

    /**
     * Processes the workspaces to an array
     *
     * @param array $workspacesDataArray
     * @param \Doctrine\Common\Collections\ArrayCollection<\Chamilo\Core\Repository\Workspace\Storage\DataClass\Workspace> $workspaces
     */
    protected function processWorkspaces(&$workspacesDataArray = [], ArrayCollection $workspaces)
    {
        $rightService = $this->getWorkspaceRightsService();

        foreach ($workspaces as $workspace)
        {
            $canUse = $rightService->canUseContentObjects($this->getUser(), $workspace);
            $canCopy = $rightService->canCopyContentObjects($this->getUser(), $workspace);

            if (!$canUse && !$canCopy)
            {
                continue;
            }

            $workspacesDataArray[] = [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
                'use_right' => $canUse,
                'copy_right' => $canCopy
            ];
        }
    }

}