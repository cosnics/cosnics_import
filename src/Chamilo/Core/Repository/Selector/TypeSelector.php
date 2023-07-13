<?php
namespace Chamilo\Core\Repository\Selector;

use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;
use Chamilo\Libraries\Translation\Translation;

/**
 * A collection of TypeSelectorCategory instances in a TypeSelector
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class TypeSelector
{
    public const PARAM_SELECTION = 'type_selection';

    /**
     * @var TypeSelectorItemInterface[]
     */
    protected $typeSelectorItems = [];

    /**
     * @var TypeSelectorCategory[]
     */
    private $categories;

    /**
     * @param TypeSelectorCategory[] $categories
     */
    public function __construct($categories = [])
    {
        $this->categories = $categories;
    }

    /**
     * @param TypeSelectorCategory $category
     */
    public function add_category(TypeSelectorCategory $category)
    {
        $this->categories[$category->getType()] = $category;
        $this->typeSelectorItems[] = $category;
    }

    /**
     * @param TypeSelectorOption $option
     */
    public function add_option(TypeSelectorOption $option)
    {
        $this->typeSelectorItems[] = $option;
    }

    /**
     * Convert the TypeSelector to an array type tree
     *
     * @return string[]
     */
    public function as_tree()
    {
        $type_options = [];
        $type_options[] = '-- ' . Translation::get('SelectAContentObjectType') . ' --';

        $prefix = (count($this->categories) > 1 ? '&mdash; ' : '');

        foreach ($this->categories as $category)
        {
            if (count($this->categories) > 1)
            {
                $type_options[$category->getType()] = $category->get_name();
            }

            foreach ($category->get_options() as $option)
            {
                $type_options[$option->get_template_registration_id()] = $prefix . $option->get_name();
            }
        }

        return $type_options;
    }

    public function category_type_exists($category_type)
    {
        return in_array($category_type, array_keys($this->categories));
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->typeSelectorItems);
    }

    public function count_options()
    {
        $total = 0;

        foreach ($this->typeSelectorItems as $typeSelectorItem)
        {
            if ($typeSelectorItem instanceof TypeSelectorCategory)
            {
                $total += $typeSelectorItem->count();
            }
            else
            {
                $total ++;
            }
        }

        return $total;
    }

    /**
     * Returns all the type selector options as a flat list (even those nested within categories)
     *
     * @return TypeSelectorOption[]
     */
    public function getAllTypeSelectorOptions()
    {
        $typeSelectorOptions = [];

        foreach ($this->typeSelectorItems as $typeSelectorItem)
        {
            if ($typeSelectorItem instanceof TypeSelectorOption)
            {
                $typeSelectorOptions[] = $typeSelectorItem;
            }
            elseif ($typeSelectorItem instanceof TypeSelectorCategory)
            {
                foreach ($typeSelectorItem->get_options() as $typeSelectorOption)
                {
                    $typeSelectorOptions[] = $typeSelectorOption;
                }
            }
        }

        return $typeSelectorOptions;
    }

    /**
     * @return TypeSelectorItemInterface[]
     */
    public function getTypeSelectorItems()
    {
        return $this->typeSelectorItems;
    }

    /**
     * @return \Chamilo\Core\Repository\Selector\TypeSelectorCategory[]
     */
    public function get_categories()
    {
        return $this->categories;
    }

    /**
     * @param string $type
     *
     * @return TypeSelectorCategory
     * @throws ObjectNotExistException
     */
    public function get_category_by_type($type)
    {
        foreach ($this->get_categories() as $category)
        {
            if ($category->getType() == $type)
            {
                return $category;
            }
        }

        throw new ObjectNotExistException(Translation::get('TypeSelectorCategory'));
    }

    /**
     * @return int[]
     */
    public function get_unique_content_object_template_ids()
    {
        $types = [];

        foreach ($this->typeSelectorItems as $typeSelectorItem)
        {
            if ($typeSelectorItem instanceof TypeSelectorCategory)
            {
                $types = array_merge($types, $typeSelectorItem->get_unique_content_object_template_ids());
            }
            elseif ($typeSelectorItem instanceof TypeSelectorOption)
            {
                if (!in_array($typeSelectorItem->get_template_registration_id(), $types))
                {
                    $types[] = $typeSelectorItem->get_template_registration_id();
                }
            }
        }

        return $types;
    }

    /**
     * Removes an item from the type selector by a given index
     *
     * @param int $index
     */
    public function removeTypeSelectorItemByIndex($index)
    {
        unset($this->typeSelectorItems[$index]);
    }

    /**
     * @param TypeSelectorCategory $categories []
     */
    public function set_categories($categories)
    {
        $this->categories = $categories;
    }

    /**
     * Sort the TypeSelectorCategory instances by name
     */
    public function sort()
    {
        usort(
            $this->typeSelectorItems, function ($itemA, $itemB) {
            return strcmp($itemA->get_name(), $itemB->get_name());
        }
        );

        foreach ($this->categories as $category)
        {
            $category->sort();
        }
    }
}