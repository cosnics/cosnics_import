<?php
namespace Chamilo\Libraries\Storage\DataClass;

use Chamilo\Core\User\Storage\DataClass\User;

/**
 * @package Chamilo\Libraries\Storage\DataClass
 *
 * @author Hans De Bisschop <hans.de.bisschop@ehb.be>
 */
class PropertyMapper
{
    /**
     *
     * @param \Chamilo\Libraries\Storage\DataClass\DataClass[] $dataClasses
     * @param string $methodName
     *
     * @return \Chamilo\Libraries\Storage\DataClass\DataClass[][]
     */
    public function groupDataClassByMethod($dataClasses, $methodName)
    {
        $mappedDataClasses = array();

        foreach ($dataClasses as $dataClass)
        {
            $groupValue = $dataClass->$methodName();

            if ($groupValue)
            {
                $mappedDataClasses[$groupValue][] = $dataClass;
            }
        }

        return $mappedDataClasses;
    }

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataClass\DataClass[] $dataClasses
     * @param string $propertyName
     *
     * @return \Chamilo\Libraries\Storage\DataClass\DataClass[][]
     */
    public function groupDataClassByProperty($dataClasses, $propertyName)
    {
        $mappedDataClasses = array();

        foreach ($dataClasses as $dataClass)
        {
            if (in_array($propertyName, $dataClass->get_default_property_names()))
            {
                $propertyValue = $dataClass->getDefaultProperty($propertyName);

                if (isset($propertyValue) && !empty($propertyValue))
                {
                    if (!array_key_exists($dataClass->getDefaultProperty($propertyName), $mappedDataClasses))
                    {
                        $mappedDataClasses[$dataClass->getDefaultProperty($propertyName)] = array();
                    }

                    $mappedDataClasses[$dataClass->getDefaultProperty($propertyName)][] = $dataClass;
                }
            }
        }

        return $mappedDataClasses;
    }

    /**
     *
     * @param string[][] $records
     * @param string $propertyName
     *
     * @return string[][]
     */
    public function groupRecordsByProperty($records, $propertyName)
    {
        $mappedRecords = array();

        foreach ($records as $record)
        {
            if (array_key_exists($propertyName, $record))
            {
                if ($record[$propertyName])
                {
                    if (!array_key_exists($record[$propertyName], $mappedRecords))
                    {
                        $mappedRecords[$record[$propertyName]] = array();
                    }

                    $mappedRecords[$record[$propertyName]][] = $record;
                }
            }
        }

        return $mappedRecords;
    }

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataClass\DataClass[] $dataClasses
     * @param string $methodName
     *
     * @return \Chamilo\Libraries\Storage\DataClass\DataClass[]
     */
    public function mapDataClassByMethod($dataClasses, $methodName)
    {
        $mappedDataClasses = array();

        foreach ($dataClasses as $dataClass)
        {
            $mapValue = $dataClass->$methodName();

            if ($mapValue)
            {
                $mappedDataClasses[$mapValue] = $dataClass;
            }
        }

        return $mappedDataClasses;
    }

    /**
     *
     * @param \Chamilo\Libraries\Storage\DataClass\DataClass[] $dataClasses
     * @param string $propertyName
     *
     * @return \Chamilo\Libraries\Storage\DataClass\DataClass[]
     */
    public function mapDataClassByProperty($dataClasses, $propertyName)
    {
        $mappedDataClasses = array();

        foreach ($dataClasses as $dataClass)
        {
            $propertyValue = $dataClass->getDefaultProperty($propertyName);

            if (isset($propertyValue) && !empty($propertyValue))
            {
                $mappedDataClasses[$propertyValue] = $dataClass;
            }
        }

        return $mappedDataClasses;
    }

    /**
     *
     * @param string[][] $records
     * @param string $propertyName
     *
     * @return string[][]
     */
    public function mapRecordsByProperty($records, $propertyName)
    {
        $mappedRecords = array();

        foreach ($records as $record)
        {
            $propertyValue = $record[$propertyName];

            if (isset($propertyValue) && !empty($propertyValue))
            {
                $mappedRecords[$propertyValue] = $record;
            }
        }

        return $mappedRecords;
    }
}