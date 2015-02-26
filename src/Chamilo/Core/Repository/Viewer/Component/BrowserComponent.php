<?php
namespace Chamilo\Core\Repository\Viewer\Component;

use Chamilo\Core\Repository\RepositoryRights;
use Chamilo\Core\Repository\Selector\TypeSelector;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataClass\SharedContentObjectRelCategory;
use Chamilo\Core\Repository\Viewer\Manager;
use Chamilo\Core\Repository\Viewer\Menu\RepositoryCategoryMenu;
use Chamilo\Core\Repository\Viewer\Menu\RepositorySharedCategoryMenu;
use Chamilo\Core\Repository\Viewer\Table\ContentObject\ContentObjectTable;
use Chamilo\Core\Rights\Entity\PlatformGroupEntity;
use Chamilo\Core\Rights\Entity\UserEntity;
use Chamilo\Libraries\Architecture\Interfaces\ComplexContentObjectSupport;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Format\Structure\Toolbar;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Interfaces\TableSupport;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Request;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Condition\PatternMatchCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\Utilities;

class BrowserComponent extends Manager implements TableSupport
{
    const SHARED_BROWSER = 'shared';
    const SHARED_BROWSER_ALLOWED = 'allow_shared_browser';
    const PROPERTY_CATEGORY = 'category';

    /**
     * The search form
     *
     * @var \libraries\format\FormValidator
     */
    private $form;

    /**
     * The renderer for the search form
     */
    private $renderer;

    public function __construct($user = null, $parent = null)
    {
        parent :: __construct($user, $parent);

        $form_parameters = $this->get_parameter();
        $form_parameters[self :: PARAM_ACTION] = self :: ACTION_BROWSER;

        if ($this->is_shared_object_browser())
        {
            $form_parameters[self :: SHARED_BROWSER] = 1;
        }

        $this->set_form(
            new FormValidator('search', 'post', $this->get_url($form_parameters), '', array('id' => 'search'), false));
        $this->get_form()->addElement(
            'text',
            self :: PARAM_QUERY,
            Translation :: get('Search', null, Utilities :: COMMON_LIBRARIES),
            'size="30" class="search_query"');
        $this->get_form()->addElement('style_submit_button', 'submit', null, array('class' => 'search'));
        $this->get_form()->setDefaults(array(self :: PARAM_QUERY => $this->get_query()));
    }

    /*
     * Inherited
     */
    public function run()
    {
        $this->renderer = clone $this->form->defaultRenderer();
        $this->renderer->setElementTemplate('<span>{element}</span> ');
        $this->form->accept($this->renderer);

        $html = array();

        $html[] = $this->render_header();

        $html[] = '<div class="search_form" style="float: right; margin: 0px 0px 5px 0px;">';
        $html[] = '<div class="simple_search">';
        $html[] = $this->renderer->toHTML();
        $html[] = '</div>';
        $html[] = '</div>';

        if ($this->get_maximum_select() > self :: SELECT_SINGLE)
        {
            $html[] = '<b>' . sprintf(
                Translation :: get('SelectMaximumNumberOfContentObjects'),
                $this->get_maximum_select()) . '</b><br />';
        }

        $menu = $this->get_menu();

        if ($this->is_allowed_shared_object_browsing())
        {
            $shared_menu = $this->get_shared_menu();
        }

        $table = $this->get_object_table();

        $html[] = '<br />';

        $html[] = '<div style="width: 15%; overflow: auto; float:left">';
        $html[] = $menu->render_as_tree();
        $html[] = '<br />';
        $html[] = $shared_menu ? $shared_menu->render_as_tree() : '';
        $html[] = '</div>';

        $html[] = '<div style="width: 83%; float: right;">';
        $html[] = $table->as_html();
        $html[] = '</div>';
        $html[] = '<div class="clear">&nbsp;</div>';

        $html[] = $this->render_footer();

        return implode("\n", $html);
    }

    /**
     *
     * @return \core\repository\viewer\ContentObjectTable
     */
    protected function get_object_table()
    {
        return new ContentObjectTable($this);
    }

    /**
     *
     * @return string NULL
     */
    protected function get_query()
    {
        if ($this->get_form()->validate())
        {

            return $this->get_form()->exportValue(self :: PARAM_QUERY);
        }

        if (Request :: get(self :: PARAM_QUERY))
        {
            return Request :: get(self :: PARAM_QUERY);
        }

        return null;
    }

