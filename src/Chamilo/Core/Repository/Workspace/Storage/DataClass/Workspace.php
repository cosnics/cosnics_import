<?php
namespace Chamilo\Core\Repository\Workspace\Storage\DataClass;

use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Storage\DataManager\DataManager;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Core\Repository\Workspace\Architecture\WorkspaceInterface;

/**
 *
 * @package Chamilo\Core\Repository\Workspace\Storage\DataClass
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class Workspace extends DataClass implements WorkspaceInterface
{
    const CLASS_NAME = __CLASS__;

    // Properties
    const PROPERTY_NAME = 'name';
    const PROPERTY_DESCRIPTION = 'description';
    const PROPERTY_CREATOR_ID = 'creator_id';
    const PROPERTY_CREATION_DATE = 'creation_date';

    /**
     *
     * @var \Chamilo\Core\User\Storage\DataClass\User
     */
    private $creator;

    /**
     *
     * @return string[]
     */
    public static function get_default_property_names()
    {
        return parent :: get_default_property_names(
            array(
                self :: PROPERTY_NAME,
                self :: PROPERTY_DESCRIPTION,
                self :: PROPERTY_CREATOR_ID,
                self :: PROPERTY_CREATION_DATE));
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->get_default_property(self :: PROPERTY_NAME);
    }

    /**
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->set_default_property(self :: PROPERTY_NAME, $name);
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->get_default_property(self :: PROPERTY_DESCRIPTION);
    }

    /**
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->set_default_property(self :: PROPERTY_DESCRIPTION, $description);
    }

    /**
     *
     * @return integer
     */
    public function getCreatorId()
    {
        return $this->get_default_property(self :: PROPERTY_CREATOR_ID);
    }

    /**
     *
     * @return \Chamilo\Core\User\Storage\DataClass\User
     */
    public function getCreator()
    {
        if (! isset($this->creator))
        {
            $this->creator = DataManager :: retrieve_by_id(User :: class_name(), $this->getCreatorId());
        }

        return $this->creator;
    }

    /**
     *
     * @param integer $creatorId
     */
    public function setCreatorId($creatorId)
    {
        $this->set_default_property(self :: PROPERTY_CREATOR_ID, $creatorId);
    }

    /**
     *
     * @see \Chamilo\Libraries\Storage\DataClass\DataClass::get_dependencies()
     */
    public function get_dependencies()
    {
        return array(
            WorkspaceEntityRelation :: class_name() => new EqualityCondition(
                new PropertyConditionVariable(
                    WorkspaceEntityRelation :: class_name(),
                    WorkspaceEntityRelation :: PROPERTY_WORKSPACE_ID),
                new StaticConditionVariable($this->getId())));
    }
}