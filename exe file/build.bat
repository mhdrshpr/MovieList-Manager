@echo off
chcp 65001 >nul
echo ===================================================
echo     Movie List - Windows Desktop App Builder (Tauri)
echo ===================================================
echo.

where cargo >nul 2>nul
if %errorlevel% neq 0 (
    echo [خطا] ابزار Cargo / Rust روی سیستم شما شناسایی نشد.
    echo برای کامپایل لوکال، لطفاً Rust را از https://rustup.rs نصب کنید.
    echo همچنین می‌توانید به سادگی از GitHub Actions آماده شده برای بیلد ابری استفاده نمایید.
    echo.
    pause
    exit /b 1
)

where cargo-tauri >nul 2>nul
if %errorlevel% neq 0 (
    echo [اطلاع] در حال نصب ابزار cargo-tauri CLI...
    cargo install tauri-cli --version "^2.0.0" --locked
)

echo [اطلاع] در حال شروع فرآیند کامپایل و بیلد فایل exe...
cd src-tauri
cargo tauri build

if %errorlevel% equ 0 (
    echo.
    echo [موفق] فایل نهایی .exe و نصبی با موفقیت در مسیر زیر ساخته شد:
    echo src-tauri\target\release\bundle\
) else (
    echo.
    echo [خطا] فرآیند بیلد با مشکل مواجه شد.
)

pause
