<?php
namespace Chamilo\Libraries\Storage\DataManager\AdoDb;

use Exception;

/**
 *
 * @package Chamilo\Libraries\Storage\DataManager\AdoDb
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class DataSourceName extends \Chamilo\Libraries\Storage\DataManager\DataSourceName
{

    public function getConnectionString(): string
    {
        $string = [];

        $string[] = $this->getDriver(true);
        $string[] = ':';
        $string[] = 'host=' . $this->getHost();
        if ($this->getPort())
        {
            $string[] = ':';
            $string[] = $this->getPort();
        }
        $string[] = ';';
        $string[] = 'dbname=' . $this->getDatabase();
        $string[] = ';';
        $string[] = 'charset=' . $this->getCharset();

        return implode('', $string);
    }

    /**
     * @throws \Exception
     */
    public function getImplementedDriver(): string
    {
        switch ($this->getDriver())
        {
            case self::DRIVER_PGSQL :
                return self::DRIVER_PGSQL;
            case self::DRIVER_SQLITE :
                return self::DRIVER_SQLITE;
            case self::DRIVER_MYSQL :
                return self::DRIVER_MYSQL;
            case self::DRIVER_MSSQL :
                return 'sqlsrv';
            case self::DRIVER_OCI :
                return self::DRIVER_OCI;
            default :
                throw new Exception(
                    'The requested driver (' . $this->getDriver() .
                    ') is not available in ADOdb. Please provide a driver for ADOdb or choose another implementation'
                );
        }
    }
}
