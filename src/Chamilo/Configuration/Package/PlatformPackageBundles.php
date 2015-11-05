<?php
namespace Chamilo\Configuration\Package;

use Chamilo\Configuration\Package\Finder\PackageBundles;
use Chamilo\Libraries\Cache\Doctrine\Provider\FilesystemCache;
use Chamilo\Libraries\File\Path;

/**
 *
 * @package Chamilo\Configuration\Package
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class PlatformPackageBundles
{
    const MODE_ALL = 1;
    const MODE_INSTALLED = 2;
    const MODE_AVAILABLE = 3;

    /**
     *
     * @var int
     */
    private $mode;

    /**
     *
     * @var PackageList
     */
    private $package_list;

    /**
     *
     * @var string[]
     */
    private $types;

    /**
     *
     * @var string[]
     */
    private $packages;

    /**
     * A list of packages grouped by package type
     *
     * @var string[][]
     */
    private $type_packages;

    /**
     *
     * @var \configuration\package\PlatformPackageBundles
     */
    private static $instance;

    /**
     *
     * @param int $mode
     * @return \Chamilo\Configuration\Package\PlatformPackageBundles
     */
    public static function getInstance($mode = self :: MODE_ALL)
    {
        if (! isset(self :: $instance[$mode]))
        {
            self :: $instance[$mode] = new self($mode);
        }

        return self :: $instance[$mode];
    }

    /**
     *
     * @param int $mode
     */
    public function __construct($mode = self :: MODE_ALL)
    {
        $this->mode = $mode;
        $this->initialize();
    }

    /**
     *
     * @return int
     */
    public function get_mode()
    {
        return $this->mode;
    }

    /**
     *
     * @return \configuration\package\storage\data_class\PackageList
     */
    public function get_package_list()
    {
        return $this->package_list;
    }

    public function get_types()
    {
        if (! isset($this->types))
        {
            $this->types = array_keys($this->get_type_packages());
        }

        return $this->types;
    }

    public function get_packages()
    {
        if (! isset($this->packages))
        {
            $this->packages = $this->package_list->get_list(true);
        }

        return $this->packages;
    }

    /**
     *
     * @param boolean $recursive
     */
    public function get_type_packages()
    {
        if (! isset($this->type_packages))
        {
            $this->type_packages = $this->package_list->get_all_packages(true);
        }

        return $this->type_packages;
    }

    /**
     *
     * @param boolean $include_installed
     * @param boolean $reset
     * @return \configuration\package\storage\data_class\PackageList
     */
    public function initialize()
    {
        $cache = new FilesystemCache(Path :: getInstance()->getCachePath(__NAMESPACE__));
        $cacheId = 'package.list.' . $this->mode;

        if ($cache->contains($cacheId))
        {
            $this->package_list = $cache->fetch($cacheId);
        }
        else
        {
            $packageListBuilder = new PackageBundles(PackageList :: ROOT, $this->mode);
            $this->package_list = $packageListBuilder->getPackageList();

            $cache->save($cacheId, $this->package_list);
        }

        return $this->package_list;
    }

    public function reset()
    {
        $this->reset_mode($this->mode);
    }

    public function reset_mode($mode = self :: MODE_ALL)
    {
        $cache = new FilesystemCache(Path :: getInstance()->getCachePath(__NAMESPACE__));
        $cacheId = 'package.list.' . $mode;

        $cache->delete($cacheId);
    }

    public function reset_all()
    {
        foreach (self :: get_modes() as $mode)
        {
            $this->reset_mode($mode);
        }
    }

    /**
     *
     * @return int[]
     */
    public static function get_modes()
    {
        return array(self :: MODE_ALL, self :: MODE_AVAILABLE, self :: MODE_INSTALLED);
    }
}
