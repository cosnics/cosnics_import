<?php
namespace Chamilo\Core\Admin\Service;

use Chamilo\Libraries\Format\Tabs\Actions;

/**
 * @package Chamilo\Core\Admin\Service
 */
interface ActionProviderInterface
{

    public function getActions(): Actions;

    public function getContext(): string;
}