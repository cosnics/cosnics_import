<?php

namespace Chamilo\Core\Admin\Announcement\Integration\Chamilo\Core\Repository\Publication\Service;

use Chamilo\Core\Admin\Announcement\Integration\Chamilo\Core\Repository\Publication\Manager;
use Chamilo\Core\Admin\Announcement\Service\PublicationService;
use Chamilo\Core\Repository\ContentObject\SystemAnnouncement\Storage\DataClass\SystemAnnouncement;
use Chamilo\Core\Repository\Publication\Domain\PublicationTarget;
use Chamilo\Core\Repository\Publication\Service\PublicationAggregatorInterface;
use Chamilo\Core\Repository\Publication\Service\PublicationTargetRenderer;
use Chamilo\Core\Repository\Publication\Service\PublicationTargetService;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Format\Form\FormValidator;
use Chamilo\Libraries\Storage\Query\Condition\Condition;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Core\Admin\Announcement\Integration\Chamilo\Core\Repository\Publication\Service
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class PublicationAggregator implements PublicationAggregatorInterface
{
    /**
     * @var \Chamilo\Core\Admin\Announcement\Integration\Chamilo\Core\Repository\Publication\Service\PublicationAttributesGenerator
     */
    private $publicationAttributesGenerator;

    /**
     * @var \Chamilo\Core\Admin\Announcement\Service\PublicationService
     */
    private $publicationService;

    /**
     *
     * @var \Symfony\Component\Translation\Translator
     */
    private $translator;

    /**
     * @var \Chamilo\Core\Repository\Publication\Service\PublicationTargetService
     */
    private $publicationTargetService;

    /**
     * @var \Chamilo\Core\Repository\Publication\Service\PublicationTargetRenderer
     */
    private $publicationTargetRenderer;

    /**
     * @param \Chamilo\Core\Admin\Announcement\Service\PublicationService $publicationService
     * @param \Symfony\Component\Translation\Translator $translator
     * @param \Chamilo\Core\Admin\Announcement\Integration\Chamilo\Core\Repository\Publication\Service\PublicationAttributesGenerator $publicationAttributesGenerator
     * @param \Chamilo\Core\Repository\Publication\Service\PublicationTargetService $publicationTargetService
     * @param \Chamilo\Core\Repository\Publication\Service\PublicationTargetRenderer $publicationTargetRenderer
     */
    public function __construct(
        PublicationService $publicationService, Translator $translator,
        PublicationAttributesGenerator $publicationAttributesGenerator,
        PublicationTargetService $publicationTargetService, PublicationTargetRenderer $publicationTargetRenderer
    )
    {
        $this->publicationService = $publicationService;
        $this->translator = $translator;
        $this->publicationAttributesGenerator = $publicationAttributesGenerator;
        $this->publicationTargetService = $publicationTargetService;
        $this->publicationTargetRenderer = $publicationTargetRenderer;
    }

    /**
     * @return \Chamilo\Core\Admin\Announcement\Integration\Chamilo\Core\Repository\Publication\Service\PublicationAttributesGenerator
     */
    public function getPublicationAttributesGenerator(): PublicationAttributesGenerator
    {
        return $this->publicationAttributesGenerator;
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Integration\Chamilo\Core\Repository\Publication\Service\PublicationAttributesGenerator $publicationAttributesGenerator
     */
    public function setPublicationAttributesGenerator(PublicationAttributesGenerator $publicationAttributesGenerator
    ): void
    {
        $this->publicationAttributesGenerator = $publicationAttributesGenerator;
    }

    /**
     * @return \Chamilo\Core\Admin\Announcement\Service\PublicationService
     */
    public function getPublicationService(): PublicationService
    {
        return $this->publicationService;
    }

    /**
     * @param \Chamilo\Core\Admin\Announcement\Service\PublicationService $publicationService
     */
    public function setPublicationService(PublicationService $publicationService): void
    {
        $this->publicationService = $publicationService;
    }

    /**
     * @param \Chamilo\Libraries\Format\Form\FormValidator $form
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param \Chamilo\Core\User\Storage\DataClass\User $user
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addPublicationTargetsToFormForContentObjectAndUser(
        FormValidator $form, ContentObject $contentObject, User $user
    )
    {
        if ($user->is_platform_admin() && $contentObject instanceof SystemAnnouncement)
        {
            $publicationTargetKey = $this->getPublicationTargetService()->addPublicationTargetAndGetKey(
                new PublicationTarget(PublicationModifier::class)
            );
            $modifierServiceKey =
                $this->getPublicationTargetService()->addModifierServiceIdentifierAndGetKey(PublicationModifier::class);

            $publicationTargetName = $this->getTranslator()->trans('TypeName', [], 'Chamilo\Core\Admin\Announcement');

            $this->getPublicationTargetRenderer()->addSinglePublicationTargetToForm(
                $form, $modifierServiceKey, $publicationTargetKey, $publicationTargetName
            );
        }
    }

    /**
     * @param integer[] $contentObjectIdentifiers
     *
     * @return boolean
     */
    public function areContentObjectsPublished(array $contentObjectIdentifiers)
    {
        $publicationCount =
            $this->getPublicationService()->countPublicationsForContentObjectIdentifiers($contentObjectIdentifiers);

        return $publicationCount > 0;
    }

    /**
     * @param integer $contentObjectIdentifier
     *
     * @return boolean
     */
    public function canContentObjectBeEdited(int $contentObjectIdentifier)
    {
        return true;
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
        int $type, int $objectIdentifier, Condition $condition = null
    )
    {
        return $this->getPublicationService()->countPublicationsForTypeAndIdentifier(
            $type, $objectIdentifier, $condition
        );
    }

    /**
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     *
     * @return boolean
     */
    public function deleteContentObjectPublications(ContentObject $contentObject)
    {
        return $this->getPublicationService()->deletePublicationsForContentObject($contentObject);
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
        int $type, int $objectIdentifier, Condition $condition = null, int $count = null, int $offset = null,
        array $orderProperties = null
    )
    {
        $publicationRecords = $this->getPublicationService()->findPublicationRecordsForTypeAndIdentifier(
            $type, $objectIdentifier, $condition, $count, $offset, $orderProperties
        );

        $publicationAttributes = array();

        foreach ($publicationRecords as $publicationRecord)
        {
            $publicationAttributes[] =
                $this->getPublicationAttributesGenerator()->createAttributesFromRecord($publicationRecord);
        }

        return $publicationAttributes;
    }

    /**
     * @return \Chamilo\Core\Repository\Publication\Service\PublicationTargetRenderer
     */
    public function getPublicationTargetRenderer(): PublicationTargetRenderer
    {
        return $this->publicationTargetRenderer;
    }

    /**
     * @param \Chamilo\Core\Repository\Publication\Service\PublicationTargetRenderer $publicationTargetRenderer
     */
    public function setPublicationTargetRenderer(PublicationTargetRenderer $publicationTargetRenderer): void
    {
        $this->publicationTargetRenderer = $publicationTargetRenderer;
    }

    /**
     * @return \Chamilo\Core\Repository\Publication\Service\PublicationTargetService
     */
    public function getPublicationTargetService(): PublicationTargetService
    {
        return $this->publicationTargetService;
    }

    /**
     * @param \Chamilo\Core\Repository\Publication\Service\PublicationTargetService $publicationTargetService
     */
    public function setPublicationTargetService(PublicationTargetService $publicationTargetService): void
    {
        $this->publicationTargetService = $publicationTargetService;
    }

    /**
     * @return \Symfony\Component\Translation\Translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param integer $contentObjectIdentifier
     *
     * @return boolean
     */
    public function isContentObjectPublished(int $contentObjectIdentifier)
    {
        $publicationCount =
            $this->getPublicationService()->countPublicationsForContentObjectIdentifier($contentObjectIdentifier);

        return $publicationCount > 0;
    }
}