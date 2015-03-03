<?php
namespace Chamilo\Core\Repository\Storage\DataClass;

use Chamilo\Core\Repository\Common\ContentObjectDifference;
use Chamilo\Core\Repository\Common\Path\ComplexContentObjectPath;
use Chamilo\Core\Repository\Instance\Storage\DataClass\SynchronizationData;
use Chamilo\Core\Repository\Publication\PublicationInterface;
use Chamilo\Core\Repository\RepositoryRights;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Core\Rights\Entity\PlatformGroupEntity;
use Chamilo\Core\Rights\Entity\UserEntity;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\Architecture\Exceptions\ObjectNotExistException;
use Chamilo\Libraries\Architecture\Interfaces\AttachmentSupport;
use Chamilo\Libraries\Architecture\Interfaces\ComplexContentObjectSupport;
use Chamilo\Libraries\Architecture\Interfaces\Versionable;
use Chamilo\Libraries\File\Filesystem;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Theme;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Platform\Translation;
use Chamilo\Libraries\Storage\DataClass\CompositeDataClass;
use Chamilo\Libraries\Storage\Parameters\DataClassCountParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassDistinctParameters;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\NotCondition;
use Chamilo\Libraries\Storage\Query\Condition\OrCondition;
use Chamilo\Libraries\Storage\Query\Condition\SubselectCondition;
use Chamilo\Libraries\Storage\Query\Join;
use Chamilo\Libraries\Storage\Query\Joins;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\StringUtilities;
use Chamilo\Libraries\Utilities\UUID;

/**
 *
 * @author Tim De Pauw
 * @author Hans De Bisschop
 * @author Dieter De Neef
 */
class ContentObject extends CompositeDataClass
{
    const CLASS_NAME = __CLASS__;

    /**
     * A state constant for an inactive object
     *
     * @var int
     */
    const STATE_INACTIVE = 1;

    /**
     * A state constant for a normal object
     *
     * @var int
     */
    const STATE_NORMAL = 2;

    /**
     * A state constant for an object in the recycling bin
     *
     * @var int
     */
    const STATE_RECYCLED = 4;

    /**
     * A state constant for an autosaved object
     *
     * @var int
     */
    const STATE_AUTOSAVE = 8;

    /**
     * A state constant for an object that has been backuped
     *
     * @var int
     */
    const STATE_BACKUP = 16;

    /**
     * Property name of this object
     */
    const PROPERTY_TYPE = 'type';
    const PROPERTY_OWNER_ID = 'owner_id';
    const PROPERTY_TITLE = 'title';
    const PROPERTY_DESCRIPTION = 'description';
    const PROPERTY_PARENT_ID = 'parent_id';
    const PROPERTY_CREATION_DATE = 'created';
    const PROPERTY_MODIFICATION_DATE = 'modified';
    const PROPERTY_OBJECT_NUMBER = 'object_number';
    const PROPERTY_STATE = 'state';
    const PROPERTY_COMMENT = 'comment';
    const PROPERTY_CONTENT_HASH = 'content_hash';
    const PROPERTY_CURRENT = 'current';
    const PROPERTY_TEMPLATE_REGISTRATION_ID = 'template_registration_id';
    const PARAM_SECURITY_CODE = 'security_code';
    const ATTACHMENT_ALL = 'all';
    const ATTACHMENT_NORMAL = 'normal';
    const PROPERTIES_ADDITIONAL = 'additional_properties';
    const CURRENT_OLD = 0;
    const CURRENT_SINGLE = 1;
    const CURRENT_MULTIPLE = 2;

    /**
     * objects attached to this object.
     */
    private $attachments = array();

    private $attachment_ids = array();

    /**
     * Objects included into this object.
     */
    private $includes;

    /**
     * The state that this object had when it was retrieved.
     * Used to determine if the state of its children should be
     * updated upon updating the object.
     */
    private $oldState;

    /**
     *
     * @var ObjectResultSet
     */
    private $synchronization_data;

    /**
     *
     * @var ComplexContentObjectPath
     */
    private $complex_content_object_path;

    /**
     *
     * @var TemplateRegistration
     */
    private $template_registration;

    /**
     * Creates a new object.
     *
     * @param $id int The numeric ID of the object. May be omitted if creating a new object.
     * @param $defaultProperties array The default properties of the object. Associative array.
     * @param $additionalProperties array The properties specific for this type of object. Associative array. Null if
     *        they are unknown at construction of the object; in this case, they will be retrieved when needed.
     */
    public function __construct($default_properties = array(), $additional_properties = null)
    {
        parent :: __construct($default_properties);
        $this->set_additional_properties($additional_properties);
        $this->oldState = $default_properties[self :: PROPERTY_STATE];
    }

    public function get_type_string()
    {
        $template_registration = $this->get_template_registration();
        $type_string = $template_registration instanceof TemplateRegistration ? 'TypeName' .
             (string) StringUtilities :: getInstance()->createString($template_registration->get_name())->upperCamelize() : null;

        return static :: type_string($this :: context(), $type_string);
    }

    public static function type_string($context, $type_string = null)
    {
        return Translation :: get($type_string ? $type_string : 'TypeName', null, $context);
    }

    /**
     * Returns the state of this object.
     *
     * @return int The state.
     */
    public function get_state()
    {
        return $this->get_default_property(self :: PROPERTY_STATE);
    }

    public function get_current()
    {
        return $this->get_default_property(self :: PROPERTY_CURRENT);
    }

    public function set_current($current)
    {
        $this->set_default_property(self :: PROPERTY_CURRENT, $current);
    }

    public function is_current()
    {
        return ($this->get_current() != 0);
    }

    /**
     * Returns the ID of this object's owner.
     *
     * @return int The ID.
     */
    public function get_owner_id()
    {
        return $this->get_default_property(self :: PROPERTY_OWNER_ID);
    }

    public function get_owner()
    {
        if (! isset($this->owner))
        {
            $this->owner = \Chamilo\Core\User\Storage\DataManager :: retrieve_by_id(
                User :: class_name(),
                (int) $this->get_owner_id());
        }

        return $this->owner;
    }

    public function get_owner_fullname()
    {
        return $this->get_owner()->get_fullname();
    }

    /**
     * Returns the title of this object.
     *
     * @return string The title.
     */
    public function get_title()
    {
        return $this->get_default_property(self :: PROPERTY_TITLE);
    }

    /**
     * Returns the description of this object.
     *
     * @return string The description.
     */
    public function get_description()
    {
        return $this->get_default_property(self :: PROPERTY_DESCRIPTION);
    }

    public function has_description()
    {
        $description = $this->get_description();
        return ($description != '<p>&#160;</p>' && count($description) > 0);
    }

