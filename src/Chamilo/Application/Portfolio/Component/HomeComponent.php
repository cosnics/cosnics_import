<?php
namespace Chamilo\Application\Portfolio\Component;

use Chamilo\Application\Portfolio\Manager;
use Chamilo\Application\Portfolio\Rights;
use Chamilo\Application\Portfolio\Service\RightsService;
use Chamilo\Application\Portfolio\Storage\DataClass\Feedback;
use Chamilo\Application\Portfolio\Storage\DataClass\Publication;
use Chamilo\Application\Portfolio\Storage\DataManager;
use Chamilo\Core\Repository\Common\Path\ComplexContentObjectPathNode;
use Chamilo\Core\Repository\ContentObject\Bookmark\Storage\DataClass\Bookmark;
use Chamilo\Core\Repository\ContentObject\Portfolio\Display\Menu;
use Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioBookmarkSupport;
use Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights;
use Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport;
use Chamilo\Core\Repository\ContentObject\Portfolio\Storage\DataClass\Portfolio;
use Chamilo\Core\Rights\Entity\PlatformGroupEntity;
use Chamilo\Core\Rights\Entity\UserEntity;
use Chamilo\Libraries\Architecture\Application\Application;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Architecture\Interfaces\DelegateComponent;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Format\Structure\ActionBar\Button;
use Chamilo\Libraries\Format\Structure\Glyph\FontAwesomeGlyph;
use Chamilo\Libraries\Platform\Session\Session;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;

