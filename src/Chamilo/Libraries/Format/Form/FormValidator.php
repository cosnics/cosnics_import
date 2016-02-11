<?php
namespace Chamilo\Libraries\Format\Form;

use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Display;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Format\Utilities\ResourceManager;
use Chamilo\Libraries\Platform\Configuration\LocalSetting;
use Chamilo\Libraries\Platform\Security;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\Utilities;
use HTML_QuickForm;
use HTML_QuickForm_RuleRegistry;

/**
 *
 * @package common.html.formvalidator
 */
/**
 * Filter
 */
define('NO_HTML', 1);
define('STUDENT_HTML', 2);
define('TEACHER_HTML', 3);
define('STUDENT_HTML_FULLPAGE', 4);
define('TEACHER_HTML_FULLPAGE', 5);
/**
 * Objects of this class can be used to create/manipulate/validate user input.
 */
class FormValidator extends HTML_QuickForm
{
    const PARAM_SUBMIT = 'submit';
    const PARAM_RESET = 'reset';
    const FORM_METHOD_POST = 'post';
    const FORM_METHOD_GET = 'get';

    private $no_errors;

    private $renderer;

    /**
     * The HTML-editors in this form
     */
    private $html_editors;

    private $with_progress_bar;

    /**
     * Constructor
     *
     * @param string $form_name Name of the form
     * @param string $method Method ('post' (default) or 'get')
     * @param string $action Action (default is $PHP_SELF)
     * @param string $target Form's target defaults to '_self'
     * @param mixed $attributes (optional)Extra attributes for <form> tag
     * @param bool $trackSubmit (optional)Whether to track if the form was submitted by adding a special hidden field
     *        (default = true)
     */
    public function __construct($form_name, $method = 'post', $action = '', $target = '', $attributes = null, $trackSubmit = true)
    {
        if (is_null($attributes))
        {
            $attributes = array();
        }
        $attributes['onreset'] = 'resetElements()';

        HTML_QuickForm :: HTML_QuickForm($form_name, $method, $action, $target, $attributes, $trackSubmit);
        // Load some custom elements and rules
        $dir = __DIR__ . '/';

        $this->registerElementType('radio',
            $dir . 'Element/HTML_QuickForm_bootstrap_radio.php',
            'HTML_QuickForm_bootstrap_radio');

        $this->registerElementType(
            'datepicker',
            $dir . 'Element/HTML_QuickForm_datepicker.php',
            'HTML_QuickForm_datepicker');
        $this->registerElementType(
            'timepicker',
            $dir . 'Element/HTML_QuickForm_timepicker.php',
            'HTML_QuickForm_timepicker');
        $this->registerElementType(
            'receivers',
            $dir . 'Element/HTML_QuickForm_receivers.php',
            'HTML_QuickForm_receivers');
        $this->registerElementType(
            'select_language',
            $dir . 'Element/HTML_QuickForm_select_language.php',
            'HTML_QuickForm_Select_Language');
        $this->registerElementType(
            'upload_or_create',
            $dir . 'Element/HTML_QuickForm_upload_or_create.php',
            'HTML_QuickForm_upload_or_create');
        $this->registerElementType(
            'element_finder',
            $dir . 'Element/HTML_QuickForm_element_finder.php',
            'HTML_QuickForm_element_finder');
        $this->registerElementType(
            'advanced_element_finder',
            $dir . 'Element/HTML_QuickForm_advanced_element_finder.php',
            'HTML_QuickForm_advanced_element_finder');
        $this->registerElementType(
            'advmultiselect',
            $dir . 'Element/HTML_QuickForm_advmultiselect_chamilo.php',
            'HTML_QuickForm_advmultiselect_chamilo');
        $this->registerElementType(
            'image_selecter',
            $dir . 'Element/HTML_QuickForm_image_selecter.php',
            'HTML_QuickForm_image_selecter');

        $this->registerElementType(
            'user_group_finder',
            $dir . 'Element/HTML_QuickForm_user_group_finder.php',
            'HTML_QuickForm_user_group_finder');
        $this->registerElementType(
            'option_orderer',
            $dir . 'Element/HTML_QuickForm_option_orderer.php',
            'HTML_QuickForm_option_orderer');
        $this->registerElementType('category', $dir . 'Element/HTML_QuickForm_category.php', 'HTML_QuickForm_category');
        $this->registerElementType('splitter', $dir . 'Element/HTML_QuickForm_splitter.php', 'HTML_QuickForm_splitter');
        $this->registerElementType(
            'style_button',
            $dir . 'Element/HTML_QuickForm_stylebutton.php',
            'HTML_QuickForm_stylebutton');
        $this->registerElementType(
            'style_submit_button',
            $dir . 'Element/HTML_QuickForm_stylesubmitbutton.php',
            'HTML_QuickForm_stylesubmitbutton');
        $this->registerElementType(
            'style_reset_button',
            $dir . 'Element/HTML_QuickForm_styleresetbutton.php',
            'HTML_QuickForm_styleresetbutton');
        $this->registerElementType(
            'checkbox',
            $dir . 'Element/HTML_QuickForm_extended_checkbox.php',
            'HTML_QuickForm_extended_checkbox');

        $this->registerRule('date', null, 'HTML_QuickForm_Rule_Date', $dir . 'Rule/HTML_QuickForm_Rule_Date.php');
        $this->registerRule(
            'date_compare',
            null,
            'HTML_QuickForm_Rule_DateCompare',
            $dir . 'Rule/HTML_QuickForm_Rule_DateCompare.php');
        $this->registerRule(
            'number_compare',
            null,
            'HTML_QuickForm_Rule_NumberCompare',
            $dir . 'Rule/HTML_QuickForm_Rule_NumberCompare.php');
        $this->registerRule(
            'username_available',
            null,
            'HTML_QuickForm_Rule_UsernameAvailable',
            $dir . 'Rule/HTML_QuickForm_Rule_UsernameAvailable.php');
        $this->registerRule(
            'username',
            null,
            'HTML_QuickForm_Rule_Username',
            $dir . 'Rule/HTML_QuickForm_Rule_Username.php');
        $this->registerRule('url', null, 'HTML_QuickForm_Rule_Url', $dir . 'Rule/HTML_QuickForm_Rule_Url.php');
        $this->registerRule(
            'filetype',
            null,
            'HTML_QuickForm_Rule_Filetype',
            $dir . 'Rule/HTML_QuickForm_Rule_Filetype.php');

        $this->registerRule(
            'disk_quota',
            null,
            'HTML_QuickForm_Rule_DiskQuota',
            $dir . 'Rule/HTML_QuickForm_Rule_DiskQuota.php');

        $this->registerRule(
            'jquery_date',
            null,
            'HTML_QuickForm_Rule_JqueryDate',
            $dir . 'Rule/HTML_QuickForm_Rule_JqueryDate.php');
        $this->registerRule(
            'jquery_time',
            null,
            'HTML_QuickForm_Rule_JqueryTime',
            $dir . 'Rule/HTML_QuickForm_Rule_JqueryTime.php');

        $this->addElement(
            'html',
            '<script type="text/javascript" src="' . Path :: getInstance()->getJavascriptPath('Chamilo\Libraries', true) .
                 'Reset.js"></script>');

        // Modify the default templates
        $this->renderer = $this->defaultRenderer();

        $form_template = <<<EOT

<form {attributes}>
{content}
	<div class="clear">
		&nbsp;
	</div>
</form>

EOT;
        $this->renderer->setFormTemplate($form_template);

        $element_template = array();
        $element_template[] = '<div class="row">';
        $element_template[] = '<div class="label">';
        $element_template[] = '{label}<!-- BEGIN required --><span class="form_required"><img src="' .
             Theme :: getInstance()->getCommonImagePath('Action/Required') .
             '" alt="*" title ="*"/></span> <!-- END required -->';
        $element_template[] = '</div>';
        $element_template[] = '<div class="formw">';
        $element_template[] = '<div class="element"><!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	{element}</div>';
        $element_template[] = '<div class="form_feedback"></div></div>';
        $element_template[] = '<div class="clear">&nbsp;</div>';
        $element_template[] = '</div>';
        $element_template = implode(PHP_EOL, $element_template);

        $this->renderer->setElementTemplate($element_template);

        $header_template = array();
        $header_template[] = '<div class="row">';
        $header_template[] = '<div class="form_header">{header}</div>';
        $header_template[] = '</div>';
        $header_template = implode(PHP_EOL, $header_template);

        $this->renderer->setHeaderTemplate($header_template);

        HTML_QuickForm :: setRequiredNote(
            '<span class="form_required"><img src="' . Theme :: getInstance()->getCommonImagePath('Action/Required') .
                 '" alt="*" title ="*"/>&nbsp;<small>' .
                 Translation :: get('ThisFieldIsRequired', null, Utilities :: COMMON_LIBRARIES) . '</small></span>');
        $required_note_template = <<<EOT
	<div class="row">
		<div class="label"></div>
		<div class="formw">{requiredNote}</div>
	</div>
EOT;
        $this->renderer->setRequiredNoteTemplate($required_note_template);
        foreach ($this->_submitValues as $index => & $value)
        {
            $value = Security :: remove_XSS($value);
        }
    }

