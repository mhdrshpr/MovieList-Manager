<?php
/**
 * env_loader.php
 * بارگذاری امن متغیرهای محیطی از فایل .env (بدون وابستگی به کامپوزر)
 * بدون بازنویسی مقادیر از پیش تعریف شده (getenv/putenv) و پشتیبانی از تنظیمات پیش‌فرض.
 */

if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);

    function envLoadFile(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // رد کردن کامنت‌ها و خطوط خالی
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            // فقط خطوط دارای علامت =
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // حذف نقل قول‌های دور مقدار
            if (strlen($value) >= 2) {
                if (
                    ($value[0] === '"' && substr($value, -1) === '"') ||
                    ($value[0] === "'" && substr($value, -1) === "'")
                ) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key === '') {
                continue;
            }

            // فقط در صورت تعریف نشده بودن ثبت کن
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    function env(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? null;
        }
        return ($value === null || $value === false) ? $default : $value;
    }

    // پوشه‌های ممکن برای فایل .env
    $candidates = [
        __DIR__ . '/../.env',   // api/../.env  =>  root/.env
        __DIR__ . '/.env',      // api/.env
    ];

    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real !== false && is_file($real)) {
            envLoadFile($real);
            break;
        }
    }
}