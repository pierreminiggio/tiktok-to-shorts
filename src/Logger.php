<?php

namespace PierreMiniggio\TiktokToShorts;

use Exception;
use Psr\Log\LoggerInterface;
use Stringable;

class Logger implements LoggerInterface
{

    public function emergency(string | Stringable $message, array $context = []): void
    {
        throw new Exception('Emergency : ' . $message . ' Context :' . json_encode($context));
    }

    public function alert(string | Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string | Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string | Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string | Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string | Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string | Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string | Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, string | Stringable $message, array $context = []): void
    {
        var_dump($level, $message, $context);
    }
}
