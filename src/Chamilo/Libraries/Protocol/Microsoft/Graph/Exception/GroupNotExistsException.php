<?php
namespace Chamilo\Libraries\Protocol\Microsoft\Graph\Exception;

use Chamilo\Core\User\Storage\DataClass\User;

/**
 * Class GroupNotExistsException
 */
class GroupNotExistsException extends \Exception
{

    /**
     * AzureUserNotExistsException constructor.
     * @param string $groupId
     */
    public function __construct(string $groupId)
    {
        parent::__construct(
            'Group does not exist in office365: ' . $groupId
        );
    }
}