<?php
namespace Chamilo\Core\Repository\Implementation\Flickr\Component;

use Chamilo\Core\Repository\Implementation\Flickr\Manager;
use Chamilo\Core\Repository\Instance\Storage\DataClass\Setting;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrieveParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Utilities\StringUtilities;

class LogoutComponent extends Manager
{

    public function run()
    {
        $external_user_id = $this->get_external_repository_manager_connector()->retrieve_user_id();
        if (! $this->get_external_repository_manager_connector()->logout())
        {
            $this->failed();
        }
        
        if (! \Chamilo\Core\Repository\Instance\Storage\DataManager::deactivate_instance_objects(
            $this->get_external_repository()->get_id(), 
            $this->get_user_id(), 
            $external_user_id))
        {
            $this->failed();
        }
        
        $conditions = [];
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Setting::class, Setting::PROPERTY_EXTERNAL_ID),
            new StaticConditionVariable($this->get_external_repository()->get_id()));
        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(Setting::class, Setting::PROPERTY_VARIABLE),
            new StaticConditionVariable('session_token'));
        
        $condition = new AndCondition($conditions);
        
        $setting = DataManager::retrieve(
            Setting::class,
            new DataClassRetrieveParameters($condition));
        $setting->set_value(null);
        
        $parameters = $this->get_parameters();
        $parameters[Manager::PARAM_ACTION] = Manager::ACTION_BROWSE_EXTERNAL_REPOSITORY;
        
        $setting->update();
        $this->redirectWithMessage(Translation::get('LogoutSuccessful', null, StringUtilities::LIBRARIES), false, $parameters);
    }

    public function failed()
    {
        $parameters = $this->get_parameters();
        $parameters[Manager::PARAM_ACTION] = Manager::ACTION_BROWSE_EXTERNAL_REPOSITORY;
        $this->redirectWithMessage(Translation::get('LogoutFailed', null, StringUtilities::LIBRARIES), true, $parameters);
    }
}
