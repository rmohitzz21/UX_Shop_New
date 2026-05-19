<?php
// includes/env.php
// Loads .env file from project root into $_ENV and getenv()
// Call this once, early in config.php

function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return; // Fall back to existing server env vars if .env missing
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and blank lines
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        // Only process lines that contain an = sign
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip inline comments (e.g. VALUE=foo  # comment)
        if (strpos($value, ' #') !== false) {
            $value = trim(explode(' #', $value, 2)[0]);
        }

        // Strip surrounding quotes if present
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Auto-load on include
loadEnv(dirname(__DIR__) . '/.env');
