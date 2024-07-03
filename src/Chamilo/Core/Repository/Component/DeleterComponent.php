<?php
namespace Chamilo\Core\Repository\Component;

use Chamilo\Core\Repository\Integration\Chamilo\Core\Tracking\Storage\DataClass\Activity;
use Chamilo\Core\Repository\Manager;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Tracking\Storage\DataClass\Event;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbTrail;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Storage\Repository\DataManager;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package repository.lib.repository_manager.component
 */

/**
 * Repository manager component which provides functionality to delete a content object from the users repository.
 */
class DeleterComponent extends Manager
{

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $ids = $this->getRequest()->getFromRequestOrQuery(self::PARAM_CONTENT_OBJECT_ID);

        if (!empty($ids))
        {
            if (!is_array($ids))
            {
                $ids = [$ids];
            }

            $failures = 0;
            $delete_version = $this->getRequest()->query->get(self::PARAM_DELETE_VERSION);
            $permanent = $this->getRequest()->query->get(self::PARAM_DELETE_PERMANENTLY);
            $recycled = $this->getRequest()->query->get(self::PARAM_DELETE_RECYCLED);

            foreach ($ids as $object_id)
            {
                $object = DataManager::retrieve_by_id(ContentObject::class, $object_id);
                $unlinkAllowed = $this->getPublicationAggregator()->canContentObjectBeUnlinked($object);

                if ($this->getWorkspaceRightsService()->canDestroyContentObject(
                    $this->get_user(), $object, $this->getWorkspace()
                ))
                {
                    if ($delete_version)
                    {
                        if (\Chamilo\Core\Repository\Storage\DataManager::content_object_deletion_allowed(
                            $object, 'version'
                        ))
                        {
                            if (!$object->delete(true))
                            {
                                $failures ++;
                            }
                            else
                            {
                                Event::trigger(
                                    'Activity', Manager::CONTEXT, [
                                        Activity::PROPERTY_TYPE => Activity::ACTIVITY_DELETED,
                                        Activity::PROPERTY_USER_ID => $this->get_user_id(),
                                        Activity::PROPERTY_DATE => time(),
                                        Activity::PROPERTY_CONTENT_OBJECT_ID => $object->get_id(),
                                        Activity::PROPERTY_CONTENT => $object->get_title()
                                    ]
                                );
                            }
                        }
                        else
                        {
                            $failures ++;
                        }
                    }
                    else
                    {
                        if (\Chamilo\Core\Repository\Storage\DataManager::content_object_deletion_allowed($object))
                        {
                            if ($permanent)
                            {
                                $versions = $object->get_content_object_versions();
                                foreach ($versions as $version)
                                {
                                    if (!$version->delete())
                                    {
                                        $failures ++;
                                    }
                                    else
                                    {
                                        Event::trigger(
                                            'Activity', Manager::CONTEXT, [
                                                Activity::PROPERTY_TYPE => Activity::ACTIVITY_DELETED,
                                                Activity::PROPERTY_USER_ID => $this->get_user_id(),
                                                Activity::PROPERTY_DATE => time(),
                                                Activity::PROPERTY_CONTENT_OBJECT_ID => $version->get_id(),
                                                Activity::PROPERTY_CONTENT => $version->get_title()
                                            ]
                                        );
                                    }
                                }
                            }
                            elseif ($recycled)
                            {
                                if (!$unlinkAllowed)
                                {
                                    $failures ++;
                                    continue;
                                }

                                $versions = $object->get_content_object_versions();
                                foreach ($versions as $version)
                                {
                                    if (!$version->recycle())
                                    {
                                        $failures ++;
                                    }
                                    else
                                    {
                                        Event::trigger(
                                            'Activity', Manager::CONTEXT, [
                                                Activity::PROPERTY_TYPE => Activity::ACTIVITY_RECYCLE,
                                                Activity::PROPERTY_USER_ID => $this->get_user_id(),
                                                Activity::PROPERTY_DATE => time(),
                                                Activity::PROPERTY_CONTENT_OBJECT_ID => $version->get_id(),
                                                Activity::PROPERTY_CONTENT => $version->get_title()
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                        else
                        {
                            $failures ++;
                        }
                    }
                }
                else
                {
                    $failures ++;
                }
            }

            if ($delete_version)
            {
                if ($failures)
                {
                    $message = 'SelectedVersionNotDeleted';
                }
                else
                {
                    $message = 'SelectedVersionDeleted';
                }
            }
            else
            {
                if ($failures)
                {
                    if (count($ids) == 1)
                    {
                        $message = 'ObjectNot' . ($permanent ? 'Deleted' : 'MovedToRecycleBin');
                        $parameter = ['OBJECT' => Translation::get('ContentObject')];
                    }
                    elseif (count($ids) > $failures)
                    {
                        $message = 'SomeObjectsNot' . ($permanent ? 'Deleted' : 'MovedToRecycleBin');
                        $parameter = ['OBJECTS' => Translation::get('ContentObjects')];
                    }
                    else
                    {
                        $message = 'ObjectsNot' . ($permanent ? 'Deleted' : 'MovedToRecycleBin');
                        $parameter = ['OBJECTS' => Translation::get('ContentObjects')];
                    }
                }
                else
                {
                    if (count($ids) == 1)
                    {
                        $message = 'Object' . ($permanent ? 'Deleted' : 'MovedToRecycleBin');
                        $parameter = ['OBJECT' => Translation::get('ContentObject')];
                    }
                    else
                    {
                        $message = 'Objects' . ($permanent ? 'Deleted' : 'MovedToRecycleBin');
                        $parameter = ['OBJECTS' => Translation::get('ContentObjects')];
                    }
                }
            }

            $parameters = [];
            $parameters[Application::PARAM_ACTION] =
                ($permanent ? self::ACTION_BROWSE_RECYCLED_CONTENT_OBJECTS : self::ACTION_BROWSE_CONTENT_OBJECTS);

            $this->redirectWithMessage(
                Translation::get($message, $parameter, StringUtilities::LIBRARIES), $failures > 0, $parameters
            );
        }
        else
        {
            return $this->display_error_page(
                htmlentities(Translation::get('NoObjectSelected', null, StringUtilities::LIBRARIES))
            );
        }
    }

    public function addAdditionalBreadcrumbs(BreadcrumbTrail $breadcrumbtrail): void
    {
        $breadcrumbtrail->add(
            new Breadcrumb(
                $this->get_url([self::PARAM_ACTION => self::ACTION_BROWSE_CONTENT_OBJECTS]),
                Translation::get('BrowserComponent')
            )
        );
    }

    public function getAdditionalParameters(array $additionalParameters = []): array
    {
        $additionalParameters[] = self::PARAM_CONTENT_OBJECT_ID;
        $additionalParameters[] = self::PARAM_DELETE_VERSION;
        $additionalParameters[] = self::PARAM_DELETE_PERMANENTLY;
        $additionalParameters[] = self::PARAM_DELETE_RECYCLED;

        return parent::getAdditionalParameters($additionalParameters);
    }
}
