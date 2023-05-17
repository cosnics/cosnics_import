<?php
namespace Chamilo\Libraries\Architecture\Test\TestCases;

/**
 * Base for test class that uses data fixtures
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
abstract class FixturesBasedTestCase extends DependencyInjectionBasedTestCase
{
    /**
     * Inserts the fixture data for the tests in the created storage units
     */
    abstract protected function createFixtureData();

    /**
     * Creates the storage units that are required for the tests
     */
    abstract protected function createStorageUnits();

    /**
     * Drops the storage units that are required for the tests
     */
    abstract protected function dropStorageUnits();

    /**
     * Returns the fixture files that need to be inserted. This method requires a multidimensional array with the
     * names of the fixture files per context
     * [ $context => [$fixtureFileName1, $fixtureFileName2] ]
     *
     * @return array
     */
    abstract protected function getFixtureFiles();

    /**
     * Returns the storage units that need to be created. This method requires a multidimensional array with the
     * names of the storage units per context
     * [ $context => [$storageUnit1, $storageUnit2] ]
     *
     * @return array
     */
    abstract protected function getStorageUnitsToCreate();

    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createStorageUnits();
        $this->createFixtureData();
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        $this->dropStorageUnits();
    }
}