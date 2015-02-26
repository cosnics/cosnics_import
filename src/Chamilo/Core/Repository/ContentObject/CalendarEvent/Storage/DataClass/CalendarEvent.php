<?php
namespace Chamilo\Core\Repository\ContentObject\CalendarEvent\Storage\DataClass;

use Chamilo\Core\Repository\ContentObject\CalendarEvent\Implementation\Export\IcalExportImplementation;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Interfaces\AttachmentSupport;
use Chamilo\Libraries\Architecture\Interfaces\Includeable;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Translation;
use Sabre\VObject;

/**
 * $Id: calendar_event.class.php 200 2009-11-13 12:30:04Z kariboe $
 * 
 * @package repository.lib.content_object.calendar_event
 * @author Hans De Bisschop
 * @author Dieter De Neef
 */
/**
 * This class represents a calendar event
 */
class CalendarEvent extends ContentObject implements Versionable, AttachmentSupport, Includeable
{
    const CLASS_NAME = __CLASS__;

    public static function get_type_name()
    {
        return ClassnameUtilities :: getInstance()->getClassNameFromNamespace(self :: CLASS_NAME, true);
    }
    
    /**
     * The start date of the calendar event
     */
    const PROPERTY_START_DATE = 'start_date';
    /**
     * The end date of the calendar event
     */
    const PROPERTY_END_DATE = 'end_date';
    /**
     * Wheter the event is to be repeated and if so, when it should be repeated
     */
    const PROPERTY_FREQUENCY = 'frequency';
    /**
     * The end date of the repetition
     */
    const PROPERTY_UNTIL = 'until';
    const PROPERTY_FREQUENCY_COUNT = 'frequency_count';
    const PROPERTY_FREQUENCY_INTERVAL = 'frequency_interval';
    const PROPERTY_BYDAY = 'byday';
    const PROPERTY_BYMONTHDAY = 'bymonthday';
    const PROPERTY_BYMONTH = 'bymonth';
    
    /**
     * The different frequency types
     */
    const FREQUENCY_NONE = 0;
    const FREQUENCY_DAILY = 1;
    const FREQUENCY_WEEKLY = 2;
    const FREQUENCY_WEEKDAYS = 3;
    const FREQUENCY_BIWEEKLY = 4;
    const FREQUENCY_MONTHLY = 5;
    const FREQUENCY_YEARLY = 6;

