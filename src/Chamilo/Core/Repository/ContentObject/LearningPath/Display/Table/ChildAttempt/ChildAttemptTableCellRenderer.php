<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Display\Table\ChildAttempt;

use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Attempt\LearningPathChildAttempt;
use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager;
use Chamilo\Core\Repository\ContentObject\LearningPath\Domain\LearningPathTreeNode;
use Chamilo\Libraries\Format\Structure\ProgressBarRenderer;
use Chamilo\Libraries\Format\Structure\Toolbar;
use Chamilo\Libraries\Format\Structure\ToolbarItem;
use Chamilo\Libraries\Format\Table\Column\TableColumn;
use Chamilo\Libraries\Format\Table\Interfaces\TableCellRendererActionsColumnSupport;
use Chamilo\Libraries\Format\Table\TableCellRenderer;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;

/**
 * Shows the progress of some tree nodes for a user in the learning path
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class ChildAttemptTableCellRenderer extends TableCellRenderer implements TableCellRendererActionsColumnSupport
{
    /**
     * Renders a single cell
     *
     * @param TableColumn $column
     * @param LearningPathChildAttempt $learningPathChildAttempt
     *
     * @return String
     */
    public function render_cell($column, $learningPathChildAttempt)
    {
        $translator = Translation::getInstance();

        switch ($column->get_name())
        {
            case 'last_start_time':
                return DatetimeUtilities::format_locale_date(null, $learningPathChildAttempt->get_start_time());
            case 'status':
                return $translator->getTranslation(
                    $learningPathChildAttempt->isFinished() ? 'Completed' : 'Incomplete'
                );
            case 'score':
                $progressBarRenderer = new ProgressBarRenderer();

                return $progressBarRenderer->render(
                    (int) $learningPathChildAttempt->get_score(), ProgressBarRenderer::MODE_SUCCESS
                );

            case 'time':
                return DatetimeUtilities::format_seconds_to_hours($learningPathChildAttempt->get_total_time());
        }

        return parent::render_cell($column, $learningPathChildAttempt);
    }

    /**
     * Define the unique identifier for the row needed for e.g.
     * checkboxes
     *
     * @param LearningPathTreeNode $learningPathTreeNode
     *
     * @return int
     */
    public function render_id_cell($learningPathTreeNode)
    {
        return $learningPathTreeNode->getId();
    }

    /**
     * Returns the actions toolbar
     *
     * @param LearningPathChildAttempt $learningPathChildAttempt
     *
     * @return String
     */
    public function get_actions($learningPathChildAttempt)
    {
        if ($this->get_component()->is_allowed_to_edit_attempt_data())
        {
            $toolbar = new Toolbar(Toolbar::TYPE_HORIZONTAL);

            $delete_url = $this->get_component()->get_url(
                array(
                    Manager::PARAM_ACTION => Manager::ACTION_ATTEMPT,
                    Manager::PARAM_CHILD_ID => $this->get_component()->getCurrentLearningPathTreeNode()->getId(),
                    Manager::PARAM_ITEM_ATTEMPT_ID => $learningPathChildAttempt->getId()
                )
            );

            $toolbar->add_item(
                new ToolbarItem(
                    Translation::get('DeleteAttempt'),
                    Theme::getInstance()->getCommonImagePath('Action/Delete'),
                    $delete_url,
                    ToolbarItem::DISPLAY_ICON,
                    true
                )
            );

            return $toolbar->render();
        }

        return null;
    }
}