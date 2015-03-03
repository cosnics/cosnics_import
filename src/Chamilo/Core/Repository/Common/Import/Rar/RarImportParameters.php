<?php
namespace Chamilo\Core\Repository\Common\Import\Rar;

use Chamilo\Core\Repository\Common\Import\ImportParameters;

class RarImportParameters extends ImportParameters
{
    const CLASS_NAME = __CLASS__;

    private $file;

    public function __construct($type, $user, $category, $file, $values)
    {
        parent :: __construct($type, $user, $category);
        $this->file = $file;
    }

    /**
     *
     * @return the $file
     */
    public function get_file()
    {
        return $this->file;
    }

    /**
     *
     * @param $file field_type
     */
    public function set_file($file)
    {
        $this->file = $file;
    }
}
