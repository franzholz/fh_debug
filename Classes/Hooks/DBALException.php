<?php

namespace JambageCom\FhDebug\Hooks;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


use Doctrine\DBAL\Driver\DriverException as DriverExceptionInterface;
use Doctrine\DBAL\Driver\ExceptionConverterDriver;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Exception;
use Throwable;

use function array_map;
use function bin2hex;
use function get_class;
use function gettype;
use function implode;
use function is_object;
use function is_resource;
use function is_string;
use function json_encode;
use function preg_replace;
use function spl_object_hash;
use function sprintf;

/**
 * Exception handler class for content object rendering
 */
class DBALException extends \Doctrine\DBAL\DBALException
{
    public static function debug(
        $exception,
        $title = null
    ) {
        $maxCount = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fh_debug']['TRACEDEPTH_EXCEPTION'];
        $trail = $exception->getTrace();

        $traceArray =
            DebugFunctions::getTraceArray(
                $trail,
                $maxCount,
                0
            );

        debug($title, 'DBALException::debug $title'); // keep this
        debug($traceArray, 'fh_debug DBAL exception handler exception trace'); // keep this
        debug($exception->getFile(), 'fh_debug exception handler exception File'); // keep this
        debug($exception->getLine(), 'fh_debug exception handler exception Line'); // keep this
    }

