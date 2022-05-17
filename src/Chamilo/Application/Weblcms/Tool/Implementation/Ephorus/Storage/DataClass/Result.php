<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Ephorus\Storage\DataClass;

use Chamilo\Application\Weblcms\Tool\Implementation\Ephorus\Storage\DataManager;
use Chamilo\Libraries\Translation\Translation;

/**
 * This class defines a result for a request for the ephorus tool
 *
 * @package application\weblcms\tool\ephorus;
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class Result extends EphorusDataClass
{
    const PROPERTY_DIFF = 'diff';
    const PROPERTY_MIMETYPE = 'mimetype';
    const PROPERTY_ORIGINAL_GUID = 'original_guid';
    const PROPERTY_PERCENTAGE = 'percentage';
    const PROPERTY_REQUEST_ID = 'request_id';
    const PROPERTY_STUDENT_NAME = 'student_name';
    const PROPERTY_STUDENT_NUMBER = 'student_number';
    const PROPERTY_TYPE = 'type';
    const PROPERTY_URL = 'url';

    /**
     * Checks this dataclass integrity before saving it to the database
     *
     * @return boolean @codeCoverageIgnore
     */
    protected function check_before_save()
    {
        $this->is_valid_request();

        return parent::check_before_save();
    }

    /**
     * Returns the datamanager
     *
     * @return DataManager @codeCoverageIgnore
     */
    public function get_data_manager()
    {
        return DataManager::getInstance();
    }

    /**
     * Returns the default properties of this dataclass
     *
     * @return string[] - The property types.
     */
    public static function get_default_property_names($default_property_names = [])
    {
        $default_property_names[] = self::PROPERTY_REQUEST_ID;
        $default_property_names[] = self::PROPERTY_URL;
        $default_property_names[] = self::PROPERTY_MIMETYPE;
        $default_property_names[] = self::PROPERTY_TYPE;
        $default_property_names[] = self::PROPERTY_PERCENTAGE;
        $default_property_names[] = self::PROPERTY_ORIGINAL_GUID;
        $default_property_names[] = self::PROPERTY_STUDENT_NUMBER;
        $default_property_names[] = self::PROPERTY_STUDENT_NAME;
        $default_property_names[] = self::PROPERTY_DIFF;

        return parent::get_default_property_names($default_property_names);
    }

    /**
     * **************************************************************************************************************
     * Validation functionality *
     * **************************************************************************************************************
     */

    /**
     * Returns the diff property of this object
     *
     * @return int
     */
    public function get_diff()
    {
        return $this->get_default_property(self::PROPERTY_DIFF);
    }

    /**
     * **************************************************************************************************************
     * Getters & Setters *
     * **************************************************************************************************************
     */

    /**
     * Returns the mimetype property of this object
     *
     * @return int
     */
    public function get_mimetype()
    {
        return $this->get_default_property(self::PROPERTY_MIMETYPE);
    }

    /**
     * Returns the original_guid property of this object
     *
     * @return int
     */
    public function get_original_guid()
    {
        return $this->get_default_property(self::PROPERTY_ORIGINAL_GUID);
    }

    /**
     * Returns the percentage property of this object
     *
     * @return int
     */
    public function get_percentage()
    {
        return $this->get_default_property(self::PROPERTY_PERCENTAGE);
    }

    /**
     * Returns the request_id property of this object
     *
     * @return string
     */
    public function get_request_id()
    {
        return $this->get_default_property(self::PROPERTY_REQUEST_ID);
    }

    /**
     * Returns the student_name property of this object
     *
     * @return int
     */
    public function get_student_name()
    {
        return $this->get_default_property(self::PROPERTY_STUDENT_NAME);
    }

    /**
     * Returns the student_number property of this object
     *
     * @return int
     */
    public function get_student_number()
    {
        return $this->get_default_property(self::PROPERTY_STUDENT_NUMBER);
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'weblcms_ephorus_result';
    }

    /**
     * Returns the type property of this object
     *
     * @return int
     */
    public function get_type()
    {
        return $this->get_default_property(self::PROPERTY_TYPE);
    }

    /**
     * Returns the url property of this object
     *
     * @return string
     */
    public function get_url()
    {
        return $this->get_default_property(self::PROPERTY_URL);
    }

    /**
     * Checks if the request for this result is valid
     *
     * @return boolean
     */
    protected function is_valid_request()
    {
        $string_utilities = $this->get_string_utilities_class();
        if ($string_utilities::getInstance()->isNullOrEmpty($this->get_request_id()))
        {
            $this->add_error(Translation::get('RequestIdIsRequired'));

            return false;
        }

        $data_manager = $this->get_data_manager_class();
        $request = $data_manager::retrieve_by_id(Request::class, (int) $this->get_request_id());
        if (!$request)
        {
            $this->add_error(Translation::get('RequestNotFound'));

            return false;
        }

        return true;
    }

    /**
     * Sets the diff property of this object
     *
     * @param $diff int
     */
    public function set_diff($diff)
    {
        $this->set_default_property(self::PROPERTY_DIFF, $diff);
    }

    /**
     * Sets the mimetype property of this object
     *
     * @param $mimetype int
     */
    public function set_mimetype($mimetype)
    {
        $this->set_default_property(self::PROPERTY_MIMETYPE, $mimetype);
    }

    /**
     * Sets the original_guid property of this object
     *
     * @param $original_guid int
     */
    public function set_original_guid($original_guid)
    {
        $this->set_default_property(self::PROPERTY_ORIGINAL_GUID, $original_guid);
    }

    /**
     * Sets the percentage property of this object
     *
     * @param $percentage int
     */
    public function set_percentage($percentage)
    {
        $this->set_default_property(self::PROPERTY_PERCENTAGE, $percentage);
    }

    /**
     * Sets the request_id property of this object
     *
     * @param $request_id string
     */
    public function set_request_id($request_id)
    {
        $this->set_default_property(self::PROPERTY_REQUEST_ID, $request_id);
    }

    /**
     * Sets the student_name property of this object
     *
     * @param $student_name int
     */
    public function set_student_name($student_name)
    {
        $this->set_default_property(self::PROPERTY_STUDENT_NAME, $student_name);
    }

    /**
     * Sets the student_number property of this object
     *
     * @param $student_number int
     */
    public function set_student_number($student_number)
    {
        $this->set_default_property(self::PROPERTY_STUDENT_NUMBER, $student_number);
    }

    /**
     * Sets the type property of this object
     *
     * @param $type int
     */
    public function set_type($type)
    {
        $this->set_default_property(self::PROPERTY_TYPE, $type);
    }

    /**
     * Sets the url property of this object
     *
     * @param $url string
     */
    public function set_url($url)
    {
        $this->set_default_property(self::PROPERTY_URL, $url);
    }
}
