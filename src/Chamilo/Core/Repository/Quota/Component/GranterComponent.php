<?php
namespace Chamilo\Core\Repository\Quota\Component;

use Chamilo\Configuration\Configuration;
use Chamilo\Core\Repository\Quota\Calculator;
use Chamilo\Core\Repository\Quota\Manager;
use Chamilo\Core\Repository\Quota\Storage\DataClass\Request;
use Chamilo\Core\Repository\Quota\Storage\DataManager;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\File\Filesystem;
use Chamilo\Libraries\Mail\Mailer\MailerFactory;
use Chamilo\Libraries\Mail\ValueObject\Mail;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;
use Exception;

/**
 * @package Chamilo\Core\Repository\Quota\Component
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class GranterComponent extends Manager
{

    public function run()
    {
        if (!$this->getRightsService()->canUserViewQuotaRequests($this->getUser()))
        {
            throw new NotAllowedException();
        }

        $ids = $this->getRequest()->get(self::PARAM_REQUEST_ID);
        $failures = 0;

        if (!empty($ids))
        {
            if (!is_array($ids))
            {
                $ids = array($ids);
            }

            foreach ($ids as $id)
            {
                $request = DataManager::retrieve_by_id(Request::class, (int) $id);

                if (!$this->getRightsService()->isUserIdentifierTargetForUser(
                    $request->get_user_id(), $this->getUser()
                ))
                {
                    $failures ++;
                }
                else
                {

                    if ($request->was_granted())
                    {
                        $failures ++;
                    }
                    else
                    {
                        $calculator = new Calculator($request->get_user());

                        if ($calculator->getAvailableAllocatedDiskSpace() > $request->get_quota())
                        {
                            $user = $request->get_user();
                            $user->set_disk_quota($user->get_disk_quota() + $request->get_quota());

                            if ($user->update())
                            {
                                $request->set_decision(Request::DECISION_GRANTED);
                                $request->set_decision_date(time());

                                if (!$request->update())
                                {
                                    $failures ++;
                                }
                                else
                                {
                                    $this->send_mail($request, $calculator);
                                }
                            }
                            else
                            {
                                $failures ++;
                            }
                        }
                        else
                        {
                            $failures ++;
                        }
                    }
                }
            }

            if ($failures)
            {
                if (count($ids) == 1)
                {
                    $message = 'ObjectNotGranted';
                    $parameter = array('OBJECT' => Translation::get('Request'));
                }
                elseif (count($ids) > $failures)
                {
                    $message = 'SomeObjectsNotGranted';
                    $parameter = array('OBJECTS' => Translation::get('Requests'));
                }
                else
                {
                    $message = 'ObjectsNotGranted';
                    $parameter = array('OBJECTS' => Translation::get('Requests'));
                }
            }
            else
            {
                if (count($ids) == 1)
                {
                    $message = 'ObjectGranted';
                    $parameter = array('OBJECT' => Translation::get('Request'));
                }
                else
                {
                    $message = 'ObjectsGranted';
                    $parameter = array('OBJECTS' => Translation::get('Requests'));
                }
            }

            $this->redirectWithMessage(
                Translation::get($message, $parameter, StringUtilities::LIBRARIES), (bool) $failures,
                array(self::PARAM_ACTION => self::ACTION_BROWSE)
            );
        }
        else
        {
            return $this->display_error_page(
                htmlentities(
                    Translation::get(
                        'NoObjectSelected', array('OBJECT' => Translation::get('Request')), StringUtilities::LIBRARIES
                    )
                )
            );
        }
    }

    public function send_mail(Request $request, Calculator $calculator)
    {
        set_time_limit(3600);

        $recipient = $request->get_user();

        $title = Translation::get(
            'RequestGrantedMailTitle', array(
                'PLATFORM' => Configuration::getInstance()->get_setting(array('Chamilo\Core\Admin', 'site_name')),
                'ADDED_QUOTA' => Filesystem::format_file_size($request->get_quota())
            )
        );

        $body = Translation::get(
            'RequestGrantedMailBody', array(
                'USER' => $recipient->get_fullname(),
                'PLATFORM' => Configuration::getInstance()->get_setting(array('Chamilo\Core\Admin', 'site_name')),
                'ADDED_QUOTA' => Filesystem::format_file_size($request->get_quota()),
                'QUOTA' => Filesystem::format_file_size($calculator->getMaximumUserDiskQuota())
            )
        );

        $mail = new Mail($title, $body, $recipient->get_email());

        $mailerFactory = new MailerFactory(Configuration::getInstance());
        $mailer = $mailerFactory->getActiveMailer();

        try
        {
            $mailer->sendMail($mail);
        }
        catch (Exception $ex)
        {
        }
    }
}
