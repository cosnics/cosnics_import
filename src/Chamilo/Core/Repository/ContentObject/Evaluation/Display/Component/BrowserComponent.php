<?php

namespace Chamilo\Core\Repository\ContentObject\Evaluation\Display\Component;

use Chamilo\Core\Repository\Common\Rendition\ContentObjectRendition;
use Chamilo\Core\Repository\Common\Rendition\ContentObjectRenditionImplementation;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Rubric\Storage\DataClass\Rubric;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Core\Repository\ContentObject\Evaluation\Display\Ajax\Manager as AjaxManager;

/**
 *
 * @package Chamilo\Core\Repository\ContentObject\Assignment\Display\Component
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class BrowserComponent extends Manager
{
    /**
     * @return string
     *
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NotAllowedException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function run()
    {
        $this->checkAccessRights();

        return $this->getTwig()->render(
            \Chamilo\Core\Repository\ContentObject\Evaluation\Display\Manager::context() . ':EntityBrowser.html.twig', $this->getTemplateProperties()
        );
    }

    /**
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NotAllowedException
     */
    protected function checkAccessRights()
    {
        if (!$this->getEvaluationServiceBridge()->canEditEvaluation())
        {
            throw new NotAllowedException();
        }
    }

    /**
     * @return string[]
     * @throws NotAllowedException
     * @throws \Chamilo\Libraries\Architecture\Exceptions\ClassNotExistException
     */
    protected function getTemplateProperties()
    {
        $evaluation = $this->get_root_content_object();

        $supportsRubrics = $this->supportsRubrics();
        $hasRubric = false;
        $canBuildRubric = false;
        $rubricPreview = null;

        if ($supportsRubrics)
        {
            $hasRubric = $this->getEvaluationRubricService()->evaluationHasRubric($evaluation);;
            $rubricPreview = $this->runRubricComponent('Preview');
            $rubricContentObject = $this->getEvaluationRubricService()->getRubricForEvaluation($evaluation);

            if ($rubricContentObject instanceof Rubric)
            {
                try
                {
                    $rubricData = $this->getRubricService()->getRubric($rubricContentObject->getActiveRubricDataId());
                    $canBuildRubric = $this->getRubricService()->canChangeRubric($rubricData);
                }
                catch (\Exception $ex)
                {
                }
            }
        }

        $contextIdentifier = $this->getEvaluationServiceBridge()->getContextIdentifier();

        return [
            'HEADER' => $this->render_header(),
            'FOOTER' => $this->render_footer(),
            'SUPPORTS_RUBRICS' => $this->supportsRubrics(),
            'HAS_RUBRIC' => $hasRubric,
            'ADD_RUBRIC_URL' => $this->get_url([self::PARAM_ACTION => self::ACTION_PUBLISH_RUBRIC]),
            'BUILD_RUBRIC_URL' => $this->get_url([self::PARAM_ACTION => self::ACTION_BUILD_RUBRIC]),
            'REMOVE_RUBRIC_URL' => $this->get_url([self::PARAM_ACTION => self::ACTION_REMOVE_RUBRIC]),
            'CAN_BUILD_RUBRIC' => $canBuildRubric,
            'RUBRIC_PREVIEW' => $rubricPreview,
            'ENTITY_TYPE' => $this->getEvaluationServiceBridge()->getCurrentEntityType(),
            'CONTEXT_CLASS' => $contextIdentifier->getContextClass(),
            'CONTEXT_ID' => $contextIdentifier->getContextId(),
            'CONTENT_OBJECT_TITLE' => $this->get_root_content_object()->get_title(),
            'CONTENT_OBJECT_RENDITION' => $this->renderContentObject(),
            'LOAD_ENTITIES_URL' => $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_AJAX,
                    AjaxManager::PARAM_ACTION => AjaxManager::ACTION_LOAD_ENTITIES
                ]
            ),
            'SAVE_SCORE_URL' => $this->get_url(
                [
                    self::PARAM_ACTION => self::ACTION_AJAX,
                    AjaxManager::PARAM_ACTION => AjaxManager::ACTION_SAVE_SCORE
                ]
            ),
            'ENTITY_BASE_URL' => $this->get_url(
                [
                    'evaluation_display_action' => 'Entry'
                ]
            )
        ];
    }

    /**
     *
     * @return string
     */
    protected function renderContentObject()
    {
        $display = ContentObjectRenditionImplementation::factory(
            $this->get_root_content_object(),
            ContentObjectRendition::FORMAT_HTML,
            ContentObjectRendition::VIEW_DESCRIPTION,
            $this
        );

        return $display->render();
    }

}