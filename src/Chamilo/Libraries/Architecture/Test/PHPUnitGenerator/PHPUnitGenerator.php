<?php
namespace Chamilo\Libraries\Architecture\Test\PHPUnitGenerator;

use Chamilo\Configuration\Service\Consulter\RegistrationConsulter;
use Chamilo\Libraries\File\SystemPathBuilder;
use DOMDocument;
use DOMXPath;
use Twig\Environment;

/**
 * Generates the global phpunit configuration file for Chamilo
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class PHPUnitGenerator implements PHPUnitGeneratorInterface
{

    protected SystemPathBuilder $pathBuilder;

    /**
     * The path to the PHPUnit configuration file
     *
     * @var string
     */
    protected $phpUnitFile;

    /**
     * @var RegistrationConsulter
     */
    protected $registrationConsulter;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    public function __construct(
        Environment $twig, SystemPathBuilder $pathBuilder, RegistrationConsulter $registrationConsulter
    )
    {
        $this->twig = $twig;
        $this->pathBuilder = $pathBuilder;
        $this->registrationConsulter = $registrationConsulter;
        $this->phpUnitFile = $pathBuilder->getStoragePath() . '/configuration/phpunit.xml';
    }

    /**
     * Generates the global phpunit configuration file for Chamilo
     *
     * @param bool $includeSource
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function generate($includeSource = true)
    {
        $testDirectories = $sourceDirectories = $excludedDirectories = [];

        $registeredPackages = $this->registrationConsulter->getRegistrationContexts();
        foreach ($registeredPackages as $registeredPackage)
        {
            $packagePath = $this->pathBuilder->namespaceToFullPath($registeredPackage);
            $testPath = $packagePath . 'Test';
            $packagePHPUnitConfiguration = $testPath . DIRECTORY_SEPARATOR . 'phpunit.xml';

            if (!file_exists($packagePHPUnitConfiguration))
            {
                continue;
            }

            $domDocument = new DOMDocument();
            $domDocument->load($packagePHPUnitConfiguration);

            $domXPath = new DOMXPath($domDocument);
            $domNodeList = $domXPath->query('testsuites/testsuite');

            if ($domNodeList->length == 0)
            {
                continue;
            }

            foreach ($domNodeList as $domElement)
            {
                $testFolder = trim($domElement->textContent);
                if (!$includeSource && $testFolder == 'Source')
                {
                    continue;
                }

                /** @var \DOMElement $domElement */
                $testDirectories[] = $testPath . DIRECTORY_SEPARATOR . $testFolder;
            }

            $sourceDirectories[] = $packagePath;
            $excludedDirectories[] = $testPath;
        }

        $phpunitConfiguration = $this->twig->render(
            'Chamilo\Libraries:PHPUnitGenerator/phpunit.xml.twig', [
                'testDirectories' => $testDirectories,
                'sourceDirectories' => $sourceDirectories,
                'excludedDirectories' => $excludedDirectories
            ]
        );

        file_put_contents($this->phpUnitFile, $phpunitConfiguration);
    }
}