    /**
     * Returns the difference of this object with a given object based on it's id.
     *
     * @param $id int The ID of the object to compare with.
     * @return Array The difference.
     */
    public function get_difference($id)
    {
        $version = DataManager :: retrieve_content_object($id);
        return ContentObjectDifference :: factory($this, $version);
    }

    /**
     * Returns the comment of this object version.
     *
     * @return string The version.
     */
    public function get_comment()
    {
        return $this->get_default_property(self :: PROPERTY_COMMENT);
    }

    /**
     * Returns the numeric identifier of the object's parent object.
     *
     * @return int The identifier.
     */
    public function get_parent_id()
    {
        return $this->get_default_property(self :: PROPERTY_PARENT_ID);
    }

    /**
     * Returns the date when this object was created, as returned by PHP's time() function.
     *
     * @return int The creation date.
     */
    public function get_creation_date()
    {
        return $this->get_default_property(self :: PROPERTY_CREATION_DATE);
    }

    /**
     * Returns the date when this object was last modified, as returned by PHP's time() function.
     *
     * @return int The modification time.
     */
    public function get_modification_date()
    {
        return $this->get_default_property(self :: PROPERTY_MODIFICATION_DATE);
    }

    /**
     * Returns the version number.
     *
     * @return int The version number.
     */
    public function get_object_number()
    {
        return $this->get_default_property(self :: PROPERTY_OBJECT_NUMBER);
    }

