<?php

namespace Chamilo\Core\Notification\Storage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @package Chamilo\Core\Notification\Storage\Entity
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 *
 * @ORM\Entity(repositoryClass="Chamilo\Core\Notification\Storage\Repository\FilterRepository")
 * @ORM\Table(
 *     name="notification_filter",
 *     indexes={@ORM\Index(name="nf_path", columns={"path"})}
 * )
 */
class Filter
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", nullable=false)
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(name="description_context", type="string", nullable=false, length=1024)
     */
    protected $descriptionContext;

    /**
     * @var \Chamilo\Core\Notification\Storage\Entity\Notification[]
     *
     * @ORM\ManyToMany(targetEntity="\Chamilo\Core\Notification\Storage\Entity\Notification", mappedBy="filters")
     */
    protected $notifications;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return \Chamilo\Core\Notification\Storage\Entity\Filter
     */
    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescriptionContext()
    {
        return $this->descriptionContext;
    }

    /**
     * @param string $descriptionContext
     *
     * @return \Chamilo\Core\Notification\Storage\Entity\Filter
     */
    public function setDescriptionContext(string $descriptionContext)
    {
        $this->descriptionContext = $descriptionContext;

        return $this;
    }

    /**
     * @return \Chamilo\Core\Notification\Storage\Entity\Notification[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @param \Chamilo\Core\Notification\Storage\Entity\Notification[] $notifications
     */
    public function setNotifications(array $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param \Chamilo\Core\Notification\Storage\Entity\Notification $notification
     */
    public function addNotification(Notification $notification)
    {
        $this->notifications[] = $notification;
    }

}