    /**
     *
     * @return \libraries\format\FormValidator
     */
    public function get_form()
    {
        return $this->form;
    }

    /**
     *
     * @param \libraries\format\FormValidator $form
     */
    public function set_form($form)
    {
        $this->form = $form;
    }

    /**
     *
     * @param boolean $allow_shared
     * @return \core\repository\RepositoryCategoryMenu
     */
    public function get_menu($allow_shared = true)
    {
        $url = $this->get_url($this->get_parameters()) . '&' . self :: PROPERTY_CATEGORY . '=%s';

        $extra = array();

        if ($this->get_query())
        {
            $search_url = '#';
            $search = array();

            if ($this->is_shared_object_browser())
            {
                $search['title'] = Translation :: get('SharedSearchResults');
            }
            else
            {
                $search['title'] = Translation :: get('SearchResults', null, Utilities :: COMMON_LIBRARIES);
            }

            $search['url'] = $search_url;
            $search['class'] = 'search_results';
            $extra[] = $search;
        }
        else
        {
            $search_url = null;
        }

        $menu = new RepositoryCategoryMenu(
            $this,
            $this->get_user_id(),
            Request :: get(self :: PROPERTY_CATEGORY) ? Request :: get(self :: PROPERTY_CATEGORY) : 0,
            $url,
            $extra,
            $this->get_types());

        if ($search_url)
        {
            $menu->forceCurrentUrl($search_url);
        }

        return $menu;
    }

    /**
     *
     * @param int $category_id
     * @return string
     */
    public function get_category_url($category_id)
    {
        return $this->get_url(array(self :: PROPERTY_CATEGORY => $category_id));
    }

    /**
     *
     * @return \core\repository\RepositorySharedCategoryMenu
     */
    public function get_shared_menu()
    {
        return new RepositorySharedCategoryMenu($this);
    }

    /**
     *
     * @param \core\repository\ContentObject $content_object
     * @return \libraries\format\Toolbar
     */
    public function get_default_browser_actions($content_object)
    {
        $toolbar = new Toolbar(Toolbar :: TYPE_HORIZONTAL);

        if ($content_object->has_right(RepositoryRights :: USE_RIGHT, $this->get_user_id()))
        {
            $toolbar->add_item(
                new ToolbarItem(
                    Translation :: get('Publish', null, Utilities :: COMMON_LIBRARIES),
                    Theme :: getInstance()->getCommonImagePath() . 'action_publish.png',
                    $this->get_url(
                        array_merge(
                            $this->get_parameters(),
                            array(
                                self :: PARAM_ACTION => self :: ACTION_PUBLISHER,
                                self :: PARAM_ID => $content_object->get_id())),
                        false),
                    ToolbarItem :: DISPLAY_ICON));
        }

        if ($content_object->has_right(RepositoryRights :: VIEW_RIGHT, $this->get_user_id()))
        {
            $toolbar->add_item(
                new ToolbarItem(
                    Translation :: get('Preview'),
                    Theme :: getInstance()->getCommonImagePath() . 'action_browser.png',
                    $this->get_url(
                        array_merge(
                            $this->get_parameters(),
                            array(
                                self :: PARAM_ACTION => self :: ACTION_VIEWER,
                                self :: PARAM_ID => $content_object->get_id())),
                        false),
                    ToolbarItem :: DISPLAY_ICON));
        }

        if ($content_object->has_right(RepositoryRights :: COLLABORATE_RIGHT, $this->get_user_id()))
        {
            $toolbar->add_item(
                new ToolbarItem(
                    Translation :: get('EditAndPublish'),
                    Theme :: getInstance()->getCommonImagePath() . 'action_editpublish.png',
                    $this->get_url(
                        array_merge(
                            $this->get_parameters(),
                            array(
                                self :: PARAM_ACTION => self :: ACTION_CREATOR,
                                self :: PARAM_EDIT_ID => $content_object->get_id())),
                        false),
                    ToolbarItem :: DISPLAY_ICON));
        }

        if ($content_object instanceof ComplexContentObjectSupport)
        {

            $preview_url = \Chamilo\Core\Repository\Manager :: get_preview_content_object_url($content_object);
            $onclick = '" onclick="javascript:openPopup(\'' . $preview_url . '\'); return false;';
            $toolbar->add_item(
                new ToolbarItem(
                    Translation :: get('Preview', null, Utilities :: COMMON_LIBRARIES),
                    Theme :: getInstance()->getCommonImagePath() . 'action_preview.png',
                    $preview_url,
                    ToolbarItem :: DISPLAY_ICON,
                    false,
                    $onclick,
                    '_blank'));
        }

        return $toolbar;
    }

