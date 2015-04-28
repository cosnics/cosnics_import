<?php
namespace Chamilo\Core\Repository\ContentObject\Survey\Page\Question\Order\Implementation\Export;

use Chamilo\Core\Repository\ContentObject\Survey\Page\Question\Order\Implementation\ExportImplementation;

/**
 *
 * @package repository.content_object.survey_order_question
 * @author Eduard Vossen
 * @author Magali Gillard
 * @author Hans De Bisschop
 */
class CpoExportImplementation extends ExportImplementation
{
    const SURVEY_MULTIPLE_CHOICE_QUESTION_EXPORT = 'survey_order_question_export';
    const OPTIONS_NODE = 'options';
    const OPTION_NODE = 'option';
    const MATCHES_NODE = 'matches';
    const MATCH_NODE = 'match';
}
?>