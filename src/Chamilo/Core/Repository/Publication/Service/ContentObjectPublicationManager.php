<?php

namespace Chamilo\Core\Repository\Publication\Service;

use Chamilo\Core\Repository\Publication\Storage\DataClass\Attributes;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Publication\PublicationInterface;
use Chamilo\Libraries\Storage\Iterator\DataClassIterator;
use Chamilo\Libraries\Storage\Query\Condition\Condition;

/**
 * Manages the communication between the repository and the publications of content objects. This service is used
 * to determine whether or not a content object can be deleted, can be edited, ...
 *
 * @package Chamilo\Core\Repository\Service
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class ContentObjectPublicationManager implements ContentObjectPublicationManagerInterface
{
    /**
     * @var \Chamilo\Core\Repository\Publication\Service\ContentObjectPublicationManagerInterface[]
     */
    protected $contentObjectPublicationManagers;

    /**
     * ContentObjectPublicationManager constructor.
     */
    public function __construct()
    {
        $this->contentObjectPublicationManagers = [];
    }

    /**
     * @param \Chamilo\Core\Repository\Publication\Service\ContentObjectPublicationManagerInterface $contentObjectPublicationManager
     */
    public function addContentObjectPublicationManager(
        ContentObjectPublicationManagerInterface $contentObjectPublicationManager
    )
    {
        $this->contentObjectPublicationManagers[] = $contentObjectPublicationManager;
    }

    /**
     * Returns whether or not a content object can be unlinked
     *
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     *
     * @return bool
     */
    public function canContentObjectBeUnlinked(ContentObject $contentObject)
    {
        foreach ($this->contentObjectPublicationManagers as $contentObjectPublicationManager)
        {
            if (!$contentObjectPublicationManager->canContentObjectBeUnlinked($contentObject))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param integer $type
     * @param integer $objectIdentifier
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     *
     * @return integer
     */
    public function countPublicationAttributes(
        int $type = PublicationInterface::ATTRIBUTES_TYPE_OBJECT, int $objectIdentifier, Condition $condition = null
    )
    {
        $count = 0;

        foreach ($this->contentObjectPublicationManagers as $contentObjectPublicationManager)
        {
            $count += $contentObjectPublicationManager->countPublicationAttributes(
                $type, $objectIdentifier, $condition
            );
        }

        return $count;
    }

    /**
     * @param integer $type
     * @param integer $objectIdentifier
     * @param \Chamilo\Libraries\Storage\Query\Condition\Condition $condition
     * @param integer $count
     * @param integer $offset
     * @param \Chamilo\Libraries\Storage\Query\OrderBy[] $orderProperties
     *
     * @return \Chamilo\Core\Repository\Publication\Storage\DataClass\Attributes[]
     */
    public function getContentObjectPublicationsAttributes(
        int $type = PublicationInterface::ATTRIBUTES_TYPE_OBJECT, int $objectIdentifier, Condition $condition = null,
        int $count = null, int $offset = null, array $orderProperties = null
    )
    {
        $publicationAttributes = array();

        foreach ($this->contentObjectPublicationManagers as $contentObjectPublicationManager)
        {
            $applicationAttributes = $contentObjectPublicationManager->getContentObjectPublicationsAttributes(
                $type, $objectIdentifier, $condition, $count, $offset, $orderProperties
            );

            if (!is_null($applicationAttributes) && count($applicationAttributes) > 0)
            {
                $publicationAttributes = array_merge($publicationAttributes, $applicationAttributes);
            }
        }

        // Sort the publication attributes
        if (count($orderProperties) > 0)
        {
            $orderProperty = $orderProperties[0];

            usort(
                $publicationAttributes,
                function (Attributes $publicationAttributeLeft, Attributes $publicationAttributeRight) use (
                    $orderProperty
                ) {
                    return strcasecmp(
                        $publicationAttributeLeft->get_default_property(
                            $orderProperty->getConditionVariable()->get_property()
                        ),
                        $publicationAttributeRight->get_default_property(
                            $orderProperty->getConditionVariable()->get_property()
                        )
                    );
                }
            );

            if ($orderProperty->getDirection() == SORT_DESC)
            {
                $publicationAttributes = array_reverse($publicationAttributes);
            }
        }

        if (isset($offset))
        {
            if (isset($count))
            {
                $publicationAttributes = array_splice($publicationAttributes, $offset, $count);
            }
            else
            {
                $publicationAttributes = array_splice($publicationAttributes, $offset);
            }
        }

        return $publicationAttributes;
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     *
     * @return boolean
     */
    public function deleteContentObjectPublications(ContentObject $contentObject)
    {
        foreach ($this->contentObjectPublicationManagers as $contentObjectPublicationManager)
        {
            if (!$contentObjectPublicationManager->deleteContentObjectPublications($contentObject))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param integer $contentObjectIdentifier
     *
     * @return boolean
     */
    public function isContentObjectPublished(int $contentObjectIdentifier)
    {
        foreach ($this->contentObjectPublicationManagers as $contentObjectPublicationManager)
        {
            if ($contentObjectPublicationManager->isContentObjectPublished($contentObjectIdentifier))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param integer[] $contentObjectIdentifiers
     *
     * @return boolean
     */
    public function areContentObjectsPublished(array $contentObjectIdentifiers)
    {
        foreach ($this->contentObjectPublicationManagers as $contentObjectPublicationManager)
        {
            if ($contentObjectPublicationManager->areContentObjectsPublished($contentObjectIdentifiers))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param integer $contentObjectIdentifier
     *
     * @return boolean
     */
    public function canContentObjectBeEdited(int $contentObjectIdentifier)
    {
        foreach ($this->contentObjectPublicationManagers as $contentObjectPublicationManager)
        {
            if (!$contentObjectPublicationManager->canContentObjectBeEdited($contentObjectIdentifier))
            {
                return false;
            }
        }

        return true;
    }
}