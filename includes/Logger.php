<?php
// includes/Logger.php

class Logger {
    private $logDir;
    private $emailLogFile;

    public function __construct() {
        $this->logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
        $this->emailLogFile = $this->logDir . '/email.log';
    }

    /**
     * Log email activities
     */
    public function log(string $type, string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$type}] {$message}\n";
        
        error_log($logMessage, 3, $this->emailLogFile);
    }
}
