<?php
namespace Chamilo\Application\Weblcms\Bridge\LearningPath\Assignment\Storage;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class DataManager
 * @inheritdoc
 */
class DataManager extends \Chamilo\Libraries\Storage\DataManager\DataManager
{
    use ContainerAwareTrait;

    const PREFIX = '';
}