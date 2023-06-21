<?php
namespace Chamilo\Core\Repository\Quota\Rights\Form;

use Chamilo\Core\Group\Integration\Chamilo\Libraries\Rights\Service\GroupEntityProvider;
use Chamilo\Core\Repository\Quota\Rights\Service\RightsService;
use Chamilo\Libraries\Format\Form\Element\AdvancedElementFinder\AdvancedElementFinderElementTypes;
use Chamilo\Libraries\Rights\Form\RightsForm;

/**
 * @package Chamilo\Core\Repository\Quota\Rights\Form
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class RightsGroupForm extends RightsForm
{
    public const PROPERTY_ACCESS = 'access';
    public const PROPERTY_TARGET_GROUPS = 'target_groups';

    /**
     * @throws \QuickformException
     */
    protected function buildFormFooter()
    {
        $this->addElement(
            'category', $this->getTranslator()->trans('RightsGroupTargets', [], 'Chamilo\Core\Repository\Quota\Rights')
        );
        $this->addElement('html', '<div class="right">');

        $types = new AdvancedElementFinderElementTypes();
        $types->add_element_type(
            $this->getEntityByType(GroupEntityProvider::ENTITY_TYPE)->getEntityElementFinderType()
        );
        $this->addElement('advanced_element_finder', self::PROPERTY_TARGET_GROUPS, null, $types);

        $this->addElement('html', '</div></div>');

        parent::buildFormFooter();
    }

    /**
     * @param string[] $defaultValues
     * @param string[] $filter
     *
     * @throws \Exception
     */
    public function setDefaults($defaultValues = null, $filter = null)
    {
        $defaultValues[self::PROPERTY_RIGHT_OPTION][RightsService::VIEW_RIGHT] = self::RIGHT_OPTION_SELECT;

        parent::setDefaults($defaultValues, $filter);
    }
}
