<?php
namespace Chamilo\Application\Calendar;

use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Calendar\Renderer\Type\ViewRenderer;
use Chamilo\Libraries\Platform\Configuration\LocalSetting;
use DateTime;
use Mobile_Detect;

/**
 *
 * @package application\calendar
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
abstract class Manager extends Application
{
    // Parameters
    public const ACTION_AVAILABILITY = 'Availability';
    public const ACTION_BROWSE = 'Browser';
    public const ACTION_ICAL = 'ICal';
    public const ACTION_PRINT = 'Printer';

    public const DEFAULT_ACTION = self::ACTION_BROWSE;

    public const PARAM_DOWNLOAD = 'download';
    public const PARAM_TIME = 'time';
    public const PARAM_VIEW = 'view';

    /**
     *
     * @var int
     */
    private $currentTime;

    /**
     *
     * @var \Chamilo\Libraries\Format\Tabs\Link\LinkTabsRenderer
     */
    private $tabs;

    /**
     *
     * @return int
     */
    public function getCurrentRendererTime()
    {
        if (!isset($this->currentTime))
        {
            $defaultRenderDate = new DateTime();
            $defaultRenderDate->setTime(0, 0);

            $this->currentTime = $this->getRequest()->query->get(
                ViewRenderer::PARAM_TIME, $defaultRenderDate->getTimestamp()
            );
        }

        return $this->currentTime;
    }

    /**
     *
     * @return string
     */
    public function getCurrentRendererType()
    {
        $rendererType = $this->getRequest()->query->get(ViewRenderer::PARAM_TYPE);

        if (!$rendererType)
        {
            $rendererType = LocalSetting::getInstance()->get('default_view', 'Chamilo\Libraries\Calendar');

            if ($rendererType == ViewRenderer::TYPE_MONTH)
            {
                $detect = new Mobile_Detect();
                if ($detect->isMobile() && !$detect->isTablet())
                {
                    $rendererType = ViewRenderer::TYPE_LIST;
                }
            }
        }

        return $rendererType;
    }

    /**
     *
     * @param int $currentTime
     */
    public function setCurrentRendererTime($currentTime)
    {
        $this->currentTime = $currentTime;
    }
}