    /**
     * @param string $method
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function notSupported($method)
    {
        $title = sprintf("Operation '%s' is not supported by platform.", $method);
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    public static function invalidPlatformSpecified(): self
    {
        $title = "Invalid 'platform' option specified, need to give an instance of " . AbstractPlatform::class . '.';
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param mixed $invalidPlatform
     */
    public static function invalidPlatformType($invalidPlatform): self
    {
        if (is_object($invalidPlatform)) {
            $title = sprintf(
                "Option 'platform' must be a subtype of '%s', instance of '%s' given",
                AbstractPlatform::class,
                $invalidPlatform::class
            );
        } else {
            $title = sprintf(
                "Option 'platform' must be an object and subtype of '%s'. Got '%s'",
                AbstractPlatform::class,
                gettype($invalidPlatform)
            );
        }
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * Returns a new instance for an invalid specified platform version.
     *
     * @param string $version        The invalid platform version given.
     * @param string $expectedFormat The expected platform version format.
     *
     * @return DBALException
     */
    public static function invalidPlatformVersionSpecified($version, $expectedFormat)
    {
        $title = sprintf(
            'Invalid platform version "%s" specified. ' .
            'The platform version has to be specified in the format: "%s".',
            $version,
            $expectedFormat
        );
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @return \Doctrine\DBAL\DBALException
     */
    public static function invalidPdoInstance()
    {
        $title = sprintf(
            "The 'pdo' option was used in DriverManager::getConnection() but no " .
            'instance of PDO was given.'
        );
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string|null $url The URL that was provided in the connection parameters (if any).
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function driverRequired($url = null)
    {
        if ($url) {
            $title = sprintf(
                "The options 'driver' or 'driverClass' are mandatory if a connection URL without scheme " .
                'is given to DriverManager::getConnection(). Given URL: %s',
                $url
            );
        } else {
            $title =
                "The options 'driver' or 'driverClass' are mandatory if no PDO " .
            'instance is given to DriverManager::getConnection().';
        }
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string   $unknownDriverName
     * @param string[] $knownDrivers
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function unknownDriver($unknownDriverName, array $knownDrivers)
    {
        $title =
            "The given 'driver' " . $unknownDriverName . ' is unknown, ' .
            'Doctrine currently supports only the following drivers: ' . implode(', ', $knownDrivers);

        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string  $sql
     * @param mixed[] $params
     *
     * @return self
     */
    public static function driverExceptionDuringQuery(Driver $driver, Throwable $driverEx, $sql, array $params = [])
    {
        $msg = "An exception occurred while executing '" . $sql . "'";
        if ($params) {
            $msg .= ' with params ' . self::formatParameters($params);
        }
        $msg .= ":\n\n" . $driverEx->getMessage();

        $result = static::wrapException($driver, $driverEx, $msg);
        return $result;

    }

    /**
     * @return self
     */
    public static function driverException(Driver $driver, Throwable $driverEx)
    {
        return static::wrapException($driver, $driverEx, 'An exception occurred in driver: ' . $driverEx->getMessage());
    }

    /**
     * @return self
     */
    private static function wrapException(Driver $driver, Throwable $driverEx, $msg)
    {
        if ($driverEx instanceof DriverException) {
            $result = $driverEx;
        } elseif ($driver instanceof ExceptionConverterDriver && $driverEx instanceof DriverExceptionInterface) {
            $result = $driver->convertException($msg, $driverEx);
        } else {
            $result = new self($msg, 0, $driverEx);
        }
        static::debug($result, $msg);
        return $result;
    }

    /**
     * Returns a human-readable representation of an array of parameters.
     * This properly handles binary data by returning a hex representation.
     *
     * @param mixed[] $params
     *
     * @return string
     */
    private static function formatParameters(array $params)
    {
        return '[' . implode(', ', array_map(static function ($param) {
            if (is_resource($param)) {
                return (string) $param;
            }

            $json = @json_encode($param);

            if (! is_string($json) || $json === 'null' && is_string($param)) {
                // JSON encoding failed, this is not a UTF-8 string.
                return sprintf('"%s"', preg_replace('/.{2}/', '\\x$0', bin2hex($param)));
            }

            return $json;
        }, $params)) . ']';
    }

    /**
     * @param string $wrapperClass
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function invalidWrapperClass($wrapperClass)
    {
        $title = "The given 'wrapperClass' " . $wrapperClass . ' has to be a ' .
            'subtype of \Doctrine\DBAL\Connection.';
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string $driverClass
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function invalidDriverClass($driverClass)
    {
        $title = "The given 'driverClass' " . $driverClass . ' has to implement the ' . Driver::class . ' interface.';
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string $tableName
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function invalidTableName($tableName)
    {
        $title = 'Invalid table name specified: ' . $tableName;
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string $tableName
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function noColumnsSpecifiedForTable($tableName)
    {
        $title = 'No columns specified for table ' . $tableName;
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @return \Doctrine\DBAL\DBALException
     */
    public static function limitOffsetInvalid()
    {
        $title = 'Invalid Offset in Limit Query, it has to be larger than or equal to 0.';
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string $name
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function typeExists($name)
    {
        $title = 'Type ' . $name . ' already exists.';
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string $name
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function unknownColumnType($name)
    {
        $title = 'Unknown column type "' . $name . '" requested. Any Doctrine type that you use has ' .
            'to be registered with \Doctrine\DBAL\Types\Type::addType(). You can get a list of all the ' .
            'known types with \Doctrine\DBAL\Types\Type::getTypesMap(). If this error occurs during database ' .
            'introspection then you might have forgotten to register all database types for a Doctrine Type. Use ' .
            'AbstractPlatform#registerDoctrineTypeMapping() or have your custom types implement ' .
            'Type#getMappedDatabaseTypes(). If the type name is empty you might ' .
            'have a problem with the cache or forgot some mapping information.';
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    /**
     * @param string $name
     *
     * @return \Doctrine\DBAL\DBALException
     */
    public static function typeNotFound($name)
    {
        $title = 'Type to be overwritten ' . $name . ' does not exist.';
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    public static function typeNotRegistered(Type $type): self
    {
        $title = sprintf('Type of the class %s@%s is not registered.', $type::class, spl_object_hash($type));
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }

    public static function typeAlreadyRegistered(Type $type): self
    {
        $title = sprintf('Type of the class %s@%s is already registered.', $type::class, spl_object_hash($type));
        $result = new self($title);
        static::debug($result, $title);
        return $result;
    }
}
