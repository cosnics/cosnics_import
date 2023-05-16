<?php
namespace Chamilo\Core\Repository\ContentObject\ForumTopic\Storage\DataClass;

use Chamilo\Core\Repository\ContentObject\Forum\EmailNotification\PostEmailNotificator;
use Chamilo\Core\Repository\ContentObject\ForumTopic\Storage\DataManager;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Interfaces\AttachmentSupport;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Storage\DataClass\DataClass;
use Chamilo\Libraries\Translation\Translation;

/**
 * Describes a Forum post.
 *
 * @package repository\forum_topic\dataclass;
 * @author  Mattias De Pauw - Hogeschool Gent
 * @author  Maarten Volckaert - Hogeschool Gent
 */
class ForumPost extends DataClass implements AttachmentSupport
{
    public const ATTACHMENT_ALL = 'all';
    public const ATTACHMENT_NORMAL = 'normal';

    public const CONTEXT = ForumTopic::CONTEXT;

    public const PROPERTIES_ADDITIONAL = 'additional_properties';
    public const PROPERTY_CONTENT = 'content';
    public const PROPERTY_CREATION_DATE = 'created';
    public const PROPERTY_FORUM_TOPIC_ID = 'forum_topic_id';
    public const PROPERTY_MODIFICATION_DATE = 'modified';
    public const PROPERTY_REPLY_ON_POST_ID = 'reply_on_post_id';
    public const PROPERTY_TITLE = 'title';
    public const PROPERTY_TYPE = 'type';
    public const PROPERTY_USER_ID = 'user_id';

    /**
     * Learning objects attached to this learning object.
     */
    private $attachments = [];

    /**
     * Attaches the learning object with the given ID to this learning object.
     *
     * @param int $id The ID of the learning object to attach.
     */
    public function attach_content_object($id, $type = self::ATTACHMENT_NORMAL)
    {
        $forum_post_attachment = new ForumPostAttachment();
        $forum_post_attachment->set_forum_post_id($this->get_id());
        $forum_post_attachment->set_attachment_id($id);
        $succes = $forum_post_attachment->create();

        return $succes;
    }

    /**
     * This method is used to attach serveral content objects.
     *
     * @param type $ids array of ID's
     * @param type $type
     *
     * @return bool
     */
    public function attach_content_objects($ids = [], $type = self::ATTACHMENT_NORMAL)
    {
        if (!is_array($ids))
        {
            $ids = [$ids];
        }

        foreach ($ids as $id)
        {
            if (!$this->attach_content_object($id, $type))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * When making a new post set the creation date and modification date to current time, expect when it's the first
     * post.
     *
     * @param bool $first_post Boolean that indicates whether the post we want to create is the first post.
     *
     * @return bool Returns whether the create was succesfull.
     */
    public function create($first_post = false): bool
    {
        $now = time();

        if (!$first_post)
        {
            $this->set_creation_date($now);
            $this->set_modification_date($now);

            $forum_topic = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                ContentObject::class, $this->get_forum_topic_id()
            );
            $email_notificator = new PostEmailNotificator();
            $email_notificator->set_post($this);

            $text =
                Translation::get('PostAddedEmailTitle', null, 'Chamilo\Core\Repository\ContentObject\Forum\Display');
            $email_notificator->set_action_title($text);

            $text = Translation::get('PostAddedEmailBody', null, 'Chamilo\Core\Repository\ContentObject\Forum\Display');
            $email_notificator->set_action_body($text);

            $email_notificator->set_action_user(
                \Chamilo\Core\User\Storage\DataManager::retrieve_by_id(
                    User::class, (int) Session::get_user_id()
                )
            );
            $succes = parent::create($this);
            $forum_topic->add_post(1, $this->get_id(), $email_notificator);
            $email_notificator->send_emails();

            return $succes;
        }

        return parent::create($this);
    }

    /**
     * Delete 1 individual post and his attachements.
     *
     * @param bool $all_posts Boolean that indicated whether we want to delete all the posts or just one single post.
     *
     * @return bool
     */
    public function delete($all_posts = false): bool
    {
        $delete_attachments = DataManager::retrieve_attached_objects($this->get_id());
        $forum_topic = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
            ContentObject::class, $this->get_forum_topic_id()
        );
        $first_post = $forum_topic->is_first_post($this);

        if ($all_posts)
        {
            $first_post = false;
        }
        if ($first_post)
        {
            $success = false;
        }
        else
        {
            $counter = count($delete_attachments);
            $succes_attachment = 0;

            foreach ($delete_attachments as $attachment)
            {
                if ($attachment->delete())
                {
                    $succes_attachment ++;
                }
            }

            if ($counter == $succes_attachment)
            {
                $success = parent::delete();
            }
            else
            {
                $success = false;
            }

            if ($success && !$all_posts)
            {
                $forum_topic->remove_post();
            }
        }

        return $success;
    }

