<?php
namespace Chamilo\Configuration\Package\Properties\Dependencies\Dependency;

use Chamilo\Configuration\Service\Consulter\RegistrationConsulter;
use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\Format\MessageLogger;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;
use Composer\Semver\Semver;
use Exception;

/**
 * @package Chamilo\Configuration\Package\Properties\Dependencies\Dependency
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class Dependency
{
    public const PROPERTY_ID = 'id';
    public const PROPERTY_VERSION = 'version';

    public const TYPE_EXTENSIONS = 'extensions';

    public const TYPE_PACKAGE = 'package';

    public const TYPE_SERVER = 'server';
    public const TYPE_SETTINGS = 'settings';

    protected $logger;

    private $id;

    /**
     * @var string
     */
    private $version;

    public function __construct()
    {
        $this->logger = MessageLogger::getInstance($this);
    }

    /**
     * Creates a dependency information string as html
     *
     * @return String
     */
    public function as_html()
    {
        $parameters = [];
        $parameters['ID'] = $this->get_id();
        $parameters['VERSION'] = $this->get_version();

        return Translation::get('Dependency', $parameters);
    }

    /**
     * Checks the dependency in the registration table of the administration
     *
     * @return bool
     */
    public function check()
    {
        $parameters = [];
        $parameters['REQUIREMENT'] = $this->as_html();

        /**
         * @var \Chamilo\Configuration\Service\Consulter\RegistrationConsulter $registrationConsulter
         */
        $registrationConsulter = DependencyInjectionContainerBuilder::getInstance()->createContainer()->get(
            RegistrationConsulter::class
        );

        $registration = $registrationConsulter->getRegistrationForContext($this->get_id());

        if (empty($registration))
        {
            $parameters['CURRENT'] = '--' . Translation::get('Nothing', [], StringUtilities::LIBRARIES) . '--';
            $this->logger->add_message(Translation::get('CurrentDependency', $parameters), MessageLogger::TYPE_ERROR);

            return false;
        }
        else
        {
            $target_version = Semver::satisfies(
                $registration[Registration::PROPERTY_VERSION], $this->get_version()
            );

            if (!$target_version)
            {
                $parameters['CURRENT'] = '--' . Translation::get('WrongVersion', [], StringUtilities::LIBRARIES) . '--';
                $this->logger->add_message(
                    Translation::get('CurrentDependency', $parameters), MessageLogger::TYPE_ERROR
                );

                return false;
            }
            else
            {
                if (!$registration[Registration::PROPERTY_STATUS])
                {
                    $parameters['CURRENT'] =
                        '--' . Translation::get('InactiveObject', [], StringUtilities::LIBRARIES) . '--';
                    $this->logger->add_message(
                        Translation::get('CurrentDependency', $parameters), MessageLogger::TYPE_ERROR
                    );

                    return false;
                }
                else
                {
                    $this->logger->add_message($parameters['REQUIREMENT']);

                    return true;
                }
            }
        }
    }

    public static function dom_node($dom_xpath, $dom_node)
    {
        $dependency = self::factory($dom_node->getAttribute('type'));
        $dependency->set_id(trim($dom_xpath->query('id', $dom_node)->item(0)->nodeValue));
        $version_node = $dom_xpath->query('version', $dom_node)->item(0);
        $version = new Version($version_node->nodeValue, $version_node->getAttribute('operator'));

        $dependency->set_version($version);

        return $dependency;
    }

    /**
     * @param string $type
     *
     * @return Dependency
     * @throws Exception
     */
    public static function factory($type)
    {
        $class =
            __NAMESPACE__ . '\\' . StringUtilities::getInstance()->createString($type)->upperCamelize() . 'Dependency';

        if (!class_exists($class))
        {
            throw new Exception(Translation::get('TypeDoesNotExist', ['type' => $type]));
        }

        return new $class();
    }

    public static function from_dom_node($dom_xpath, $dom_node)
    {
        $class = self::type($dom_node->getAttribute('type'));

        return $class::dom_node($dom_xpath, $dom_node);
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_logger()
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * @param string $context
     *
     * @return bool
     */
    public function needs($context)
    {
        return $this->get_id() == $context;
    }

    /**
     * @param string $id
     */
    public function set_id($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $version
     */
    public function set_version($version)
    {
        $this->version = $version;
    }

    public static function type($type)
    {
        return __NAMESPACE__ . '\\' . StringUtilities::getInstance()->createString($type)->upperCamelize() .
            'Dependency';
    }
}
