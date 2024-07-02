<?php
namespace Chamilo\Core\User\Integration\Chamilo\Core\Reporting\Block;

use Chamilo\Core\Reporting\ReportingBlock;
use Chamilo\Core\User\Manager;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Parameters\RetrievesParameters;

abstract class Block extends ReportingBlock
{

    /**
     * Generates an array from a tracker Currently only supports 1 serie
     *
     * @param Tracker $tracker
     *
     * @return array
     * @todo support multiple series
     */
    public static function array_from_tracker($tracker, $condition = null, $description = null)
    {
        $c = 0;
        $array = [];

        $trackerdata = DataManager::retrieves(get_class($tracker), new RetrievesParameters(condition: $condition));

        foreach ($trackerdata as $key => $value)
        {
            $arr[$value->get_name()] = $value->get_value();
        }

        return $arr;
    }

    public static function getDateArray($data, $format)
    {
        $login_dates = [];

        foreach ($data as $login_date)
        {
            $date = date($format, $login_date->get_date());

            if (array_key_exists($date, $login_dates))
            {
                $login_dates[$date] ++;
            }
            else
            {
                $login_dates[$date] = 1;
            }
        }

        return $login_dates;
    }

    public function get_user_id()
    {
        return $this->get_parent()->get_parameter(Manager::PARAM_USER_USER_ID);
    }
}
