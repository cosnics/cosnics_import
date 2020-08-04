<?php

namespace Chamilo\Core\Repository\ContentObject\Rubric\Display\Component;

use Chamilo\Core\Repository\ContentObject\Rubric\Display\Form\EntryFormType;
use Chamilo\Core\Repository\ContentObject\Rubric\Display\Form\Handler\EntryFormHandler;
use Chamilo\Core\Repository\ContentObject\Rubric\Display\Form\Handler\EntryFormHandlerParameters;
use Chamilo\Core\Repository\ContentObject\Rubric\Display\Manager;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;

/**
 * @package Chamilo\Core\Repository\ContentObject\Rubric\Display\Component
 *
 * @author - Sven Vanpoucke - Hogeschool Gent
 */
class EntryComponent extends Manager implements DelegateComponent
{

    /**
     * @return string
     * @throws \Doctrine\ORM\ORMException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \Chamilo\Core\Repository\ContentObject\Rubric\Domain\Exceptions\InvalidChildTypeException
     * @throws \Exception
     */
    function run()
    {
        $rubric = $this->getRubric();
        $rubricData = $this->getRubricService()->getRubric($rubric->getActiveRubricDataId());

        $form = $this->getForm()->create(EntryFormType::class);

        $formHandler = $this->getFormHandler();

        $formHandler->setParameters(
            new EntryFormHandlerParameters(
                $this->getUser(), $rubricData, $this->getRubricBridge()->getContextIdentifier(),
                $this->getRubricBridge()->getTargetUsers()
            )
        );

        $formHandled = $formHandler->handle($form, $this->getRequest());

        if ($formHandled)
        {
            return '';
        }
        else
        {
            return $this->getTwig()->render(
                'Chamilo\Core\Repository\ContentObject\Rubric:RubricEntry.html.twig',
                [
                    'RUBRIC_DATA_JSON' => $this->getSerializer()->serialize($rubricData, 'json'),
                    'FORM' => $form->createView()
                ]
            );
        }
    }

    /**
     * @return EntryFormHandler
     */
    protected function getFormHandler()
    {
        return $this->getService(EntryFormHandler::class);
    }
}
