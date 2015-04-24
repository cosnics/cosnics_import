<?php
namespace Chamilo\Libraries\Format;

/**
 *
 * @package Chamilo\Libraries\Format
 * @author Roan Embrechts
 * @author Tim De Pauw
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class Display
{
    const MESSAGE_TYPE_CONFIRM = 'confirm';
    const MESSAGE_TYPE_NORMAL = 'normal';
    const MESSAGE_TYPE_WARNING = 'warning';
    const MESSAGE_TYPE_ERROR = 'error';
    const MESSAGE_TYPE_FATAL = 'fatal';

    /**
     *
     * @param string $message
     * @return string
     */
    public static function normal_message($message)
    {
        return self :: message(self :: MESSAGE_TYPE_NORMAL, $message);
    }

    /**
     *
     * @param string $message
     * @return string
     */
    public static function error_message($message)
    {
        return self :: message(self :: MESSAGE_TYPE_ERROR, $message);
    }

    /**
     *
     * @param string $message
     * @return string
     */
    public static function warning_message($message)
    {
        return self :: message(self :: MESSAGE_TYPE_WARNING, $message);
    }

    /**
     *
     * @param string $type
     * @param string $message
     * @return string
     */
    public static function message($type = self :: MESSAGE_TYPE_NORMAL, $message)
    {
        $html = array();

        $html[] = '<div class="' . $type . '-message">';
        $html[] = $message;
        $html[] = '<div class="close_message" id="closeMessage"></div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }
}