    /**
     *
     * @return boolean
     */
    public function is_shared_object_browser()
    {
        return (Request :: get(self :: SHARED_BROWSER) == 1);
    }

    public function add_additional_breadcrumbs(BreadcrumbTrail $breadcrumbtrail)
    {
        $breadcrumbtrail->add_help('repo_viewer_browser');
    }

    /**
     *
     * @return boolean
     */
    public function is_allowed_shared_object_browsing()
    {
        $parameters = $this->get_parameters();
        $allowed = $parameters[self :: SHARED_BROWSER_ALLOWED];
        if ($allowed === false)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /*
     * (non-PHPdoc) @see \libraries\format\TableSupport::get_table_condition()
     */
    public function get_table_condition($table_class_name)
    {
        $type_selector = TypeSelector :: populate($this->get_types(), $this->get_user_id());
        $all_types = $type_selector->get_unique_content_object_template_ids();

        $type_selection = TypeSelector :: get_selection();

        if ($type_selection)
        {
            $types = array($type_selection);
            $types = array_intersect($types, $all_types);
        }
        else
        {
            $types = $all_types;
        }

        $conditions = array();
        $type_conditions = array();

        $conditions[] = new InCondition(
            new PropertyConditionVariable(
                ContentObject :: class_name(),
                ContentObject :: PROPERTY_TEMPLATE_REGISTRATION_ID),
            $types);

        $query = $this->get_query();

        if (isset($query) && $query != '')
        {
            $or_conditions[] = new PatternMatchCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_TITLE),
                '*' . $query . '*',
                ContentObject :: get_table_name());
            $or_conditions[] = new PatternMatchCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_DESCRIPTION),
                '*' . $query . '*',
                ContentObject :: get_table_name());
            $conditions[] = new OrCondition($or_conditions);
        }

        if (! $this->is_shared_object_browser())
        {
            if (! isset($query) || $query == '')
            {
                $category = Request :: get('category');
                $category = $category ? $category : 0;
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_PARENT_ID),
                    new StaticConditionVariable($category));
            }

            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_OWNER_ID),
                new StaticConditionVariable($this->get_user()->get_id()));
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
                new StaticConditionVariable(ContentObject :: STATE_NORMAL));

            foreach ($this->get_excluded_objects() as $excluded)
            {
                $conditions[] = new NotCondition(
                    new EqualityCondition(
                        new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                        new StaticConditionVariable($excluded)));
            }
        }
        else
        {
            $share_category = Request :: get(\Chamilo\Core\Repository\Manager :: PARAM_SHARED_CATEGORY_ID);
            if ($share_category)
            {
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        SharedContentObjectRelCategory :: class_name(),
                        SharedContentObjectRelCategory :: PROPERTY_CATEGORY_ID),
                    new StaticConditionVariable($share_category));
            }

            $entities = array();
            $entities[] = new UserEntity();
            $entities[] = new PlatformGroupEntity();

            $retrieve_types = array(RepositoryRights :: TYPE_USER_CONTENT_OBJECT);

            $shared_locations_by_type = RepositoryRights :: get_instance()->get_location_overview_with_rights_granted(
                \Chamilo\Core\Repository\Manager :: context(),
                $this->get_user_id(),
                $entities,
                null,
                $retrieve_types,
                RepositoryRights :: TREE_TYPE_USER);

            if (empty($shared_locations_by_type))
            {
                $shared_locations_by_type[] = - 1;
            }

            $conditions[] = new InCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                array_unique($shared_locations_by_type[RepositoryRights :: TYPE_USER_CONTENT_OBJECT]));
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_STATE),
                new StaticConditionVariable(ContentObject :: STATE_NORMAL));
        }

        return new AndCondition($conditions);
    }
}
