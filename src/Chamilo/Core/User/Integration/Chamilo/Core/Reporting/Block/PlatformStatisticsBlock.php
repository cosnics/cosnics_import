<?php
namespace Chamilo\Core\User\Integration\Chamilo\Core\Reporting\Block;

use Chamilo\Core\Reporting\ReportingData;
use Chamilo\Core\Reporting\Viewer\Rendition\Block\Type\Html;
use Chamilo\Core\User\Integration\Chamilo\Core\Tracking\Storage\DataClass\LoginLogout;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\DataClassParameters;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\DatetimeUtilities;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 *
 * @package Chamilo\Core\User\Integration\Chamilo\Core\Reporting\Block
 */
class PlatformStatisticsBlock extends Block
{

    public function count_data()
    {
        $reporting_data = new ReportingData();
        $uid = $this->get_user_id();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(LoginLogout::class, LoginLogout::PROPERTY_USER_ID),
            new StaticConditionVariable($uid)
        );
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(LoginLogout::class, LoginLogout::PROPERTY_TYPE),
            new StaticConditionVariable('login')
        );
        $condition = new AndCondition($conditions);

        $trackerdata = DataManager::retrieves(LoginLogout::class, new DataClassParameters(condition: $condition));

        $firstconnection = null;
        foreach ($trackerdata as $key => $value)
        {
            if (!isset($firstconnection))
            {
                $firstconnection = $value->get_date();
                $lastconnection = $value->get_date();
            }
            else
            {
                if (($value->get_date() - $firstconnection) < 0)
                {
                    $firstconnection = $value->get_date();
                }
                else
                {
                    if (($value->get_date() - $lastconnection) > 0)
                    {
                        $lastconnection = $value->get_date();
                    }
                }
            }
        }

        $arr[Translation::get('FirstConnection')][] = DatetimeUtilities::getInstance()->formatLocaleDate(
            Translation::get('DateFormatShort', null, StringUtilities::LIBRARIES) . ', ' .
            Translation::get('TimeNoSecFormat', null, StringUtilities::LIBRARIES), $firstconnection
        );
        $arr[Translation::get('LastConnection')][] = DatetimeUtilities::getInstance()->formatLocaleDate(
            Translation::get('DateFormatShort', null, StringUtilities::LIBRARIES) . ', ' .
            Translation::get('TimeNoSecFormat', null, StringUtilities::LIBRARIES), $lastconnection
        );

        $keys = array_keys($arr);
        $reporting_data->set_categories($keys);
        $reporting_data->set_rows([Translation::get('Date')]);

        foreach ($keys as $key => $name)
        {
            $reporting_data->add_data_category_row($name, Translation::get('Date'), $arr[$name]);
        }

        return $reporting_data;
    }

    public function get_views()
    {
        return [Html::VIEW_TABLE, Html::VIEW_CSV, Html::VIEW_XLSX, Html::VIEW_XML];
    }

    /**
     * Checks if a given start date is greater than a given end date
     *
     * @param $start_date <type>
     * @param $end_date <type>
     *
     * @return <type>
     */
    public static function greaterDate($start_date, $end_date)
    {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        if ($start - $end > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function retrieve_data()
    {
        return $this->count_data();
    }
}
