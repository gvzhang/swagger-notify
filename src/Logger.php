<?php

/**
 * 日志记录
 */

namespace App;

class Logger
{
    private static $LOG_NAME = "app";

    public static function write($msg)
    {
        $logName = self::$LOG_NAME . "-" . date("Y-m-d") . ".log";
        $logPath = rootPath() . "/log";
        if (!is_dir($logPath)) {
            mkdir($logPath);
        }
        return file_put_contents($logPath . "/" . $logName, $msg . "\n", FILE_APPEND);
    }
}