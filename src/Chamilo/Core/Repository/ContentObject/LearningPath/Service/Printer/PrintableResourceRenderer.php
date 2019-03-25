<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Service\Printer;

use Chamilo\Core\Repository\ContentObject\File\Storage\DataClass\File;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Symfony\Component\Translation\Translator;

/**
 * Class PrintableResourceRenderer
 * @package Chamilo\Core\Repository\ContentObject\LearningPath\Service\Printer
 */
class PrintableResourceRenderer
{
    /**
     *
     * @var \DOMXPath
     */
    private $domXpath;

    /**
     *
     * @var \DOMDocument
     */
    private $domDocument;

    /**
     * @var \Chamilo\Core\Repository\Common\ContentObjectResourceParser
     */
    protected $resourceParser;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    protected $translator;

    /**
     * PrintableResourceRenderer constructor.
     *
     * @param \Chamilo\Core\Repository\Common\ContentObjectResourceParser $resourceParser
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function __construct(\Chamilo\Core\Repository\Common\ContentObjectResourceParser $resourceParser, Translator $translator)
    {
        $this->resourceParser = $resourceParser;
        $this->translator = $translator;
    }

    /**
     * @param string $htmlContent
     *
     * @return string
     */
    public function renderResourcesInContent(string $htmlContent)
    {
        $this->domDocument = $this->resourceParser->getDomDocument($htmlContent);
        $this->domXpath = $this->resourceParser->getDomXPath($this->domDocument);

        $this->processOldResources();
        $this->processContentObjectPlaceholders();

        return $this->domDocument->saveHTML();
    }

    /**
     * Processes the new placeholders for content object using the resource parser
     */
    protected function processContentObjectPlaceholders()
    {
        $placeholders = $this->resourceParser->getContentObjectDomElements($this->domXpath);
        foreach ($placeholders as $placeholder)
        {
            /**
             * @var \DOMElement $placeholder
             */
            $contentObject = $this->resourceParser->getContentObjectFromDomElement($placeholder);
            $this->handleContentObject($placeholder, $contentObject);
        }
    }

    /**
     * Processes the old resource tags (this code needs to be here for legacy html content)
     */
    protected function processOldResources()
    {
        $resources = $this->domXpath->query('//resource');
        foreach ($resources as $resource)
        {
            /** @var \DOMElement $resource * */
            $source = $resource->getAttribute('source');

            try
            {
                /** @var ContentObject $contentObject */
                $contentObject = \Chamilo\Core\Repository\Storage\DataManager::retrieve_by_id(
                    ContentObject::class_name(),
                    $source
                );

                $this->handleContentObject($resource, $contentObject);
            }
            catch (\Exception $exception)
            {
                continue;
            }
        }
    }

    /**
     * Parses an element for a single content object. Determines whether or not it is printable or should be replaced
     * by a warning
     *
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     * @param \DOMElement $contentObjectElement
     */
    protected function handleContentObject(\DOMElement $contentObjectElement, ContentObject $contentObject)
    {
        if (!$contentObject instanceof ContentObject)
        {
            return;
        }

        if ($contentObject instanceof File && $contentObject->is_image())
        {
            return;
        }

        $element = $this->getResourceNotificationElement($contentObject);
        $element = $this->domDocument->importNode($element, true);

        $contentObjectElement->parentNode->insertBefore($element, $contentObjectElement);
        $contentObjectElement->parentNode->removeChild($contentObjectElement);
    }

    /**
     * Creates an alert box with a notification text that the resource is not printable and the website should be
     * visited to view the full version.
     *
     * @param \Chamilo\Core\Repository\Storage\DataClass\ContentObject $contentObject
     *
     * @return \DOMElement
     */
    protected function getResourceNotificationElement(ContentObject $contentObject)
    {
        if ($contentObject instanceof File && $contentObject->is_image())
        {
            throw new \InvalidArgumentException(
                'The given content object is an image and should not be replaced in a printable format'
            );
        }

        $domElement = $this->domDocument->createElement(
            'div',
            $this->translator->trans(
                'CanNotPrintResourceNotification', [], 'Chamilo\Core\Repository\ContentObject\LearningPath\Display'
            )
        );

        $classAttribute = $this->domDocument->createAttribute('class');
        $classAttribute->value = 'alert alert-info';

        $domElement->appendChild($classAttribute);

        return $domElement;
    }

}