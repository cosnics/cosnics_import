<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Home;

use Chamilo\Application\Weblcms\CourseType\Storage\DataManager as CourseTypeDataManager;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseTypeUserCategory;
use Chamilo\Application\Weblcms\Storage\DataClass\CourseUserCategory;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\SubselectCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 * Connector for the weblcms blocks configurations
 * 
 * @author Sven Vanpoucke
 */
class Connector
{

    /**
     * **************************************************************************************************************
     * Filtered course list block configuration connectors *
     * **************************************************************************************************************
     */
    
    /**
     * Returns the active course types for which a user can choose
     * 
     * @return array
     */
    public function get_course_types()
    {
        $available_course_types = [];
        
        $available_course_types[json_encode(array(0))] = Translation::get('NoCourseType');
        
        $available_course_types = array_merge(
            $available_course_types, 
            self::get_user_course_categories_for_course_types_as_array(0, Translation::get('NoCourseType')));
        
        $course_types = CourseTypeDataManager::retrieve_active_course_types();
        foreach($course_types as $course_type)
        {
            $available_course_types[json_encode(array($course_type->get_id()))] = $course_type->get_title();
            
            $available_course_types = array_merge(
                $available_course_types, 
                self::get_user_course_categories_for_course_types_as_array(
                    $course_type->get_id(), 
                    $course_type->get_title()));
        }
        
        return $available_course_types;
    }

    function get_all_course_types()
    {
        $course_types_result = CourseTypeDataManager::retrieve_active_course_types();
        
        foreach($course_types_result as $course_type)
        {
            $course_types[$course_type->get_id()] = $course_type->get_title();
        }
        return $course_types;
    }

    /**
     * **************************************************************************************************************
     * Filtered course list block configuration helper functionality *
     * **************************************************************************************************************
     */
    
    /**
     * Generates a valid array for the course type list with the available user course categories for that course type
     * for the given course type id and title
     * 
     * @param int $course_type_id
     * @param string $course_type_title
     *
     * @return string[int]
     */
    private function get_user_course_categories_for_course_types_as_array($course_type_id, $course_type_title)
    {
        $user_course_categories = [];
        
        $categories = self::get_user_course_categories_for_course_type($course_type_id);
        foreach($categories as $user_course_category)
        {
            $user_course_categories[json_encode(array($course_type_id, $user_course_category->get_id()))] = $course_type_title .
                 ' - ' . $user_course_category->get_title() . '';
        }
        
        return $user_course_categories;
    }

    /**
     * Returns the available user course categories for a given course type id
     * 
     * @param int $course_type_id
     *
     * @return \Doctrine\Common\Collections\ArrayCollection<CourseUserCategory>
     */
    private function get_user_course_categories_for_course_type($course_type_id)
    {
        $subconditions = [];
        
        $subconditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                CourseTypeUserCategory::class, 
                CourseTypeUserCategory::PROPERTY_USER_ID), 
            new StaticConditionVariable(Session::get_user_id()));
        
        $subconditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                CourseTypeUserCategory::class, 
                CourseTypeUserCategory::PROPERTY_COURSE_TYPE_ID), 
            new StaticConditionVariable($course_type_id));
        
        $subcondition = new AndCondition($subconditions);
        
        $condition = new SubselectCondition(
            new PropertyConditionVariable(CourseUserCategory::class, CourseUserCategory::PROPERTY_ID), 
            new PropertyConditionVariable(
                CourseTypeUserCategory::class, 
                CourseTypeUserCategory::PROPERTY_COURSE_USER_CATEGORY_ID),
            $subcondition);
        
        return DataManager::retrieves(
            CourseUserCategory::class, 
            new DataClassRetrievesParameters($condition));
    }
}
