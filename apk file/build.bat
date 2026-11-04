@echo off
chcp 65001 >nul
echo ===================================================
echo   Movie List - Android APK Builder (Bubblewrap TWA)
echo ===================================================
echo.

where node >nul 2>nul
if %errorlevel% neq 0 (
    echo [خطا] محیط Node.js روی سیستم شما شناسایی نشد.
    echo برای کامپایل لوکال، لطفاً Node.js را از https://nodejs.org نصب فرمایید.
    echo همچنین می‌توانید به سادگی و بدون هیچ پیش‌نیازی از GitHub Actions استفاده نمایید.
    echo.
    pause
    exit /b 1
)

where java >nul 2>nul
if %errorlevel% neq 0 (
    echo [هشدار] جاوا (JDK) شناسایی نشد. Bubblewrap برای ساخت نهایی APK به JDK 17+ نیاز دارد.
    echo در صورت بروز خطا، JDK 17 و Android SDK Command-line Tools را نصب کنید.
    echo یا پروژه را در گیت‌هاب پوش کرده و از تب Actions خروجی را مستقیم دریافت کنید.
    echo.
)

echo [اطلاع] بررسی و نصب Bubblewrap CLI...
call npm list -g @bubblewrap/cli >nul 2>nul
if %errorlevel% neq 0 (
    echo [اطلاع] در حال نصب ابزار رسمی گوگل @bubblewrap/cli...
    call npm install -g @bubblewrap/cli
)

echo.
echo [اطلاع] در حال آماده‌سازی و ساخت APK اندروید...
call npx @bubblewrap/cli build --skipVersionUpgrade

if %errorlevel% equ 0 (
    echo.
    echo ===================================================
    echo [موفق] فایل APK و Bundle اندروید با موفقیت ساخته شدند!
    echo فایل‌های خروجی در همین پوشه قرار گرفتند:
    echo - app-release-signed.apk
    echo - app-release-bundle.aab
    echo ===================================================
) else (
    echo.
    echo [خطا یا راهنمایی] فرآیند لوکال نیاز به مسیرهای JDK و Android SDK دارد.
    echo بهترین و ساده‌ترین روش: استفاده از گردش‌کار خودکار GitHub Actions در تب Actions است.
)

echo.
pause
