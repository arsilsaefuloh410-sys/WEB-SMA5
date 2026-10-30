<?php
/**
 * ==========================================================
 * Pemuat Berkas Konfigurasi .env (Tanpa Library Eksternal)
 * ==========================================================
 * Membaca pasangan KEY=VALUE dari berkas .env di root proyek.
 * Baris kosong dan komentar (#) diabaikan.
 */

function load_env(string $path): array
{
    $vars = [];

    if (!is_file($path) || !is_readable($path)) {
        return $vars;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Abaikan baris kosong & komentar
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Abaikan baris tanpa tanda =
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        // Buang tanda kutip ganda/satu di sekeliling nilai
        $len = strlen($value);
        if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        if ($key !== '') {
            $vars[$key] = $value;
        }
    }

    return $vars;
}
