<?php
namespace Chamilo\Libraries\Storage\DataManager\Doctrine\Driver\Mssql;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\PDOConnection;
use PDO;

/**
 *
 * @package Chamilo\Libraries\Storage\DataManager\Doctrine\Driver\Mssql
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author Magali Gillard <magali.gillard@ehb.be>
 * @author Eduard Vossen <eduard.vossen@ehb.be>
 */
class MsSqlDoctrinePdoConnection extends PDOConnection implements Connection
{

    /**
     *
     * @see \Doctrine\DBAL\Driver\PDOConnection::query()
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();

        if (! extension_loaded('pdo_dblib') && extension_loaded('pdo_sqlsrv'))
        {
            $stmt->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        }

        return $stmt;
    }

    public function getServerVersion()
    {
        return null;
    }
}
