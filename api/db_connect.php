<?php
require_once __DIR__ . "/env_loader.php";

// تنظیمات ماندگاری دائمی سشن کاربر (یک سال: 31,536,000 ثانیه)
$sessionLifetime = (int) env("SESSION_LIFETIME", 31536000);

// پوشه اختصاصی برای ذخیره امن سشن‌ها تا توسط کران‌جاب سیستم‌عامل سرور زودتر از موعد پاک نشود
$sessDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sessions';
if (!is_dir($sessDir)) {
    @mkdir($sessDir, 0700, true);
    @file_put_contents($sessDir . DIRECTORY_SEPARATOR . '.htaccess', "Deny from all\n");
    @file_put_contents($sessDir . DIRECTORY_SEPARATOR . 'index.html', "");
}
if (is_dir($sessDir) && is_writable($sessDir)) {
    ini_set('session.save_path', $sessDir);
}

ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
ini_set('session.cookie_lifetime', (string) $sessionLifetime);
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');

$isSecure = env("SESSION_COOKIE_SECURE", 0) == 1 || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

if (session_status() === PHP_SESSION_NONE) {
    session_name(env("SESSION_NAME", "MOVIE_SESSION"));
    session_set_cookie_params([
        "lifetime" => $sessionLifetime,
        "path" => "/",
        "domain" => "",
        "secure" => $isSecure,
        "httponly" => true,
        "samesite" => env("SESSION_COOKIE_SAMESITE", "Lax"),
    ]);
    session_start();
}

// تمدید کوکی سشن در هر بازدید کاربر جهت باقی ماندن دائمی ورود به سیستم در PWA
if (session_status() === PHP_SESSION_ACTIVE && isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),
        session_id(),
        [
            'expires' => time() + $sessionLifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => env("SESSION_COOKIE_SAMESITE", "Lax")
        ]
    );
}

// مقادیر دیتابیس از فایل .env (با fallback به مقادیر قبلی)
$host = env("DB_HOST", "localhost");
$dbname = env("DB_NAME", "mhdrshpr_movie");
$username = env("DB_USER", "mhdrshpr_movieadmin");
$password = env("DB_PASS", '$NZ,2om%DKxo@+]h');
$charset = env("DB_CHARSET", "utf8mb4");

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET NAMES $charset");
    // اطمینان از وجود ستون watch_date در جدول پیش‌درآمدها و خط زمانی (prequels)
    try {
        $pdo->exec("ALTER TABLE prequels ADD COLUMN watch_date VARCHAR(20) DEFAULT NULL");
    } catch (Throwable $t) {
        // ستون از قبل وجود دارد یا جدول هنوز ایجاد نشده است
    }
} catch (PDOException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "ok" => false,
        "message" => "Database connection failed",
    ]);
    error_log("DB error: " . $e->getMessage());
    exit;
}

// تابع کمکی برای چک لاگین
function isLoggedIn()
{
    return isset($_SESSION["user_id"]);
}

// تابع کمکی برای پاسخ JSON
function jsonResponse($ok, $message = "", $data = null)
{
    echo json_encode(
        ["ok" => $ok, "message" => $message, "data" => $data],
        JSON_UNESCAPED_UNICODE
    );
    exit();
}