    /**
     * Removes the learning object with the given ID from this learning object's attachment list.
     *
     * @param int $id The ID of the learning object to remove from the attachment list.
     *
     * @return bool True if the attachment was removed, false if it did not exist.
     */
    public function detach_content_object($id)
    {
        return DataManager::detach_content_object($this, $id);
    }

    /**
     * Returns the default properties of this dataclass
     *
     * @return String[] - The property names.
     */
    public static function getDefaultPropertyNames(array $extendedPropertyNames = []): array
    {
        return parent::getDefaultPropertyNames(
            [
                self::PROPERTY_TITLE,
                self::PROPERTY_FORUM_TOPIC_ID,
                self::PROPERTY_CONTENT,
                self::PROPERTY_USER_ID,
                self::PROPERTY_REPLY_ON_POST_ID,
                self::PROPERTY_CREATION_DATE,
                self::PROPERTY_MODIFICATION_DATE
            ]
        );
    }

    /**
     * @return string
     */
    public static function getStorageUnitName(): string
    {
        return 'respository_forum_post';
    }

    /**
     * Returns the learning objects attached to this learning object.
     *
     * @param type $type
     *
     * @return array The learning objects.
     */
    public function get_attached_content_objects($type = self::ATTACHMENT_NORMAL)
    {
        $this->attachments[$type] = DataManager::retrieve_attached_object_from_forum_post($this->get_id());

        return $this->attachments[$type];
    }

    /**
     * Returns the content of this object.
     *
     * @return string The content of the post.
     */
    public function get_content()
    {
        return $this->getDefaultProperty(self::PROPERTY_CONTENT);
    }

    /**
     * Returns the date when this object was created, as returned by PHP's time() function.
     *
     * @return int The creation date.
     */
    public function get_creation_date()
    {
        return $this->getDefaultProperty(self::PROPERTY_CREATION_DATE);
    }

