<?php
namespace Chamilo\Core\Repository\Workspace\Storage\DataClass;

use Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface;
use Chamilo\Core\Repository\Workspace\Favourite\Storage\DataClass\WorkspaceUserFavourite;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;

/**
 *
 * @package Chamilo\Core\Repository\Workspace\Storage\DataClass
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class Workspace extends DataClass implements WorkspaceInterface
{
    const PROPERTY_CREATION_DATE = 'creation_date';

    // Properties

    const PROPERTY_CREATOR_ID = 'creator_id';

    const PROPERTY_DESCRIPTION = 'description';

    const PROPERTY_NAME = 'name';

    const WORKSPACE_TYPE = 2;

    /**
     *
     * @var \Chamilo\Core\User\Storage\DataClass\User
     */
    private $creator;

    /**
     *
     * @return integer
     */
    public function getCreationDate()
    {
        return $this->get_default_property(self::PROPERTY_CREATION_DATE);
    }

    /**
     *
     * @return \Chamilo\Core\User\Storage\DataClass\User
     */
    public function getCreator()
    {
        if (!isset($this->creator))
        {
            $this->creator = DataManager::retrieve_by_id(User::class, $this->getCreatorId());
        }

        return $this->creator;
    }

    /**
     *
     * @return integer
     */
    public function getCreatorId()
    {
        return $this->get_default_property(self::PROPERTY_CREATOR_ID);
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->get_default_property(self::PROPERTY_DESCRIPTION);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface::getHash()
     */
    public function getHash()
    {
        return md5(serialize(array(__CLASS__, $this->getWorkspaceType(), $this->getId())));
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->get_default_property(self::PROPERTY_NAME);
    }

    public function getTitle()
    {
        return $this->getName();
    }

    public function getWorkspaceType()
    {
        return self::WORKSPACE_TYPE;
    }

    /**
     *
     * @return string[]
     */
    public static function get_default_property_names($extended_property_names = [])
    {
        return parent::get_default_property_names(
            array(
                self::PROPERTY_NAME,
                self::PROPERTY_DESCRIPTION,
                self::PROPERTY_CREATOR_ID,
                self::PROPERTY_CREATION_DATE
            )
        );
    }

    public function get_dependencies($dependencies = [])
    {
        return array(
            WorkspaceEntityRelation::class => new EqualityCondition(
                new PropertyConditionVariable(
                    WorkspaceEntityRelation::class, WorkspaceEntityRelation::PROPERTY_WORKSPACE_ID
                ), new StaticConditionVariable($this->getId())
            ),
            WorkspaceContentObjectRelation::class => new EqualityCondition(
                new PropertyConditionVariable(
                    WorkspaceContentObjectRelation::class, WorkspaceContentObjectRelation::PROPERTY_WORKSPACE_ID
                ), new StaticConditionVariable($this->getId())
            ),
            WorkspaceCategoryRelation::class => new EqualityCondition(
                new PropertyConditionVariable(
                    WorkspaceCategoryRelation::class, WorkspaceCategoryRelation::PROPERTY_WORKSPACE_ID
                ), new StaticConditionVariable($this->getId())
            ),
            WorkspaceUserFavourite::class => new EqualityCondition(
                new PropertyConditionVariable(
                    WorkspaceUserFavourite::class, WorkspaceUserFavourite::PROPERTY_WORKSPACE_ID
                ), new StaticConditionVariable($this->getId())
            )
        );
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'repository_workspace';
    }

    /*
     * (non-PHPdoc) @see \Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface::getWorkspaceType()
     */

    /**
     *
     * @param integer $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->set_default_property(self::PROPERTY_CREATION_DATE, $creationDate);
    }

    /*
     * (non-PHPdoc) @see \Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface::getTitle()
     */

    /**
     *
     * @param integer $creatorId
     */
    public function setCreatorId($creatorId)
    {
        $this->set_default_property(self::PROPERTY_CREATOR_ID, $creatorId);
    }

    /**
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->set_default_property(self::PROPERTY_DESCRIPTION, $description);
    }

    /**
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->set_default_property(self::PROPERTY_NAME, $name);
    }
}