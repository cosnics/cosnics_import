<?php
namespace Chamilo\Core\Repository\ContentObject\Announcement\Storage\DataClass;

use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Libraries\Architecture\Interfaces\AttachmentSupportInterface;
use Chamilo\Libraries\Architecture\Interfaces\VersionableInterface;

/**
 * @package Chamilo\Core\Repository\ContentObject\Announcement\Storage\DataClass
 */
class Announcement extends ContentObject implements VersionableInterface, AttachmentSupportInterface
{
    public const CONTEXT = 'Chamilo\Core\Repository\ContentObject\Announcement';
}