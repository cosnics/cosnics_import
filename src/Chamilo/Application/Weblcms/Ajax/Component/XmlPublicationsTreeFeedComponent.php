<?php
namespace Chamilo\Application\Weblcms\Ajax\Component;

use Chamilo\Application\Weblcms\Ajax\Manager;
use Chamilo\Application\Weblcms\Course\Storage\DataClass\Course;
use Chamilo\Application\Weblcms\Course\Storage\DataManager as CourseDataManager;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublicationCategory;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\OrderBy;
use Chamilo\Libraries\Storage\Query\OrderProperty;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

class XmlPublicationsTreeFeedComponent extends Manager
{

    public function run()
    {
        $publications_tree = [];
        $course = null;
        $user = null;

        $category_id = $this->getRequest()->query->get('parent_id');

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_PARENT
            ), new StaticConditionVariable($category_id)
        );

        $categories_tree = DataManager::retrieves(
            ContentObjectPublicationCategory::class, new DataClassRetrievesParameters(
                condition: $condition, orderBy: new OrderBy([
                new OrderProperty(
                    new PropertyConditionVariable(
                        ContentObjectPublicationCategory::class,
                        ContentObjectPublicationCategory::PROPERTY_DISPLAY_ORDER
                    )
                )
            ])
            )
        );

        header('Content-Type: text/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL, '<tree>' . PHP_EOL;
        echo $this->dump_tree($categories_tree);
        echo '</tree>';
    }

    public function contains_results($objects)
    {
        if (count($objects))
        {
            return true;
        }

        return false;
    }

    public function dump_categories_tree($categories)
    {
        foreach ($categories as $category)
        {
            $has_children = $this->has_sub_categories($category->get_id()) ? 1 : 0;
            $class = $this->get_category_class($category);

            echo '<leaf id="' . $category->get_id() . '" classes="' . $class . '" has_children="';
            echo $has_children . '" title="' . htmlspecialchars($category->get_name()) . '" description="';
            echo htmlspecialchars($category->get_name()) . '"/>' . PHP_EOL;
        }
    }

    public function dump_tree($categories)
    {
        if ($this->contains_results($categories))
        {
            $this->dump_categories_tree($categories);
        }
    }

    public function get_category_class(ContentObjectPublicationCategory $category)
    {
        $course = CourseDataManager::retrieve_by_id(Course::class, $category->get_course());
        $user = $this->getUser();

        if ($category->get_visibility())
        {
            if (DataManager::tool_category_has_new_publications(
                $category->get_tool(), $user, $course, $category->get_id()
            ))
            {
                $glyph = new FontAwesomeGlyph('folder', ['fas-ci-new'], null, 'fas');

                return $glyph->getClassNamesString();
            }
            else
            {
                $glyph = new FontAwesomeGlyph('folder', [], null, 'fas');

                return $glyph->getClassNamesString();
            }
        }
        else
        {
            $glyph = new FontAwesomeGlyph('folder', ['text-muted'], null, 'fas');

            return $glyph->getClassNamesString();
        }
    }

    public function has_sub_categories($category_id)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_PARENT
            ), new StaticConditionVariable($category_id)
        );

        return DataManager::count(
                ContentObjectPublicationCategory::class, new DataClassCountParameters($condition)
            ) > 0;
    }
}