/**
 *
 * @package Chamilo\Application\Portfolio\Component$HomeComponent
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class HomeComponent extends \Chamilo\Application\Portfolio\Manager implements PortfolioDisplaySupport, DelegateComponent,
    PortfolioComplexRights, PortfolioBookmarkSupport
{

    /**
     *
     * @var \Chamilo\Application\Portfolio\Storage\DataClass\Publication
     */
    private $publication;

    /**
     *
     * @var \Chamilo\Core\User\Storage\DataClass\\User
     */
    private $virtualUser;

    /**
     *
     * @var integer
     */
    private $rightsUserIdentifier;

    /**
     *
     * @see \Chamilo\Libraries\Architecture\Application\Application::run()
     */
    public function run()
    {
        $this->set_parameter(self::PARAM_USER_ID, $this->getCurrentUserId());

        $context = Portfolio::package() . '\Display';

        return $this->getApplicationFactory()->getApplication(
            $context,
            new ApplicationConfiguration($this->getRequest(), $this->get_user(), $this))->run();
    }

    /**
     *
     * @return \Chamilo\Application\Portfolio\Storage\DataClass\Publication
     */
    public function getPublication()
    {
        if (! isset($this->publication))
        {
            $this->publication = $this->getPublicationService()->getPublicationForUserIdentifier(
                $this->getCurrentUserId());

            if (! $this->publication instanceof Publication)
            {
                $this->publication = $this->getPublicationService()->createRootPortfolioAndPublicationForUser(
                    $this->getCurrentUser());
            }
        }

        return $this->publication;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::get_root_content_object()
     */
    public function get_root_content_object()
    {
        return $this->getPublication()->get_content_object();
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::retrieve_portfolio_feedbacks()
     */
    public function retrieve_portfolio_feedbacks(ComplexContentObjectPathNode $node, $count, $offset)
    {
        return $this->getFeedbackService()->findFeedbackForPublicationNodeUserIdentifierCountAndOffset(
            $this->getPublication(),
            $node,
            $this->getFeedbackUserIdentifier($node),
            $count,
            $offset);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::count_portfolio_feedbacks()
     */
    public function count_portfolio_feedbacks(ComplexContentObjectPathNode $node)
    {
        return $this->getFeedbackService()->countFeedbackForPublicationNodeAndUserIdentifier(
            $this->getPublication(),
            $node,
            $this->getFeedbackUserIdentifier($node));
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::retrieve_portfolio_feedback()
     */
    public function retrieve_portfolio_feedback($feedbackIdentifier)
    {
        return $this->getFeedbackService()->findFeedbackByIdentfier($feedbackIdentifier);
    }

    /**
     *
     * @param \Chamilo\Core\Repository\Common\Path\ComplexContentObjectPathNode $node
     * @return integer
     */
    private function getFeedbackUserIdentifier(ComplexContentObjectPathNode $node = null)
    {
        if (! $this->is_allowed_to_view_feedback($node))
        {
            return $this->get_rights_user_id();
        }

        return null;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::get_portfolio_feedback()
     */
    public function get_portfolio_feedback()
    {
        return $this->getFeedbackService()->getFeedbackInstanceForPublication($this->getPublication());
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::get_portfolio_tree_menu_url()
     */
    public function get_portfolio_tree_menu_url()
    {
        return Path::getInstance()->getBasePath(true) . 'index.php?' . Application::PARAM_CONTEXT . '=' .
             Manager::context() . '&' . Application::PARAM_ACTION . '=' . Manager::ACTION_HOME . '&' .
             Manager::PARAM_USER_ID . '=' . $this->getCurrentUserId() . '&' .
             \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::PARAM_ACTION . '=' .
             \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::ACTION_VIEW_COMPLEX_CONTENT_OBJECT . '&' .
             \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::PARAM_STEP . '=' . Menu::NODE_PLACEHOLDER;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::is_allowed_to_update_feedback()
     */
    public function is_allowed_to_update_feedback($feedback)
    {
        return $this->getRightsService()->isFeedbackOwner($feedback, $this->get_rights_user_id());
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::is_allowed_to_delete_feedback()
     */
    public function is_allowed_to_delete_feedback($feedback)
    {
        return $this->getRightsService()->isFeedbackOwner($feedback, $this->get_rights_user_id());
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::is_allowed_to_create_feedback()
     */
    public function is_allowed_to_create_feedback(ComplexContentObjectPathNode $node = null)
    {
        return $this->getRightsService()->isAllowedToCreateFeedback(
            $this->getPublication(),
            $this->get_rights_user_id(),
            $node);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::is_allowed_to_view_feedback()
     */
    public function is_allowed_to_view_feedback(ComplexContentObjectPathNode $node = null)
    {
        return $this->getRightsService()->isAllowedToViewFeedback(
            $this->getPublication(),
            $this->get_rights_user_id(),
            $node);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::is_allowed_to_edit_content_object()
     */
    public function is_allowed_to_edit_content_object(ComplexContentObjectPathNode $node = null)
    {
        $isPublisher = $this->getPublication()->get_publisher_id() == $this->get_rights_user_id();

        $contextEditRight = $this->getRightsService()->is_allowed(
            RightsService::EDIT_RIGHT,
            $this->get_location($node),
            $this->get_rights_user_id());

        $portfolioEditRight = $this->getWorkspaceRightsService()->canEditContentObject(
            $this->get_user(),
            $this->get_root_content_object());

        if ($node instanceof ComplexContentObjectPathNode)
        {
            $contentObjectEditRight = $this->getWorkspaceRightsService()->canEditContentObject(
                $this->get_user(),
                $node->get_content_object());
        }
        else
        {
            $contentObjectEditRight = false;
        }

        return $isPublisher || $contextEditRight || $portfolioEditRight || $contentObjectEditRight;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::is_allowed_to_view_content_object()
     */
    public function is_allowed_to_view_content_object(ComplexContentObjectPathNode $node = null)
    {
        $is_publisher = $this->get_rights_user_id() == $this->getPublication()->get_publisher_id();

        $has_right = $this->getRightsService()->is_allowed(
            RightsService::VIEW_RIGHT,
            $this->get_location($node),
            $this->get_rights_user_id());

        return $is_publisher || $has_right;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioBookmarkSupport::get_portfolio_bookmark()
     */
    public function get_portfolio_bookmark($current_step)
    {
        $portfolioOwner = $this->get_root_content_object()->get_owner();

        $content_object = new Bookmark();
        $content_object->set_title(Translation::get('BookmarkTitle', array('NAME' => $portfolioOwner->get_fullname())));
        $content_object->set_description(
            Translation::get('BookmarkDescription', array('NAME' => $portfolioOwner->get_fullname())));
        $content_object->set_application(__NAMESPACE__);
        $content_object->set_url(
            $this->get_url(
                array(
                    \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::PARAM_ACTION => \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::ACTION_VIEW_COMPLEX_CONTENT_OBJECT,
                    \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::PARAM_STEP => $current_step)));
        $content_object->set_owner_id($this->get_user_id());

        return $content_object;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::get_portfolio_additional_actions()
     */
    public function get_portfolio_additional_actions()
    {
        return array(
            new Button(
                Translation::get('BrowserComponent'),
                new FontAwesomeGlyph('search'),
                $this->get_url(
                    array(self::PARAM_ACTION => self::ACTION_BROWSE),
                    array(
                        self::PARAM_USER_ID,
                        \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::PARAM_ACTION,
                        \Chamilo\Core\Repository\ContentObject\Portfolio\Display\Manager::PARAM_STEP))));
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::get_locations()
     */
    public function get_locations($nodes)
    {
        $locations = array();

        foreach ($nodes as $node)
        {
            $locations[] = $this->get_location($node);
        }

        return $locations;
    }

    /**
     *
     * @param ComplexContentObjectPathNode $node
     *
     * @return \application\portfolio\RightsLocation
     */
    public function get_location(ComplexContentObjectPathNode $node = null)
    {
        return $this->getRightsService()->get_location($node, $this->getPublication()->get_id());
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::get_available_rights()
     */
    public function get_available_rights()
    {
        return $this->getRightsService()->getAvailableRights();
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::get_entities()
     */
    public function get_entities()
    {
        $entities = array();
        $entities[UserEntity::ENTITY_TYPE] = new UserEntity();
        $entities[PlatformGroupEntity::ENTITY_TYPE] = new PlatformGroupEntity();

        return $entities;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::get_selected_entities()
     */
    public function get_selected_entities(ComplexContentObjectPathNode $node)
    {
        $location = $this->get_location($node);

        return $this->getRightsService()->findRightsLocationEntityRightsForLocationAndRights(
            $location,
            $this->get_available_rights());
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::invert_location_entity_right()
     */
    public function invert_location_entity_right($rightId, $entityId, $entityType, $locationId)
    {
        return $this->getRightsService()->invertLocationEntityRight(
            $rightId,
            $entityId,
            $entityType,
            $locationId,
            $this->getPublication()->getId());
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::is_allowed_to_set_content_object_rights()
     */
    public function is_allowed_to_set_content_object_rights()
    {
        return $this->getRightsService()->isAllowedToSetContentObjectRights($this->getUser(), $this->getPublication());
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::retrieve_portfolio_possible_view_users()
     */
    public function retrieve_portfolio_possible_view_users($condition, $count, $offset, $orderProperty)
    {
        return $this->getUserService()->findUsers($condition, $offset, $count, $orderProperty);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::count_portfolio_possible_view_users()
     */
    public function count_portfolio_possible_view_users($condition)
    {
        return $this->getUserService()->countUsers($condition);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::set_portfolio_virtual_user_id()
     */
    public function set_portfolio_virtual_user_id($virtualUserIdentifier)
    {
        return $this->getRightsService()->setVirtualUser($virtualUserIdentifier);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::clear_virtual_user_id()
     */
    public function clear_virtual_user_id()
    {
        return $this->getRightsService()->clearVirtualUser();
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioComplexRights::get_portfolio_virtual_user()
     */
    public function get_portfolio_virtual_user()
    {
        return $this->getRightsService()->getVirtualUser();
    }

    /**
     * Get the user_id that should be used for rights checks
     *
     * @return int
     */
    private function get_rights_user_id()
    {
        if (! isset($this->rightsUserIdentifier))
        {
            if ($this instanceof PortfolioComplexRights && $this->is_allowed_to_set_content_object_rights())
            {
                $virtual_user = $this->get_portfolio_virtual_user();

                if ($virtual_user instanceof \Chamilo\Core\User\Storage\DataClass\User)
                {
                    $this->rightsUserIdentifier = $virtual_user->get_id();
                }
                else
                {
                    $this->rightsUserIdentifier = $this->get_user_id();
                }
            }
            else
            {
                $this->rightsUserIdentifier = $this->get_user_id();
            }
        }

        return $this->rightsUserIdentifier;
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::is_own_portfolio()
     */
    public function is_own_portfolio()
    {
        return $this->get_user_id() == $this->getPublication()->get_publisher_id();
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::retrieve_portfolio_notification()
     */
    public function retrieve_portfolio_notification(
        \Chamilo\Core\Repository\Common\Path\ComplexContentObjectPathNode $node)
    {
        return $this->getNotificationService()->findPortfolioNotificationForPublicationUserAndNode(
            $this->getPublication(),
            $this->getUser(),
            $node);
    }

    /**
     * Retrieves the portfolio notifications for the given node
     *
     * @param ComplexContentObjectPathNode $node
     * @return \Chamilo\Libraries\Storage\ResultSet\ResultSet
     */
    public function retrievePortfolioNotifications(
        \Chamilo\Core\Repository\Common\Path\ComplexContentObjectPathNode $node)
    {
        return $this->getNotificationService()->findPortfolioNotificationsForPublicationAndNode(
            $this->getPublication(),
            $node);
    }

    /**
     *
     * @see \Chamilo\Core\Repository\ContentObject\Portfolio\Display\PortfolioDisplaySupport::get_portfolio_notification()
     */
    public function get_portfolio_notification()
    {
        return $this->getNotificationService()->getNotificationInstanceForPublication($this->getPublication());
    }
}