    public function set_error_reporting($enabled)
    {
        $this->no_errors = ! $enabled;
    }

    /**
     * Returns the renderer
     *
     * @return HTML_QuickForm_Renderer_Default
     */
    public function get_renderer()
    {
        return $this->renderer;
    }

    public function set_renderer($renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     *
     * @param \HTML_QuickForm_group $group
     * @param string $element_name
     * @return \HTML_QuickForm_element
     */
    public function get_group_element($group_name, $element_name)
    {
        $group_elements = $this->getElement($group_name)->getElements();

        foreach ($group_elements as $group_element)
        {
            if ($group_element->getName() == $element_name)
            {
                return $group_element;
            }
        }
    }

    /**
     * Add a textfield to the form.
     * A trim-filter is attached to the field.
     *
     * @param string $label The label for the form-element
     * @param string $name The element name
     * @param boolean $required Is the form-element required (default=true)
     * @param array $attributes Optional list of attributes for the form-element
     * @return HTML_QuickForm_input The element.
     */
    public function add_textfield($name, $label, $required = true, $attributes = array())
    {
        if (! array_key_exists('size', $attributes))
        {
            $attributes['size'] = 50;
        }
        $element = $this->addElement('text', $name, $label, $attributes);
        $this->applyFilter($name, 'trim');
        if ($required)
        {
            $this->addRule(
                $name,
                Translation :: get('ThisFieldIsRequired', null, Utilities :: COMMON_LIBRARIES),
                'required');
        }
        return $element;
    }

    public function create_textfield($name, $label, $attributes = array())
    {
        if (! array_key_exists('size', $attributes))
        {
            $attributes['size'] = 50;
        }
        $element = $this->createElement('text', $name, $label, $attributes);
        return $element;
    }

    /**
     * Add a password field to the form.
     *
     * @param $name
     * @param $label
     * @param $required
     * @param $attributes
     */
    public function add_password($name, $label, $required = true, $attributes = array())
    {
        $element = $this->create_password($name, $label, $attributes);
        $this->addElement($element);
        if ($required)
        {
            $this->addRule(
                $name,
                Translation :: get('ThisFieldIsRequired', null, Utilities :: COMMON_LIBRARIES),
                'required');
        }
        return $element;
    }

    /**
     * Create a password field.
     *
     * @param $name
     * @param $label
     * @param $attributes
     */
    public function create_password($name, $label, $attributes = array())
    {
        if (! array_key_exists('size', $attributes))
        {
            $attributes['size'] = 50;
        }
        $element = $this->createElement('password', $name, $label, $attributes);
        return $element;
    }

    /**
     * Adds a select control to the form.
     *
     * @param string $name The element name.
     * @param string $label The element label.
     * @param array $values Associative array of possible values.
     * @param boolean $required <code>true</code> if required (default), <code>false</code> otherwise.
     * @param array $attributes Element attributes (optional).
     * @return HTML_QuickForm_select The element.
     */
    public function add_select($name, $label, $values, $required = true, $attributes = array())
    {
        $element = $this->addElement('select', $name, $label, $values, $attributes);
        if ($required)
        {
            $this->addRule(
                $name,
                Translation :: get('ThisFieldIsRequired', null, Utilities :: COMMON_LIBRARIES),
                'required');
        }
        return $element;
    }

    /**
     * Add a HTML-editor to the form to fill in a title.
     * A trim-filter is attached to the field. A HTML-filter is
     * attached to the field (cleans HTML) A rule is attached to check for unwanted HTML
     *
     * @param string $label The label for the form-element
     * @param string $name The element name
     * @param boolean $required Is the form-element required (default=true)
     * @return HTML_QuickForm_html_editor The element.
     */
    public function add_html_editor($name, $label, $required = true, $options = array(), $attributes = array())
    {
        $html_editor = FormValidatorHtmlEditor :: factory(
            LocalSetting :: getInstance()->get('html_editor'),
            $name,
            $label,
            $required,
            $options,
            $attributes);
        $html_editor->set_form($this);
        $html_editor->add();
    }

    /*
     * Adds tabs to a form @param array $tabs An array of tab objects that specifies the tabs that are going to be
     * created @param int $selected_tab The tab that is selected @author Tristan Verheecke
     */
    public function add_tabs($tabs, $selected_tab)
    {
        // $renderer_name = ClassnameUtilities :: getInstance()->getClassnameFromObject($this, true);
        // $course_tabs = new DynamicTabsRenderer($renderer_name);
        //
        // foreach ($tabs as $index => $tab)
        // {
        // $course_tabs->add_tab(new DynamicContentTab($index, $tab[1], null, $this->display_courses($tab[0], $index)));
        // }
        //
        // $html[] = $course_tabs->render();
        $this->addElement('html', '<div id="form_tabs">');
        $this->addElement('html', '<ul>');
        foreach ($tabs as $index => $tab)
        {
            $this->addElement('html', '<li><a href="#form_tabs-' . $index . '">');
            $this->addElement('html', '<span class="category">');
            $this->addElement('html', '<span class="title">' . Translation :: get($tab->get_title()) . '</span>');
            $this->addElement('html', '</span>');
            $this->addElement('html', '</a></li>');
        }
        $this->addElement('html', '</ul>');
        foreach ($tabs as $index => $tab)
        {
            // $this->addElement('html', '<h2>' . $tab->get_title() . '</h2>');
            $this->addElement('html', '<div class="form_tab" id="form_tabs-' . $index . '">');
            call_user_func(array($this, $tab->get_method()));
            $this->addElement('html', '<div class="clear"></div>');
            $this->addElement('html', '</div>');
        }

        $this->addElement('html', '</div>');
        $this->addElement('html', '<script type="text/javascript">');
        $this->addElement('html', '  var tabnumber = ' . $selected_tab . ';');
        $this->addElement('html', '</script>');

        $this->addElement(
            'html',
            ResourceManager :: get_instance()->get_resource_html(
                Path :: getInstance()->getJavascriptPath('Chamilo\Libraries', true) . 'FormTabs.js'));
    }

    public function create_html_editor($name, $label, $options = array(), $attributes = array())
    {
        $html_editor = FormValidatorHtmlEditor :: factory(
            LocalSetting :: getInstance()->get('html_editor'),
            $name,
            $label,
            false,
            $options,
            $attributes);
        $html_editor->set_form($this);
        return $html_editor->create();
    }

    public function register_html_editor($name)
    {
        $this->html_editors[] = $name;
    }

    public function unregister_html_editor($name)
    {
        $key = array_search($name, $this->html_editors);

        if ($key)
        {
            unset($this->html_editors[$key]);
        }
    }

    public function get_html_editors()
    {
        return $this->html_editors;
    }

    /**
     * Add a datepicker element to the form A rule is added to check if the date is a valid one
     *
     * @param string $label The label for the form-element
     * @param string $name The element name
     * @return HTML_QuickForm_datepicker The element.
     */
    public function add_datepicker($name, $label, $include_time_picker = true)
    {
        // $attributes = array_merge(array('form_name' => $this->getAttribute('name'), 'class' => $name),$attributes);
        $element = $this->addElement(
            'datepicker',
            $name,
            $label,
            array('form_name' => $this->getAttribute('name'), 'class' => $name),
            $include_time_picker);
        $this->addRule($name, Translation :: get('InvalidDate'), 'date');
        return $element;
    }

    /**
     * Add a option_orderer element to the form
     *
     * @param string $label The label for the form-element
     * @param string $name The element name
     * @return HTML_QuickForm_datepicker The element.
     */
    public function add_option_orderer($name, $label, $options, $separator)
    {
        $element = $this->addElement(
            'option_orderer',
            $name,
            $label,
            $options,
            $separator,
            array('form_name' => $this->getAttribute('name'), 'class' => $name));
        return $element;
    }

    /**
     * Add a timepicker element to the form
     *
     * @param string $label The label for the form-element
     * @param string $name The element name
     * @return HTML_QuickForm_datepicker The element.
     */
    public function add_timepicker($name, $label, $include_minutes_picker = true)
    {
        $element = $this->addElement(
            'timepicker',
            $name,
            $label,
            array('form_name' => $this->getAttribute('name'), 'class' => $name),
            $include_minutes_picker);
        return $element;
    }

    /**
     * Add a timewindow element to the form.
     * 2 datepicker elements are added and a rule to check if the first date is
     * before the second one.
     *
     * @param string $label The label for the form-element
     * @param string $name The element name
     */
    public function add_timewindow($name_1, $name_2, $label_1, $label_2, $include_time_picker = true)
    {
        $elements[] = $this->add_datepicker($name_1, $label_1, $include_time_picker);
        $elements[] = $this->add_datepicker($name_2, $label_2, $include_time_picker);
        $this->addRule(
            array($name_1, $name_2),
            Translation :: get('StartDateShouldBeBeforeEndDate'),
            'date_compare',
            'lte');

        return $elements;
    }

    /**
     */
    public function add_forever_or_timewindow($element_label = 'PublicationPeriod', $element_name_prefix = '', $use_dimensions = false)
    {
        if (! $use_dimensions)
        {
            $elementName = $element_name_prefix . 'forever';
            $fromName = $element_name_prefix . 'from_date';
            $toName = $element_name_prefix . 'to_date';
        }
        else
        {
            $elementName = $element_name_prefix . '[forever]';
            $fromName = $element_name_prefix . '[from_date]';
            $toName = $element_name_prefix . '[to_date]';
        }

        $choices[] = $this->createElement(
            'radio',
            $elementName,
            '',
            Translation :: get('Forever'),
            1,
            array('id' => 'forever', 'onclick' => 'javascript:timewindow_hide(\'forever_timewindow\')'));
        $choices[] = $this->createElement(
            'radio',
            $elementName,
            '',
            Translation :: get('LimitedPeriod'),
            0,
            array('id' => 'limited', 'onclick' => 'javascript:timewindow_show(\'forever_timewindow\')'));
        $this->addGroup($choices, null, Translation :: get($element_label), '<br />', false);
        $this->addElement('html', '<div style="margin-left:25px;display:block;" id="forever_timewindow">');
        $this->add_timewindow($fromName, $toName, '', '');
        $this->addElement('html', '</div>');
        $this->addElement(
            'html',
            "<script type=\"text/javascript\">
					/* <![CDATA[ */
					var expiration = document.getElementById('forever');
					if (expiration.checked)
					{
						timewindow_hide('forever_timewindow');
					}
					function timewindow_show(item) {
						el = document.getElementById(item);
						el.style.display='';
					}
					function timewindow_hide(item) {
						el = document.getElementById(item);
						el.style.display='none';
					}
					/* ]]> */
					</script>\n");
    }

    /**
     */
    public function add_forever_or_expiration_date_window($element_name, $element_label = 'ExpirationDate')
    {
        $choices[] = $this->createElement(
            'radio',
            'forever',
            '',
            Translation :: get('Forever'),
            1,
            array('onclick' => 'javascript:timewindow_hide(\'forever_timewindow\')', 'id' => 'forever'));
        $choices[] = $this->createElement(
            'radio',
            'forever',
            '',
            Translation :: get('LimitedPeriod'),
            0,
            array('onclick' => 'javascript:timewindow_show(\'forever_timewindow\')'));
        $this->addGroup($choices, null, Translation :: get($element_label), '<br />', false);
        $this->addElement('html', '<div style="margin-left: 25px; display: block;" id="forever_timewindow">');
        $this->addElement('datepicker', $element_name, '', array('form_name' => $this->getAttribute('name')), false);
        $this->addElement('html', '</div>');
        $this->addElement(
            'html',
            "<script type=\"text/javascript\">
					/* <![CDATA[ */
					var expiration = document.getElementById('forever');
					if (expiration.checked)
					{
						timewindow_hide('forever_timewindow');
					}
					function timewindow_show(item) {
						el = document.getElementById(item);
						el.style.display='';
					}
					function timewindow_hide(item) {
						el = document.getElementById(item);
						el.style.display='none';
					}
					/* ]]> */
					</script>\n");
    }

    public function add_receivers($elementName, $elementLabel, $attributes, $no_selection = 'Everybody', $legend = null)
    {
        $choices = array();
        $choices[] = $this->createElement(
            'radio',
            $elementName . '_option',
            '',
            Translation :: get($no_selection),
            '0',
            array(
                'onclick' => 'javascript:receivers_hide(\'receivers_window_' . $elementName . '\')',
                'id' => 'receiver_' . $elementName));
        $choices[] = $this->createElement(
            'radio',
            $elementName . '_option',
            '',
            Translation :: get('SelectGroupsUsers'),
            '1',
            array('onclick' => 'javascript:receivers_show(\'receivers_window_' . $elementName . '\')'));
        $this->addGroup($choices, null, $elementLabel, '<br />', false);
        $this->addElement(
            'html',
            '<div style="margin-left: 25px; display: block;" id="receivers_window_' . $elementName . '">');

        $this->add_element_finder_with_legend($elementName, null, $attributes, $legend);

        $this->addElement('html', '</div>');
        $this->addElement(
            'html',
            "<script type=\"text/javascript\">
					/* <![CDATA[ */
					var expiration_" . $elementName . " = document.getElementById('receiver_" . $elementName . "');
					if (expiration_" . $elementName . ".checked)
					{
						receivers_hide('receivers_window_" . $elementName . "');
					}
					function receivers_show(item) {
						el = document.getElementById(item);
						el.style.display='';
					}
					function receivers_hide(item) {
						el = document.getElementById(item);
						el.style.display='none';
					}
					function reset_receivers_" . $elementName . "()
					{
						setTimeout(
							function()
							{
								if (expiration_" . $elementName . ".checked)
									receivers_hide('receivers_window_" . $elementName . "');
								else
									receivers_show('receivers_window_" . $elementName . "');
							},30);
    				}
    				$(document).ready(function ()
					{
						$(document).on('click', ':reset', reset_receivers_" . $elementName . ");
					});
					/* ]]> */
					</script>\n");
    }

    public function add_element_finder_with_legend($elementName, $elementLabel, $attributes, $legend = null)
    {
        $element_finder = $this->createElement(
            'user_group_finder',
            $elementName . '_elements',
            $elementLabel,
            $attributes['search_url'],
            $attributes['locale'],
            $attributes['defaults'],
            $attributes['options']);
        $element_finder->excludeElements($attributes['exclude']);
        $this->addElement($element_finder);

        if ($legend)
        {
            $this->addElement('static', null, null, $legend->as_html());
        }
    }

    public function add_receivers_variable($elementName, $elementLabel, $attributes, $radioArray, $defaultSelected)
    {
        $choices = array();
        if (! is_array($radioArray))
        {
            $radioArray = array($radioArray);
        }
        foreach ($radioArray as $radioType)
        {
            $choices[] = $this->createElement(
                'radio',
                $elementName . '_option',
                '',
                Translation :: get($radioType),
                $radioType,
                array(
                    'onclick' => 'javascript:receivers_hide(\'' . $elementName . 'receivers_window\')',
                    'id' => $elementName . 'receiver'));
        }
        $choices[] = $this->createElement(
            'radio',
            $elementName . '_option',
            '',
            Translation :: get('SelectGroupsUsers'),
            '1',
            array(
                'onclick' => 'javascript:receivers_show(\'' . $elementName . 'receivers_window\')',
                'id' => $elementName . 'group'));
        $this->addGroup($choices, null, $elementLabel, '<br />', false);
        $idGroup = $elementName . 'group';
        $nameWindow = $elementName . 'receivers_window';
        $this->addElement(
            'html',
            '<div style="margin-left: 25px; display: block;" id="' . $elementName . 'receivers_window">');

        $element_finder = $this->createElement(
            'user_group_finder',
            $elementName . '_elements',
            '',
            $attributes['search_url'],
            $attributes['locale'],
            $attributes['defaults'],
            $attributes['options']);

        $element_finder->excludeElements($attributes['exclude']);

        $this->addElement($element_finder);
        $this->addElement('html', '</div>');

        $this->addElement(
            'html',
            "<script type=\"text/javascript\">
					/* <![CDATA[ */
					var expiration_" . $elementName . " = document.getElementById('$idGroup');
					if (expiration_" . $elementName . ".checked)
					{
						receivers_show('$nameWindow');
					}
                    else
                    {
                        receivers_hide('$nameWindow');
                    }
					function receivers_show(item) {
						el = document.getElementById(item);
						el.style.display='';
					}
					function receivers_hide(item) {
						el = document.getElementById(item);
						el.style.display='none';
					}
					function reset_receivers_" . $elementName . "()
					{
						setTimeout(
							function()
							{
								if (expiration_" . $elementName . ".checked)
									receivers_show('$nameWindow');
								else
									receivers_hide('$nameWindow');
							},30);
    				}
    				$(document).ready(function ()
					{
						$(document).on('click', ':reset', reset_receivers_" . $elementName . ");
					});
					/* ]]> */
					</script>\n");
    }

    public function add_indicators($elementName, $elementLabel, $attributes)
    {
        $this->addElement('html', '<div style="display: block;" id="receivers_window">');
        $element_finder = $this->createElement(
            'element_finder',
            $elementName . '_elements',
            '',
            $attributes['search_url'],
            $attributes['locale'],
            $attributes['defaults']);
        $element_finder->excludeElements($attributes['exclude']);
        $this->addElement($element_finder);
        $this->addElement('html', '</div>');
        $this->addElement(
            'html',
            "<script type=\"text/javascript\">
					/* <![CDATA[ */
					function receivers_show(item) {
						el = document.getElementById(item);
						el.style.display='';
					}
					function receivers_hide(item) {
						el = document.getElementById(item);
						el.style.display='none';
					}
					/* ]]> */
					</script>\n");
    }

    public function add_checkbox_javascript()
    {
        $html = array();

        $html[] = '<script type="text/javascript">';
        $html[] = '$(document).ready(function() {';
        $html[] = '$(\':checkbox:not(.no-toggle-style)\').bootstrapToggle({';
        $html[] = 'on: \'' . Translation :: get('ConfirmOn', array(), Utilities :: COMMON_LIBRARIES) . '\',';
        $html[] = 'off: \'' . Translation :: get('ConfirmOff', array(), Utilities :: COMMON_LIBRARIES) . '\',';
        $html[] = 'size: \'small\'';
        $html[] = '});';
        $html[] = '});';
        $html[] = '</script>';

        $this->addElement('html', implode(PHP_EOL, $html));
    }

    /**
     * Add a button to the form to add resources.
     */
    public function add_resource_button()
    {
        $group[] = $this->createElement(
            'static',
            'add_resource_img',
            null,
            '<img src="' . Theme :: getInstance()->getCommonImagePath('Action/Attachment') . '" alt="' .
                 Translation :: get('Attachment') . '"/>');
        $group[] = $this->createElement(
            'submit',
            'add_resource',
            Translation :: get('Attachment'),
            'class="link_alike"');
        $this->addGroup($group);
    }

    /**
     * Adds a progress bar to the form.
     * Once the user submits the form, a progress bar (animated gif) is displayed. The
     * progress bar will disappear once the page has been reloaded.
     *
     * @param int $delay The number of seconds between the moment the user submits the form and the start of the
     *        progress bar.
     */
    public function add_progress_bar($delay = 2)
    {
        $this->with_progress_bar = true;
        $this->updateAttributes(
            "onsubmit=\"javascript: myUpload.start('dynamic_div','" .
                 Theme :: getInstance()->getCommonImagePath('Action/ProgressBar', 'gif') . "','" .
                 Translation :: get('PleaseStandBy') . "','" . $this->getAttribute('id') . "');\"");
        $this->addElement(
            'html',
            '<script src="' . Path :: getInstance()->getJavascriptPath('Chamilo\Libraries', true) .
                 'Upload.js" type="text/javascript"></script>');
        $this->addElement(
            'html',
            '<script type="text/javascript">var myUpload = new upload(' . (abs(intval($delay)) * 1000) . ');</script>');
    }

    public function validate_csv($value)
    {
        $registry = & HTML_QuickForm_RuleRegistry :: singleton();
        $rulenr = '-1';
        foreach ($this->_rules as $target => $rules)
        {
            $rulenr ++;
            $submitValue = $value[$rulenr];
            foreach ($rules as $elementName => $rule)
            {
                $result = $registry->validate($rule['type'], $submitValue, $rule['format'], false);
                if (! $this->isElementRequired($target))
                {
                    if (! isset($submitValue) || '' == $submitValue)
                    {
                        continue 2;
                    }
                }

                if (! $result || (! empty($rule['howmany']) && $rule['howmany'] > (int) $result))
                {

                    if (isset($rule['group']))
                    {

                        $this->_errors[$rule['group']] = $rule['message'];
                    }
                    else
                    {
                        $this->_errors[$target] = $rule['message'];
                    }
                }
            }
        }
        return (0 == count($this->_errors));
    }

    /**
     * Adds a warning message to the form.
     *
     * @param string $label The label for the error message
     * @param string $message The actual error message
     */
    function add_warning_message($name, $label, $message, $no_margin = false)
    {
        $html = '<div id="' . $name . '" class="row"><div class="formwa' . ($no_margin ? ' formwa_no_margin' : '') . '">';
        if ($label)
        {
            $html .= '<b>' . $label . '</b><br />';
        }
        $html .= $message . '</div></div>';
        $this->addElement('html', $html);
    }

    /**
     * Adds an error message to the form.
     *
     * @param string $label The label for the error message
     * @param string $message The actual error message
     */
    function add_error_message($name, $label, $message, $no_margin = false)
    {
        $html = '<div id="' . $name . '" class="row"><div class="forme' . ($no_margin ? ' forme_no_margin' : '') . '">';
        if ($label)
        {
            $html .= '<b>' . $label . '</b><br />';
        }
        $html .= $message . '</div></div>';
        $this->addElement('html', $html);
    }

    /**
     * Adds an error message to the form.
     *
     * @param string $label The label for the error message
     * @param string $message The actual error message
     */
    function add_information_message($name, $label, $message, $no_margin = false)
    {
        $html = '<div id="' . $name . '" class="row"><div class="formc' . ($no_margin ? ' formc_no_margin' : '') . '">';
        if ($label)
        {
            $html .= '<b>' . $label . '</b><br />';
        }
        $html .= $message . '</div></div>';
        $this->addElement('html', $html);
    }

    public function parse_checkbox_value($value = null)
    {
        if (isset($value) && $value == 1)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    /**
     * Adds javascript code to hide a certain element.
     */
    public function add_element_hider($type, $extra = null)
    {
        $html = array();
        if ($type == 'script_block')
        {
            $html[] = '<script type="text/javascript">';
            $html[] = 'function showElement(item)';
            $html[] = '{';
            $html[] = '	if (document.getElementById(item).style.display == \'block\')';
            $html[] = '  {';
            $html[] = '		document.getElementById(item).style.display = \'none\';';
            $html[] = '  }';
            $html[] = '	else';
            $html[] = '  {';
            $html[] = '		document.getElementById(item).style.display = \'block\';';
            $html[] = '		document.getElementById(item).value = \'Version comments here ...\';';
            $html[] = '	}';
            $html[] = '}';
            $html[] = '</script>';
        }
        elseif ($type == 'script_radio')
        {
            $html[] = '<script type="text/javascript">';
            $html[] = 'function showRadio(type, item)';
            $html[] = '{';
            $html[] = '	if (type == \'A\')';
            $html[] = '	{';
            $html[] = '		for (var j = item; j >= 0; j--)';
            $html[] = '		{';
            $html[] = '			var it = type + j;';
            $html[] = '			if (document.getElementById(it).style.visibility == \'hidden\')';
            $html[] = '			{';
            $html[] = '				document.getElementById(it).style.visibility = \'visible\';';
            $html[] = '			};';
            $html[] = '		}';
            $html[] = '		for (var j = item; j < ' . $extra->get_version_count() . '; j++)';
            $html[] = '		{';
            $html[] = '			var it = type + j;';
            $html[] = '			if (document.getElementById(it).style.visibility == \'visible\')';
            $html[] = '			{';
            $html[] = '				document.getElementById(it).style.visibility = \'hidden\';';
            $html[] = '			};';
            $html[] = '		}';
            $html[] = '	}';
            $html[] = '	else if (type == \'B\')';
            $html[] = '	{';
            $html[] = '		item++;';
            $html[] = '		for (var j = item; j >= 0; j--)';
            $html[] = '		{';
            $html[] = '			var it = type + j;';
            $html[] = '			if (document.getElementById(it).style.visibility == \'visible\')';
            $html[] = '			{';
            $html[] = '				document.getElementById(it).style.visibility = \'hidden\';';
            $html[] = '			};';
            $html[] = '		}';
            $html[] = '		for (var j = item; j <= ' . $extra->get_version_count() . '; j++)';
            $html[] = '		{';
            $html[] = '			var it = type + j;';
            $html[] = '			if (document.getElementById(it).style.visibility == \'hidden\')';
            $html[] = '			{';
            $html[] = '				document.getElementById(it).style.visibility = \'visible\';';
            $html[] = '			};';
            $html[] = '		}';
            $html[] = '	}';
            $html[] = '}';
            $html[] = '</script>';
        }
        elseif ($type == 'begin')
        {
            $html[] = '<div id="' . $extra . '" style="display: none;">';
        }
        elseif ($type == 'end')
        {
            $html[] = '</div>';
        }

        if (isset($html))
        {
            $this->addElement('html', implode(PHP_EOL, $html));
        }
    }

    /**
     * Returns the HTML representation of this form.
     */
    public function toHtml()
    {
        $error = false;

        foreach ($this->_elements as $index => $element)
        {
            if (! is_null(parent :: getElementError($element->getName())))
            {
                $error = true;
                break;
            }
        }

        $return_value = '';

        if ($this->no_errors)
        {
            $renderer = $this->defaultRenderer();
            $element_template = <<<EOT
	<div class="row">
		<div class="label">
			<!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
		</div>
		<div class="formw">
			<!-- BEGIN error --><!-- END error -->	{element}
		</div>
	</div>

EOT;
            $renderer->setElementTemplate($element_template);
        }
        elseif ($error)
        {
            $return_value .= Display :: error_message(Translation :: get('FormHasErrorsPleaseComplete'), true);
        }

        $return_value .= parent :: toHtml();
        // Add the div which will hold the progress bar

        if ($this->with_progress_bar)
        {
            $return_value .= '<div id="dynamic_div" style="display:block; margin-left:40%; margin-top:10px;"></div>';
        }

        return $return_value;
    }

    /**
     * Formats an multiple dimension array to a single dimension array to support default values in the quickform
     * library because quickform produces arrays when an array is used in the name, but quickform does not accept arrays
     * for the default values, instead the inner arrays are converted as strings
     *
     * @param string[] $array
     *
     * @return string
     */
    protected function multi_dimensional_array_to_single_dimensional_array($array, $level = 0)
    {
        $single_dimension_array = array();

        foreach ($array as $key => $element)
        {
            $key = ($level == 0) ? $key : '[' . $key . ']';

            if (is_array($element))
            {
                $single_array = $this->multi_dimensional_array_to_single_dimensional_array($element, $level + 1);
                foreach ($single_array as $child_key => $child_element)
                {
                    $single_dimension_array[$key . $child_key] = $child_element;
                }
            }
            else
            {
                $element_string = $element;
                $single_dimension_array[$key] = $element_string;
            }
        }

        return $single_dimension_array;
    }

    function &createGroup($elements, $name = null, $groupLabel = '', $separator = null, $appendName = true)
    {
        static $anonGroups = 1;

        if (0 == strlen($name))
        {
            $name = 'qf_group_' . $anonGroups ++;
            $appendName = false;
        }
        $group = & $this->createElement('group', $name, $groupLabel, $elements, $separator, $appendName);
        return $group;
    }

    public function addSaveResetButtons()
    {
        $buttons = array();

        $buttons[] = $this->createElement(
            'style_submit_button',
            'submit',
            Translation :: get('Save', null, Utilities :: COMMON_LIBRARIES),
            array('class' => 'positive'));

        $buttons[] = $this->createElement(
            'style_reset_button',
            'reset',
            Translation :: get('Reset', null, Utilities :: COMMON_LIBRARIES),
            array('class' => 'normal empty'));

        $this->addGroup($buttons, 'buttons', null, '&nbsp;', false);
    }
}