    /**
     * Returns the object ids attached to this object.
     *
     * @return array The objects.
     */
    public function get_attached_content_object_ids($type = self :: ATTACHMENT_NORMAL)
    {
        if (! is_array($this->attachment_ids[$type]))
        {
            $conditions = array();
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectAttachment :: class_name(),
                    ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
                new StaticConditionVariable($this->get_id()));
            if ($type != self :: ATTACHMENT_ALL)
            {
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        ContentObjectAttachment :: class_name(),
                        ContentObjectAttachment :: PROPERTY_TYPE),
                    new StaticConditionVariable($type));
            }
            $condition = new AndCondition($conditions);

            $parameters = new DataClassDistinctParameters($condition, ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID);
            $this->attachment_ids[$type] = DataManager :: distinct(ContentObjectAttachment :: class_name(), $parameters);
        }
        return $this->attachment_ids[$type];
    }

    public function get_content_object_versions($include_last = true)
    {
        if (! is_array($this->versions))
        {
            $this->versions = DataManager :: retrieve_content_object_versions($this)->as_array();
        }
        if ($include_last)
            return $this->versions;
        else
            return array_slice($this->versions, 1, sizeof($this->versions) - 1);
    }

    public function get_latest_version_id()
    {
        return $this->get_latest_version()->get_id();
    }

    public function get_latest_version()
    {
        return DataManager :: retrieve_most_recent_content_object_version($this);
    }

    /**
     * Returns the edition of this object
     *
     * @return an int; the number of the version.
     */
    public function get_content_object_edition()
    {
        return array_search($this->id, DataManager :: get_version_ids($this)) + 1;
    }

    /**
     * Returns the id of the template for the content object
     *
     * @return int
     */
    public function get_template_registration_id()
    {
        return $this->get_default_property(self :: PROPERTY_TEMPLATE_REGISTRATION_ID);
    }

    public function get_template_registration()
    {
        if (! isset($this->template_registration))
        {
            $this->template_registration = \Chamilo\Core\Repository\Configuration :: registration_by_id(
                (int) $this->get_template_registration_id());

            if (! $this->template_registration instanceof TemplateRegistration)
            {
                throw new ObjectNotExistException(Translation :: get('TemplateRegistration'));
            }
        }

        return $this->template_registration;
    }

    /**
     * Sets this object's state to any of the STATE_* constants.
     *
     * @param $state int The state.
     * @return boolean True upon success, false upon failure.
     */
    public function set_state($state)
    {
        return $this->set_default_property(self :: PROPERTY_STATE, $state);
    }

    /**
     * Sets the ID of this object's owner.
     *
     * @param $id int The ID.
     */
    public function set_owner_id($owner)
    {
        $this->set_default_property(self :: PROPERTY_OWNER_ID, $owner);
    }

    /**
     * Sets the object number of this object.
     *
     * @param $object_number int The Object Number.
     */
    public function set_object_number($object_number)
    {
        $this->set_default_property(self :: PROPERTY_OBJECT_NUMBER, $object_number);
    }

    /**
     * Sets the title of this object.
     *
     * @param $title string The title.
     */
    public function set_title($title)
    {
        $this->set_default_property(self :: PROPERTY_TITLE, $title);
    }

    /**
     * Sets the description of this object.
     *
     * @param $description string The description.
     */
    public function set_description($description)
    {
        $this->set_default_property(self :: PROPERTY_DESCRIPTION, $description);
    }

    /**
     * Sets the comment of this object version.
     *
     * @param $comment string The comment.
     */
    public function set_comment($comment)
    {
        $this->set_default_property(self :: PROPERTY_COMMENT, $comment);
    }

    /**
     * Sets the ID of this object's parent object.
     *
     * @param $parent int The ID.
     */
    public function set_parent_id($parent)
    {
        $this->set_default_property(self :: PROPERTY_PARENT_ID, $parent);
    }

    /**
     * Sets the date when this object was created.
     *
     * @param $created int The creation date, as returned by time().
     */
    public function set_creation_date($created)
    {
        $this->set_default_property(self :: PROPERTY_CREATION_DATE, $created);
    }

    /**
     * Sets the date when this object was modified.
     *
     * @param $modified int The modification date, as returned by time().
     */
    public function set_modification_date($modified)
    {
        $this->set_default_property(self :: PROPERTY_MODIFICATION_DATE, $modified);
    }

    public function get_content_hash()
    {
        return $this->get_default_property(self :: PROPERTY_CONTENT_HASH);
    }

    public function set_content_hash($content_hash)
    {
        $this->set_default_property(self :: PROPERTY_CONTENT_HASH, $content_hash);
    }

    /**
     * Sets the template id of the content object
     *
     * @param int $template_registration_id
     */
    public function set_template_registration_id($template_registration_id)
    {
        $this->set_default_property(self :: PROPERTY_TEMPLATE_REGISTRATION_ID, $template_registration_id);
    }

    /**
     * Determines whether this object can have versions.
     *
     * @return boolean True if the object is versionable, false otherwise.
     * @deprecated Use instanceof Versionable directly from now on
     */
    public function is_versionable()
    {
        return $this instanceof Versionable;
    }

    /**
     * Checks whether the current version of the object is the latest version
     *
     * @deprecated Use is_current() now
     */
    public function is_latest_version()
    {
        return $this->is_current();
    }

    /**
     * Returns the number of versions of the object
     */
    public function get_version_count()
    {
        return count(DataManager :: get_version_ids($this));
    }

    /**
     * Checks if this object has versions
     *
     * @return boolean
     */
    public function has_versions()
    {
        return ($this->get_current() != 1);
    }

    /**
     * Attaches the object with the given ID to this object.
     *
     * @param $id int The ID of the object to attach.
     */
    public function attach_content_object($id, $type = self :: ATTACHMENT_NORMAL)
    {
        if ($this->is_attached_to($id, $type))
        {
            return true;
        }
        else
        {
            $attachment = new ContentObjectAttachment();
            $attachment->set_attachment_id($id);
            $attachment->set_content_object_id($this->get_id());
            $attachment->set_type($type);
            return $attachment->create();
        }
    }

    public function attach_content_objects($ids = array(), $type = self :: ATTACHMENT_NORMAL)
    {
        if (! is_array($ids))
        {
            $ids = array($ids);
        }

        foreach ($ids as $id)
        {
            if (! $this->attach_content_object($id, $type))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Is the object attached to object with the identifier as passed on
     *
     * @param $object_id int
     * @return boolean
     */
    public function is_attached_to($object_id, $type = ContentObject ::ATTACHMENT_NORMAL)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID),
            new StaticConditionVariable($object_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_TYPE),
            new StaticConditionVariable($type));
        $condition = new AndCondition($conditions);

        return DataManager :: count(ContentObjectAttachment :: class_name(), $condition) > 0;
    }

    public function is_included_in($object_id)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_INCLUDE_ID),
            new StaticConditionVariable($object_id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));
        $condition = new AndCondition($conditions);

        return DataManager :: count(ContentObjectInclude :: class_name(), $condition) > 0;
    }

    /**
     * Recursive method to check all the attachments and includes of a content object to see if the check content object
     * is attached or included in the given content object
     *
     * @param $content_object_id int
     * @param $check_content_object_id int
     *
     * @return boolean
     */
    public function is_attached_to_or_included_in($object_id)
    {
        if ($this->is_attached_to($object_id))
        {
            return true;
        }

        if ($this->is_included_in($object_id))
        {
            return true;
        }

        $attachments = $this->get_attachments();
        foreach ($attachments as $attachment)
        {
            if ($attachment->is_attached_to_or_included_in($object_id))
            {
                return true;
            }
        }

        $includes = $this->get_includes();
        foreach ($includes as $include)
        {
            if ($include->is_attached_to_or_included_in($object_id))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Includes the object with the given ID in this object.
     *
     * @param $id int The ID of the object to include.
     */
    public function include_content_object($id)
    {
        if ($this->is_included_in($id))
        {
            return true;
        }
        else
        {
            $include = new ContentObjectInclude();
            $include->set_include_id($id);
            $include->set_content_object_id($this->get_id());
            return $include->create();
        }
    }

    /**
     * Removes the object with the given ID from this object's attachment list.
     *
     * @param $id int The ID of the object to remove from the attachment list.
     * @return boolean True if the attachment was removed, false if it did not exist.
     */
    public function detach_content_object($id, $type = self :: ATTACHMENT_NORMAL)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID),
            new StaticConditionVariable($id));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_TYPE),
            new StaticConditionVariable($type));
        $condition = new AndCondition($conditions);

        $attachment = DataManager :: retrieve(ContentObjectAttachment :: class_name(), $condition);
        if ($attachment instanceof ContentObjectAttachment)
        {
            return $attachment->delete();
        }
        else
        {
            return false;
        }
    }

    public function detach_content_objects($ids = array(), $type = self :: ATTACHMENT_NORMAL)
    {
        if (! is_array($ids))
        {
            $ids = array($ids);
        }

        foreach ($ids as $id)
        {
            if (! $this->detach_content_object($id, $type))
            {
                return false;
            }
        }
        return true;
    }

    public function truncate_attachments($type = self :: ATTACHMENT_NORMAL)
    {
        // Reset the cache
        $this->truncate_attachment_cache($type);

        // Delete all types of attachments from persistent storage (only the
        // links, not the actual objects)
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_TYPE),
            new StaticConditionVariable($type));
        $condition = new AndCondition($conditions);
        $attachments = $this->get_attachments($type);
        foreach ($attachments as $attachment)
        {
            if (! $attachment->delete())
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Empty the lazy load variables to trigger a new retrieve
     *
     * @param $type String
     */
    public function truncate_attachment_cache($type = self :: ATTACHMENT_NORMAL)
    {
        unset($this->attachment_ids[$type]);
        unset($this->attachments[$type]);
    }

    /**
     * Removes the object with the given ID from this object's include list.
     *
     * @param $id int The ID of the object to remove from the include list.
     * @return boolean True if the include was removed, false if it did not exist.
     */
    public function exclude_content_object($id)
    {
        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_INCLUDE_ID),
            new StaticConditionVariable($id));
        $condition = new AndCondition($conditions);

        $include = DataManager :: retrieve(ContentObjectInclude :: class_name(), $condition);

        if ($include instanceof ContentObjectInclude)
        {
            return $include->delete();
        }
        else
        {
            return false;
        }
    }

    public function create($create_in_batch = false)
    {
        $content_object = $this;

        // TRANSACTION
        $success = DataManager :: transactional(
            function ($c) use($create_in_batch, $content_object)
            { // checks wether to create a new content object or
              // version:
              // if the ID is set, we create a new version,
              // otherwise a new CO.
                $orig_id = $content_object->get_id();
                $version = isset($orig_id);

                $now = time();
                $content_object->set_creation_date($now);
                $content_object->set_modification_date($now);

                if (! $content_object->get_template_registration_id())
                {
                    $default_template_registration = \Chamilo\Core\Repository\Configuration :: registration_default_by_type(
                        ClassnameUtilities :: getInstance()->getNamespaceParent($content_object->context(), 2));

                    $content_object->set_template_registration_id($default_template_registration->get_id());
                }

                if ($version)
                { // id changes in create new version, so location needs to be fetched
                  // now
                    $original_location = $content_object->get_rights_location();
                    $content_object->set_current(ContentObject :: CURRENT_MULTIPLE);
                }
                else
                {
                    $content_object->set_object_number(UUID :: v4());
                    $content_object->set_current(ContentObject :: CURRENT_SINGLE);
                }

                if (! call_user_func_array(
                    array($content_object, '\Chamilo\Libraries\Storage\DataClass\DataClass::create'),
                    array()))
                {

                    return false;
                }

                if ($version)
                {
                    $conditions = array();
                    $conditions[] = new EqualityCondition(
                        new PropertyConditionVariable(
                            ContentObject :: class_name(),
                            ContentObject :: PROPERTY_OBJECT_NUMBER),
                        new StaticConditionVariable($content_object->get_object_number()));
                    $conditions[] = new NotCondition(
                        new EqualityCondition(
                            new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID),
                            new StaticConditionVariable($content_object->get_id())));
                    $condition = new AndCondition($conditions);
                    $parameters = new DataClassRetrievesParameters($condition);
                    $objects = DataManager :: retrieve_content_objects($content_object :: class_name(), $parameters);
                    while ($object = $objects->next_result())
                    {
                        $object->set_current(ContentObject :: CURRENT_OLD);
                        $object->update();
                    }
                }

                $parent = $content_object->get_parent_id();
                if (! $parent)
                {
                    $parent_id = RepositoryRights :: get_instance()->get_user_root_id($content_object->get_owner_id());
                }
                else
                {
                    $parent_id = RepositoryRights :: get_instance()->get_location_id_by_identifier_from_user_subtree(
                        RepositoryRights :: TYPE_USER_CATEGORY,
                        $content_object->get_parent_id(),
                        $content_object->get_owner_id());
                }

                $new_location = RepositoryRights :: get_instance()->create_location_in_user_tree(
                    RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
                    $content_object->get_id(),
                    $parent_id,
                    $content_object->get_owner_id(),
                    $create_in_batch);

                if (! $new_location)
                {

                    return false;
                }

                if ($version) // copy the granted rights
                {
                    $rights_entities = $original_location->get_rights_entities();
                    foreach ($rights_entities as $rights_entity)
                    {
                        if (! RepositoryRights :: get_instance()->set_location_entity_right(
                            $rights_entity->get_right_id(),
                            $rights_entity->get_entity_id(),
                            $rights_entity->get_entity_type(),
                            $new_location->get_id()))
                        {
                            return false;
                        }
                    }
                }

                return true;
            });

        return $success;
    }

    public function get_rights_location()
    {
        return RepositoryRights :: get_instance()->get_location_by_identifier_from_users_subtree(
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $this->get_id(),
            $this->get_owner_id());
    }

    public function create_all()
    {
        $now = time();

        if (! $this->get_creation_date())
        {
            $this->set_creation_date($now);
        }

        if (! $this->get_modification_date())
        {
            $this->set_modification_date($now);
        }

        $this->set_object_number(UUID :: v4());

        if (! parent :: create())
        {
            return false;
        }

        if ($this->get_owner_id() == 0)
            return true;

        $parent = $this->get_parent_id();
        if (! $parent)
        {
            $parent_id = RepositoryRights :: get_instance()->get_user_root_id($this->get_owner_id());
        }
        else
        {
            $parent_id = RepositoryRights :: get_instance()->get_location_id_by_identifier_from_user_subtree(
                RepositoryRights :: TYPE_USER_CATEGORY,
                $this->get_parent_id(),
                $this->get_owner_id());
        }

        if (! RepositoryRights :: get_instance()->create_location_in_user_tree(
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $this->get_id(),
            $parent_id,
            $this->get_owner_id()))
        {
            return false;
        }

        return true;
    }

    /**
     * Instructs the data manager to update the object, making any modifications permanent.
     * Also sets the object's
     * modification date to the current time if the update is a true update. A true update is an update that implicates
     * a change to a property that affects the object itself; changing the object's category, for instance, should not
     * change the last modification date.
     *
     * @param $trueUpdate boolean True if the update is a true update (default), false otherwise.
     * @return boolean True if the update succeeded, false otherwise.
     */
    public function update($trueUpdate = true)
    {
        if ($trueUpdate)
        {
            $this->set_modification_date(time());
        }
        $success = parent :: update();
        if (! $success)
        {
            return false;
        }
        /*
         * $state = $this->get_state(); if ($state == $this->oldState) { return true; } $child_ids = self ::
         * get_child_ids($this->get_id()); $dm->set_content_object_states($child_ids, $state);
         */
        /*
         * We return true here regardless of the result of the child update,
         * since the object itself did get updated.
         */
        return true;
    }

    public function recycle()
    {
        $this->set_modification_date(time());
        $this->set_state(self :: STATE_RECYCLED);

        return parent :: update();
    }

    public function move($new_parent_id)
    {
        $content_object = $this;

        // TRANSACTION
        $success = DataManager :: transactional(
            function ($c) use($new_parent_id, $content_object)
            {
                $content_object->set_parent_id($new_parent_id);
                $succes = call_user_func_array(
                    array($content_object, '\Chamilo\Libraries\Storage\DataClass\DataClass::update'),
                    array());

                if (! $succes)
                {
                    return false;
                }
                if ($new_parent_id == 0)
                {
                    $new_parent = RepositoryRights :: get_instance()->get_user_root_id($content_object->get_owner_id());
                }
                else
                {
                    $new_parent = RepositoryRights :: get_instance()->get_location_id_by_identifier_from_user_subtree(
                        RepositoryRights :: TYPE_USER_CATEGORY,
                        $new_parent_id,
                        $content_object->get_owner_id());
                }

                $location = RepositoryRights :: get_instance()->get_location_by_identifier_from_users_subtree(
                    RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
                    $content_object->get_id(),
                    $content_object->get_owner_id());

                if ($location)
                {
                    return $location->move($new_parent);
                }
                else
                {
                    return false;
                }
            });
        return $success;
    }

    // create a version
    public function version()
    {
        return $this->create();
    }

    /**
     * Instructs the data manager to delete the object.
     */
    public function delete($only_version = false)
    {
        $content_object = $this;

        // TRANSACTION
        $success = DataManager :: transactional(
            function ($c) use($only_version, $content_object)
            {
                if ($only_version)
                {
                    if (! $content_object->version_delete())
                    {
                        return false;
                    }

                    $count = DataManager :: count_content_object_versions($content_object);
                    if ($content_object->is_latest_version() && $count > 0)
                    {
                        $new_latest_content_object = DataManager :: retrieve_most_recent_content_object_version(
                            $content_object);
                        $new_latest_content_object->set_current(
                            ($count > 1 ? $content_object :: CURRENT_MULTIPLE : $content_object :: CURRENT_SINGLE));

                        return $new_latest_content_object->update();
                    }
                    return true;
                }
                else
                {
                    $versions = $content_object->get_content_object_versions();
                    foreach ($versions as $version)
                    {
                        if (! $version->delete(true))
                        {
                            return false;
                        }
                    }
                    return true;
                }
            });

        return $success;
    }

    public function version_delete()
    {
        $location = RepositoryRights :: get_instance()->get_location_by_identifier_from_users_subtree(
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $this->get_id(),
            $this->get_owner_id());

        if ($location)
        {
            if (! $location->delete())
            {
                return false;
            }
        }

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));

        if (! DataManager :: deletes(ContentObjectAttachment :: CLASS_NAME, $condition))
            return false;

        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));

        if (! DataManager :: deletes(ContentObjectInclude :: CLASS_NAME, $condition))
            return false;

        $external_sync = $this->get_synchronization_data();
        if ($external_sync instanceof SynchronizationData)
        {
            if (! $external_sync->delete())
            {
                return false;
            }
        }

        return parent :: delete();
    }

    /**
     * Returns the dependencies for this dataclass
     *
     * @return string[string]
     */
    protected function get_dependencies()
    {
        return array(
            ContentObjectRelTag :: class_name() => new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectRelTag :: class_name(),
                    ContentObjectRelTag :: PROPERTY_CONTENT_OBJECT_ID),
                new StaticConditionVariable($this->get_id())));
    }

    public function delete_links()
    {
        // Delete attachment links of the object
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID),
            new StaticConditionVariable($this->get_id()));
        if (! DataManager :: deletes(ContentObjectAttachment :: class_name(), $condition))
        {
            return false;
        }

        $conditions = array();
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_REF),
            new StaticConditionVariable($this->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($this->get_id()),
            ComplexContentObjectItem :: get_table_name());

        $condition = new OrCondition($conditions);

        $items = DataManager :: retrieve_complex_content_object_items(
            ComplexContentObjectItem :: class_name(),
            $condition);
        while ($item = $items->next_result())
        {
            if (! $item->delete())
            {
                return false;
            }
        }

        $includes = $this->get_includes();
        foreach ($includes as $include)
        {
            if (! $include->delete())
            {
                return false;
            }
        }

        if (DataManager :: delete_content_object_publications($this) && $this->delete_assisting_content_objects())
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function delete_assisting_content_objects()
    {
        $assisting_types = DataManager :: get_active_helper_types();
        $failures = 0;

        foreach ($assisting_types as $type)
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable($type :: class_name(), 'reference_id'),
                new StaticConditionVariable($this->get_id()));
            $assisting_objects = DataManager :: retrieve_active_content_objects($type, $condition);

            while ($assisting_object = $assisting_objects->next_result())
            {
                $conditions = array();
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        ComplexContentObjectItem :: class_name(),
                        ComplexContentObjectItem :: PROPERTY_REF),
                    new StaticConditionVariable($assisting_object->get_id()));
                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        ComplexContentObjectItem :: class_name(),
                        ComplexContentObjectItem :: PROPERTY_PARENT),
                    new StaticConditionVariable($assisting_object->get_id()),
                    ComplexContentObjectItem :: get_table_name());

                $condition = new OrCondition($conditions);

                $items = DataManager :: retrieve_complex_content_object_items(
                    ComplexContentObjectItem :: class_name(),
                    $condition);
                while ($item = $items->next_result())
                {
                    if (! $item->delete())
                    {
                        $failures ++;
                    }
                }

                if (! $assisting_object->delete())
                {
                    $failures ++;
                }
            }
        }

        return ($failures == 0);
    }

    /**
     * Retrieves this object's ancestors.
     *
     * @return array The ancestors, all objects.
     */
    public function get_ancestors()
    {
        $ancestors = array();
        $aid = $this->get_parent_id();
        while ($aid > 0)
        {
            $ancestor = DataManager :: retrieve_content_object($aid);
            $ancestors[] = $ancestor;
            $aid = $ancestor->get_parent_id();
        }
        return $ancestors;
    }

    /**
     * Checks if the given ID is the ID of one of this object's ancestors.
     *
     * @param $ancestor_id int
     * @return boolean True if the ID belongs to an ancestor, false otherwise.
     */
    public function has_ancestor($ancestor_id)
    {
        $aid = $this->get_parent_id();
        while ($aid > 0)
        {
            if ($aid == $ancestor_id)
            {
                return true;
            }
            $ancestor = DataManager :: retrieve_content_object($aid);
            $aid = $ancestor->get_parent_id();
        }
        return false;
    }

    /**
     * Determines whether this object may be moved to the object with the given ID.
     * By default, a object may be moved to
     * another object if the other object is not the object itself, the object is not an ancestor of the other object,
     * and the other object is a category.
     *
     * @param $target int The ID of the target object.
     * @return boolean True if the move is allowed, false otherwise.
     */
    public function move_allowed($target)
    {
        /*
         * if ($target == $this->get_id()) { return false; } $target_object = RepositoryDataManager ::
         * get_instance()->retrieve_content_object($target); if ($target_object->get_type() != 'category') { return
         * false; } return !$target_object->has_ancestor($this->get_id());
         */
        return true;
    }

    // XXX: Keep this around? Override? Make useful?
    public function __tostring()
    {
        return get_class($this) . '#' . $this->get_id() . ' (' . $this->get_title() . ')';
    }

    /**
     * Determines whether this object supports attachments, i.e.
     * whether other objects may be attached to it.
     *
     * @return boolean True if attachments are supported, false otherwise.
     * @deprecated Use instanceof AttachmentSupport directly from now on
     */
    public function supports_attachments()
    {
        return $this instanceof AttachmentSupport;
    }

    /**
     * Determines whether this object is a complex object
     *
     * @return boolean True if the LO is a CLO
     * @deprecated Use instanceof ComplexContentObjectSupport directly from now on
     */
    public function is_complex_content_object()
    {
        return $this instanceof ComplexContentObjectSupport;
    }

    /**
     *
     * @return ComplexContentObjectPath
     */
    public function get_complex_content_object_path()
    {
        if (! isset($this->complex_content_object_path))
        {
            $this->complex_content_object_path = ComplexContentObjectPath :: factory(self :: context(), $this);
        }

        return $this->complex_content_object_path;
    }

    /**
     * Gets the name of the icon corresponding to this object.
     */
    public function get_icon_name($size = Theme :: ICON_SMALL)
    {
        return $size;
    }

    public function get_icon_image($size = Theme :: ICON_SMALL, $is_available = true)
    {
        $template_registration = $this->get_template_registration();

        if ($template_registration instanceof TemplateRegistration)
        {
            $size = 'template/' . $template_registration->get_name() . '/' . $size;
            $type_string = 'TypeName' .
                 (string) StringUtilities :: getInstance()->createString($template_registration->get_name())->upperCamelize();
        }
        else
        {
            $type_string = null;
        }

        return static :: icon_image(
            ClassnameUtilities :: getInstance()->getNamespaceParent($this->context(), 2),
            $size,
            $this->is_current() && $is_available,
            $type_string);
    }

    public static function icon_image($context, $size = Theme :: ICON_SMALL, $is_current = true, $type_string = null)
    {
        return '<img src="' . static :: icon_path($context, $size, $is_current) . '" alt="' . static :: type_string(
            $context,
            $type_string) . '" title="' . static :: type_string($context, $type_string) . '"/>';
    }

    public function get_icon_path($size = Theme :: ICON_SMALL)
    {
        return static :: icon_path(
            ClassnameUtilities :: getInstance()->getNamespaceParent($this->context(), 2),
            $size,
            $this->is_current());
    }

    public static function icon_path($context, $size = Theme :: ICON_SMALL, $is_current = true)
    {
        return Theme :: getInstance()->getImagePath($context, 'Logo/' . $size . ($is_current ? '' : '_na'));
    }

    /**
     * Get the default properties of all objects.
     *
     * @return array The property names.
     */
    static public function get_default_property_names($extended_property_names = array())
    {
        return parent :: get_default_property_names(
            array(
                self :: PROPERTY_OWNER_ID,
                self :: PROPERTY_TYPE,
                self :: PROPERTY_TITLE,
                self :: PROPERTY_DESCRIPTION,
                self :: PROPERTY_PARENT_ID,
                self :: PROPERTY_TEMPLATE_REGISTRATION_ID,
                self :: PROPERTY_CREATION_DATE,
                self :: PROPERTY_MODIFICATION_DATE,
                self :: PROPERTY_OBJECT_NUMBER,
                self :: PROPERTY_STATE,
                self :: PROPERTY_COMMENT,
                self :: PROPERTY_CONTENT_HASH,
                self :: PROPERTY_CURRENT));
    }

    static public function get_searchable_property_names()
    {
        return array();
    }

    /**
     * Checks if the given identifier is the name of a default object property.
     *
     * @param $name string The identifier.
     * @return boolean True if the identifier is a property name, false otherwise.
     */
    static public function is_default_property_name($name)

    {
        return in_array($name, self :: get_default_property_names());
    }

    static public function is_additional_property_name($name)

    {
        return in_array($name, self :: get_additional_property_names());
    }

    /**
     * Get all properties of this type of object that should be taken into account to calculate the used disk space.
     *
     * @return mixed The property names. Either a string, an array of strings, or null if no properties affect disk
     *         quota.
     */
    static public function get_disk_space_properties()
    {
        return null;
    }

    /**
     * Converts a object type name to the corresponding class name.
     *
     * @param $type string The type name.
     * @return string The class name.
     */
    static public function type_to_class($type)

    {
        return self :: get_content_object_type_namespace($type) . '\\' .
             (string) StringUtilities :: getInstance()->createString($type)->upperCamelize();
    }

    /**
     * Converts a class name to the corresponding object type name.
     *
     * @param $class string The class name.
     * @return string The type name.
     */
    static public function class_to_type($class)

    {
        return ClassnameUtilities :: getInstance()->getClassNameFromNamespace($class, true);
    }

    /**
     * Returns the names of the properties which are UI-wise filled by the integrated html editor
     *
     * @return multitype:string
     */
    static public function get_html_editors($html_editors = array())

    {
        $html_editors[] = self :: PROPERTY_DESCRIPTION;
        return $html_editors;
    }

    /**
     *
     * @param $content_object_id integer
     * @return ContentObject An object inheriting from ContentObject
     */
    public static function get_by_id($content_object_id)
    {
        return DataManager :: retrieve_content_object($content_object_id);
    }

    /**
     *
     * @return array:
     */
    public static function get_managers()
    {
        return array();
    }

    /**
     *
     * @return ExternalSync false
     */
    public function get_synchronization_data()
    {
        if (! isset($this->synchronization_data))
        {
            $sync_condition = new EqualityCondition(
                new PropertyConditionVariable(
                    SynchronizationData :: class_name(),
                    SynchronizationData :: PROPERTY_CONTENT_OBJECT_ID),
                new StaticConditionVariable($this->get_id()));

            $this->synchronization_data = \Chamilo\Core\Repository\Instance\Storage\DataManager :: retrieve_synchronization_data_set(
                $sync_condition)->next_result();
        }

        return $this->synchronization_data;
    }

    public function set_synchronization_data($external_sync)
    {
        $this->synchronization_data = $external_sync;
    }

    public function get_shared_users()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectUserShare :: class_name(),
                ContentObjectUserShare :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));
        $shared_users = DataManager :: retrieves(ContentObjectUserShare :: class_name(), $condition);

        $users = array();

        while ($shared_user = $shared_users->next_result())
        {
            $users[$shared_user->get_user_id()] = $shared_user->get_user()->get_fullname();
        }

        asort($users);
        return $users;
    }

    public function get_shared_groups()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectGroupShare :: class_name(),
                ContentObjectGroupShare :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));
        $shared_groups = DataManager :: retrieves(ContentObjectGroupShare :: class_name(), $condition);

        $groups = array();

        while ($shared_group = $shared_groups->next_result())
        {
            $groups[$shared_group->get_group_id()] = $shared_group->get_group()->get_name();
        }

        asort($groups);
        return $groups;
    }

    public function is_external()
    {
        $is_external = $this->get_synchronization_data();

        return isset($is_external);
    }

    public static function get_content_object_type_namespace($type)
    {
        // sometimes, the $type is already prefixed, in which case no prefix should be added.
        $prefix = 'Chamilo\Core\Repository\ContentObject\\';
        if (strpos($type, $prefix) === 0)
            return $type;
        else
            return $prefix . $type;
    }

    public static function type_exists($type)
    {
        $path = Path :: getInstance()->namespaceToFullPath(
            'Chamilo\Core\Repository\ContentObject\\' .
                 (string) StringUtilities :: getInstance()->createString($type)->upperCamelize());

        if (file_exists($path) && is_dir($path))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function get_version_header()
    {
        return '<img src="' . Theme :: getInstance()->getImagePath('Chamilo\Core\Repository', 'versions_header') .
             '" alt="' . Translation :: get('Versions') . '" title="' . Translation :: get('Versions') . '" />';
    }

    public function is_not_allowed_shared_objects()
    {
        return false;
    }

    /**
     * Checks if a user has the given right on this content object If user equals the owner then all rights are
     * available
     */
    public function has_right($right, $user_id)
    {
        if (! $user_id)
        {
            $user_id = Session :: get_user_id();
        }

        if ($this->get_owner_id() == $user_id)
        {
            return true;
        }

        return RepositoryRights :: get_instance()->is_allowed_in_user_subtree(
            $right,
            $this->get_id(),
            RepositoryRights :: TYPE_USER_CONTENT_OBJECT,
            $this->get_owner_id(),
            $user_id);
    }

    public function share_with_user($user_id, $rights = array())
    {
        $location = \Chamilo\Core\Repository\Share\Manager :: get_current_user_tree_location(
            Session :: get_user_id(),
            $this->get_id());
        if ($location)
        {
            $succes = true;
            if (! is_array($rights))
            {
                $rights = array($rights);
            }
            foreach ($rights as $right)
            {
                $succes &= RepositoryRights :: get_instance()->set_location_entity_right(
                    $right,
                    $user_id,
                    UserEntity :: ENTITY_TYPE,
                    $location->get_id());
            }
        }
        else
        {
            $succes = false;
        }
        return $succes;
    }

    public function share_with_group($group_id, $rights = array())
    {
        $location = \Chamilo\Core\Repository\Share\Manager :: get_current_user_tree_location(
            Session :: get_user_id(),
            $this->get_id());
        if ($location)
        {
            $succes = true;
            if (! is_array($rights))
            {
                $rights = array($rights);
            }
            foreach ($rights as $right)
            {
                $succes &= RepositoryRights :: get_instance()->set_location_entity_right(
                    $right,
                    $group_id,
                    PlatformGroupEntity :: ENTITY_TYPE,
                    $location->get_id());
            }
        }
        else
        {
            $succes = false;
        }
        return $succes;
    }

    /**
     * Creates a security code.
     * The following values are used because they only change in special cases (copy, import).
     * This is important because it is hardcoded into some fields with included content e.g. description was used: If a
     * change was made to the description of an included object, the security code in the including object wouldn't
     * match anymore unless replaced.
     *
     * @return type
     */
    public function calculate_security_code()
    {
        return sha1($this->get_id() . ':' . $this->get_creation_date());
    }

    /**
     * Function that updates the embedded links in fields like description
     *
     * @param $mapping array Each key(old_id) is mapped to its new object (an object is needed to calculate the security
     *        code)
     */
    public function update_include_links(array $mapping)
    {
        if (count($this->get_includes()) == 0)
        {
            return;
        }

        $fields = static :: get_html_editors();

        foreach ($mapping as $old_id => $new_object)
        {
            $pattern = '/core\.php\?go=document_downloader&amp;display=1&amp;object=' . $old_id .
                 '(&amp;security_code=[^\&]+)?&amp;application=repository/';

            $security_code = $new_object->calculate_security_code();

            $replacement_string = 'core.php?go=document_downloader&amp;display=1&amp;object=' . $new_object->get_id() .
                 '&amp;security_code=' . $security_code . '&amp;application=repository';

            foreach ($fields as $field)
            {
                $value = $this->get_default_property($field);
                $value = preg_replace($pattern, $replacement_string, $value);
                $this->set_default_property($field, $value);
            }

            $this->process_additional_include_links($pattern, $replacement_string);
        }

        $this->update();
    }

    /**
     * placeholder: if a specific type of content object stores include links outside the fields, this function
     * processes them e.g: documents: in the html file
     */
    public function process_additional_include_links($pattern, $replacement_string)
    {
        return true;
    }

    public function has_publications()
    {
        return $this->count_publications() > 0;
    }

    public function has_parents()
    {
        return $this->count_parents() > 0;
    }

    public function has_children()
    {
        return $this->count_children() > 0;
    }

    public function has_attachments()
    {
        return $this->count_attachments() > 0;
    }

    public function has_includes()
    {
        return $this->count_includes() > 0;
    }

    public function has_attachers($only_version = false)
    {
        return $this->count_attachers($only_version) > 0;
    }

    public function has_includers($only_version = false)
    {
        return $this->count_includers($only_version) > 0;
    }

    public function count_publications($user)
    {
        return \Chamilo\Core\Repository\Publication\Storage\DataManager\DataManager :: count_publication_attributes(
            PublicationInterface :: ATTRIBUTES_TYPE_OBJECT,
            $this->get_id());
    }

    public function count_parents()
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_REF),
            new StaticConditionVariable($this->get_id()),
            ComplexContentObjectItem :: get_table_name());

        $helper_types = DataManager :: get_active_helper_types();

        foreach ($helper_types as $helper_type)
        {
            $subselect_condition = new EqualityCondition(
                new PropertyConditionVariable($helper_type, 'reference_id'),
                new StaticConditionVariable($this->get_id()));
            $conditions[] = new SubselectCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem :: class_name(),
                    ComplexContentObjectItem :: PROPERTY_REF),
                new PropertyConditionVariable($helper_type :: class_name(), $helper_type :: PROPERTY_ID),
                null,
                $subselect_condition);
        }

        $condition = new OrCondition($conditions);
        return DataManager :: count_complex_content_object_items(ComplexContentObjectItem :: class_name(), $condition);
    }

    public function count_children()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($this->get_id()),
            ComplexContentObjectItem :: get_table_name());
        return DataManager :: count_complex_content_object_items(ComplexContentObjectItem :: class_name(), $condition);
    }

    public function count_attachments()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));

        $join = new Join(
            ContentObject :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectAttachment :: class_name(),
                    ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

        $parameters = new DataClassCountParameters($condition, new Joins(array($join)));
        return DataManager :: count(ContentObjectAttachment :: class_name(), $parameters);
    }

    public function count_includes()
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
            new StaticConditionVariable($this->get_id()));

        $join = new Join(
            ContentObject :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectInclude :: class_name(),
                    ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

        $parameters = new DataClassCountParameters($condition, new Joins(array($join)));
        return DataManager :: count(ContentObjectInclude :: class_name(), $parameters);
    }

    public function count_attachers($only_version = false)
    {
        // if ($only_version)
        // {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID),
            new StaticConditionVariable($this->get_id()));

        $join = new Join(
            ContentObject :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectAttachment :: class_name(),
                    ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

        $parameters = new DataClassCountParameters($condition, new Joins(array($join)));
        return DataManager :: count(ContentObjectAttachment :: class_name(), $parameters);
    }

    public function count_includers($only_version = false)
    {
        // if ($only_version)
        // {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_INCLUDE_ID),
            new StaticConditionVariable($this->get_id()));

        $join = new Join(
            ContentObject :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectInclude :: class_name(),
                    ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

        $parameters = new DataClassCountParameters($condition, new Joins(array($join)));
        return DataManager :: count(ContentObjectInclude :: class_name(), $parameters);
    }

    public function get_publications($count, $offset, $order_by)
    {
        return \Chamilo\Core\Repository\Publication\Storage\DataManager\DataManager :: get_content_object_publication_attributes(
            $this->get_id(),
            PublicationInterface :: ATTRIBUTES_TYPE_OBJECT,
            null,
            $count,
            $offset,
            $order_by);
    }

    public function get_parents($order_by = array(), $offset = null, $count = null)
    {
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_REF),
            new StaticConditionVariable($this->get_id()));

        $helper_types = DataManager :: get_active_helper_types();

        foreach ($helper_types as $helper_type)
        {
            $subselect_condition = new EqualityCondition(
                new PropertyConditionVariable($helper_type, 'reference_id'),
                new StaticConditionVariable($this->get_id()));
            $conditions[] = new SubselectCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem :: class_name(),
                    ComplexContentObjectItem :: PROPERTY_REF),
                new PropertyConditionVariable($helper_type :: class_name(), $helper_type :: PROPERTY_ID),
                null,
                $subselect_condition);
        }

        $condition = new OrCondition($conditions);
        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by);

        return DataManager :: retrieve_complex_content_object_items(
            ComplexContentObjectItem :: class_name(),
            $parameters);
    }

    public function get_children($order_by = array(), $offset = null, $count = null)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ComplexContentObjectItem :: class_name(),
                ComplexContentObjectItem :: PROPERTY_PARENT),
            new StaticConditionVariable($this->get_id()),
            ComplexContentObjectItem :: get_table_name());
        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by);
        return DataManager :: retrieve_complex_content_object_items(
            ComplexContentObjectItem :: class_name(),
            $parameters);
    }

    public function get_attachments($type = self :: ATTACHMENT_NORMAL, $order_by = array(), $offset = null, $count = null)
    {
        if (! is_array($this->attachments[$type]))
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectAttachment :: class_name(),
                    ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
                new StaticConditionVariable($this->get_id()));

            $join = new Join(
                ContentObjectAttachment :: class_name(),
                new EqualityCondition(
                    new PropertyConditionVariable(
                        ContentObjectAttachment :: class_name(),
                        ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID),
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

            $parameters = new DataClassRetrievesParameters(
                $condition,
                $count,
                $offset,
                $order_by,
                new Joins(array($join)));
            $this->attachments[$type] = DataManager :: retrieve_content_objects(
                ContentObject :: class_name(),
                $parameters)->as_array();
        }
        return $this->attachments[$type];
    }

    public function get_attachers($order_by = array(), $offset = null, $count = null)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectAttachment :: class_name(),
                ContentObjectAttachment :: PROPERTY_ATTACHMENT_ID),
            new StaticConditionVariable($this->get_id()));

        $join = new Join(
            ContentObjectAttachment :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectAttachment :: class_name(),
                    ContentObjectAttachment :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by, new Joins(array($join)));
        return DataManager :: retrieve_content_objects(ContentObject :: class_name(), $parameters)->as_array();
    }

    public function get_includes($order_by = array(), $offset = null, $count = null)
    {
        if (! is_array($this->includes))
        {
            $condition = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectInclude :: class_name(),
                    ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
                new StaticConditionVariable($this->get_id()));

            $join = new Join(
                ContentObjectInclude :: class_name(),
                new EqualityCondition(
                    new PropertyConditionVariable(
                        ContentObjectInclude :: class_name(),
                        ContentObjectInclude :: PROPERTY_INCLUDE_ID),
                    new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

            $parameters = new DataClassRetrievesParameters(
                $condition,
                $count,
                $offset,
                $order_by,
                new Joins(array($join)));
            $this->includes = DataManager :: retrieve_content_objects(ContentObject :: class_name(), $parameters)->as_array();
        }
        return $this->includes;
    }

    public function get_includers($order_by = array(), $offset = null, $count = null)
    {
        $condition = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectInclude :: class_name(),
                ContentObjectInclude :: PROPERTY_INCLUDE_ID),
            new StaticConditionVariable($this->get_id()));

        $join = new Join(
            ContentObjectInclude :: class_name(),
            new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectInclude :: class_name(),
                    ContentObjectInclude :: PROPERTY_CONTENT_OBJECT_ID),
                new PropertyConditionVariable(ContentObject :: class_name(), ContentObject :: PROPERTY_ID)));

        $parameters = new DataClassRetrievesParameters($condition, $count, $offset, $order_by, new Joins(array($join)));
        return DataManager :: retrieve_content_objects(ContentObject :: class_name(), $parameters)->as_array();
    }

    public function get_virtual_path()
    {
        $category_id = $this->get_parent_id();
        $virtual_path = array();

        while ($category_id != 0)
        {
            $category = DataManager :: retrieve_by_id(RepositoryCategory :: class_name(), $category_id);
            $category_id = $category->get_parent();
            array_unshift($virtual_path, Filesystem :: create_safe_name($category->get_name()));
        }

        array_unshift($virtual_path, Filesystem :: create_safe_name($this->get_owner_fullname()));

        return implode(DIRECTORY_SEPARATOR, $virtual_path) . DIRECTORY_SEPARATOR;
    }

    public function get_packages_from_filesystem()
    {
        $types = array();

        $directories = Filesystem :: get_directory_content(
            Path :: getInstance()->namespaceToFullPath('Chamilo\Core\Repository\ContentObject'),
            Filesystem :: LIST_DIRECTORIES,
            true);

        foreach ($directories as $directory)
        {
            $directory_name_split = explode('Chamilo\Core\Repository\ContentObject\\', $directory);
            $namespace = self :: get_content_object_type_namespace($directory_name_split[1]);

            if (\Chamilo\Configuration\Package\Storage\DataClass\Package :: exists($namespace))
            {
                $types[] = $namespace;
            }
        }

        return $types;
    }

    public static function get_active_status_types()
    {
        return array(self :: STATE_NORMAL, self :: STATE_RECYCLED, self :: STATE_AUTOSAVE, self :: STATE_BACKUP);
    }

    public static function get_inactive_status_types()
    {
        return array(
            self :: STATE_NORMAL + self :: STATE_INACTIVE,
            self :: STATE_RECYCLED + self :: STATE_INACTIVE,
            self :: STATE_AUTOSAVE + self :: STATE_INACTIVE,
            self :: STATE_BACKUP + self :: STATE_INACTIVE);
    }

    public static function is_available($type)
    {
        $namespace = ClassnameUtilities :: getInstance()->getNamespaceParent(
            ClassnameUtilities :: getInstance()->getNamespaceFromClassname($type),
            2);
        $registration = \Chamilo\Configuration\Configuration :: registration($namespace);

        // Type should be registered to be available
        if (! $registration || ! $registration->is_active())
        {
            return false;
        }

        if (! $type :: is_type_available())
        {
            return false;
        }

        return true;
    }

    public static function is_type_available()
    {
        return true;
    }
}