    /**
     * Returns the numeric identifier of the object's parent.
     *
     * @return int The identifier.
     */
    public function get_forum_topic_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_FORUM_TOPIC_ID);
    }

    /**
     * **************************************************************************************************************
     * Setters *
     * **************************************************************************************************************
     */

    /**
     * Returns the date when this object was last modified, as returned by PHP's time() function.
     *
     * @return int The modification time.
     */
    public function get_modification_date()
    {
        return $this->getDefaultProperty(self::PROPERTY_MODIFICATION_DATE);
    }

    /**
     * Returns a integer representation if its a reply on another post.
     *
     * @return int The id of the post its a reply on.
     */
    public function get_reply_on_post_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_REPLY_ON_POST_ID);
    }

    /**
     * Returns the title of this object
     *
     * @return string The title of the post
     */
    public function get_title()
    {
        return $this->getDefaultProperty(self::PROPERTY_TITLE);
    }

    /**
     * Returns a user object of the creator of this post
     *
     * @return User
     */
    public function get_user()
    {
        if (!isset($this->user))
        {
            $this->user = \Chamilo\Core\User\Storage\DataManager::retrieve_by_id(
                User::class, (int) $this->get_user_id()
            );
        }

        return $this->user;
    }

    /**
     * Returns the ID of this object's owner.
     *
     * @return int The ID.
     */
    public function get_user_id()
    {
        return $this->getDefaultProperty(self::PROPERTY_USER_ID);
    }

    /**
     * Sets the content of this object.
     *
     * @param string $content The content of the post.
     */
    public function set_content($content)
    {
        $this->setDefaultProperty(self::PROPERTY_CONTENT, $content);
    }

    /**
     * **************************************************************************************************************
     * CRUD *
     * **************************************************************************************************************
     */

    /**
     * Sets the date when this object was created.
     *
     * @param int $created The creation date of this post object.
     */
    public function set_creation_date($created)
    {
        $this->setDefaultProperty(self::PROPERTY_CREATION_DATE, $created);
    }

    /**
     * Sets the ID of this object's parent object.
     *
     * @param int $forum_topic_id The ID of the forum topic in which this post can be found.
     */
    public function set_forum_topic_id($forum_topic_id)
    {
        $this->setDefaultProperty(self::PROPERTY_FORUM_TOPIC_ID, $forum_topic_id);
    }

    /**
     * Sets the date when this object was modified.
     *
     * @param int $modified The modification date of this post object.
     */
    public function set_modification_date($modified)
    {
        $this->setDefaultProperty(self::PROPERTY_MODIFICATION_DATE, $modified);
    }

    /**
     * **************************************************************************************************************
     * Attachments *
     * **************************************************************************************************************
     */

    /**
     * Sets the ID of the reply on a post .
     *
     * @param int $forum_post_id
     */
    public function set_reply_on_post_id($forum_post_id)
    {
        $this->setDefaultProperty(self::PROPERTY_REPLY_ON_POST_ID, $forum_post_id);
    }

    /**
     * Sets the title of this post.
     *
     * @param string $title The title of this post object.
     */
    public function set_title($title)
    {
        $this->setDefaultProperty(self::PROPERTY_TITLE, $title);
    }

    /**
     * Sets the ID of this object's owner.
     *
     * @param int $user The user id.
     */
    public function set_user_id($user)
    {
        $this->setDefaultProperty(self::PROPERTY_USER_ID, $user);
    }

    /**
     * Update a post object and its content
     *
     * @param bool $request_from_topic
     *
     * @return bool returns true when post is updated succesfull.
     * @throws \Exception
     */
    public function update($request_from_topic = false): bool
    {
        if (!$request_from_topic)
        {

            $now = time();
            $this->set_modification_date($now);

            $forum_topic = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                ContentObject::class, $this->get_forum_topic_id()
            );
            $first_post = $forum_topic->is_first_post($this);
            if ($first_post)
            {
                $forum_topic->set_title($this->get_title());
                $forum_topic->set_description($this->get_content());
                $forum_topic->set_modification_date($now);
                $forum_topic->update(true);
            }

            $email_notificator = new PostEmailNotificator();
            $email_notificator->set_post($this);
            $email_notificator->set_action_user(
                \Chamilo\Core\User\Storage\DataManager::retrieve_by_id(
                    User::class, (int) Session::get_user_id()
                )
            );

            // $emailnotificator->set_action_user($this->get_user());

            if ($first_post)
            {
                $email_notificator->set_first_post_text(
                    Translation::get('PostFirstPostComment', null, 'Chamilo\Core\Repository\ContentObject\Forum')
                );
            }

            $text = Translation::get(
                'PostEditedEmailTitle', null, 'Chamilo\Core\Repository\ContentObject\Forum'
            );
            $email_notificator->set_action_title($text);

            $text = Translation::get(
                'PostEditedEmailBody', null, 'Chamilo\Core\Repository\ContentObject\Forum'
            );
            $email_notificator->set_action_body($text);

            $forum_topic->notify_subscribed_users_edited_post_topic($email_notificator);
            $email_notificator->send_emails();
        }

        return parent::update();
    }
}