    public static $days = array(1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU');

    /**
     * Gets the start date of this calendar event
     * 
     * @return int The start date
     */
    public function get_start_date()
    {
        return $this->get_additional_property(self :: PROPERTY_START_DATE);
    }

    /**
     * Sets the start date of this calendar event
     * 
     * @param int The start date
     */
    public function set_start_date($start_date)
    {
        return $this->set_additional_property(self :: PROPERTY_START_DATE, $start_date);
    }

    /**
     * Gets the end date of this calendar event
     * 
     * @return int The end date
     */
    public function get_end_date()
    {
        return $this->get_additional_property(self :: PROPERTY_END_DATE);
    }

    /**
     * Sets the end date of this calendar event
     * 
     * @param int The end date
     */
    public function set_end_date($end_date)
    {
        return $this->set_additional_property(self :: PROPERTY_END_DATE, $end_date);
    }

    /**
     * Gets the frequency of this calendar event
     * 
     * @return int The frequency
     */
    public function get_frequency()
    {
        return $this->get_additional_property(self :: PROPERTY_FREQUENCY);
    }

    /**
     * Sets the frequency of this calendar event
     * 
     * @param int The frequency
     */
    public function set_frequency($frequency)
    {
        return $this->set_additional_property(self :: PROPERTY_FREQUENCY, $frequency);
    }

    /**
     * Gets the end date of this calendar event repetition
     * 
     * @return int The repetition end date
     */
    public function get_until()
    {
        return $this->get_additional_property(self :: PROPERTY_UNTIL);
    }

    /**
     * Sets the end date of this calendar event repetition
     * 
     * @param int The repetition end date
     */
    public function set_until($until)
    {
        return $this->set_additional_property(self :: PROPERTY_UNTIL, $until);
    }

    /**
     * Returns whether or not the calendar event repeats itself
     * 
     * @return boolean
     */
    public function has_frequency()
    {
        $repeat = $this->get_frequency();
        return ($repeat != '0');
    }

    /**
     * Returns whether or not the calendar event repeats itself indefinately
     * 
     * @return boolean
     */
    public function frequency_is_indefinately()
    {
        $repeat_to = $this->get_until();
        return ($repeat_to == 0 || is_null($repeat_to));
    }

    /**
     * Return the frequency as a string
     */
    public function get_frequency_as_string()
    {
        return self :: frequency_as_string($this->get_frequency());
    }

    /**
     *
     * @return string
     */
    public static function frequency_as_string($frequency)
    {
        switch ($frequency)
        {
            case self :: FREQUENCY_DAILY :
                $string = Translation :: get('Daily');
                break;
            case self :: FREQUENCY_WEEKLY :
                $string = Translation :: get('Weekly');
                break;
            case self :: FREQUENCY_MONTHLY :
                $string = Translation :: get('Monthly');
                break;
            case self :: FREQUENCY_YEARLY :
                $string = Translation :: get('Yearly');
                break;
            case self :: FREQUENCY_WEEKDAYS :
                $string = Translation :: get('Weekdays');
                break;
            case self :: FREQUENCY_BIWEEKLY :
                $string = Translation :: get('Biweekly');
                break;
        }
        
        return $string;
    }

    public function get_frequency_count()
    {
        return $this->get_additional_property(self :: PROPERTY_FREQUENCY_COUNT);
    }

    public function set_frequency_count($frequency_count)
    {
        return $this->set_additional_property(self :: PROPERTY_FREQUENCY_COUNT, $frequency_count);
    }

    public function get_frequency_interval()
    {
        return $this->get_additional_property(self :: PROPERTY_FREQUENCY_INTERVAL);
    }

    public function set_frequency_interval($frequency_interval)
    {
        return $this->set_additional_property(self :: PROPERTY_FREQUENCY_INTERVAL, $frequency_interval);
    }

    public function get_byday()
    {
        return $this->get_additional_property(self :: PROPERTY_BYDAY);
    }

    public function set_byday($byday)
    {
        return $this->set_additional_property(self :: PROPERTY_BYDAY, $byday);
    }

    public function get_bymonthday()
    {
        return $this->get_additional_property(self :: PROPERTY_BYMONTHDAY);
    }

    public function set_bymonthday($bymonthday)
    {
        return $this->set_additional_property(self :: PROPERTY_BYMONTHDAY, $bymonthday);
    }

    public function get_bymonth()
    {
        return $this->get_additional_property(self :: PROPERTY_BYMONTH);
    }

    public function set_bymonth($bymonth)
    {
        return $this->set_additional_property(self :: PROPERTY_BYMONTH, $bymonth);
    }

    public function get_repeats($from_date = 0, $to_date = 0)
    {
        $vcalendar = new VObject\Component\VCalendar();
        
        $start_date_time = new \DateTime();
        $start_date_time->setTimestamp($this->get_start_date());
        
        $end_date_time = new \DateTime();
        $end_date_time->setTimestamp($this->get_end_date());
        
        $vevent = $vcalendar->add('VEVENT');
        
        $vevent->add('SUMMARY', $this->get_title());
        $vevent->add('DESCRIPTION', $this->get_description());
        $vevent->add('DTSTART', $start_date_time);
        $vevent->add('DTEND', $end_date_time);
        
        $rrules = IcalExportImplementation :: rrule($this);
        
        $bydays = array();
        
        foreach ($rrules['BYDAY'] as $byday)
        {
            $bydays[] = implode('', $byday);
        }
        
        $rrules['BYDAY'] = implode(',', $bydays);
        
        $vevent->add('RRULE', $rrules);
        $vevent->add('UID', uniqid());
        
        $from_date_time = new \DateTime();
        $from_date_time->setTimestamp($from_date);
        
        $to_date_time = new \DateTime();
        $to_date_time->setTimestamp($to_date);
        
        $vcalendar->expand($from_date_time, $to_date_time);
        
        return $vcalendar->VEVENT;
    }

    public static function get_frequency_options()
    {
        $options = array();
        
        $options[self :: FREQUENCY_DAILY] = Translation :: get('Daily');
        $options[self :: FREQUENCY_WEEKLY] = Translation :: get('Weekly');
        $options[self :: FREQUENCY_MONTHLY] = Translation :: get('Monthly');
        $options[self :: FREQUENCY_YEARLY] = Translation :: get('Yearly');
        $options[self :: FREQUENCY_WEEKDAYS] = Translation :: get('Weekdays');
        $options[self :: FREQUENCY_BIWEEKLY] = Translation :: get('BiWeekly');
        
        return $options;
    }

    public static function get_additional_property_names()
    {
        return array(
            self :: PROPERTY_START_DATE, 
            self :: PROPERTY_END_DATE, 
            self :: PROPERTY_UNTIL, 
            self :: PROPERTY_FREQUENCY, 
            self :: PROPERTY_FREQUENCY_COUNT, 
            self :: PROPERTY_FREQUENCY_INTERVAL, 
            self :: PROPERTY_BYDAY, 
            self :: PROPERTY_BYMONTH, 
            self :: PROPERTY_BYMONTHDAY);
    }

    public function get_icon_name($size = Theme :: ICON_SMALL)
    {
        if ($this->has_frequency())
        {
            return $size . '_repeat';
        }
        else
        {
            return $size;
        }
    }

    public function get_icon_image($size = Theme :: ICON_SMALL, $is_available = true)
    {
        return static :: icon_image(
            $this->context(), 
            $size, 
            $this->is_current() && $is_available, 
            $this->has_frequency());
    }

    public static function icon_image($context, $size = Theme :: ICON_SMALL, $is_current = true, $has_frequency = false)
    {
        if ($has_frequency)
        {
            $size = $size . '_repeat';
        }
        
        return parent :: icon_image($context, $size, $is_current);
    }

    public function get_type_string()
    {
        if ($this->has_frequency())
        {
            return Translation :: get('RepeatingCalendarEvent');
        }
        else
        {
            return parent :: get_type_string();
        }
    }

    public static function get_byday_options()
    {
        $translator = Translation :: get_instance();
        
        return $result = array(
            1 => $translator->get("Monday"), 
            2 => $translator->get("Tuesday"), 
            3 => $translator->get("Wednesday"), 
            4 => $translator->get("Thursday"), 
            5 => $translator->get("Friday"), 
            6 => $translator->get("Saturday"), 
            7 => $translator->get("Sunday"));
    }

    public static function get_bymonthday_options()
    {
        return array(
            1 => 1, 
            2 => 2, 
            3 => 3, 
            4 => 4, 
            5 => 5, 
            6 => 6, 
            7 => 7, 
            8 => 8, 
            9 => 9, 
            10 => 10, 
            11 => 11, 
            12 => 12, 
            13 => 13, 
            14 => 14, 
            15 => 15, 
            16 => 16, 
            17 => 17, 
            18 => 18, 
            19 => 19, 
            20 => 20, 
            21 => 21, 
            22 => 22, 
            23 => 23, 
            24 => 24, 
            25 => 25, 
            26 => 26, 
            27 => 27, 
            28 => 28, 
            29 => 29, 
            30 => 30, 
            31 => 31);
    }

    public static function get_bymonth_options()
    {
        $translator = Translation :: get_instance();
        
        return array(
            1 => $translator->get("January"), 
            2 => $translator->get("February"), 
            3 => $translator->get("March"), 
            4 => $translator->get("April"), 
            5 => $translator->get("May"), 
            6 => $translator->get("June"), 
            7 => $translator->get("Juli"), 
            8 => $translator->get("August"), 
            9 => $translator->get("September"), 
            10 => $translator->get("October"), 
            11 => $translator->get("November"), 
            12 => $translator->get("December"));
    }

    public static function get_bymonth_string($month)
    {
        $translation = self :: get_bymonth_options();
        return $translation[$month];
    }

    public static function get_byday_ical_format($rank, $day)
    {
        $format = array();
        if ($rank != 0)
        {
            $format[] = $rank;
        }
        
        $format[] = self :: get_day_ical_format($day);
        return implode('', $format);
    }

    public static function get_day_ical_format($day)
    {
        return self :: $days[$day];
    }

    public static function get_rank_options()
    {
        $ranks = array();
        $ranks[0] = Translation :: get('Every');
        $ranks[1] = Translation :: get('First');
        $ranks[2] = Translation :: get('Second');
        $ranks[3] = Translation :: get('Third');
        $ranks[4] = Translation :: get('Fourth');
        $ranks[5] = Translation :: get('Fifth');
        $ranks[- 1] = Translation :: get('Last');
        
        return $ranks;
    }

    public static function get_rank_string($rank)
    {
        $translation = self :: get_rank_options();
        return $translation[$rank];
    }

    public static function get_day_format($day)
    {
        $days = array_flip(self :: $days);
        return $days[$day];
    }

    public static function get_day_string($day_number)
    {
        if (! is_numeric($day_number))
        {
            $day_number = self :: get_day_format($day_number);
        }
        $translation = self :: get_byday_options();
        return $translation[$day_number];
    }

    public static function get_byday_parts($bydays)
    {
        $bydays = explode(',', $bydays);
        $parts = array();
        foreach ($bydays as $byday)
        {
            preg_match_all('/(-?[1-5]?)([A-Z]+)/', $byday, $byday_parts);
            $parts[] = array($byday_parts[1] == 0 ? 0 : $byday_parts[1][0], $byday_parts[2][0]);
        }
        
        return $parts;
    }
}
