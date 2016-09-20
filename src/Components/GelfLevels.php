<?php
namespace DreamFactory\Core\Logger\Components;

use DreamFactory\Library\Utility\Enums\FactoryEnum;

/**
 * The "level" of a GELF message.
 *
 * Equal to the standard syslog levels
 */
class GelfLevels extends FactoryEnum
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @var int
     */
    const __default = self::INFO;
    /**
     * @var int
     */
    const EMERGENCY = 0;
    /**
     * @var int
     */
    const ALERT = 1;
    /**
     * @var int
     */
    const CRITICAL = 2;
    /**
     * @var int
     */
    const ERROR = 3;
    /**
     * @var int
     */
    const WARNING = 4;
    /**
     * @var int
     */
    const NOTICE = 5;
    /**
     * @var int
     */
    const INFO = 6;
    /**
     * @var int
     */
    const DEBUG = 7;
}