<?php
namespace Chamilo\Core\Repository\ContentObject\Task\Filter\Renderer;

use Chamilo\Core\Repository\ContentObject\Task\Filter\FilterData;
use Chamilo\Core\Repository\ContentObject\Task\Storage\DataClass\Task;
use Chamilo\Libraries\Platform\Translation;

/**
 * Render the parameters set via FilterData as HTML
 * 
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class HtmlFilterRenderer extends \Chamilo\Core\Repository\Filter\Renderer\HtmlFilterRenderer
{
    /*
     * (non-PHPdoc) @see \core\repository\filter\renderer\HtmlFilterRenderer::add_properties()
     */
    public function add_properties()
    {
        $filter_data = $this->get_filter_data();
        $html = array();
        
        $html[] = parent :: add_properties();
        
        // Frequency
        if ($filter_data->has_filter_property(FilterData :: FILTER_FREQUENCY))
        {
            $translation = Translation :: get(
                'RepeatSearchParameter', 
                array(
                    'REPEAT' => Task :: frequency_as_string(
                        $filter_data->get_filter_property(FilterData :: FILTER_FREQUENCY))));
            
            $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_FREQUENCY) . '">' .
                 $translation . '</div>';
        }
        
        // Task type
        if ($filter_data->has_filter_property(FilterData :: FILTER_CATEGORY))
        {
            $translation = Task :: category_as_string($filter_data->get_filter_property(FilterData :: FILTER_CATEGORY));
            
            $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_CATEGORY) . '">' .
                 $translation . '</div>';
        }
        
        // Priority
        if ($filter_data->has_filter_property(FilterData :: FILTER_PRIORITY))
        {
            $translation = Translation :: get(
                'TaskPrioritySearchParameter', 
                array(
                    'TASK_PRIORITY' => Task :: task_priority_as_string(
                        $filter_data->get_filter_property(FilterData :: FILTER_PRIORITY))));
            
            $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_PRIORITY) . '">' .
                 $translation . '</div>';
        }
        
        // Start date
        if ($filter_data->has_date(FilterData :: FILTER_START_DATE))
        {
            $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_START_DATE) . '">' . Translation :: get(
                'StartsBetween', 
                array(
                    'FROM' => $filter_data->get_start_date(FilterData :: FILTER_FROM_DATE), 
                    'TO' => $filter_data->get_start_date(FilterData :: FILTER_TO_DATE))) . '</div>';
        }
        else
        {
            if ($filter_data->get_creation_date(FilterData :: FILTER_FROM_DATE))
            {
                $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_START_DATE) .
                     '">' . Translation :: get(
                        'StartsAfter', 
                        array('FROM' => $filter_data->get_start_date(FilterData :: FILTER_FROM_DATE))) . '</div>';
            }
            elseif ($filter_data->get_start_date(FilterData :: FILTER_TO_DATE))
            {
                $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_START_DATE) .
                     '">' . Translation :: get(
                        'StartsBefore', 
                        array('TO' => $filter_data->get_start_date(FilterData :: FILTER_TO_DATE))) . '</div>';
            }
        }
        
        // End date
        if ($filter_data->has_date(FilterData :: FILTER_DUE_DATE))
        {
            $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_DUE_DATE) . '">' . Translation :: get(
                'EndsBetween', 
                array(
                    'FROM' => $filter_data->get_due_date(FilterData :: FILTER_FROM_DATE), 
                    'TO' => $filter_data->get_due_date(FilterData :: FILTER_TO_DATE))) . '</div>';
        }
        else
        {
            if ($filter_data->get_due_date(FilterData :: FILTER_FROM_DATE))
            {
                $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_DUE_DATE) . '">' .
                     Translation :: get(
                        'EndsAfter', 
                        array('FROM' => $filter_data->get_modification_date(FilterData :: FILTER_FROM_DATE))) . '</div>';
            }
            elseif ($filter_data->get_due_date(FilterData :: FILTER_TO_DATE))
            {
                $html[] = '<div class="parameter" id="' . $this->get_parameter_name(FilterData :: FILTER_DUE_DATE) . '">' . Translation :: get(
                    'EndsBefore', 
                    array('TO' => $filter_data->get_due_date(FilterData :: FILTER_TO_DATE))) . '</div>';
            }
        }
        
        return implode("\n", $html);
    }
}