// ==================== 32 STANDARD GENRES CATALOG & HELPERS ====================
if (!function_exists('getStandardGenresCatalog')) {
    function getStandardGenresCatalog() {
        return [
            ['slug' => 'action', 'name_fa' => 'اکشن', 'name_en' => 'Action', 'name' => 'اکشن / Action', 'color' => '#ef4444', 'synonyms' => ['اکشن', 'action', 'مبارزه‌ای', 'حادثه‌ای', 'اکشن-ماجراجویی', 'اکشن ماجراجویی']],
            ['slug' => 'adventure', 'name_fa' => 'ماجراجویی', 'name_en' => 'Adventure', 'name' => 'ماجراجویی / Adventure', 'color' => '#f97316', 'synonyms' => ['ماجراجویی', 'adventure', 'ماجراجویانه', 'اکتشافی', 'ماجرایی']],
            ['slug' => 'animation', 'name_fa' => 'انیمیشن', 'name_en' => 'Animation', 'name' => 'انیمیشن / Animation', 'color' => '#eab308', 'synonyms' => ['انیمیشن', 'animation', 'پویانمایی', 'کارتون', 'انیمشن', 'کارتونی']],
            ['slug' => 'anime', 'name_fa' => 'انیمه', 'name_en' => 'Anime', 'name' => 'انیمه / Anime', 'color' => '#ec4899', 'synonyms' => ['انیمه', 'anime', 'انیمه ژاپنی', 'مانگا', 'اوتاکو', 'manga', 'انیمه سریالی', 'انیمه سینمایی']],
            ['slug' => 'biography', 'name_fa' => 'زندگی‌نامه', 'name_en' => 'Biography', 'name' => 'زندگی‌نامه / Biography', 'color' => '#84cc16', 'synonyms' => ['زندگی‌نامه', 'زندگی نامه', 'زندگینامه', 'بیوگرافی', 'biography', 'زندگینامه‌ای']],
            ['slug' => 'comedy', 'name_fa' => 'کمدی', 'name_en' => 'Comedy', 'name' => 'کمدی / Comedy', 'color' => '#10b981', 'synonyms' => ['کمدی', 'comedy', 'طنز', 'خنده‌دار', 'طنزآمیز', 'شوخی', 'کمیدی']],
            ['slug' => 'sitcom', 'name_fa' => 'کمدی موقعیت', 'name_en' => 'Sitcom', 'name' => 'کمدی موقعیت / Sitcom', 'color' => '#0d9488', 'synonyms' => ['کمدی موقعیت', 'سیتکام', 'سیت کام', 'sitcom', 'situation comedy', 'کمدی سیتکام', 'کمدی وضعیت', 'طنز موقعیت']],
            ['slug' => 'crime', 'name_fa' => 'جنایی', 'name_en' => 'Crime', 'name' => 'جنایی / Crime', 'color' => '#06b6d4', 'synonyms' => ['جنایی', 'crime', 'پلیسی', 'تبهکاری', 'جنایتکارانه']],
            ['slug' => 'documentary', 'name_fa' => 'مستند', 'name_en' => 'Documentary', 'name' => 'مستند / Documentary', 'color' => '#0ea5e9', 'synonyms' => ['مستند', 'documentary', 'مستندسازی', 'واقعی']],
            ['slug' => 'drama', 'name_fa' => 'درام', 'name_en' => 'Drama', 'name' => 'درام / Drama', 'color' => '#3b82f6', 'synonyms' => ['درام', 'drama', 'احساسی', 'اجتماعی', 'دراماتیک']],
            ['slug' => 'family', 'name_fa' => 'خانوادگی', 'name_en' => 'Family', 'name' => 'خانوادگی / Family', 'color' => '#6366f1', 'synonyms' => ['خانوادگی', 'family', 'کودک', 'کودک و نوجوان', 'نوجوان', 'کودکان']],
            ['slug' => 'fantasy', 'name_fa' => 'فانتزی', 'name_en' => 'Fantasy', 'name' => 'فانتزی / Fantasy', 'color' => '#8b5cf6', 'synonyms' => ['فانتزی', 'fantasy', 'جادویی', 'افسانه‌ای', 'تخیلی', 'فانتزی-تخیلی']],
            ['slug' => 'film-noir', 'name_fa' => 'فیلم نوآر', 'name_en' => 'Film-Noir', 'name' => 'فیلم نوآر / Film-Noir', 'color' => '#475569', 'synonyms' => ['فیلم نوآر', 'نوآر', 'نوار', 'noir', 'film noir', 'film-noir']],
            ['slug' => 'history', 'name_fa' => 'تاریخی', 'name_en' => 'History', 'name' => 'تاریخی / History', 'color' => '#a855f7', 'synonyms' => ['تاریخی', 'history', 'تاریخ', 'کهن', 'باستانی']],
            ['slug' => 'horror', 'name_fa' => 'ترسناک', 'name_en' => 'Horror', 'name' => 'ترسناک / Horror', 'color' => '#dc2626', 'synonyms' => ['ترسناک', 'horror', 'وحشت', 'وحشتناک', 'دلهره‌آور', 'اسلشر', 'وحشت‌آور']],
            ['slug' => 'music', 'name_fa' => 'موسیقی', 'name_en' => 'Music', 'name' => 'موسیقی / Music', 'color' => '#ec4899', 'synonyms' => ['موسیقی', 'music', 'موزیک', 'کنسرت', 'آهنگ']],
            ['slug' => 'musical', 'name_fa' => 'موزیکال', 'name_en' => 'Musical', 'name' => 'موزیکال / Musical', 'color' => '#f43f5e', 'synonyms' => ['موزیکال', 'musical', 'آهنگین', 'رقص و آواز']],
            ['slug' => 'mystery', 'name_fa' => 'معمایی', 'name_en' => 'Mystery', 'name' => 'معمایی / Mystery', 'color' => '#14b8a6', 'synonyms' => ['معمایی', 'mystery', 'رازآلود', 'راز آلود', 'معما', 'کارآگاهی']],
            ['slug' => 'romance', 'name_fa' => 'عاشقانه', 'name_en' => 'Romance', 'name' => 'عاشقانه / Romance', 'color' => '#fb7185', 'synonyms' => ['عاشقانه', 'romance', 'رمانتیک', 'عاطفی', 'عشق']],
            ['slug' => 'sci-fi', 'name_fa' => 'علمی-تخیلی', 'name_en' => 'Sci-Fi', 'name' => 'علمی-تخیلی / Sci-Fi', 'color' => '#0284c7', 'synonyms' => ['علمی-تخیلی', 'علمی تخیلی', 'sci fi', 'sci-fi', 'scifi', 'science fiction', 'فضایی', 'تخیلی-علمی']],
            ['slug' => 'sport', 'name_fa' => 'ورزشی', 'name_en' => 'Sport', 'name' => 'ورزشی / Sport', 'color' => '#16a34a', 'synonyms' => ['ورزشی', 'sport', 'ورزش', 'مسابقه', 'فوتبال', 'رزمی']],
            ['slug' => 'thriller', 'name_fa' => 'هیجان‌انگیز', 'name_en' => 'Thriller', 'name' => 'هیجان‌انگیز / Thriller', 'color' => '#b91c1c', 'synonyms' => ['هیجان‌انگیز', 'هیجان انگیز', 'thriller', 'تریلر', 'تعلیق', 'دلهره', 'هیجانی']],
            ['slug' => 'war', 'name_fa' => 'جنگی', 'name_en' => 'War', 'name' => 'جنگی / War', 'color' => '#78716c', 'synonyms' => ['جنگی', 'war', 'دفاع مقدس', 'نظامی', 'جنگ', 'ارتش']],
            ['slug' => 'western', 'name_fa' => 'وسترن', 'name_en' => 'Western', 'name' => 'وسترن / Western', 'color' => '#d97706', 'synonyms' => ['وسترن', 'western', 'غرب وحشی', 'کابویی']],
            ['slug' => 'superhero', 'name_fa' => 'ابرقهرمانی', 'name_en' => 'Superhero', 'name' => 'ابرقهرمانی / Superhero', 'color' => '#7c3aed', 'synonyms' => ['ابرقهرمانی', 'superhero', 'کمیک', 'comic', 'سوپرهیرو', 'قهرمانی']],
            ['slug' => 'psychological', 'name_fa' => 'روانشناختی', 'name_en' => 'Psychological', 'name' => 'روانشناختی / Psychological', 'color' => '#6d28d9', 'synonyms' => ['روانشناختی', 'روان‌شناختی', 'psychological', 'روانشناسی', 'روانی', 'ذهنی']],
            ['slug' => 'spy', 'name_fa' => 'جاسوسی', 'name_en' => 'Spy', 'name' => 'جاسوسی / Spy', 'color' => '#334155', 'synonyms' => ['جاسوسی', 'spy', 'اطلاعاتی', 'امنیتی', 'مامور مخفی']],
            ['slug' => 'supernatural', 'name_fa' => 'ماوراءالطبیعه', 'name_en' => 'Supernatural', 'name' => 'ماوراءالطبیعه / Supernatural', 'color' => '#4338ca', 'synonyms' => ['ماوراءالطبیعه', 'ماوراء الطبیعه', 'ماورایی', 'supernatural', 'سوپرنچرال', 'ارواح']],
            ['slug' => 'post-apocalyptic', 'name_fa' => 'پساآخرالزمانی', 'name_en' => 'Post-Apocalyptic', 'name' => 'پساآخرالزمانی / Post-Apocalyptic', 'color' => '#713f12', 'synonyms' => ['پساآخرالزمانی', 'پسا آخرالزمانی', 'آخرالزمانی', 'post-apocalyptic', 'dystopian', 'ویران‌شهر', 'دیستوپیا']],
            ['slug' => 'cyberpunk', 'name_fa' => 'سایبرپانک', 'name_en' => 'Cyberpunk', 'name' => 'سایبرپانک / Cyberpunk', 'color' => '#059669', 'synonyms' => ['سایبرپانک', 'cyberpunk', 'آینده‌نگرانه', 'تکنولوژی']],
            ['slug' => 'short', 'name_fa' => 'کوتاه', 'name_en' => 'Short', 'name' => 'کوتاه / Short', 'color' => '#64748b', 'synonyms' => ['کوتاه', 'short', 'فیلم کوتاه', 'مستند کوتاه']],
            ['slug' => 'gangster', 'name_fa' => 'مافیایی', 'name_en' => 'Gangster', 'name' => 'مافیایی / Gangster', 'color' => '#1e293b', 'synonyms' => ['مافیایی', 'گانگستری', 'gangster', 'mafia', 'مافیا', 'تبهکاران']]
        ];
    }
}

