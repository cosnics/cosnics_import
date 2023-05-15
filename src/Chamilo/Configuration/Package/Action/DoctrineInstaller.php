<?php
namespace Chamilo\Configuration\Package\Action;

use Chamilo\Libraries\Architecture\ClassnameUtilities;
use Chamilo\Libraries\File\Filesystem;
use Chamilo\Libraries\File\Path;
use Chamilo\Libraries\Storage\DataManager\Doctrine\ORM\PackagesMappingDriverFactory;
use Doctrine\ORM\EntityManager;
use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\DependencyInjection\ExtensionFinder\DirectoryContainerExtensionFinder;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;

/**
 * This installer can be used to create the storage structure with doctrine
 *
 * @author Sven Vanpoucke
 * @author Directie Onderwijs - Digitaal Leren
 */
abstract class DoctrineInstaller extends Installer
{
    /**
     * Scans for the available storage units and creates them
     *
     * @return boolean
     */
    public function install_storage_units()
    {
        $cacheDir = Path::getInstance()->getCachePath('Hogent\Libraries\DependencyInjection');
        $cacheFile = $cacheDir . 'InstallDependencyInjection.php';

        if (!is_dir($cacheDir))
        {
            Filesystem::create_dir($cacheDir);
        }

        $containerBuilder = new DependencyInjectionContainerBuilder(
            null, new DirectoryContainerExtensionFinder(Path::getInstance()->getBasePath()), $cacheFile,
            'ChamiloInstallContainer'
        );

        $container = $containerBuilder->createContainer();

        /** @var PackagesMappingDriverFactory $packagesMappingDriverFactory */
        $packagesMappingDriverFactory = $container->get('Doctrine\ORM\PackagesMappingDriverFactory');

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $schema_tool = new SchemaTool($entityManager);

        $classesMetadata = [];

        try
        {
            $classNameUtilities = ClassnameUtilities::getInstance();
            $package = $classNameUtilities->getNamespaceParent($this->context());

            $packages = array(
                $this->context() => Path::getInstance()->namespaceToFullPath($package) .
                    'Resources/Configuration/Config.yml'
            );

            $mappingDriver = $packagesMappingDriverFactory->createMappingDriverForPackages($packages);

            $entityClasses = $mappingDriver->getAllClassNames();
            foreach ($entityClasses as $entityClass)
            {
                if (in_array($entityClass, $this->getExcludedEntityClasses()))
                {
                    continue;
                }

                $classesMetadata[] = $entityManager->getClassMetadata($entityClass);
            }

            $schema_tool->updateSchema($classesMetadata, true);
        }
        catch (Exception $ex)
        {
            echo '<pre>';
            print_r($ex->getMessage());
            print_r($ex->getTraceAsString());

            return false;
        }

        return true;
    }

    /**
     * Returns an array of the excluded entity classes
     *
     * @return string[]
     */
    protected function getExcludedEntityClasses()
    {
        return [];
    }
}