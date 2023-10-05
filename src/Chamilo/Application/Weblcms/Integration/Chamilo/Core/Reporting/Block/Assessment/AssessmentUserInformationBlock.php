<?php
namespace Chamilo\Application\Weblcms\Integration\Chamilo\Core\Reporting\Block\Assessment;

use Chamilo\Application\Weblcms\Integration\Chamilo\Core\Tracking\Storage\DataClass\AssessmentAttempt;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataManager;
use Chamilo\Core\Reporting\ReportingData;
use Chamilo\Core\Reporting\Viewer\Rendition\Block\Type\Html;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;

/**
 * @package application.weblcms.php.reporting.blocks Reporting block displaying information about the assessment and the
 *          user
 * @author  Joris Willems <joris.willems@gmail.com>
 * @author  Alexander Van Paemel
 */
class AssessmentUserInformationBlock extends AssessmentUsersBlock
{

    public function count_data()
    {
        $reporting_data = new ReportingData();

        $categories = $this->get_assessment_information_headers();
        $categories = array_merge($categories, $this->get_user_reporting_info_headers());

        $reporting_data->set_categories($categories);

        $user_id = $this->getRequest()->query->get('users');
        $user = DataManager::retrieve_by_id(User::class, $user_id);

        $publication = DataManager::retrieve_by_id(
            ContentObjectPublication::class, $this->getPublicationId()
        );

        $this->add_category_from_array(
            Translation::get('Details'), $this->get_assessment_information($publication), $reporting_data
        );

        $user_attempts = $this->calculate_user_attempt_summary_data();

        $reporting_info = $this->get_user_reporting_info($user, $user_attempts[$user->get_id()]);
        $this->add_category_from_array(Translation::get('Details'), $reporting_info, $reporting_data);

        $reporting_data->set_rows([Translation::get('Details')]);

        return $reporting_data;
    }

    protected function get_assessment_attempts_condition()
    {
        $conditions = [];

        $conditions[] = parent::get_assessment_attempts_condition();

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(AssessmentAttempt::class, AssessmentAttempt::PROPERTY_USER_ID),
            new StaticConditionVariable($this->get_user_id())
        );

        return new AndCondition($conditions);
    }

    public function get_views()
    {
        return [Html::VIEW_TABLE];
    }

    public function retrieve_data()
    {
        return $this->count_data();
    }
}
