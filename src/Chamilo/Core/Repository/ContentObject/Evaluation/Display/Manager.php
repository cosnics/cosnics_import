<?php

namespace Chamilo\Core\Repository\ContentObject\Evaluation\Display;

use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Bridge\FeedbackRightsServiceBridge;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Bridge\FeedbackServiceBridge;
use Chamilo\Core\Repository\ContentObject\Evaluation\Storage\DataClass\Evaluation;
use Chamilo\Core\Repository\ContentObject\Rubric\Service\RubricService;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Service\EntityService;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Service\EvaluationRubricService;
use Chamilo\Core\Repository\ContentObject\Rubric\Display\Bridge\RubricBridgeInterface;
use Chamilo\Core\Repository\Feedback\Bridge\FeedbackServiceBridgeInterface;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Bridge\Interfaces\EvaluationServiceBridgeInterface;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Bridge\RubricBridge;
use Chamilo\Core\Repository\ContentObject\Rubric\Storage\DataClass\Rubric;
use Chamilo\Core\Repository\Workspace\Service\ContentObjectService;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Application\ApplicationConfigurationInterface;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Architecture\Exceptions\UserException;

/**
 *
 * @package Chamilo\Core\Repository\ContentObject\Evaluation\Display
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
abstract class Manager extends \Chamilo\Core\Repository\Display\Manager
{
    const PARAM_ACTION = 'evaluation_display_action';

    const DEFAULT_ACTION = 'Browser';

    const ACTION_AJAX = 'Ajax';
    const ACTION_PUBLISH_RUBRIC = 'PublishRubric';
    const ACTION_BUILD_RUBRIC = 'BuildRubric';
    const ACTION_REMOVE_RUBRIC = 'RemoveRubric';
    const ACTION_EXPORT = 'Export';

    const ACTION_ENTRY = 'Entry';
    const ACTION_SAVE_SCORE = 'SaveScore';

    /**
     * @param \Chamilo\Libraries\Architecture\Application\ApplicationConfigurationInterface $applicationConfiguration
     */
    public function __construct(ApplicationConfigurationInterface $applicationConfiguration)
    {
        parent::__construct($applicationConfiguration);
        $this->buildBridgeServices();
        $entityIdentifier = $this->getRequest()->query->get('entity_id');
        if ($entityIdentifier)
        {
            $this->set_parameter('entity_id', $entityIdentifier);
        }
    }

    /**
     * Builds the bridge services for the feedback and for the extensions
     */
    protected function buildBridgeServices()
    {
        $rubricBridge = new RubricBridge($this->getEvaluationServiceBridge());
        $feedbackRightsServiceBridge = new FeedbackRightsServiceBridge();
        $feedbackRightsServiceBridge->setCurrentUser($this->getUser());

        $feedbackServiceBridge = $this->getService(FeedbackServiceBridge::class);
        $this->getBridgeManager()->addBridge($feedbackServiceBridge);
        $this->getBridgeManager()->addBridge($feedbackRightsServiceBridge);
        $this->getBridgeManager()->addBridge($rubricBridge);
    }

    protected function getFeedbackServiceBridge() : FeedbackServiceBridge
    {
        return $this->getBridgeManager()->getBridgeByInterface(FeedbackServiceBridgeInterface::class);
    }

    /**
     * @return bool
     */
    protected function supportsRubrics()
    {
        return $this->getRegistrationConsulter()->isContextRegistered(
            'Chamilo\\Core\\Repository\\ContentObject\\Rubric'
        );
    }

    /**
     * @return ContentObjectService
     */
    protected function getContentObjectService()
    {
        return $this->getService(ContentObjectService::class);
    }

    /**
     * @param string $action
     *
     * @param bool $embedded
     *
     * @return string|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Chamilo\Libraries\Architecture\Exceptions\ClassNotExistException
     */
    protected function runRubricComponent(string $action, bool $embedded = true)
    {
        $rubricId = $this->get_root_content_object()->getRubricId();

        if (!$rubricId)
        {
            return '';
        }

        try
        {
            $rubric = $this->getContentObjectService()->findById($rubricId);
        }
        catch (\TypeError | \Exception $e)
        {
            return false;
        }

        if (!$rubric instanceof Rubric)
        {
            return '';
        }

        $applicationConfiguration =
            new ApplicationConfiguration($this->getRequest(), $this->getUser(), $this, [], $embedded);

        $applicationConfiguration->set(
            \Chamilo\Core\Repository\ContentObject\Rubric\Display\Manager::PARAM_RUBRIC_CONTENT_OBJECT, $rubric
        );

        $application =
            $this->getApplicationFactory()->getApplication(
                'Chamilo\Core\Repository\ContentObject\Rubric\Display', $applicationConfiguration, $action
            );

        $response = $application->run();

        if ($embedded && ($response instanceof JsonResponse || $response instanceof RedirectResponse))
        {
            $response->send();
            exit;
        }

        return $response;
    }

    /**
     * @return \Chamilo\Core\Repository\ContentObject\Evaluation\Display\Bridge\Interfaces\EvaluationServiceBridgeInterface
     */
    protected function getEvaluationServiceBridge()
    {
        return $this->getBridgeManager()->getBridgeByInterface(EvaluationServiceBridgeInterface::class);
    }

    /**
     * @return Chamilo\Core\Repository\ContentObject\Rubric\Display\Bridge\RubricBridgeInterface
     */
    protected function getRubricBridge()
    {
        return $this->getBridgeManager()->getBridgeByInterface(RubricBridgeInterface::class);
    }

    /**
     * @return EntityService
     */
    protected function getEntityService()
    {
        return $this->getService(EntityService::class);
    }

    /**
     * @return EvaluationRubricService
     */
    protected function getEvaluationRubricService()
    {
        return $this->getService(EvaluationRubricService::class);
    }

    /**
     * @return RubricService
     */
    protected function getRubricService()
    {
        return $this->getService(RubricService::class);
    }

    /**
     * @return \Chamilo\Core\Repository\Storage\DataClass\ContentObject | \Chamilo\Core\Repository\ContentObject\Evaluation\Storage\DataClass\Evaluation
     */
    public function getEvaluation()
    {
        return $this->get_root_content_object();
    }

    /**
     * @throws NotAllowedException
     * @throws UserException
     */
    public function validateEvaluationEntityInput()
    {
        $this->validateIsPostRequest();
        $this->validateIsEvaluation();
        $this->validateEntity();
    }

    /**
     * @throws NotAllowedException
     */
    protected function validateIsPostRequest()
    {
        if (!$this->getRequest()->isMethod('POST'))
        {
            throw new NotAllowedException();
        }
    }

    /**
     * @throws UserException
     */
    protected function validateIsEvaluation()
    {
        $evaluation = $this->get_root_content_object();

        if (! $evaluation instanceof Evaluation)
        {
            $this->throwUserException('EvaluationNotFound');
        }
    }

    /**
     * @throws UserException
     */
    protected function validateEntity()
    {
        $entityId = $this->getRequest()->getFromPostOrUrl('entity_id');

        if (empty($entityId))
        {
            $this->throwUserException('EntityIdNotProvided');
        }

        $userIds = $this->getEvaluationServiceBridge()->getTargetEntityIds();

        if (! in_array($entityId, $userIds))
        {
            $this->throwUserException('EntityNotInList');
        }
    }

    /**
     * @param string $key
     * @throws UserException
     */
    public function throwUserException($key = "")
    {
        throw new UserException(
            $this->getTranslator()->trans($key, [], Manager::context())
        );
    }

}