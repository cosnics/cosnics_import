<?php
namespace Chamilo\Core\Repository\ContentObject\Forum\Display\Component;

use Chamilo\Core\Repository\ContentObject\Forum\Display\Manager;
use Chamilo\Core\Repository\ContentObject\Forum\Storage\DataClass\Forum;
use Chamilo\Core\Repository\Storage\DataClass\ComplexContentObjectItem;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Core\Repository\Viewer\Architecture\Traits\ViewerTrait;
use Chamilo\Core\Repository\Viewer\ViewerInterface;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\Format\Structure\Breadcrumb;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;
use Exception;

/**
 * @package repository.lib.complex_display.forum.component
 */
class ForumSubforumCreatorComponent extends Manager implements ViewerInterface, DelegateComponent
{
    use ViewerTrait;

    public function run()
    {
        $forum = $this->getForum();

        if ($this->get_user()->isPlatformAdmin() || $this->get_user_id() == $forum->get_owner_id() ||
            $this->isForumManager($this->get_user()))
        {

            if (!$this->isAnyObjectSelectedInViewer())
            {

                $exclude = $this->retrieve_used_items($this->get_root_content_object()->get_id());

                $exclude[] = $this->get_root_content_object()->get_id();

                $this->getBreadcrumbTrail()->add(
                    new Breadcrumb(
                        $this->get_url(
                            [
                                self::PARAM_ACTION => self::ACTION_VIEW_FORUM,
                                self::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => null
                            ]
                        ), $this->get_root_content_object()->get_title()
                    )
                );

                if ($this->get_complex_content_object_item())
                {

                    $forums_with_key_cloi = [];
                    $forums_with_key_cloi = $this->retrieve_children_from_root_to_cloi(
                        $this->get_root_content_object()->get_id(), $this->get_complex_content_object_item()->get_id()
                    );

                    if ($forums_with_key_cloi)
                    {

                        foreach ($forums_with_key_cloi as $key => $value)
                        {

                            $this->getBreadcrumbTrail()->add(
                                new Breadcrumb(
                                    $this->get_url(
                                        [
                                            self::PARAM_ACTION => self::ACTION_VIEW_FORUM,
                                            self::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID => $key
                                        ]
                                    ), $value->get_title()
                                )
                            );
                        }
                    }
                    else
                    {
                        throw new Exception('The forum you requested has not been found');
                    }
                }

                $applicationConfiguration = new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this);

                $component = $this->getApplicationFactory()->getApplication(
                    \Chamilo\Core\Repository\Viewer\Manager::CONTEXT, $applicationConfiguration
                );
                $component->set_maximum_select(\Chamilo\Core\Repository\Viewer\Manager::SELECT_SINGLE);
                $component->set_parameter(self::PARAM_ACTION, self::ACTION_CREATE_SUBFORUM);
                $component->set_parameter(
                    self::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID, $this->get_complex_content_object_item_id()
                );
                $component->set_excluded_objects($exclude);

                return $component->run();
            }
            else
            {
                $cloi = ComplexContentObjectItem::factory(Forum::class);

                if ($this->get_complex_content_object_item())
                {
                    $cloi->set_parent($this->get_complex_content_object_item()->get_ref());
                }
                else
                {
                    $cloi->set_parent($this->get_root_content_object_id());
                }

                $cloi->set_ref($this->getObjectsSelectedInviewer());
                $cloi->set_user_id($this->get_user_id());
                $cloi->set_display_order(
                    DataManager::select_next_display_order($cloi->get_parent())
                );

                $success = $cloi->create();

                $this->my_redirect($success);
            }
        }
        else
        {
            throw new NotAllowedException();
        }
    }

    public function get_allowed_content_object_types()
    {
        return [Forum::class];
    }

    private function my_redirect($success)
    {
        $message = htmlentities(
            Translation::get(
                ($success ? 'ObjectCreated' : 'ObjectNotCreated'), ['OBJECT' => Translation::get('Subforum')],
                StringUtilities::LIBRARIES
            )
        );

        $params = [];
        $params[self::PARAM_ACTION] = self::ACTION_VIEW_FORUM;
        $params[self::PARAM_COMPLEX_CONTENT_OBJECT_ITEM_ID] = $this->get_complex_content_object_item_id();

        $this->redirectWithMessage($message, !$success, $params);
    }

    private function retrieve_used_items($object)
    {
        $items = [];
        $items = array_merge($items, $this->retrieve_used_items_parents($object));
        $complex_content_object_items = DataManager::retrieve_complex_content_object_items(
            ComplexContentObjectItem::class, new EqualityCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_PARENT
                ), new StaticConditionVariable($object), ComplexContentObjectItem::getStorageUnitName()
            )
        );
        foreach ($complex_content_object_items as $complex_content_object_item)
        {

            if ($complex_content_object_item->is_complex())
            {

                $items[] = $complex_content_object_item->get_ref();
                $items = array_merge($items, $this->retrieve_used_items($complex_content_object_item->get_ref()));
            }
        }

        return $items;
    }

    private function retrieve_used_items_parents($object_id)
    {
        $items = [];
        $items[] = $object_id;
        $complex_content_object_items_parent = DataManager::retrieve_complex_content_object_items(
            ComplexContentObjectItem::class, new EqualityCondition(
                new PropertyConditionVariable(
                    ComplexContentObjectItem::class, ComplexContentObjectItem::PROPERTY_REF
                ), new StaticConditionVariable($object_id)
            )
        );
        foreach ($complex_content_object_items_parent as $complex_content_object_item_parent)
        {
            if ($complex_content_object_item_parent->is_complex())
            {

                $items = array_merge(
                    $items, $this->retrieve_used_items_parents($complex_content_object_item_parent->get_parent())
                );
            }
        }

        return $items;
    }
}
