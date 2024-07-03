<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\Admin;

use Chamilo\Application\Weblcms\Course\Storage\DataClass\Course;
use Chamilo\Application\Weblcms\Course\Storage\DataManager as CourseDataManager;
use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\CourseBlock;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Core\Reporting\ReportingData;
use Chamilo\Core\Reporting\Viewer\Rendition\Block\Type\Html;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Storage\StorageParameters;
use Chamilo\Libraries\Translation\Translation;

class CoursesPerCategoryBlock extends CourseBlock
{

    public function count_data()
    {
        $reporting_data = new ReportingData();
        $reporting_data->set_rows([Translation::get('count')]);

        $categories = DataManager::retrieve_course_categories_ordered_by_name();

        foreach ($categories as $category)
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(Course::class, Course::PROPERTY_CATEGORY_ID),
                new StaticConditionVariable($category->get_id())
            );

            $reporting_data->add_category($category->get_name());
            $reporting_data->add_data_category_row(
                $category->get_name(), Translation::get('count'),
                CourseDataManager::count(Course::class, new StorageParameters(condition: $condition))
            );
        }

        return $reporting_data;
    }

    public function get_views()
    {
        return [
            Html::VIEW_TABLE,
            Html::VIEW_PIE
        ];
    }

    public function retrieve_data()
    {
        return $this->count_data();
    }
}
