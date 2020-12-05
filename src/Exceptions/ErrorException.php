<?php

/**
 * @package bones
 * @link https://github.com/bayfrontmedia/bones
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\Bones\Exceptions;

/*
 * Thrown when a PHP error is encountered.
 */

use Throwable;

class ErrorException extends BonesException
{

    public function __construct($message = "", $code = 0, Throwable $previous = NULL)
    {

        $msg_exp = explode(' ', $message);

        foreach ($msg_exp as $msg) {

            /*
             * For security reasons, if a local file, show only the filename
             * not the entire server path.
             */

            if (file_exists($msg)) {

                $message = str_replace($msg, basename($msg), $message);

            }

        }

        parent::__construct($message, $code, $previous);
    }

}