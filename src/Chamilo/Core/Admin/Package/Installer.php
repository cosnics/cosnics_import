<?php
namespace Chamilo\Core\Admin\Package;

use Chamilo\Configuration\Storage\DataClass\Setting;
use Chamilo\Configuration\Storage\DataManager;
use Chamilo\Core\Admin\Announcement\Service\RightsService;
use Chamilo\Core\Admin\Announcement\Storage\DataClass\RightsLocation;
use Chamilo\Core\Admin\Manager;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\Storage\Cache\DataClassRepositoryCache;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package Chamilo\Core\Admin\Package
 */
class Installer extends \Chamilo\Configuration\Package\Action\Installer
{
    public const CONTEXT = Manager::CONTEXT;

    /**
     * Runs the install-script.
     */
    public function extra()
    {
        // Update the default settings to the database
        if (!$this->update_settings())
        {
            return false;
        }
        else
        {
            $this->add_message(
                self::TYPE_NORMAL, Translation::get(
                'ObjectsAdded', ['OBJECTS' => Translation::get('DefaultSettings')], StringUtilities::LIBRARIES
            )
            );
        }

        $location = $this->getRightsService()->createRoot(true);

        if (!$location instanceof RightsLocation)
        {
            return false;
        }
        else
        {
            $this->add_message(
                self::TYPE_NORMAL, Translation::get(
                'ObjectCreated', ['OBJECT' => Translation::get('RightsTree')], StringUtilities::LIBRARIES
            )
            );
        }

        return true;
    }

    /**
     * @return \Chamilo\Libraries\Storage\Cache\DataClassRepositoryCache
     */
    protected function getDataClassRepositoryCache()
    {
        return $this->getService(
            DataClassRepositoryCache::class
        );
    }

    /**
     * @return \Chamilo\Core\Admin\Announcement\Service\RightsService
     */
    protected function getRightsService()
    {
        return $this->getService(RightsService::class);
    }

    /**
     * @param string $serviceName
     *
     * @return object
     * @throws \Exception
     */
    protected function getService(string $serviceName)
    {
        return DependencyInjectionContainerBuilder::getInstance()->createContainer()->get(
            $serviceName
        );
    }

    public function update_settings()
    {
        $values = $this->get_form_values();

        $settings = [];
        $settings[] = ['Chamilo\Core\Admin', 'site_name', $values['site_name']];
        $settings[] = ['Chamilo\Core\Admin', 'platform_language', $values['platform_language']];
        $settings[] = ['Chamilo\Core\Admin', 'version', '1.0'];
        $settings[] = ['Chamilo\Core\Admin', 'theme', 'Aqua'];

        $settings[] = ['Chamilo\Core\Admin', 'institution', $values['organization_name']];
        $settings[] = ['Chamilo\Core\Admin', 'institution_url', $values['organization_url']];

        $settings[] = ['Chamilo\Core\Admin', 'show_administrator_data', 'true'];
        $settings[] = ['Chamilo\Core\Admin', 'administrator_firstname', $values['admin_firstname']];
        $settings[] = ['Chamilo\Core\Admin', 'administrator_surname', $values['admin_surname']];
        $settings[] = ['Chamilo\Core\Admin', 'administrator_email', $values['admin_email']];
        $settings[] = ['Chamilo\Core\Admin', 'administrator_telephone', $values['admin_phone']];

        $this->getDataClassRepositoryCache()->truncate(Setting::class);

        foreach ($settings as $setting)
        {
            $setting_object = DataManager::retrieve_setting_from_variable_name($setting[1], $setting[0]);
            $setting_object->set_application($setting[0]);
            $setting_object->set_variable($setting[1]);
            $setting_object->set_value($setting[2]);

            if (!$setting_object->update())
            {
                return false;
            }
        }

        return true;
    }
}
