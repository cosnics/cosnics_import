<?php
namespace Chamilo\Core\Repository\Component;

use Chamilo\Configuration\Storage\DataClass\Registration;
use Chamilo\Core\Metadata\Entity\DataClassEntity;
use Chamilo\Core\Metadata\Relation\Service\RelationService;
use Chamilo\Core\Metadata\Storage\DataClass\Relation;
use Chamilo\Core\Metadata\Storage\DataClass\Schema;
use Chamilo\Core\Repository\Manager;
use Chamilo\Libraries\Architecture\Application\ApplicationConfiguration;
use Chamilo\Libraries\Format\Breadcrumb\BreadcrumbLessComponentInterface;
use Chamilo\Libraries\Translation\Translation;
use Exception;

/**
 * @package Chamilo\Core\Repository\Component
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 * @author  Eduard Vossen <eduard.vossen@ehb.be>
 */
class SchemaLinkerComponent extends Manager implements BreadcrumbLessComponentInterface
{

    /**
     * @return string
     * @throws \Chamilo\Libraries\Architecture\Exceptions\ClassNotExistException
     * @throws \ReflectionException
     */
    public function run()
    {
        $component = $this->getApplicationFactory()->getApplication(
            \Chamilo\Core\Metadata\Relation\Instance\Manager::CONTEXT,
            new ApplicationConfiguration($this->getRequest(), $this->getUser(), $this)
        );

        $component->setTargetEntities($this->getTargetEntities());
        $component->setRelations($this->getRelation());
        $component->setSourceEntities($this->getSourceEntities());

        return $component->run();
    }

    /**
     * @return \Chamilo\Core\Metadata\Storage\DataClass\Relation[]
     * @throws \Exception
     */
    public function getRelation()
    {
        $relation = $this->getRelationService()->getRelationByName('isAvailableFor');

        if (!$relation instanceof Relation)
        {
            throw new Exception(
                Translation::get(
                    'RelationNotAvailable', ['TYPE' => 'isAvailableFor'], 'Chamilo\Core\Metadata\Relation'
                )
            );
        }

        return [$relation];
    }

    /**
     * @return \Chamilo\Core\Metadata\Relation\Service\RelationService
     */
    private function getRelationService()
    {
        return $this->getService(RelationService::class);
    }

    /**
     * @return \Chamilo\Core\Metadata\Entity\EntityInterface[]
     */
    public function getSourceEntities()
    {
        $entities = [];
        $entities[] = $this->getDataClassEntityFactory()->getEntity(Schema::class);

        return $entities;
    }

    /**
     * @return \Chamilo\Core\Metadata\Entity\EntityInterface[]
     * @throws \Symfony\Component\Cache\Exception\CacheException
     */
    public function getTargetEntities()
    {
        $registrations = $this->getRegistrationConsulter()->getRegistrationsByType(
            'Chamilo\Core\Repository\ContentObject'
        );

        $entities = [];

        foreach ($registrations as $registration)
        {
            $entities[] = $this->getDataClassEntityFactory()->getEntity(
                $registration[Registration::PROPERTY_CONTEXT] . '\Storage\DataClass\\' .
                $registration[Registration::PROPERTY_NAME], DataClassEntity::INSTANCE_IDENTIFIER
            );
        }

        return $entities;
    }
}