if (!function_exists('canonicalGenreText')) {
    function canonicalGenreText($str) {
        $str = trim((string)$str);
        if ($str === '') return '';
        $str = str_replace(
            ['ي', 'ك', 'ة', 'ۀ', '‌', 'آ', 'أ', 'إ'],
            ['ی', 'ک', 'ه', 'ه', ' ', 'ا', 'ا', 'ا'],
            $str
        );
        $str = preg_replace('/[\-\_\/\\\|\(\)\[\]\.\:\,\;،؟\!\?]+/u', ' ', $str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/\s+/u', ' ', $str);
        return trim($str);
    }
}

if (!function_exists('cleanGenreText')) {
    function cleanGenreText($str) {
        $str = trim((string)$str);
        if ($str === '') return '';
        $str = str_replace(['ي', 'ك', 'ة', 'ۀ', '‌'], ['ی', 'ک', 'ه', 'ه', ' '], $str);
        $str = preg_replace('/\s*\/\s*/u', ' / ', $str);
        $str = preg_replace('/\s+/u', ' ', $str);
        return trim($str);
    }
}

if (!function_exists('matchGenreToStandard')) {
    function matchGenreToStandard($rawToken, $catalog) {
        try {
            $rawClean = cleanGenreText($rawToken);
            if ($rawClean === '') return null;

            $cToken = canonicalGenreText($rawToken);
            if ($cToken === '') return null;

            // تشخیص وجود کاراکترهای فارسی و انگلیسی (شرط الزامی دوزبانه بودن ژانر)
            $hasPersianChars = (bool)preg_match('/[\x{0600}-\x{06FF}]/u', $rawClean);
            $hasEnglishChars = (bool)preg_match('/[a-zA-Z]/u', $rawClean);
            $isBilingualText = ($hasPersianChars && $hasEnglishChars);

            // ۱. بررسی تطابق با کاتالوگ ۳۱ ژانر استاندارد
            foreach ($catalog as $g) {
                $cName = canonicalGenreText($g['name']);       // e.g. "مستند documentary"
                $cFa   = canonicalGenreText($g['name_fa']);    // e.g. "مستند"
                $cEn   = canonicalGenreText($g['name_en']);    // e.g. "documentary"
                $cSlug = canonicalGenreText($g['slug']);       // e.g. "documentary"

                // آیا هر دو نام فارسی و انگلیسی در توکن وجود دارند؟
                $hasFa = ($cFa !== '' && strpos($cToken, $cFa) !== false);
                $hasEn = (($cEn !== '' && strpos($cToken, $cEn) !== false) || ($cSlug !== '' && strpos($cToken, $cSlug) !== false));

                // فقط و فقط اگر هر دو زبان در متن حاضر باشند و با کاتالوگ مطابقت داشته باشد، استاندارد کامل است
                if ($isBilingualText && ($cToken === $cName || $cToken === ($cEn . ' ' . $cFa) || ($hasFa && $hasEn))) {
                    return [
                        'genre' => $g,
                        'confidence' => 'exact',
                        'is_standard' => true // استاندارد کامل: دارای هر دو نام فارسی و انگلیسی
                    ];
                }

                // اگر فقط نام فارسی یا فقط نام انگلیسی داشت:
                // تطابق معنایی ۱۰۰٪ با ژانر دارد، اما ناقص/تک‌زبانه است و باید به فرمت دوزبانه استاندارد تبدیل شود!
                if ($cToken === $cFa || $cToken === $cEn || $cToken === $cSlug || $hasFa || $hasEn) {
                    return [
                        'genre' => $g,
                        'confidence' => 'high',
                        'is_standard' => false // پیشنهاد اصلاح به فرمت رسمی دوزبانه
                    ];
                }
            }

            // ۲. بررسی مترادف‌ها (Synonyms)
            foreach ($catalog as $g) {
                if (!empty($g['synonyms'])) {
                    foreach ($g['synonyms'] as $syn) {
                        $cSyn = canonicalGenreText($syn);
                        if ($cToken === $cSyn || strpos($cToken, $cSyn) !== false) {
                            return [
                                'genre' => $g,
                                'confidence' => 'high',
                                'is_standard' => false // مترادف‌ها باید به نام رسمی دوزبانه تبدیل شوند
                            ];
                        }
                    }
                }
            }

            // ۳. بررسی شباهت املایی با Levenshtein (فقط برای کلمات ۳ حرف به بالا)
            if (mb_strlen($cToken, 'UTF-8') >= 3) {
                $bestMatch = null;
                $minDist = 999;
                $tokenByteLen = strlen($cToken);

                foreach ($catalog as $g) {
                    $cFa = canonicalGenreText($g['name_fa']);
                    $faByteLen = strlen($cFa);
                    if ($tokenByteLen <= 255 && $faByteLen <= 255) {
                        $levFa = levenshtein($cToken, $cFa);
                        if ($levFa < $minDist && $levFa <= 2) {
                            $minDist = $levFa;
                            $bestMatch = $g;
                        }
                    }

                    $cEn = canonicalGenreText($g['name_en']);
                    $enByteLen = strlen($cEn);
                    if ($tokenByteLen <= 255 && $enByteLen <= 255) {
                        $levEn = levenshtein($cToken, $cEn);
                        if ($levEn < $minDist && $levEn <= 2) {
                            $minDist = $levEn;
                            $bestMatch = $g;
                        }
                    }
                }

                if ($bestMatch && $minDist <= 2) {
                    return [
                        'genre' => $bestMatch,
                        'confidence' => 'medium',
                        'is_standard' => false
                    ];
                }
            }

            return null;
        } catch (Throwable $e) {
            return null;
        }
    }
}


// ---------- مهاجرت امن اسکیما (ایجاد خودکار جداول جدید) ----------
function runSchemaMigrations($pdo)
{
    $migrations = [
        // گروه‌بندی لیست‌ها
        "CREATE TABLE IF NOT EXISTS `list_groups` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `color` varchar(20) NOT NULL DEFAULT '#6366f1',
            `description` text,
            `banner` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        // کتابخانه ژانرها
        "CREATE TABLE IF NOT EXISTS `genres` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `slug` varchar(120) DEFAULT NULL,
            `color` varchar(20) DEFAULT '#0891b2',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_user_genre` (`user_id`, `name`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    ];

    try {
        foreach ($migrations as $sql) {
            $pdo->exec($sql);
        }
    } catch (PDOException $e) {
        // برخی موتورهای MySQL از ADD COLUMN IF NOT EXISTS پشتیبانی نمی‌کنند؛
        // در صورت خطا، وجود ستون را بررسی می‌کنیم.
        if (strpos($e->getMessage(), "Duplicate column") !== false ||
            strpos($e->getMessage(), "already exists") !== false) {
            // ستون از قبل وجود دارد؛ ادامه بده
        } else {
            error_log("Migration error: " . $e->getMessage());
        }
    }

    // بررسی دستی وجود ستون group_id (برای سازگاری)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM lists LIKE 'group_id'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `lists` ADD COLUMN `group_id` int(11) DEFAULT NULL;");
        }
    } catch (PDOException $e) {
        error_log("group_id column check error: " . $e->getMessage());
    }
}

runSchemaMigrations($pdo);