<?php
namespace Chamilo\Core\Repository\ContentObject\Assignment\Display\Component;

use Chamilo\Core\Repository\ContentObject\Assignment\Display\Bridge\Storage\DataClass\Entry;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Form\CodePageCorrectorFormType;
use Chamilo\Core\Repository\ContentObject\Assignment\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Page\Storage\DataClass\Page;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\Application\ApplicationConfigurationInterface;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Translation\Translation;

/**
 * Class EntryRevisorInPageComponent
 * Work in progress
 */
class EntryCodePageCorrectorComponent extends Manager
{

    public function __construct(ApplicationConfigurationInterface $applicationConfiguration)
    {
        parent::__construct($applicationConfiguration);
    }

    /**
     * @return string
     * @throws NotAllowedException
     * @throws \Exception
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function run()
    {
        $this->initializeEntry();
        $this->checkAccessRights();

        $formFactory = $this->getForm();

        $correctionPage = new Page();
        //todo checks on filetype
        $content = file_get_contents(
            $this->getConfigurablePathBuilder()->getRepositoryPath() . $this->getEntry()->getContentObject()->get_path()
        );
        $correctionPage->set_description($content);

        $form = $formFactory->create(CodePageCorrectorFormType::class, [
                ContentObject::PROPERTY_DESCRIPTION => '<pre><code>' . $content . '</code></pre>'
            ]);

        $form->handleRequest($this->getRequest());

        if ($form->isValid())
        {
            $formData = $form->getData();
            $page = new Page();
            $page->set_description($formData[Page::PROPERTY_DESCRIPTION]);
            $page->set_title($this->getEntry()->getContentObject()->get_title() . ' - verbeterd');
            $page->set_creation_date(time());
            $page->set_modification_date(time());
            $page->set_owner_id($this->getUser()->getId());

            $success = $page->create();
            $this->getDataProvider()->attachContentObjectToEntry($this->getEntry(), $page);

            $this->redirectWithMessage(
                $this->getTranslator()->trans(
                    $success ? 'ObjectCreated' : 'ObjectNotCreated',
                    ['OBJECT' => $this->getTranslator()->trans('Page', [], 'Chamilo\Core\Repository\Page')],
                    'Chamilo\Libraries'
                ), !$success, [
                    self::PARAM_ACTION => self::ACTION_ENTRY
                ]
            );
        }

        $formView = $form->createView();

        return $this->getTwig()->render(
                Manager::CONTEXT . ':EntryCodePageCorrector.html.twig', [
                    'HEADER' => $this->render_header(),
                    'FOOTER' => $this->render_footer(),
                    'form' => $formView
                ]
            );
    }

    /**
     * @throws \Chamilo\Libraries\Architecture\Exceptions\NotAllowedException
     */
    protected function checkAccessRights()
    {
        if ($this->getDataProvider()->canEditAssignment())
        {
            return;
        }

        throw new NotAllowedException();
    }

    /**
     *
     */
    protected function initializeEntry()
    {
        parent::initializeEntry();

        if (!$this->entry instanceof Entry)
        {
            $breadcrumbTrail = BreadcrumbTrail::getInstance();
            $breadcrumbTrail->get_last()->set_name(
                Translation::getInstance()->getTranslation('ViewerComponent', null, Manager::CONTEXT)
            );
        }
    }
}
