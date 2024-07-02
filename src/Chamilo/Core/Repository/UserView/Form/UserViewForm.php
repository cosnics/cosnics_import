<?php
namespace Chamilo\Core\Repository\UserView\Form;

use Chamilo\Core\Repository\Selector\TypeSelectorFactory;
use Chamilo\Core\Repository\UserView\Storage\DataClass\UserView;
use Chamilo\Core\Repository\UserView\Storage\DataClass\UserViewRelContentObject;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\RetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 *
 * @package core\repository\user_view
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class UserViewForm extends FormValidator
{
    public const TYPE_CREATE = 1;
    public const TYPE_EDIT = 2;

    /**
     *
     * @var int
     */
    private $form_type;

    /**
     *
     * @var \core\repository\user_view\UserView
     */
    private $user_view;

    /**
     *
     * @param int $form_type
     * @param \core\repository\user_view\UserView $user_view
     * @param string $action
     */
    public function __construct($form_type, $user_view, $action)
    {
        parent::__construct('user_views_settings', self::FORM_METHOD_POST, $action);

        $this->user_view = $user_view;

        $this->form_type = $form_type;
        if ($this->form_type == self::TYPE_EDIT)
        {
            $this->build_editing_form();
        }
        elseif ($this->form_type == self::TYPE_CREATE)
        {
            $this->build_creation_form();
        }

        $this->setDefaults();
    }

    public function build_basic_form()
    {
        $this->addElement(
            'text', UserView::PROPERTY_NAME, Translation::get('Name', null, StringUtilities::LIBRARIES),
            ['size' => '50']
        );
        $this->addRule(
            UserView::PROPERTY_NAME, Translation::get('ThisFieldIsRequired', null, StringUtilities::LIBRARIES),
            'required'
        );
        $this->add_html_editor(
            UserView::PROPERTY_DESCRIPTION, Translation::get('Description', null, StringUtilities::LIBRARIES), false
        );

        $registrations = \Chamilo\Core\Repository\Storage\DataManager::get_registered_types();
        $hidden_types = \Chamilo\Core\Repository\Storage\DataManager::get_active_helper_types();

        $typeSelectorFactory = new TypeSelectorFactory(
            \Chamilo\Core\Repository\Storage\DataManager::get_registered_types()
        );
        $type_selector = $typeSelectorFactory->getTypeSelector();

        foreach ($type_selector->get_categories() as $category)
        {
            foreach ($category->get_options() as $option)
            {
                $content_object_template_ids[$option->get_template_registration_id()] = $option->get_label();
            }
        }

        if ($this->form_type == self::TYPE_EDIT)
        {

            $relations = DataManager::retrieves(
                UserViewRelContentObject::class, new RetrievesParameters(
                    condition: new EqualityCondition(
                        new PropertyConditionVariable(
                            UserViewRelContentObject::class, UserViewRelContentObject::PROPERTY_USER_VIEW_ID
                        ), new StaticConditionVariable($this->get_user_view()->get_id())
                    )
                )
            );

            foreach ($relations as $relation)
            {
                $defaults[] = $relation->get_content_object_template_id();
            }
        }

        $this->addElement(
            'select', 'types', Translation::get('SelectTypesToShow'), $content_object_template_ids, [
                'multiple' => 'true',
                'size' => (count($content_object_template_ids) > 10 ? 10 : count($content_object_template_ids))
            ]
        );

        $this->setDefaults(['types' => $defaults]);
    }

    public function build_creation_form()
    {
        $this->build_basic_form();

        $buttons[] = $this->createElement(
            'style_submit_button', 'submit', Translation::get('Create', null, StringUtilities::LIBRARIES)
        );
        $buttons[] = $this->createElement(
            'style_reset_button', 'reset', Translation::get('Reset', null, StringUtilities::LIBRARIES)
        );

        $this->addGroup($buttons, 'buttons', null, '&nbsp;', false);
    }

    public function build_editing_form()
    {
        $user_view = $this->user_view;
        $this->build_basic_form();

        $this->addElement('hidden', UserView::PROPERTY_ID);

        $buttons[] = $this->createElement(
            'style_submit_button', 'submit', Translation::get('Update', null, StringUtilities::LIBRARIES), null, null,
            new FontAwesomeGlyph('arrow-right')
        );
        $buttons[] = $this->createElement(
            'style_reset_button', 'reset', Translation::get('Reset', null, StringUtilities::LIBRARIES)
        );

        $this->addGroup($buttons, 'buttons', null, '&nbsp;', false);
    }

    /**
     *
     * @return bool
     */
    public function create_user_view()
    {
        $values = $this->exportValues();

        $this->user_view->set_name($values[UserView::PROPERTY_NAME]);
        $this->user_view->set_description($values[UserView::PROPERTY_DESCRIPTION]);

        if ($this->user_view->create())
        {
            foreach ($values['types'] as $template_id)
            {
                $relation = new UserViewRelContentObject();
                $relation->set_user_view_id($this->user_view->get_id());
                $relation->set_content_object_template_id($template_id);

                if (!$relation->create())
                {
                    return false;
                }
            }

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     *
     * @return \core\repository\user_view\UserView
     */
    public function get_user_view()
    {
        return $this->user_view;
    }

    /**
     *
     * @see HTML_QuickForm::setDefaults()
     */
    public function setDefaults($defaults = [], $filter = null)
    {
        $defaults[UserView::PROPERTY_ID] = $this->user_view->get_id();
        $defaults[UserView::PROPERTY_NAME] = $this->user_view->get_name();
        $defaults[UserView::PROPERTY_DESCRIPTION] = $this->user_view->get_description();
        parent::setDefaults($defaults);
    }

    /**
     *
     * @return bool
     */
    public function update_user_view()
    {
        $user_view = $this->user_view;
        $values = $this->exportValues();

        $user_view->set_name($values[UserView::PROPERTY_NAME]);
        $user_view->set_description($values[UserView::PROPERTY_DESCRIPTION]);

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                UserViewRelContentObject::class, UserViewRelContentObject::PROPERTY_USER_VIEW_ID
            ), new StaticConditionVariable($user_view->get_id())
        );

        $types = DataManager::retrieves(
            UserViewRelContentObject::class, new RetrievesParameters(condition: $condition)
        );
        $existing_types = [];
        foreach ($types as $type)
        {
            $existing_types[] = $type->get_content_object_template_id();
        }

        $new_types = $values['types'];
        $to_add = array_diff($new_types, $existing_types);
        $to_delete = array_diff($existing_types, $new_types);

        foreach ($to_add as $type_to_add)
        {
            $user_view_type = new UserViewRelContentObject();
            $user_view_type->set_user_view_id($user_view->get_id());
            $user_view_type->set_content_object_template_id($type_to_add);
            $user_view_type->create();
        }

        $types->first();
        foreach ($types as $type)
        {
            if (in_array($type->get_content_object_template_id(), $to_delete))
            {
                $type->delete();
            }
        }

        return $user_view->update();
    }
}
