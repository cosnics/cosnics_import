<?php

namespace Chamilo\Core\Notification\Storage\Repository\QueueNotificationTrigger;

/**
 * Interface QueueNotificationTriggerRepositoryInterface
 *
 * @package Chamilo\Core\Notification\QueueNotificationTrigger
 *
 * @author - Sven Vanpoucke - Hogeschool Gent
 */
interface QueueNotificationTriggerRepositoryInterface
{
    /**
     * @param $notificationTriggerData
     * @param \DateTime $createdDate
     */
    public function addNotificationTriggerToQueue($notificationTriggerData, \DateTime $createdDate);
}