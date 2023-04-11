<?php
namespace Chamilo\Core\Repository\Publication\Service;

use Chamilo\Core\Repository\Publication\Manager;
use Chamilo\Libraries\Format\Form\FormValidator;
use Symfony\Component\Translation\Translator;

/**
 * @package Chamilo\Core\Repository\Publication\Service
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class PublicationTargetRenderer
{

    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @throws \QuickformException
     */
    public function addFooterToForm(FormValidator $form)
    {
        $tableFooter = [];

        $tableFooter[] = '</tbody>';
        $tableFooter[] = '</table>';

        $form->addElement('category');
        $form->addElement('html', implode(PHP_EOL, $tableFooter));
    }

    /**
     * @param string[] $columnNames
     *
     * @throws \QuickformException
     */
    public function addHeaderToForm(
        FormValidator $form, string $title, array $columnNames, bool $hasOnlyOneLocation = false
    )
    {
        $tableHeader = [];

        $tableHeader[] = '<table class="table table-striped table-bordered table-hover table-responsive">';
        $tableHeader[] = '<thead>';
        $tableHeader[] = '<tr>';

        $tableHeader[] = '<th class="cell-stat-x2">';

        if (!$hasOnlyOneLocation)
        {
            $tableHeader[] = '<div class="checkbox no-toggle-style">';
            $tableHeader[] = '<input class="select-all" type="checkbox" />';
            $tableHeader[] = '<label></label>';
            $tableHeader[] = '</div>';
        }

        $tableHeader[] = '</th>';

        foreach ($columnNames as $columnName)
        {
            $tableHeader[] = '<th>' . $columnName . '</th>';
        }

        $tableHeader[] = '</th>';

        $tableHeader[] = '</tr>';
        $tableHeader[] = '</thead>';
        $tableHeader[] = '<tbody>';

        $form->addElement('category', $title, 'publication-location');
        $form->addElement('html', implode(PHP_EOL, $tableHeader));
    }

    /**
     * @param string[] $targetNames
     *
     * @throws \QuickformException
     */
    public function addPublicationTargetToForm(
        FormValidator $form, string $publicationContext, string $targetKey, array $targetNames
    )
    {
        $renderer = $form->defaultRenderer();

        $group = [];

        $group[] = $form->createElement('checkbox', $this->getCheckboxName($publicationContext, $targetKey));

        foreach ($targetNames as $targetName)
        {
            $group[] = $form->createElement('static', null, null, $targetName);
        }

        $form->addGroup($group, 'target_' . $targetKey, null, '', false);

        $renderer->setElementTemplate('<tr>{element}</tr>', 'target_' . $targetKey);
        $renderer->setGroupElementTemplate('<td>{element}</td>', 'target_' . $targetKey);
    }

    /**
     * @throws \QuickformException
     */
    public function addSinglePublicationTargetToForm(
        FormValidator $form, string $publicationContext, string $targetKey, string $targetName
    )
    {
        $columnName = $this->getTranslator()->trans('Target', [], Manager::CONTEXT);

        $this->addHeaderToForm($form, $targetName, [$columnName], true);
        $this->addPublicationTargetToForm($form, $publicationContext, $targetKey, [$targetName]);
        $this->addFooterToForm($form);
    }

    protected function getCheckboxName(string $publicationContext, string $targetKey): string
    {
        return Manager::WIZARD_TARGET . '[' . $publicationContext . '][' . Manager::WIZARD_TARGET . '][' . $targetKey .
            ']';
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }
}