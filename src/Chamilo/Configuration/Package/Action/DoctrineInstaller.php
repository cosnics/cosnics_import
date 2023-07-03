<?php
namespace Chamilo\Configuration\Package\Action;

use Chamilo\Libraries\DependencyInjection\DependencyInjectionContainerBuilder;
use Chamilo\Libraries\DependencyInjection\ExtensionFinder\DirectoryContainerExtensionFinder;
use Chamilo\Libraries\Storage\DataManager\Doctrine\ORM\PackagesMappingDriverFactory;
use Doctrine\ORM\EntityManager;
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
     * Returns an array of the excluded entity classes
     *
     * @return string[]
     */
    protected function getExcludedEntityClasses()
    {
        return [];
    }

    /**
     * Scans for the available storage units and creates them
     *
     * @return bool
     */
    public function install_storage_units()
    {
        $cacheDir = $this->getConfigurablePathBuilder()->getCachePath('Hogent\Libraries\DependencyInjection');
        $cacheFile = $cacheDir . 'InstallDependencyInjection.php';

        if (!is_dir($cacheDir))
        {
            $this->getFilesystem()->mkdir($cacheDir);
        }

        $containerBuilder = new DependencyInjectionContainerBuilder(
            null, new DirectoryContainerExtensionFinder($this->getSystemPathBuilder()->getBasePath()), $cacheFile,
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
            $packages = [
                static::CONTEXT => $this->getSystemPathBuilder()->namespaceToFullPath(static::CONTEXT) .
                    'Resources/Configuration/Config.yml'
            ];

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
}