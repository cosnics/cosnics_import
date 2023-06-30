<?php
namespace Chamilo\Core\User\Component;

use Chamilo\Core\Tracking\Storage\DataClass\Event;
use Chamilo\Core\User\Form\UserExportForm;
use Chamilo\Core\User\Manager;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Core\User\Storage\DataManager;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\File\Export\Export;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;

/**
 * @package user.lib.user_manager.component
 */
class ExporterComponent extends Manager
{
    public const EXPORT_ACTION_ADD = 'A';
    public const EXPORT_ACTION_DEFAULT = self::EXPORT_ACTION_ADD;
    public const EXPORT_ACTION_DELETE = 'D';
    public const EXPORT_ACTION_UPDATE = 'U';

    /**
     * Runs this component and displays its output.
     */
    public function run()
    {
        $this->checkAuthorization(Manager::CONTEXT, 'ManageUsers');

        if (!$this->get_user()->isPlatformAdmin())
        {
            throw new NotAllowedException();
        }

        $form = new UserExportForm(UserExportForm::TYPE_EXPORT, $this->get_url());

        if ($form->validate())
        {
            $export = $form->exportValues();
            $file_type = $export['file_type'];
            $result = DataManager::retrieves(
                User::class, new DataClassRetrievesParameters()
            );
            foreach ($result as $user)
            {
                if ($file_type == 'pdf')
                {
                    $user_array = $this->prepare_for_pdf_export($user);
                }
                else
                {
                    $user_array = $this->prepare_for_other_export($user);
                }

                Event::trigger(
                    'Export', Manager::CONTEXT,
                    ['target_user_id' => $user->get_id(), 'action_user_id' => $this->get_user()->get_id()]
                );
                $data[] = $user_array;
            }
            $this->export_users($file_type, $data);
        }
        else
        {
            $html = [];

            $html[] = $this->render_header();
            $html[] = $form->toHtml();
            $html[] = $this->render_footer();

            return implode(PHP_EOL, $html);
        }
    }

    /**
     * @throws \Exception
     */
    public function export_users($file_type, $data)
    {
        $filename = 'export_users_' . date('Y-m-d_H-i-s');

        if ($file_type == 'pdf')
        {
            $data = [['key' => 'users', 'data' => $data]];
        }

        $this->getExporter($file_type)->sendtoBrowser($filename, $data);
    }

    protected function getExporter($fileType): Export
    {
        return $this->getService('Chamilo\Libraries\File\Export\'' . $fileType . '\'' . $fileType . 'Export');
    }

    protected function getPlatformLanguageForUser(User $user): string
    {
        return $this->getUserSettingService()->getSettingForUser($user, 'Chamilo\Core\Admin', 'platform_language');
    }

    public function prepare_for_other_export($user, $action = self::EXPORT_ACTION_DEFAULT)
    {
        // action => needed for import back into chamilo
        $user_array['action'] = $action;

        // $user_array[User::PROPERTY_USER_ID] = $user->get_id();
        $user_array[User::PROPERTY_LASTNAME] = $user->get_lastname();
        $user_array[User::PROPERTY_FIRSTNAME] = $user->get_firstname();
        $user_array[User::PROPERTY_USERNAME] = $user->get_username();
        $user_array[User::PROPERTY_EMAIL] = $user->get_email();

        $user_array['language'] = $this->getPlatformLanguageForUser($user);
        $user_array[User::PROPERTY_STATUS] = $user->get_status();
        $user_array[User::PROPERTY_ACTIVE] = $user->get_active();
        $user_array[User::PROPERTY_OFFICIAL_CODE] = $user->get_official_code();
        $user_array[User::PROPERTY_PHONE] = $user->get_phone();

        $act_date = $user->get_activation_date();

        $user_array[User::PROPERTY_ACTIVATION_DATE] = $act_date;

        $exp_date = $user->get_expiration_date();

        $user_array[User::PROPERTY_EXPIRATION_DATE] = $exp_date;

        $user_array[User::PROPERTY_AUTH_SOURCE] = $user->get_auth_source();

        return $user_array;
    }

    public function prepare_for_pdf_export($user)
    {
        $lastname_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_LASTNAME)->upperCamelize()
        );
        $firstname_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_FIRSTNAME)->upperCamelize()
        );
        $username_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_USERNAME)->upperCamelize()
        );
        $email_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_EMAIL)->upperCamelize()
        );
        $language_title = Translation::get(
            (string) StringUtilities::getInstance()->createString('language')->upperCamelize()
        );
        $status_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_STATUS)->upperCamelize()
        );
        $active_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_ACTIVE)->upperCamelize()
        );
        $official_code_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_OFFICIAL_CODE)->upperCamelize()
        );
        $phone_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_PHONE)->upperCamelize()
        );
        $activation_date_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_ACTIVATION_DATE)->upperCamelize()
        );
        $expiration_date_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_EXPIRATION_DATE)->upperCamelize()
        );
        $auth_source_title = Translation::get(
            (string) StringUtilities::getInstance()->createString(User::PROPERTY_AUTH_SOURCE)->upperCamelize()
        );

        $user_array[$lastname_title] = $user->get_lastname();
        $user_array[$firstname_title] = $user->get_firstname();
        $user_array[$username_title] = $user->get_username();
        $user_array[$email_title] = $user->get_email();
        $user_array[$language_title] = $this->getPlatformLanguageForUser($user);
        $user_array[$status_title] = $user->get_status();
        $user_array[$active_title] = $user->get_active();
        $user_array[$official_code_title] = $user->get_official_code();
        $user_array[$phone_title] = $user->get_phone();

        $act_date = $user->get_activation_date();

        $user_array[$activation_date_title] = $act_date;

        $exp_date = $user->get_expiration_date();

        $user_array[$expiration_date_title] = $exp_date;

        $user_array[$auth_source_title] = $user->get_auth_source();

        return $user_array;
    }
}
