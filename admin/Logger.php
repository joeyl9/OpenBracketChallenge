<?php
class SimpleLogger {
    private $logFile;

    public function __construct() {
        // PERMANENT LOG LOCATION
        // Ensure /admin/logs/ exists and is writable (CHMOD 777 usually needed on shared logs)
        $this->logFile = __DIR__ . '/logs/app_errors.log';
    }

    public function error($message, $context = []) {
        $this->write('ERROR', $message, $context);
    }

    public function info($message, $context = []) {
        $this->write('INFO', $message, $context);
    }

    private function write($level, $message, $context) {
        $date = date('Y-m-d H:i:s');
        $jsonContext = !empty($context) ? json_encode($context) : '';
        $line = "[$date] [$level] $message $jsonContext" . PHP_EOL;
        
        // Simple rotation check (If > 5MB, rotate)
        if (file_exists($this->logFile) && filesize($this->logFile) > 5 * 1024 * 1024) {
            $bak = $this->logFile . '.' . date('Ymd_His') . '.bak';
            @rename($this->logFile, $bak);
        }

        // Use error_log as fallback if file write fails, or just try append
        @file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}
?>

