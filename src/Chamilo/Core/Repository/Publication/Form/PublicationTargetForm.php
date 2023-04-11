<?php
namespace Chamilo\Core\Repository\Publication\Form;

use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Publication\Service\PublicationAggregator;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Core\Repository\Workspace\Service\RightsService;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Exceptions\NoObjectSelectedException;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Architecture\Exceptions\UserException;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Format\Structure\Glyph\IdentGlyph;
use Chamilo\Libraries\Format\Table\ArrayCollectionTableRenderer;
use Chamilo\Libraries\Format\Table\Column\StaticTableColumn;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * Class for application settings page Displays a form where the user can enter the installation settings regarding the
 * applications
 */
class PublicationTargetForm extends FormValidator
{

    /**
     * @var \Chamilo\Libraries\Architecture\Application\Application
     */
    private $application;

    private $applications;

    /**
     * @var int
     */
    private $contentObjectIdentifiers;

    /**
     * @var \Chamilo\Core\Repository\Storage\DataClass\ContentObject[]
     */
    private $content_objects;

    /**
     * @var string
     */
    private $type;

    /**
     * @param \Chamilo\Libraries\Architecture\Application\Application $application
     *
     * @throws NoObjectSelectedException
     * @throws NotAllowedException
     * @throws \Exception
     */
    public function __construct(Application $application, $action)
    {
        $this->application = $application;
        parent::__construct('page_locations', self::FORM_METHOD_POST, $action);

        $this->contentObjectIdentifiers = $this->getApplication()->getRequest()->get(
            Manager::PARAM_CONTENT_OBJECT_ID
        );

        if (empty($this->contentObjectIdentifiers))
        {
            throw new NoObjectSelectedException(Translation::get('ContentObject'));
        }

        if (!is_array($this->contentObjectIdentifiers))
        {
            $this->contentObjectIdentifiers = [$this->contentObjectIdentifiers];
        }

        $this->content_objects = [];
        $this->type = null;

        // Check whether the selected objects exist and perform the necessary rights checks
        foreach ($this->contentObjectIdentifiers as $id)
        {
            $content_object = DataManager::retrieve_by_id(
                ContentObject::class, $id
            );

            // fail if no object exists
            if (!$content_object instanceof ContentObject)
            {
                throw new NoObjectSelectedException(Translation::get('ContentObject'));
            }

            // Check the USE-right
            if (!$this->getWorkspaceRightsService()->canUseContentObject(
                $this->getApplication()->get_user(), $content_object
            ))
            {
                throw new NotAllowedException();
            }

            // Don't allow publication if the content object is in the RECYCLED
            // state
            if ($content_object->get_state() == ContentObject::STATE_RECYCLED)
            {
                throw new NotAllowedException();
            }

            $this->content_objects[] = $content_object;

            if ($this->type == null)
            {
                $this->type = $content_object->getType();
            }
            elseif ($this->type != $content_object->getType())
            {
                throw new UserException(Translation::get('ObjectsNotSameType'));
            }
        }

        $this->buildForm();
    }

    /**
     * @return string
     * @throws \QuickformException
     */
    public function add_selected_content_objects()
    {
        $category_title = htmlentities(
            Translation::get(count($this->content_objects) > 1 ? 'LocationSelectionsInfo' : 'LocationSelectionInfo')
        );

        $this->addElement('category', $category_title, 'publication-location');

        usort(
            $this->content_objects, function ($contentObjectOne, $contentObjectTwo) {
            return strcasecmp($contentObjectOne->get_title(), $contentObjectTwo->get_title());
        }
        );

        $table_data = [];

        foreach ($this->content_objects as $content_object)
        {
            $table_data[] = [
                $content_object->get_icon_image(IdentGlyph::SIZE_MINI),
                $content_object->get_title()
            ];
        }

        $glyph = new FontAwesomeGlyph('folder', [], Translation::get('Type'));

        $header = [];
        $header[] = new StaticTableColumn('category', $glyph->render(), 'cell-stat-x2');
        $header[] = new StaticTableColumn(
            Translation::get('Title', null, Manager::context())
        );

        $table = new ArrayCollectionTableRenderer(
            $table_data, $header, [], 1, count($table_data), SORT_ASC, 'selected-content-objects'
        );
        $this->addElement('html', $table->render());
    }

    /**
     * @throws \QuickformException
     */
    public function buildForm()
    {
        $this->_formBuilt = true;

        $this->add_selected_content_objects();

        $this->getPublicationAggregator()->addPublicationTargetsToFormForContentObjectAndUser(
            $this, $this->content_objects[0], $this->getApplication()->getUser()
        );

        $html = [];
        $html[] = '<div style="padding: 5px 0px;">';
        $html[] = '<a href="#" class="select-all-checkboxes">';
        $html[] = Translation::get('SelectAll', null, StringUtilities::LIBRARIES);
        $html[] = '</a>';
        $html[] = ' - ';
        $html[] = '<a href="#" class="select-no-checkboxes">';
        $html[] = Translation::get('UnselectAll', null, StringUtilities::LIBRARIES);
        $html[] = '</a>';
        $html[] = '</div>';

        $this->addElement('html', implode('', $html));

        $this->addElement('html', '<br /><br />');

        $this->addElement(
            'style_submit_button', 'publish', Translation::get('Publish', null, StringUtilities::LIBRARIES), null, null,
            new FontAwesomeGlyph('ok-sign')
        );

        $this->addElement(
            'html',
            '<script src="' . Path::getInstance()->getJavascriptPath('Chamilo\Core\Repository\Publication', true) .
            'Visibility.js' . '"></script>'
        );
    }

    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return \Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface
     */
    protected function getPublicationAggregator()
    {
        $dependencyInjectionContainer = DependencyInjectionContainerBuilder::getInstance()->createContainer();

        return $dependencyInjectionContainer->get(PublicationAggregator::class);
    }

    protected function getWorkspaceRightsService(): RightsService
    {
        return $this->getService(RightsService::class);
    }
}
