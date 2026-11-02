<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "db_connect.php";

function sanitizeBannerField($banner) {
    if (!$banner || !is_string($banner)) return null;
    $b = trim($banner);
    if ($b === '' || strpos($b, '${') !== false || strpos($b, 'undefined') !== false || strpos($b, 'null') !== false) {
        return null;
    }
    return $b;
}

$action = $_REQUEST["action"] ?? "";

/* ============================================================
   بخش ۱: احراز هویت
   ============================================================ */

// ==================== REGISTER ====================
if ($action === "register") {
    $username = trim($_POST["username"] ?? "");
    $display_name = trim($_POST["display_name"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($display_name) || empty($password)) {
        jsonResponse(false, "لطفاً تمام فیلدها را پر کنید");
    }

    // اعتبارسنجی نام کاربری
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        jsonResponse(false, "نام کاربری باید ۳ تا ۳۰ کاراکتر و فقط شامل حروف انگلیسی، اعداد و زیرخط باشد");
    }

    // اعتبارسنجی حداقل طول رمز عبور
    if (strlen($password) < 6) {
        jsonResponse(false, "رمز عبور باید حداقل ۶ کاراکتر باشد");
    }

    // محدودسازی ثبت‌نام‌های ناموفق (ضد اسپم)
    $regKey = "reg_attempts_" . md5(strtolower($username));
    if (isset($_SESSION[$regKey]) && $_SESSION[$regKey] > 10) {
        jsonResponse(false, "تعداد تلاش‌های ثبت‌نام زیاد بود. لطفاً بعداً دوباره تلاش کنید");
    }
    $_SESSION[$regKey] = (int) ($_SESSION[$regKey] ?? 0) + 1;

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonResponse(false, "این نام کاربری قبلاً ثبت شده است");
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, display_name, password_hash) VALUES (?, ?, ?)"
    );
    if ($stmt->execute([$username, $display_name, $hash])) {
        unset($_SESSION[$regKey]);
        jsonResponse(true, "ثبت نام با موفقیت انجام شد");
    } else {
        jsonResponse(false, "خطا در ثبت نام");
    }
}

// ==================== LOGIN ====================
if ($action === "login") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        jsonResponse(false, "نام کاربری و رمز عبور الزامی است");
    }

    $maxAttempts = (int) env("LOGIN_MAX_ATTEMPTS", 5);
    $lockoutMinutes = (int) env("LOGIN_LOCKOUT_MINUTES", 15);
    $lockKey = "login_lock_" . md5(strtolower($username));
    $attemptKey = "login_attempts_" . md5(strtolower($username));

    if (isset($_SESSION[$lockKey]) && time() < $_SESSION[$lockKey]) {
        $remaining = ceil(($_SESSION[$lockKey] - time()) / 60);
        jsonResponse(false, "تلاش‌های ناموفق زیاد بود. لطفاً تا $remaining دقیقه دیگر دوباره تلاش کنید");
    }

    $stmt = $pdo->prepare(
        "SELECT id, username, display_name, password_hash FROM users WHERE username = ?"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {
        unset($_SESSION[$attemptKey], $_SESSION[$lockKey]);

        if (env("SESSION_REGENERATE_ON_LOGIN", 1) == 1) {
            session_regenerate_id(true);
        }

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["display_name"] = $user["display_name"];

        jsonResponse(true, "ورود موفق", [
            "id" => $user["id"],
            "username" => $user["username"],
            "display_name" => $user["display_name"],
        ]);
    } else {
        $attempts = (int) ($_SESSION[$attemptKey] ?? 0) + 1;
        if ($attempts >= $maxAttempts) {
            $_SESSION[$attemptKey] = 0;
            $_SESSION[$lockKey] = time() + ($lockoutMinutes * 60);
            jsonResponse(false, "تلاش‌های ناموفق زیاد بود. تا $lockoutMinutes دقیقه دیگر قفل شد");
        } else {
            $_SESSION[$attemptKey] = $attempts;
            $remaining = $maxAttempts - $attempts;
            jsonResponse(false, "نام کاربری یا رمز عبور اشتباه است. $remaining تلاش باقی مانده");
        }
    }
}

// ==================== LOGOUT ====================
if ($action === "logout") {
    session_destroy();
    jsonResponse(true, "خروج با موفقیت انجام شد");
}

// ==================== CHECK ====================
if ($action === "check") {
    if (isLoggedIn()) {
        jsonResponse(true, "لاگین هستید", [
            "display_name" => $_SESSION["display_name"],
        ]);
    } else {
        jsonResponse(false, "لاگین نیستید");
    }
}

// ==================== UPDATE PROFILE ====================
if ($action === "update_profile") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $display_name = trim($_POST["display_name"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($display_name)) {
        jsonResponse(false, "نام نمایشی الزامی است");
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            jsonResponse(false, "رمز عبور باید حداقل ۶ کاراکتر باشد");
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "UPDATE users SET display_name = ?, password_hash = ? WHERE id = ?"
        );
        $ok = $stmt->execute([$display_name, $hash, $_SESSION["user_id"]]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET display_name = ? WHERE id = ?");
        $ok = $stmt->execute([$display_name, $_SESSION["user_id"]]);
    }

    if ($ok) {
        $_SESSION["display_name"] = $display_name;
        jsonResponse(true, "پروفایل با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی");
    }
}


/* ============================================================
   بخش ۲: لیست‌ها
   ============================================================ */

// ==================== GET LISTS ====================
if ($action === "get_lists") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $stmt = $pdo->prepare(
        "SELECT id, name, color, description, banner, group_id, created_at FROM lists WHERE user_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$_SESSION["user_id"]]);
    $lists = $stmt->fetchAll();
    foreach ($lists as &$l) {
        $l["banner"] = sanitizeBannerField($l["banner"] ?? null);
    }
    unset($l);
    jsonResponse(true, "", $lists);
}

// ==================== ADD LIST ====================
if ($action === "add_list") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $name = trim($_POST["name"] ?? "");
    $color = $_POST["color"] ?? "#6366f1";
    $description = trim($_POST["description"] ?? "");

    if (empty($name)) {
        jsonResponse(false, "نام لیست الزامی است");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO lists (user_id, name, color, description) VALUES (?, ?, ?, ?)"
    );
    if ($stmt->execute([$_SESSION["user_id"], $name, $color, $description])) {
        $list_id = $pdo->lastInsertId();
        $share_code = generateUniqueShareCode();
        $stmt2 = $pdo->prepare("UPDATE lists SET share_code = ? WHERE id = ?");
        $stmt2->execute([$share_code, $list_id]);
        checkAllAchievements($pdo, $_SESSION["user_id"], "add_list");
        jsonResponse(true, "لیست با موفقیت ساخته شد", ["id" => $list_id]);
    } else {
        jsonResponse(false, "خطا در ساخت لیست");
    }
}

// ==================== DELETE LIST ====================
if ($action === "delete_list") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM lists WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$list_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "لیست حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف لیست");
    }
}

// ==================== UPDATE LIST COLOR ====================
if ($action === "update_list_color") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);
    $color = $_POST["color"] ?? "#6366f1";

    $stmt = $pdo->prepare(
        "UPDATE lists SET color = ? WHERE id = ? AND user_id = ?"
    );
    if ($stmt->execute([$color, $list_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "رنگ با موفقیت تغییر کرد");
    } else {
        jsonResponse(false, "خطا در تغییر رنگ");
    }
}

// ==================== UPDATE LIST ====================
if ($action === "update_list") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $color = $_POST["color"] ?? "#6366f1";
    $description = trim($_POST["description"] ?? "");

    if (empty($name)) {
        jsonResponse(false, "نام لیست الزامی است");
    }

    $stmt = $pdo->prepare(
        "UPDATE lists SET name = ?, color = ?, description = ? WHERE id = ? AND user_id = ?"
    );
    if (
        $stmt->execute([
            $name,
            $color,
            $description,
            $list_id,
            $_SESSION["user_id"],
        ])
    ) {
        jsonResponse(true, "لیست با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی لیست");
    }
}


/* ============================================================
   بخش ۳: گروه‌های لیست
   ============================================================ */

// ==================== GET GROUPS ====================
if ($action === "get_groups") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $stmt = $pdo->prepare(
        "SELECT * FROM list_groups WHERE user_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$_SESSION["user_id"]]);
    $groups = $stmt->fetchAll();

    foreach ($groups as &$g) {
        $stmt2 = $pdo->prepare(
            "SELECT id, name, color, description, banner FROM lists WHERE user_id = ? AND group_id = ? ORDER BY id DESC"
        );
        $stmt2->execute([$_SESSION["user_id"], $g["id"]]);
        $g["lists"] = $stmt2->fetchAll();
        foreach ($g["lists"] as &$gl) {
            $gl["banner"] = sanitizeBannerField($gl["banner"] ?? null);
        }
        unset($gl);
    }

    jsonResponse(true, "", $groups);
}

// ==================== ADD GROUP ====================
if ($action === "add_group") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $name = trim($_POST["name"] ?? "");
    $color = $_POST["color"] ?? "#6366f1";
    $description = trim($_POST["description"] ?? "");

    if (empty($name)) {
        jsonResponse(false, "نام گروه الزامی است");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO list_groups (user_id, name, color, description) VALUES (?, ?, ?, ?)"
    );
    if ($stmt->execute([$_SESSION["user_id"], $name, $color, $description])) {
        jsonResponse(true, "گروه با موفقیت ساخته شد", ["id" => $pdo->lastInsertId()]);
    } else {
        jsonResponse(false, "خطا در ساخت گروه");
    }
}

// ==================== UPDATE GROUP ====================
if ($action === "update_group") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $group_id = (int) ($_POST["group_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $color = $_POST["color"] ?? "#6366f1";
    $description = trim($_POST["description"] ?? "");

    if (empty($name)) {
        jsonResponse(false, "نام گروه الزامی است");
    }

    $stmt = $pdo->prepare(
        "UPDATE list_groups SET name = ?, color = ?, description = ? WHERE id = ? AND user_id = ?"
    );
    if ($stmt->execute([$name, $color, $description, $group_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "گروه با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی گروه");
    }
}

// ==================== DELETE GROUP ====================
if ($action === "delete_group") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $group_id = (int) ($_POST["group_id"] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM list_groups WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$group_id, $_SESSION["user_id"]])) {
        $stmt2 = $pdo->prepare("UPDATE lists SET group_id = NULL WHERE group_id = ? AND user_id = ?");
        $stmt2->execute([$group_id, $_SESSION["user_id"]]);
        jsonResponse(true, "گروه حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف گروه");
    }
}

// ==================== ADD LIST TO GROUP ====================
if ($action === "add_list_to_group") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $group_id = (int) ($_POST["group_id"] ?? 0);
    $list_id = (int) ($_POST["list_id"] ?? 0);

    $stmt = $pdo->prepare("SELECT id FROM list_groups WHERE id = ? AND user_id = ?");
    $stmt->execute([$group_id, $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "گروه یافت نشد");
    }

    $stmt = $pdo->prepare("UPDATE lists SET group_id = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$group_id, $list_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "لیست به گروه اضافه شد");
    } else {
        jsonResponse(false, "خطا در افزودن به گروه");
    }
}

// ==================== REMOVE LIST FROM GROUP ====================
if ($action === "remove_list_from_group") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);

    $stmt = $pdo->prepare("UPDATE lists SET group_id = NULL WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$list_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "لیست از گروه حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف از گروه");
    }
}


/* ============================================================
   بخش ۴: کتابخانه ژانر
   ============================================================ */

// Note: get_genres is unified below with the 30 standard catalog genres and usage statistics.

// ==================== ADD GENRE ====================
if ($action === "add_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $name = trim($_POST["name"] ?? "");
    $color = trim($_POST["color"] ?? "#0891b2");

    if (empty($name)) {
        jsonResponse(false, "نام ژانر الزامی است");
    }
    if (mb_strlen($name, "UTF-8") > 60) {
        jsonResponse(false, "نام ژانر نباید بیشتر از ۶۰ کاراکتر باشد");
    }

    $stmt = $pdo->prepare("SELECT id FROM genres WHERE user_id = ? AND name = ?");
    $stmt->execute([$_SESSION["user_id"], $name]);
    if ($stmt->fetch()) {
        jsonResponse(false, "این ژانر قبلاً در کتابخانه ثبت شده است");
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));

    $stmt = $pdo->prepare("INSERT INTO genres (user_id, name, slug, color) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION["user_id"], $name, $slug, $color])) {
        jsonResponse(true, "ژانر با موفقیت اضافه شد", ["id" => $pdo->lastInsertId()]);
    } else {
        jsonResponse(false, "خطا در افزودن ژانر");
    }
}

// ==================== DELETE GENRE ====================
if ($action === "delete_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $genre_id = (int) ($_POST["genre_id"] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM genres WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$genre_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "ژانر از کتابخانه حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف ژانر");
    }
}

// ==================== UPDATE GENRE ====================
if ($action === "update_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $genre_id = (int) ($_POST["genre_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $color = trim($_POST["color"] ?? "#0891b2");

    if (empty($name)) {
        jsonResponse(false, "نام ژانر الزامی است");
    }

    $stmt = $pdo->prepare("UPDATE genres SET name = ?, color = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$name, $color, $genre_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "ژانر با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی ژانر");
    }
}


/* ============================================================
   بخش ۵: فیلم‌ها
   ============================================================ */

// ==================== ADD MOVIE ====================
// ==================== GENRE LIBRARY (31 STANDARD GENRES & AUDIT ENGINE) ====================

/**
 * دریافت کاتالوگ ۳۲ ژانر استاندارد سینمایی و تلویزیونی به همراه ترجمه، رنگ و مترادف‌ها
 */
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

/**
 * نرمال‌سازی کلمه‌ای و پاک‌سازی کامل علائم (-، /، پرانتز و فواصل) برای تطبیق ۱۰۰ درصدی
 */
if (!function_exists('canonicalGenreText')) {
function canonicalGenreText($str) {
    $str = trim((string)$str);
    if ($str === '') return '';
    // تبدیل حروف عربی و انواع آ به الف ساده
    $str = str_replace(
        ['ي', 'ك', 'ة', 'ۀ', '‌', 'آ', 'أ', 'إ'],
        ['ی', 'ک', 'ه', 'ه', ' ', 'ا', 'ا', 'ا'],
        $str
    );
    // تبدیل تمام علائم نگارشی (اسلش، خط تیره، زیرخط، پرانتز، براکت، دونقطه، کاما و غیره) به فاصله
    $str = preg_replace('/[\-\_\/\\\|\(\)\[\]\.\:\,\;،؟\!\?]+/u', ' ', $str);
    // حروف کوچک در حالت یونیکد
    $str = mb_strtolower($str, 'UTF-8');
    // یکپارچه‌سازی فاصله‌های متوالی
    $str = preg_replace('/\s+/u', ' ', $str);
    return trim($str);
}
}

/**
 * پاک‌سازی و یکسان‌سازی رشته ژانر جهت نمایش
 */
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

/**
 * تطبیق توکن ژانر با ۳۰ ژانر استاندارد و تشخیص اشتباهات املایی/معنایی
 * با نادیده گرفتن علائم و تطابق کلمه‌به‌کلمه ۱۰۰ درصدی
 */
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

// ==================== ENDPOINTS ژانرها ====================

// دریافت کتابخانه ۳۰ ژانر استاندارد همراه با تعداد استفاده در عناوین کاربر
if ($action === "get_genres") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $catalog = getStandardGenresCatalog();
    $user_id = $_SESSION["user_id"];

    // واکشی تمام فیلدهای ژانر فیلم‌ها و سریال‌های کاربر
    $stmtM = $pdo->prepare("SELECT genre FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND genre IS NOT NULL AND genre != ''");
    $stmtM->execute([$user_id]);
    $rawMovieGenres = $stmtM->fetchAll(PDO::FETCH_COLUMN);

    $stmtS = $pdo->prepare("SELECT genre FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND genre IS NOT NULL AND genre != ''");
    $stmtS->execute([$user_id]);
    $rawSeriesGenres = $stmtS->fetchAll(PDO::FETCH_COLUMN);

    $allRawGenres = array_merge($rawMovieGenres, $rawSeriesGenres);

    // شمارش دفعات استفاده از هر ژانر استاندارد
    $counts = [];
    foreach ($catalog as $g) {
        $counts[$g['slug']] = 0;
    }

    foreach ($allRawGenres as $raw) {
        $tokens = preg_split('/[,،\r\n]+/u', (string)$raw);
        foreach ($tokens as $token) {
            $match = matchGenreToStandard($token, $catalog);
            if ($match && isset($match['genre']['slug'])) {
                $counts[$match['genre']['slug']]++;
            }
        }
    }

    // ادغام نتایج
    $result = [];
    foreach ($catalog as $g) {
        $g['usage_count'] = $counts[$g['slug']] ?? 0;
        $g['is_standard'] = 1;
        $result[] = $g;
    }

    jsonResponse(true, "", $result);
}

// بررسی و ممیزی ژانرهای عناوین کاربر (Genre Audit & Diagnostics)
if ($action === "get_genre_audit") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    try {
        $catalog = getStandardGenresCatalog();
        $user_id = $_SESSION["user_id"];

        // دریافت فیلم‌های کاربر
        $stmtM = $pdo->prepare("
            SELECT m.id, m.title, m.genre, m.list_id, COALESCE(l.name, '') AS list_name, 'movie' AS type 
            FROM movies m 
            LEFT JOIN lists l ON m.list_id = l.id 
            WHERE l.user_id = ? AND m.genre IS NOT NULL AND m.genre != ''
        ");
        $stmtM->execute([$user_id]);
        $movies = $stmtM->fetchAll();

        // دریافت سریال‌های کاربر
        $stmtS = $pdo->prepare("
            SELECT s.id, s.title, s.genre, s.list_id, COALESCE(l.name, '') AS list_name, 'series' AS type 
            FROM series s 
            LEFT JOIN lists l ON s.list_id = l.id 
            WHERE l.user_id = ? AND s.genre IS NOT NULL AND s.genre != ''
        ");
        $stmtS->execute([$user_id]);
        $series = $stmtS->fetchAll();

        $allTitles = array_merge($movies, $series);
        $totalTitlesChecked = count($allTitles);

        $issuesMap = [];
        $cleanTitlesCount = 0;
        $redundancyList = [];

        foreach ($allTitles as $item) {
            $tokens = preg_split('/[,،\r\n]+/u', (string)$item['genre']);
            $hasIssue = false;
            $matchedSlugs = [];

            foreach ($tokens as $token) {
                $tokenClean = cleanGenreText($token);
                if ($tokenClean === '') continue;

                $match = matchGenreToStandard($tokenClean, $catalog);
                if ($match && isset($match['genre']['slug'])) {
                    $matchedSlugs[$match['genre']['slug']] = $tokenClean;
                }

                // اگر با هیچ ژانری تطابق ۱۰۰٪ نداشت یا استاندارد نبود
                if (!$match || !$match['is_standard']) {
                    $hasIssue = true;
                    $key = canonicalGenreText($tokenClean);
                    if ($key === '') $key = mb_strtolower($tokenClean, 'UTF-8');

                    if (!isset($issuesMap[$key])) {
                        $issuesMap[$key] = [
                            'raw_genre' => $tokenClean,
                            'suggested_genre' => $match ? $match['genre'] : null,
                            'confidence' => $match ? $match['confidence'] : 'none',
                            'affected_count' => 0,
                            'items' => []
                        ];
                    }

                    $issuesMap[$key]['affected_count']++;
                    $issuesMap[$key]['items'][] = [
                        'id' => (int)$item['id'],
                        'type' => $item['type'],
                        'title' => $item['title'],
                        'full_genre' => $item['genre'],
                        'list_name' => $item['list_name']
                    ];
                }
            }

            // بررسی همپوشانی جفت ژانر Sitcom و Comedy
            if (isset($matchedSlugs['sitcom']) && isset($matchedSlugs['comedy'])) {
                $redundancyList[] = [
                    'id' => (int)$item['id'],
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'list_name' => $item['list_name'],
                    'full_genre' => $item['genre'],
                    'pair_type' => 'sitcom_comedy',
                    'pair_title' => 'کمدی موقعیت (Sitcom) و کمدی (Comedy)',
                    'desc' => 'این عنوان شامل هر دو ژانر سیتکام و کمدی است که همپوشان هستند.',
                    'genre1' => $matchedSlugs['sitcom'],
                    'genre2' => $matchedSlugs['comedy'],
                    'genre1_canonical' => 'کمدی موقعیت / Sitcom',
                    'genre2_canonical' => 'کمدی / Comedy'
                ];
            }

            // بررسی همپوشانی جفت ژانر Anime و Animation
            if (isset($matchedSlugs['anime']) && isset($matchedSlugs['animation'])) {
                $redundancyList[] = [
                    'id' => (int)$item['id'],
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'list_name' => $item['list_name'],
                    'full_genre' => $item['genre'],
                    'pair_type' => 'anime_animation',
                    'pair_title' => 'انیمه (Anime) و انیمیشن (Animation)',
                    'desc' => 'این عنوان شامل هر دو ژانر انیمه و انیمیشن است که همپوشان هستند.',
                    'genre1' => $matchedSlugs['anime'],
                    'genre2' => $matchedSlugs['animation'],
                    'genre1_canonical' => 'انیمه / Anime',
                    'genre2_canonical' => 'انیمیشن / Animation'
                ];
            }

            if (!$hasIssue) {
                $cleanTitlesCount++;
            }
        }

        $issuesList = array_values($issuesMap);
        // مرتب‌سازی بر اساس بیشترین تعداد عنوان تحت تاثیر
        usort($issuesList, function($a, $b) {
            return $b['affected_count'] - $a['affected_count'];
        });

        jsonResponse(true, "", [
            'total_titles_checked' => $totalTitlesChecked,
            'clean_titles_count' => $cleanTitlesCount,
            'non_standard_count' => count($issuesList),
            'issues' => $issuesList,
            'redundancies' => $redundancyList,
            'catalog' => $catalog
        ]);
    } catch (Throwable $e) {
        jsonResponse(false, "خطا در ارزیابی هوشمند ژانرها: " . $e->getMessage());
    }
}

// جایگزینی و اصلاح ژانر در عناوین کاربر (تکی یا دسته‌جمعی)
if ($action === "replace_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $oldGenre = cleanGenreText($_POST["old_genre"] ?? "");
    $newGenre = cleanGenreText($_POST["new_genre"] ?? "");
    $targetType = $_POST["target_type"] ?? "all"; // all, movie, series
    $targetId = isset($_POST["target_id"]) ? (int)$_POST["target_id"] : null;

    if (empty($oldGenre)) {
        jsonResponse(false, "ژانر قبلی الزامی است");
    }

    $isDelete = ($newGenre === '' || $newGenre === '__DELETE__');
    $cOldGenre = canonicalGenreText($oldGenre);
    $user_id = $_SESSION["user_id"];
    $updatedCount = 0;

    $pdo->beginTransaction();
    try {
        // ۱. پردازش فیلم‌ها
        if ($targetType === "all" || $targetType === "movie") {
            $sql = "SELECT m.id, m.genre FROM movies m JOIN lists l ON m.list_id = l.id WHERE l.user_id = ? AND m.genre IS NOT NULL AND m.genre != ''";
            $params = [$user_id];
            if ($targetId && $targetType === "movie") {
                $sql .= " AND m.id = ?";
                $params[] = $targetId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $movies = $stmt->fetchAll();

            $updateStmt = $pdo->prepare("UPDATE movies SET genre = ? WHERE id = ?");
            foreach ($movies as $m) {
                $tokens = preg_split('/[,،\r\n]+/u', (string)$m['genre']);
                $newTokens = [];
                $changed = false;

                foreach ($tokens as $t) {
                    $tc = cleanGenreText($t);
                    if ($tc === '') continue;

                    // بررسی تطابق کلمه‌ای یا دقیق
                    if (canonicalGenreText($tc) === $cOldGenre || mb_strtolower($tc, 'UTF-8') === mb_strtolower($oldGenre, 'UTF-8')) {
                        $changed = true;
                        if (!$isDelete) {
                            if (!in_array($newGenre, $newTokens)) {
                                $newTokens[] = $newGenre;
                            }
                        }
                    } else {
                        if (!in_array($tc, $newTokens)) {
                            $newTokens[] = $tc;
                        }
                    }
                }

                if ($changed) {
                    $newGenreStr = implode(', ', $newTokens);
                    $updateStmt->execute([$newGenreStr, $m['id']]);
                    $updatedCount++;
                }
            }
        }

        // ۲. پردازش سریال‌ها
        if ($targetType === "all" || $targetType === "series") {
            $sql = "SELECT s.id, s.genre FROM series s JOIN lists l ON s.list_id = l.id WHERE l.user_id = ? AND s.genre IS NOT NULL AND s.genre != ''";
            $params = [$user_id];
            if ($targetId && $targetType === "series") {
                $sql .= " AND s.id = ?";
                $params[] = $targetId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $seriesList = $stmt->fetchAll();

            $updateStmt = $pdo->prepare("UPDATE series SET genre = ? WHERE id = ?");
            foreach ($seriesList as $s) {
                $tokens = preg_split('/[,،\r\n]+/u', (string)$s['genre']);
                $newTokens = [];
                $changed = false;

                foreach ($tokens as $t) {
                    $tc = cleanGenreText($t);
                    if ($tc === '') continue;

                    if (canonicalGenreText($tc) === $cOldGenre || mb_strtolower($tc, 'UTF-8') === mb_strtolower($oldGenre, 'UTF-8')) {
                        $changed = true;
                        if (!$isDelete) {
                            if (!in_array($newGenre, $newTokens)) {
                                $newTokens[] = $newGenre;
                            }
                        }
                    } else {
                        if (!in_array($tc, $newTokens)) {
                            $newTokens[] = $tc;
                        }
                    }
                }

                if ($changed) {
                    $newGenreStr = implode(', ', $newTokens);
                    $updateStmt->execute([$newGenreStr, $s['id']]);
                    $updatedCount++;
                }
            }
        }

        $pdo->commit();
        $msg = $isDelete
            ? "ژانر در $updatedCount عنوان با موفقیت حذف شد"
            : "ژانر در $updatedCount عنوان با موفقیت جایگزین شد";
        jsonResponse(true, $msg, [
            'updated_count' => $updatedCount
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        jsonResponse(false, "خطا در جایگزینی ژانر: " . $e->getMessage());
    }
}

// اصلاح دسته‌جمعی ژانرهای پیشنهادی (Batch Fix All)
if ($action === "batch_fix_genres") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $rawFixes = $_POST["fixes"] ?? "[]";
    $fixes = is_array($rawFixes) ? $rawFixes : json_decode($rawFixes, true);
    if (!is_array($fixes) || empty($fixes)) {
        jsonResponse(false, "لیست اصلاحات نامعتبر یا خالی است");
    }

    $user_id = $_SESSION["user_id"];
    $totalUpdated = 0;

    $pdo->beginTransaction();
    try {
        // ایجاد نگاشت old => new
        $map = [];
        foreach ($fixes as $fix) {
            $old = cleanGenreText($fix['old'] ?? "");
            $new = cleanGenreText($fix['new'] ?? "");
            if (!empty($old)) {
                $cKey = canonicalGenreText($old);
                $map[$cKey] = $new;
                $map[mb_strtolower($old, 'UTF-8')] = $new;
            }
        }

        if (!empty($map)) {
            // فیلم‌ها
            $stmtM = $pdo->prepare("SELECT m.id, m.genre FROM movies m JOIN lists l ON m.list_id = l.id WHERE l.user_id = ? AND m.genre IS NOT NULL AND m.genre != ''");
            $stmtM->execute([$user_id]);
            $movies = $stmtM->fetchAll();
            $upM = $pdo->prepare("UPDATE movies SET genre = ? WHERE id = ?");

            foreach ($movies as $m) {
                $tokens = preg_split('/[,،\r\n]+/u', (string)$m['genre']);
                $newTokens = [];
                $changed = false;

                foreach ($tokens as $t) {
                    $tc = cleanGenreText($t);
                    if ($tc === '') continue;

                    $cTc = canonicalGenreText($tc);
                    $lower = mb_strtolower($tc, 'UTF-8');

                    if (isset($map[$cTc]) || isset($map[$lower])) {
                        $target = isset($map[$cTc]) ? $map[$cTc] : $map[$lower];
                        $isDelete = ($target === '' || $target === '__DELETE__');
                        if (!$isDelete) {
                            if (!in_array($target, $newTokens)) {
                                $newTokens[] = $target;
                            }
                        }
                        $changed = true;
                    } else {
                        if (!in_array($tc, $newTokens)) {
                            $newTokens[] = $tc;
                        }
                    }
                }

                if ($changed) {
                    $upM->execute([implode(', ', $newTokens), $m['id']]);
                    $totalUpdated++;
                }
            }

            // سریال‌ها
            $stmtS = $pdo->prepare("SELECT s.id, s.genre FROM series s JOIN lists l ON s.list_id = l.id WHERE l.user_id = ? AND s.genre IS NOT NULL AND s.genre != ''");
            $stmtS->execute([$user_id]);
            $seriesList = $stmtS->fetchAll();
            $upS = $pdo->prepare("UPDATE series SET genre = ? WHERE id = ?");

            foreach ($seriesList as $s) {
                $tokens = preg_split('/[,،\r\n]+/u', (string)$s['genre']);
                $newTokens = [];
                $changed = false;

                foreach ($tokens as $t) {
                    $tc = cleanGenreText($t);
                    if ($tc === '') continue;

                    $cTc = canonicalGenreText($tc);
                    $lower = mb_strtolower($tc, 'UTF-8');

                    if (isset($map[$cTc]) || isset($map[$lower])) {
                        $target = isset($map[$cTc]) ? $map[$cTc] : $map[$lower];
                        $isDelete = ($target === '' || $target === '__DELETE__');
                        if (!$isDelete) {
                            if (!in_array($target, $newTokens)) {
                                $newTokens[] = $target;
                            }
                        }
                        $changed = true;
                    } else {
                        if (!in_array($tc, $newTokens)) {
                            $newTokens[] = $tc;
                        }
                    }
                }

                if ($changed) {
                    $upS->execute([implode(', ', $newTokens), $s['id']]);
                    $totalUpdated++;
                }
            }
        }

        $pdo->commit();
        jsonResponse(true, "تعداد $totalUpdated عنوان با موفقیت یکپارچه‌سازی و اصلاح شد", [
            'updated_count' => $totalUpdated
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        jsonResponse(false, "خطا در اصلاح دسته‌جمعی: " . $e->getMessage());
    }
}

// به‌روزرسانی مستقیم ژانر یک عنوان مشخص (فیلم یا سریال)
if ($action === "update_media_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $type = $_POST["type"] ?? "movie"; // movie یا series
    $id = (int)($_POST["id"] ?? 0);
    $genre = cleanGenreText($_POST["genre"] ?? "");
    $user_id = $_SESSION["user_id"];

    if ($id <= 0) {
        jsonResponse(false, "شناسه عنوان نامعتبر است");
    }

    if ($type === "movie") {
        $stmt = $pdo->prepare("UPDATE movies SET genre = ? WHERE id = ? AND list_id IN (SELECT id FROM lists WHERE user_id = ?)");
    } else {
        $stmt = $pdo->prepare("UPDATE series SET genre = ? WHERE id = ? AND list_id IN (SELECT id FROM lists WHERE user_id = ?)");
    }

    if ($stmt->execute([$genre, $id, $user_id])) {
        jsonResponse(true, "ژانر عنوان با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی ژانر");
    }
}

// دریافت عناوین مرتبط با یک ژانر استاندارد
if ($action === "get_titles_by_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $genre_slug = trim($_REQUEST["slug"] ?? "");
    $catalog = getStandardGenresCatalog();
    $targetGenre = null;

    foreach ($catalog as $g) {
        if ($g['slug'] === $genre_slug || mb_strtolower($g['name_fa'], 'UTF-8') === mb_strtolower($genre_slug, 'UTF-8')) {
            $targetGenre = $g;
            break;
        }
    }

    if (!$targetGenre) {
        jsonResponse(false, "ژانر مورد نظر یافت نشد");
    }

    $user_id = $_SESSION["user_id"];

    // دریافت تمام عناوین کاربر
    $stmtM = $pdo->prepare("SELECT m.id, m.title, m.genre, m.release_year, m.rating, l.name AS list_name, 'movie' AS type FROM movies m JOIN lists l ON m.list_id = l.id WHERE l.user_id = ? AND m.genre IS NOT NULL AND m.genre != ''");
    $stmtM->execute([$user_id]);
    $movies = $stmtM->fetchAll();

    $stmtS = $pdo->prepare("SELECT s.id, s.title, s.genre, s.start_year AS release_year, NULL AS rating, l.name AS list_name, 'series' AS type FROM series s JOIN lists l ON s.list_id = l.id WHERE l.user_id = ? AND s.genre IS NOT NULL AND s.genre != ''");
    $stmtS->execute([$user_id]);
    $seriesList = $stmtS->fetchAll();

    $matchedTitles = [];
    foreach (array_merge($movies, $seriesList) as $item) {
        $tokens = preg_split('/[,،\r\n]+/u', (string)$item['genre']);
        foreach ($tokens as $t) {
            $match = matchGenreToStandard($t, $catalog);
            if ($match && $match['genre']['slug'] === $targetGenre['slug']) {
                $matchedTitles[] = $item;
                break;
            }
        }
    }

    jsonResponse(true, "", [
        'genre' => $targetGenre,
        'titles' => $matchedTitles,
        'count' => count($matchedTitles)
    ]);
}

// افزودن، حذف و ویرایش اختصاصی (پشتیبانی از کدهای قبلی)
if ($action === "add_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }
    jsonResponse(true, "ژانرهای استاندارد در کتابخانه فعال هستند");
}

if ($action === "delete_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }
    jsonResponse(true, "ژانرهای استاندارد پایه سیستم هستند و نیازی به حذف ندارند");
}

if ($action === "update_genre") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }
    jsonResponse(true, "ژانر به‌روزرسانی شد");
}


if ($action === "add_movie") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $release_year = $_POST["release_year"] ?? null;

    if (empty($title)) {
        jsonResponse(false, "عنوان فیلم الزامی است");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO movies (list_id, title, genre, release_year) VALUES (?, ?, ?, ?)"
    );
    if ($stmt->execute([$list_id, $title, $genre, $release_year])) {
        jsonResponse(true, "فیلم با موفقیت اضافه شد", [
            "id" => $pdo->lastInsertId(),
        ]);
    } else {
        jsonResponse(false, "خطا در افزودن فیلم");
    }
}

// ==================== UPDATE MOVIE ====================
if ($action === "update_movie") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $movie_id = (int) ($_POST["movie_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $release_year = $_POST["release_year"] ?? null;
    $downloaded = isset($_POST["downloaded"]) ? 1 : 0;
    $watched = isset($_POST["watched"]) ? 1 : 0;
    $watch_date = $_POST["watch_date"] ?? null;

    if ($watched == 0) {
        $watch_date = null;
    }

    if (empty($title)) {
        jsonResponse(false, "عنوان فیلم الزامی است");
    }

    $stmt = $pdo->prepare(
        "UPDATE movies SET title = ?, genre = ?, release_year = ?, downloaded = ?, watched = ?, watch_date = ? WHERE id = ?"
    );
    if (
        $stmt->execute([
            $title,
            $genre,
            $release_year,
            $downloaded,
            $watched,
            $watch_date,
            $movie_id,
        ])
    ) {
        jsonResponse(true, "فیلم با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی فیلم");
    }
}

// ==================== DELETE MOVIE ====================
if ($action === "delete_movie") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $movie_id = (int) ($_POST["movie_id"] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
    if ($stmt->execute([$movie_id])) {
        jsonResponse(true, "فیلم حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف فیلم");
    }
}


/* ============================================================
   بخش ۶: سریال‌ها
   ============================================================ */

// ==================== ADD SERIES ====================
if ($action === "add_series") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $start_year = $_POST["start_year"] ?? null;
    $end_year = $_POST["end_year"] ?? null;
    $status = $_POST["status"] ?? "ongoing";

    if (empty($title)) {
        jsonResponse(false, "عنوان سریال الزامی است");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO series (list_id, title, genre, start_year, end_year, status) VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (
        $stmt->execute([
            $list_id,
            $title,
            $genre,
            $start_year,
            $end_year,
            $status,
        ])
    ) {
        jsonResponse(true, "سریال با موفقیت اضافه شد", [
            "id" => $pdo->lastInsertId(),
        ]);
    } else {
        jsonResponse(false, "خطا در افزودن سریال");
    }
}

// ==================== UPDATE SERIES ====================
if ($action === "update_series") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $series_id = (int) ($_POST["series_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $start_year = $_POST["start_year"] ?? null;
    $end_year = $_POST["end_year"] ?? null;
    $status = $_POST["status"] ?? "ongoing";

    $stmt = $pdo->prepare(
        "UPDATE series SET title = ?, genre = ?, start_year = ?, end_year = ?, status = ? WHERE id = ?"
    );
    if (
        $stmt->execute([
            $title,
            $genre,
            $start_year,
            $end_year,
            $status,
            $series_id,
        ])
    ) {
        jsonResponse(true, "سریال با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی سریال");
    }
}

// ==================== DELETE SERIES ====================
if ($action === "delete_series") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $series_id = (int) ($_POST["series_id"] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM series WHERE id = ?");
    if ($stmt->execute([$series_id])) {
        jsonResponse(true, "سریال حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف سریال");
    }
}


/* ============================================================
   بخش ۷: فصل‌ها
   ============================================================ */

// ==================== ADD SEASON ====================
if ($action === "add_season") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $series_id = (int) ($_POST["series_id"] ?? 0);
    $season_number = (int) ($_POST["season_number"] ?? 0);
    $episode_count = (int) ($_POST["episode_count"] ?? 0);

    $stmt = $pdo->prepare(
        "INSERT INTO seasons (series_id, season_number, episode_count) VALUES (?, ?, ?)"
    );
    if ($stmt->execute([$series_id, $season_number, $episode_count])) {
        jsonResponse(true, "فصل با موفقیت اضافه شد");
    } else {
        jsonResponse(false, "خطا در افزودن فصل");
    }
}

// ==================== UPDATE SEASON ====================
if ($action === "update_season") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $season_id = (int) ($_POST["season_id"] ?? 0);
    $episode_count = (int) ($_POST["episode_count"] ?? 0);
    $downloaded = isset($_POST["downloaded"]) ? 1 : 0;
    $watched = isset($_POST["watched"]) ? 1 : 0;
    $watch_date = $_POST["watch_date"] ?? null;

    $stmt = $pdo->prepare(
        "UPDATE seasons SET episode_count = ?, downloaded = ?, watched = ?, watch_date = ? WHERE id = ?"
    );
    if (
        $stmt->execute([
            $episode_count,
            $downloaded,
            $watched,
            $watch_date,
            $season_id,
        ])
    ) {
        jsonResponse(true, "فصل با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی فصل");
    }
}

// ==================== DELETE SEASON ====================
if ($action === "delete_season") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $season_id = (int) ($_POST["season_id"] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM seasons WHERE id = ?");
    if ($stmt->execute([$season_id])) {
        jsonResponse(true, "فصل حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف فصل");
    }
}


/* ============================================================
   بخش ۸: دریافت لیست کامل
   ============================================================ */

// ==================== GET FULL LIST ====================
if ($action === "get_full_list") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_REQUEST["list_id"] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT id, name, color, description, banner FROM lists WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$list_id, $_SESSION["user_id"]]);
    $list = $stmt->fetch();

    if (!$list) {
        jsonResponse(false, "لیست یافت نشد");
    }
    $list["banner"] = sanitizeBannerField($list["banner"] ?? null);

    $stmt = $pdo->prepare(
        "SELECT * FROM movies WHERE list_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$list_id]);
    $movies = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT * FROM series WHERE list_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$list_id]);
    $series = $stmt->fetchAll();

    foreach ($series as &$s) {
        $stmt2 = $pdo->prepare(
            "SELECT * FROM seasons WHERE series_id = ? ORDER BY season_number"
        );
        $stmt2->execute([$s["id"]]);
        $s["seasons"] = $stmt2->fetchAll();
    }

    jsonResponse(true, "", [
        "list" => $list,
        "movies" => $movies,
        "series" => $series,
    ]);
}


/* ============================================================
   بخش ۹: مدیریت دانلود و تماشا
   ============================================================ */

// ==================== TOGGLE DOWNLOADED MOVIE ====================
if ($action === "toggle_downloaded_movie") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $movie_id = (int) ($_POST["movie_id"] ?? 0);
    $downloaded = (int) ($_POST["downloaded"] ?? 0);

    $stmt = $pdo->prepare("UPDATE movies SET downloaded = ? WHERE id = ?");
    if ($stmt->execute([$downloaded, $movie_id])) {
        jsonResponse(true, $downloaded ? "دانلود شد" : "دانلود لغو شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی");
    }
}

// ==================== MARK WATCHED MOVIE ====================
if ($action === "mark_watched_movie") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $movie_id = (int) ($_POST["movie_id"] ?? 0);
    $watched = (int) ($_POST["watched"] ?? 0);
    $watch_date = $_POST["watch_date"] ?? null;

    if ($watch_date === "" || $watch_date === null || $watch_date === "null") {
        $watch_date = 0;
    } else {
        $watch_date = (int) $watch_date;
    }

    if ($watched == 0) {
        $watch_date = 0;
    }

    $stmt = $pdo->prepare(
        "UPDATE movies SET watched = ?, watch_date = ? WHERE id = ?"
    );
    if ($stmt->execute([$watched, $watch_date, $movie_id])) {
        checkAllAchievements($pdo, $_SESSION["user_id"], "watch_movie");

        // ✅ چک کردن کامل شدن لیست
        if ($watched == 1) {
            $stmt2 = $pdo->prepare("SELECT list_id FROM movies WHERE id = ?");
            $stmt2->execute([$movie_id]);
            $listId = (int) $stmt2->fetchColumn();
            if ($listId > 0) {
                $stmt3 = $pdo->prepare("SELECT name FROM lists WHERE id = ?");
                $stmt3->execute([$listId]);
                $listName = $stmt3->fetchColumn() ?: '';
                checkListCompleteAchievement(
                    $pdo,
                    $_SESSION["user_id"],
                    $listId,
                    $listName
                );
            }
        }

        jsonResponse(true, $watched ? "تماشا شد" : "تماشا لغو شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی");
    }
}

// ==================== TOGGLE DOWNLOADED SEASON ====================
if ($action === "toggle_downloaded_season") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $season_id = (int) ($_POST["season_id"] ?? 0);
    $downloaded = (int) ($_POST["downloaded"] ?? 0);

    $stmt = $pdo->prepare("UPDATE seasons SET downloaded = ? WHERE id = ?");
    if ($stmt->execute([$downloaded, $season_id])) {
        jsonResponse(true, $downloaded ? "دانلود شد" : "دانلود لغو شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی");
    }
}

// ==================== MARK WATCHED SEASON ====================
if ($action === "mark_watched_season") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $season_id = (int) ($_POST["season_id"] ?? 0);
    $watched = (int) ($_POST["watched"] ?? 0);
    $watch_date = $_POST["watch_date"] ?? null;

    if ($watch_date === "" || $watch_date === null || $watch_date === "null") {
        $watch_date = 0;
    } else {
        $watch_date = (int) $watch_date;
    }

    if ($watched == 0) {
        $watch_date = 0;
    }

    // بررسی مالکیت
    $stmt = $pdo->prepare("
        SELECT s.id FROM seasons s 
        JOIN series sr ON s.series_id = sr.id 
        JOIN lists l ON sr.list_id = l.id 
        WHERE s.id = ? AND l.user_id = ?
    ");
    $stmt->execute([$season_id, $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare(
        "UPDATE seasons SET watched = ?, watch_date = ? WHERE id = ?"
    );
    if ($stmt->execute([$watched, $watch_date, $season_id])) {
        checkAllAchievements($pdo, $_SESSION["user_id"], "watch_season");

        // ✅ چک کردن کامل شدن لیست
        if ($watched == 1) {
            $stmt2 = $pdo->prepare("
                SELECT l.id, l.name 
                FROM seasons s 
                JOIN series sr ON s.series_id = sr.id 
                JOIN lists l ON sr.list_id = l.id 
                WHERE s.id = ?
            ");
            $stmt2->execute([$season_id]);
            $listInfo = $stmt2->fetch();
            if ($listInfo) {
                checkListCompleteAchievement(
                    $pdo,
                    $_SESSION["user_id"],
                    (int) $listInfo['id'],
                    $listInfo['name']
                );
            }
        }

        jsonResponse(true, $watched ? "تماشا شد" : "تماشا لغو شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی");
    }
}


/* ============================================================
   بخش ۱۰: پیش‌درآمد / دنباله / مجموعه
   ============================================================ */

// ==================== GET PREQUELS ====================
if ($action === "get_prequels") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $media_type = $_REQUEST["media_type"] ?? "";
    $media_id = (int) ($_REQUEST["media_id"] ?? 0);

    if (!in_array($media_type, ["movie", "series"]) || $media_id <= 0) {
        jsonResponse(false, "اطلاعات نامعتبر");
    }

    // چک کردن مالکیت
    if ($media_type === "movie") {
        $stmt = $pdo->prepare(
            "SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE m.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        if (!$stmt->fetch()) {
            jsonResponse(false, "دسترسی غیرمجاز");
        }
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.id FROM series s JOIN lists l ON s.list_id = l.id WHERE s.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        if (!$stmt->fetch()) {
            jsonResponse(false, "دسترسی غیرمجاز");
        }
    }

        // استخراج اطلاعات آیتم اصلی جهت نمایش در تایم‌لاین
    $mainMedia = null;
    if ($media_type === "movie") {
        $stmtMain = $pdo->prepare("
            SELECT m.id, m.title, m.release_year, m.genre, m.watched, m.rating, m.watch_date, c.comment 
            FROM movies m 
            LEFT JOIN comments c ON c.media_type = 'movie' AND c.media_id = m.id AND c.user_id = ?
            WHERE m.id = ?
        ");
        $stmtMain->execute([$_SESSION["user_id"], $media_id]);
        $mainMedia = $stmtMain->fetch();
        if ($mainMedia) {
            $mainMedia['media_type'] = 'movie';
        }
    } else {
        $stmtMain = $pdo->prepare("SELECT id, title, release_year, genre FROM series WHERE id = ?");
        $stmtMain->execute([$media_id]);
        $mainMedia = $stmtMain->fetch();
        if ($mainMedia) {
            $mainMedia['media_type'] = 'series';
            $mainMedia['watched'] = 0;
            $mainMedia['rating'] = null;
            $mainMedia['watch_date'] = null;
            $mainMedia['comment'] = null;
        }
    }

    $stmt = $pdo->prepare(
        "SELECT id, relation_type, title, release_year, related_type, sort_order, genre, watched, rating, comment, watch_date FROM prequels WHERE media_type = ? AND media_id = ? ORDER BY sort_order ASC, id ASC"
    );
    $stmt->execute([$media_type, $media_id]);
    $items = $stmt->fetchAll();

    jsonResponse(true, "", [
        "main_media" => $mainMedia,
        "items" => $items
    ]);
}

if ($action === "add_prequel") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $media_type = $_POST["media_type"] ?? "";
    $media_id = (int) ($_POST["media_id"] ?? 0);
    $relation_type = $_POST["relation_type"] ?? "prequel";
    $title = trim($_POST["title"] ?? "");
    $release_year = $_POST["release_year"] ?? null;
    $related_type = $_POST["related_type"] ?? "movie";
    $sort_order = (int) ($_POST["sort_order"] ?? 0);
    $genre = trim($_POST["genre"] ?? "");
    $watched = !empty($_POST["watched"]) ? 1 : 0;
    $rating = (isset($_POST["rating"]) && $_POST["rating"] !== "" && $_POST["rating"] !== "null") ? (int)$_POST["rating"] : null;
    $comment = trim($_POST["comment"] ?? "");

    if (!in_array($media_type, ["movie", "series"]) || $media_id <= 0) {
        jsonResponse(false, "اطلاعات نامعتبر");
    }

    if (!in_array($relation_type, ["prequel", "sequel", "collection"])) {
        jsonResponse(false, "نوع ارتباط نامعتبر است");
    }

    if (empty($title)) {
        jsonResponse(false, "عنوان نمی‌تواند خالی باشد");
    }

    if (
        $release_year &&
        (!is_numeric($release_year) ||
            $release_year < 1888 ||
            $release_year > 2100)
    ) {
        jsonResponse(false, "سال ساخت نامعتبر است");
    }

    if (!in_array($related_type, ["movie", "series"])) {
        jsonResponse(false, "نوع مدیا نامعتبر است");
    }

    // چک کردن مالکیت (ابتدا انجام شود)
    $hasAccess = false;
    if ($media_type === "movie") {
        $stmt = $pdo->prepare(
            "SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE m.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        $hasAccess = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.id FROM series s JOIN lists l ON s.list_id = l.id WHERE s.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        $hasAccess = $stmt->fetch();
    }

    if (!$hasAccess) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    // اگر sort_order صفر بود، آن را در انتهای لیست قرار دهیم
    if ($sort_order <= 0) {
        $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM prequels WHERE media_type = ? AND media_id = ?");
        $stmtMax->execute([$media_type, $media_id]);
        $sort_order = (int)$stmtMax->fetchColumn();
    }

    $watch_date = $_POST["watch_date"] ?? null;
    if (empty($watch_date) || $watch_date === "0" || $watch_date === 0) {
        $watch_date = null;
    }

    // درج در دیتابیس
    $stmt = $pdo->prepare(
        "INSERT INTO prequels (media_type, media_id, relation_type, title, release_year, related_type, sort_order, genre, watched, rating, comment, watch_date) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (
        $stmt->execute([
            $media_type,
            $media_id,
            $relation_type,
            $title,
            $release_year ? (int)$release_year : null,
            $related_type,
            $sort_order,
            $genre ?: null,
            $watched,
            $rating,
            $comment ?: null,
            $watch_date,
        ])
    ) {
        // بررسی و اعطای دستاوردهای مربوط به prequel
        checkAllAchievements($pdo, $_SESSION["user_id"], "add_prequel");

        jsonResponse(true, "با موفقیت اضافه شد", [
            "id" => $pdo->lastInsertId(),
        ]);
    } else {
        jsonResponse(false, "خطا در افزودن");
    }
}

if ($action === "update_prequel_item") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $prequel_id = (int) ($_POST["prequel_id"] ?? 0);
    if ($prequel_id <= 0) {
        jsonResponse(false, "شناسه آیتم نامعتبر است");
    }

    // چک کردن مالکیت
    $stmt = $pdo->prepare("
        SELECT p.id FROM prequels p 
        LEFT JOIN movies m ON p.media_type = 'movie' AND p.media_id = m.id 
        LEFT JOIN series s ON p.media_type = 'series' AND p.media_id = s.id 
        LEFT JOIN lists lm ON m.list_id = lm.id 
        LEFT JOIN lists ls ON s.list_id = ls.id 
        WHERE p.id = ? AND (lm.user_id = ? OR ls.user_id = ?)
    ");
    $stmt->execute([$prequel_id, $_SESSION["user_id"], $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $updates = [];
    $params = [];

    if (isset($_POST["watched"])) {
        $updates[] = "watched = ?";
        $params[] = !empty($_POST["watched"]) ? 1 : 0;
    }

    if (array_key_exists("watch_date", $_POST)) {
        $wd = trim($_POST["watch_date"] ?? "");
        $updates[] = "watch_date = ?";
        $params[] = (!empty($wd) && $wd !== "0" && $wd !== "null") ? $wd : null;
    }

    if (array_key_exists("rating", $_POST)) {
        $val = $_POST["rating"];
        if ($val === "" || $val === null || $val === "null") {
            $updates[] = "rating = NULL";
        } else {
            $r = (int)$val;
            $updates[] = "rating = ?";
            $params[] = max(1, min(10, $r));
        }
    }

    if (array_key_exists("comment", $_POST)) {
        $updates[] = "comment = ?";
        $params[] = trim($_POST["comment"] ?? "") ?: null;
    }

    if (isset($_POST["title"])) {
        $t = trim($_POST["title"]);
        if (!empty($t)) {
            $updates[] = "title = ?";
            $params[] = $t;
        }
    }

    if (array_key_exists("release_year", $_POST)) {
        $y = $_POST["release_year"];
        $updates[] = "release_year = ?";
        $params[] = (!empty($y) && is_numeric($y)) ? (int)$y : null;
    }

    if (array_key_exists("genre", $_POST)) {
        $updates[] = "genre = ?";
        $params[] = trim($_POST["genre"] ?? "") ?: null;
    }

    if (isset($_POST["relation_type"]) && in_array($_POST["relation_type"], ["prequel", "sequel", "collection"])) {
        $updates[] = "relation_type = ?";
        $params[] = $_POST["relation_type"];
    }

    if (isset($_POST["related_type"]) && in_array($_POST["related_type"], ["movie", "series"])) {
        $updates[] = "related_type = ?";
        $params[] = $_POST["related_type"];
    }

    if (isset($_POST["sort_order"])) {
        $updates[] = "sort_order = ?";
        $params[] = (int)$_POST["sort_order"];
    }

    if (empty($updates)) {
        jsonResponse(false, "هیچ فیلدی برای به‌روزرسانی ارسال نشد");
    }

    $params[] = $prequel_id;
    $sql = "UPDATE prequels SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        jsonResponse(true, "با موفقیت به‌روزرسانی شد");
    } else {
        jsonResponse(false, "خطا در به‌روزرسانی");
    }
}

if ($action === "reorder_prequels") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $media_type = $_POST["media_type"] ?? "";
    $media_id = (int) ($_POST["media_id"] ?? 0);
    $order_ids = $_POST["order_ids"] ?? [];

    if (is_string($order_ids)) {
        $order_ids = json_decode($order_ids, true) ?: array_filter(array_map('intval', explode(',', $order_ids)));
    }

    if (!in_array($media_type, ["movie", "series"]) || $media_id <= 0 || !is_array($order_ids)) {
        jsonResponse(false, "اطلاعات نامعتبر");
    }

    // چک کردن مالکیت
    $hasAccess = false;
    if ($media_type === "movie") {
        $stmt = $pdo->prepare(
            "SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE m.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        $hasAccess = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.id FROM series s JOIN lists l ON s.list_id = l.id WHERE s.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        $hasAccess = $stmt->fetch();
    }

    if (!$hasAccess) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare("UPDATE prequels SET sort_order = ? WHERE id = ? AND media_type = ? AND media_id = ?");
    $pdo->beginTransaction();
    try {
        foreach ($order_ids as $idx => $pid) {
            $stmt->execute([$idx + 1, (int)$pid, $media_type, $media_id]);
        }
        $pdo->commit();
        jsonResponse(true, "ترتیب ذخیره شد");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        jsonResponse(false, "خطا در ذخیره ترتیب: " . $e->getMessage());
    }
}

if ($action === "delete_prequel") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $prequel_id = (int) ($_POST["prequel_id"] ?? 0);

    $stmt = $pdo->prepare("
        SELECT p.id FROM prequels p 
        LEFT JOIN movies m ON p.media_type = 'movie' AND p.media_id = m.id 
        LEFT JOIN series s ON p.media_type = 'series' AND p.media_id = s.id 
        LEFT JOIN lists lm ON m.list_id = lm.id 
        LEFT JOIN lists ls ON s.list_id = ls.id 
        WHERE p.id = ? AND (lm.user_id = ? OR ls.user_id = ?)
    ");
    $stmt->execute([$prequel_id, $_SESSION["user_id"], $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare("DELETE FROM prequels WHERE id = ?");
    if ($stmt->execute([$prequel_id])) {
        jsonResponse(true, "حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف");
    }
}

// ==================== GET ALL PREQUELS FOR LIST ====================
if ($action === "get_all_prequels_for_list") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_REQUEST["list_id"] ?? 0);

    $stmt = $pdo->prepare("SELECT id FROM lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$list_id, $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare("
        SELECT p.* FROM prequels p 
        JOIN movies m ON p.media_type = 'movie' AND p.media_id = m.id 
        WHERE m.list_id = ?
    ");
    $stmt->execute([$list_id]);
    $movie_prequels = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT p.* FROM prequels p 
        JOIN series s ON p.media_type = 'series' AND p.media_id = s.id 
        WHERE s.list_id = ?
    ");
    $stmt->execute([$list_id]);
    $series_prequels = $stmt->fetchAll();

    jsonResponse(true, "", [
        "movies" => $movie_prequels,
        "series" => $series_prequels,
    ]);
}


/* ============================================================
   بخش ۱۱: امتیازدهی
   ============================================================ */

// ==================== RATE MOVIE ====================
if ($action === "rate_movie") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $movie_id = (int) ($_POST["movie_id"] ?? 0);
    $rating_value = $_POST["rating"] ?? null;

    if (
        $rating_value === "" ||
        $rating_value === null ||
        $rating_value === "null"
    ) {
        $rating = null;
    } else {
        $rating = (int) $rating_value;
        if ($rating < 1 || $rating > 10) {
            jsonResponse(false, "امتیاز باید بین ۱ تا ۱۰ باشد");
        }
    }

    $stmt = $pdo->prepare(
        "SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE m.id = ? AND l.user_id = ?"
    );
    $stmt->execute([$movie_id, $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare("UPDATE movies SET rating = ? WHERE id = ?");
    if ($stmt->execute([$rating, $movie_id])) {
        checkAllAchievements($pdo, $_SESSION["user_id"], "add_rating");
        jsonResponse(
            true,
            $rating !== null ? "امتیاز ثبت شد" : "امتیاز حذف شد"
        );
    } else {
        jsonResponse(false, "خطا در ثبت امتیاز");
    }
}

// ==================== RATE SEASON ====================
if ($action === "rate_season") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $season_id = (int) ($_POST["season_id"] ?? 0);
    $rating_value = $_POST["rating"] ?? null;

    if (
        $rating_value === "" ||
        $rating_value === null ||
        $rating_value === "null"
    ) {
        $rating = null;
    } else {
        $rating = (int) $rating_value;
        if ($rating < 1 || $rating > 10) {
            jsonResponse(false, "امتیاز باید بین ۱ تا ۱۰ باشد");
        }
    }

    $stmt = $pdo->prepare("
        SELECT s.id FROM seasons s 
        JOIN series sr ON s.series_id = sr.id 
        JOIN lists l ON sr.list_id = l.id 
        WHERE s.id = ? AND l.user_id = ?
    ");
    $stmt->execute([$season_id, $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare("UPDATE seasons SET rating = ? WHERE id = ?");
    if ($stmt->execute([$rating, $season_id])) {
        jsonResponse(
            true,
            $rating !== null ? "امتیاز ثبت شد" : "امتیاز حذف شد"
        );
    } else {
        jsonResponse(false, "خطا در ثبت امتیاز");
    }
}


/* ============================================================
   بخش ۱۲: بنر
   ============================================================ */

// ==================== UPLOAD BANNER ====================
if ($action === "upload_banner") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT id, banner FROM lists WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$list_id, $_SESSION["user_id"]]);
    $list = $stmt->fetch();

    if (!$list) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    if (
        !isset($_FILES["banner"]) ||
        $_FILES["banner"]["error"] !== UPLOAD_ERR_OK
    ) {
        jsonResponse(false, "لطفاً یک فایل انتخاب کنید");
    }

    $file = $_FILES["banner"];

    $allowed_types = ["image/jpeg", "image/png", "image/jpg"];
    if (!in_array($file["type"], $allowed_types)) {
        jsonResponse(false, "فقط فایل‌های JPG و PNG مجاز هستند");
    }

    if ($file["size"] > 5 * 1024 * 1024) {
        jsonResponse(false, "حجم فایل نباید بیشتر از ۵ مگابایت باشد");
    }

    $upload_dir = __DIR__ . "/../uploads/banners/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if ($list["banner"] && file_exists($upload_dir . $list["banner"])) {
        unlink($upload_dir . $list["banner"]);
    }

    $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $banner_name = "banner_" . $list_id . "_" . time() . "." . $extension;
    $upload_path = $upload_dir . $banner_name;

    if (move_uploaded_file($file["tmp_name"], $upload_path)) {
        $stmt = $pdo->prepare("UPDATE lists SET banner = ? WHERE id = ?");
        if ($stmt->execute([$banner_name, $list_id])) {
            jsonResponse(true, "بنر با موفقیت آپلود شد", [
                "banner" => $banner_name,
            ]);
        } else {
            unlink($upload_path);
            jsonResponse(false, "خطا در ذخیره اطلاعات");
        }
    } else {
        jsonResponse(false, "خطا در آپلود فایل");
    }
}

// ==================== REMOVE BANNER ====================
if ($action === "remove_banner") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT id, banner FROM lists WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$list_id, $_SESSION["user_id"]]);
    $list = $stmt->fetch();

    if (!$list) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    if ($list["banner"]) {
        $upload_dir = __DIR__ . "/../uploads/banners/";
        $file_path = $upload_dir . $list["banner"];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $stmt = $pdo->prepare("UPDATE lists SET banner = NULL WHERE id = ?");
    if ($stmt->execute([$list_id])) {
        jsonResponse(true, "بنر با موفقیت حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف بنر");
    }
}


/* ============================================================
   بخش ۱۳: کامنت‌های فیلم و فصل
   ============================================================ */

// ==================== GET COMMENT ====================
if ($action === "get_comment") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $media_type = $_REQUEST["media_type"] ?? "";
    $media_id = (int) ($_REQUEST["media_id"] ?? 0);

    if (!in_array($media_type, ["movie", "season"]) || $media_id <= 0) {
        jsonResponse(false, "اطلاعات نامعتبر");
    }

    if ($media_type === "movie") {
        $stmt = $pdo->prepare(
            "SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE m.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        if (!$stmt->fetch()) {
            jsonResponse(false, "دسترسی غیرمجاز");
        }
    } else {
        $stmt = $pdo->prepare("
            SELECT s.id FROM seasons s 
            JOIN series sr ON s.series_id = sr.id 
            JOIN lists l ON sr.list_id = l.id 
            WHERE s.id = ? AND l.user_id = ?
        ");
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        if (!$stmt->fetch()) {
            jsonResponse(false, "دسترسی غیرمجاز");
        }
    }

    $stmt = $pdo->prepare("
        SELECT c.id, c.comment, c.created_at, c.updated_at, u.display_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.media_type = ? AND c.media_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$media_type, $media_id]);
    $comment = $stmt->fetch();

    jsonResponse(true, "", $comment ?: null);
}

// ==================== SAVE COMMENT ====================
if ($action === "save_comment") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $media_type = $_POST["media_type"] ?? "";
    $media_id = (int) ($_POST["media_id"] ?? 0);
    $comment_text = trim($_POST["comment"] ?? "");

    if (!in_array($media_type, ["movie", "season"]) || $media_id <= 0) {
        jsonResponse(false, "اطلاعات نامعتبر");
    }

    if (empty($comment_text)) {
        jsonResponse(false, "متن کامنت نمی‌تواند خالی باشد");
    }

    if (mb_strlen($comment_text, "UTF-8") > 1000) {
        jsonResponse(false, "کامنت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد");
    }

    if ($media_type === "movie") {
        $stmt = $pdo->prepare(
            "SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE m.id = ? AND l.user_id = ?"
        );
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        if (!$stmt->fetch()) {
            jsonResponse(false, "دسترسی غیرمجاز");
        }
    } else {
        $stmt = $pdo->prepare("
            SELECT s.id FROM seasons s 
            JOIN series sr ON s.series_id = sr.id 
            JOIN lists l ON sr.list_id = l.id 
            WHERE s.id = ? AND l.user_id = ?
        ");
        $stmt->execute([$media_id, $_SESSION["user_id"]]);
        if (!$stmt->fetch()) {
            jsonResponse(false, "دسترسی غیرمجاز");
        }
    }

    $stmt = $pdo->prepare(
        "SELECT id FROM comments WHERE media_type = ? AND media_id = ? AND user_id = ?"
    );
    $stmt->execute([$media_type, $media_id, $_SESSION["user_id"]]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE comments SET comment = ? WHERE id = ?");
        if ($stmt->execute([$comment_text, $existing["id"]])) {
            checkAllAchievements($pdo, $_SESSION["user_id"], "add_comment");
            jsonResponse(true, "کامنت با موفقیت به‌روزرسانی شد", [
                "id" => $existing["id"],
            ]);
        } else {
            jsonResponse(false, "خطا در به‌روزرسانی کامنت");
        }
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO comments (user_id, media_type, media_id, comment) VALUES (?, ?, ?, ?)"
        );
        if (
            $stmt->execute([
                $_SESSION["user_id"],
                $media_type,
                $media_id,
                $comment_text,
            ])
        ) {
            checkAllAchievements($pdo, $_SESSION["user_id"], "add_comment");
            jsonResponse(true, "کامنت با موفقیت ثبت شد", [
                "id" => $pdo->lastInsertId(),
            ]);
        } else {
            jsonResponse(false, "خطا در ثبت کامنت");
        }
    }
}

// ==================== DELETE COMMENT ====================
if ($action === "delete_comment") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $comment_id = (int) ($_POST["comment_id"] ?? 0);

    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$comment_id, $_SESSION["user_id"]])) {
        jsonResponse(true, "کامنت با موفقیت حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف کامنت");
    }
}

// ==================== GET ALL COMMENTS FOR LIST ====================
if ($action === "get_all_comments_for_list") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_REQUEST["list_id"] ?? 0);

    $stmt = $pdo->prepare("SELECT id FROM lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$list_id, $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare("
        SELECT c.*, u.display_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN movies m ON c.media_type = 'movie' AND c.media_id = m.id 
        WHERE m.list_id = ?
    ");
    $stmt->execute([$list_id]);
    $movie_comments = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT c.*, u.display_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN seasons s ON c.media_type = 'season' AND c.media_id = s.id 
        JOIN series sr ON s.series_id = sr.id 
        WHERE sr.list_id = ?
    ");
    $stmt->execute([$list_id]);
    $season_comments = $stmt->fetchAll();

    jsonResponse(true, "", [
        "movies" => $movie_comments,
        "seasons" => $season_comments,
    ]);
}


/* ============================================================
   بخش ۱۴: تاریخ شمسی
   ============================================================ */

// ==================== GET PERSIAN DATE ====================
if ($action === "get_persian_date") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $result = getPersianDateFromTimeIR();
    jsonResponse(
        $result["ok"],
        $result["message"] ?? "",
        $result["data"] ?? null
    );
}

function getPersianDateFromTimeIR()
{
    $url = "https://time.ir/";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    );

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$html) {
        return getFallbackPersianDate();
    }

    if (preg_match("/(\d{4})\/(\d{2})\/(\d{2})/", $html, $matches)) {
        $year = $matches[1];
        $month = $matches[2];
        $day = $matches[3];
        $dateNumber = intval($year . $month . $day);

        return [
            "ok" => true,
            "message" => "تاریخ دریافت شد",
            "data" => [
                "date" => $dateNumber,
                "date_string" => "$year-$month-$day",
                "year" => $year,
                "month" => $month,
                "day" => $day,
            ],
        ];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, "HTML-ENTITIES", "UTF-8"));
    $xpath = new DOMXPath($dom);

    $selectors = [
        "//span[@class='date']",
        "//div[@class='date']",
        "//span[contains(@class, 'date')]",
        "//div[contains(@class, 'date')]",
    ];

    foreach ($selectors as $selector) {
        $elements = $xpath->query($selector);
        if ($elements->length > 0) {
            $dateText = trim($elements->item(0)->nodeValue);
            if (
                preg_match("/(\d{4})\/(\d{2})\/(\d{2})/", $dateText, $matches)
            ) {
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
                $dateNumber = intval($year . $month . $day);

                return [
                    "ok" => true,
                    "message" => "تاریخ دریافت شد",
                    "data" => [
                        "date" => $dateNumber,
                        "date_string" => "$year-$month-$day",
                        "year" => $year,
                        "month" => $month,
                        "day" => $day,
                    ],
                ];
            }
        }
    }

    return getFallbackPersianDate();
}

function getFallbackPersianDate()
{
    $timestamp = time();
    $jalali = jdtogregorian(
        gregoriantojd(date("m"), date("d"), date("Y")) - 226899
    );

    $altUrl = "http://api.aladine.ir/api/date";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $altUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data["date"])) {
            $dateNumber = intval(str_replace("-", "", $data["date"]));
            return [
                "ok" => true,
                "message" => "تاریخ از سرویس جایگزین دریافت شد",
                "data" => ["date" => $dateNumber],
            ];
        }
    }

    $persianDate = persian_date("Ymd");
    if ($persianDate) {
        return [
            "ok" => true,
            "message" => "تاریخ شمسی محاسبه شد",
            "data" => ["date" => intval($persianDate)],
        ];
    }

    return [
        "ok" => false,
        "message" => "خطا در دریافت تاریخ امروز",
    ];
}

function simpleGregorianToJalali($gy, $gm, $gd)
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = $gm > 2 ? $gy + 1 : $gy;
    $days =
        355666 +
        365 * $gy +
        floor(($gy2 + 3) / 4) -
        floor(($gy2 + 99) / 100) +
        floor(($gy2 + 399) / 400) +
        $gd +
        $g_d_m[$gm - 1];
    $jy = -1595 + 33 * floor($days / 12053);
    $days %= 12053;
    $jm = floor($days / 31);
    $days %= 31;
    if ($jm > 6) {
        $jm = 6;
        $days = 0;
    }
    $jd = $days + 1;
    return [$jy, $jm + 1, $jd];
}

function persian_date($format)
{
    $gy = date("Y");
    $gm = date("m");
    $gd = date("d");
    list($jy, $jm, $jd) = simpleGregorianToJalali($gy, $gm, $gd);

    if ($format === "Ymd") {
        return sprintf("%04d%02d%02d", $jy, $jm, $jd);
    }
    return sprintf("%04d/%02d/%02d", $jy, $jm, $jd);
}

function persian_date_from_gregorian($year, $month, $day)
{
    list($jy, $jm, $jd) = simpleGregorianToJalali($year, $month, $day);
    return sprintf("%04d/%02d/%02d", $jy, $jm, $jd);
}

function getDayOfYear($year, $month, $day)
{
    $daysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
    $isLeap =
        $year % 33 === 1 ||
        $year % 33 === 5 ||
        $year % 33 === 9 ||
        $year % 33 === 13 ||
        $year % 33 === 17 ||
        $year % 33 === 22 ||
        $year % 33 === 26 ||
        $year % 33 === 30;
    if ($isLeap) {
        $daysInMonth[11] = 30;
    }

    $dayOfYear = 0;
    for ($i = 0; $i < $month - 1; $i++) {
        $dayOfYear += $daysInMonth[$i];
    }
    $dayOfYear += $day;
    return $dayOfYear;
}


/* ============================================================
   بخش ۱۵: اشتراک‌گذاری لیست
   ============================================================ */

// ==================== GET SHARE CODE ====================
if ($action === "get_share_code") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_REQUEST["list_id"] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT share_code FROM lists WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$list_id, $_SESSION["user_id"]]);
    $list = $stmt->fetch();

    if (!$list) {
        jsonResponse(false, "لیست یافت نشد");
    }

    jsonResponse(true, "", ["share_code" => $list["share_code"]]);
}

// ==================== REGENERATE SHARE CODE ====================
if ($action === "regenerate_share_code") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $list_id = (int) ($_POST["list_id"] ?? 0);

    $stmt = $pdo->prepare("SELECT id FROM lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$list_id, $_SESSION["user_id"]]);
    if (!$stmt->fetch()) {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $new_code = generateUniqueShareCode();

    $stmt = $pdo->prepare("UPDATE lists SET share_code = ? WHERE id = ?");
    if ($stmt->execute([$new_code, $list_id])) {
        checkAllAchievements($pdo, $_SESSION["user_id"], "share_list");
        jsonResponse(true, "کد اشتراک جدید ساخته شد", [
            "share_code" => $new_code,
        ]);
    } else {
        jsonResponse(false, "خطا در بازسازی کد");
    }
}

// ==================== GET SHARED LIST ====================
if ($action === "get_shared_list") {
    $share_code = trim($_REQUEST["share_code"] ?? "");

    if (empty($share_code) || strlen($share_code) != 15) {
        jsonResponse(false, "کد اشتراک نامعتبر است");
    }

    $list = getListByShareCode($share_code);

    if (!$list) {
        jsonResponse(false, "لیست مورد نظر یافت نشد");
    }

    $stmt = $pdo->prepare(
        "SELECT * FROM movies WHERE list_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$list["id"]]);
    $movies = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT * FROM series WHERE list_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$list["id"]]);
    $series = $stmt->fetchAll();

    foreach ($series as &$s) {
        $stmt2 = $pdo->prepare(
            "SELECT * FROM seasons WHERE series_id = ? ORDER BY season_number"
        );
        $stmt2->execute([$s["id"]]);
        $s["seasons"] = $stmt2->fetchAll();
    }

    $stmt = $pdo->prepare("
        SELECT p.* FROM prequels p 
        JOIN movies m ON p.media_type = 'movie' AND p.media_id = m.id 
        WHERE m.list_id = ?
    ");
    $stmt->execute([$list["id"]]);
    $movie_prequels = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT p.* FROM prequels p 
        JOIN series s ON p.media_type = 'series' AND p.media_id = s.id 
        WHERE s.list_id = ?
    ");
    $stmt->execute([$list["id"]]);
    $series_prequels = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT c.*, u.display_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN movies m ON c.media_type = 'movie' AND c.media_id = m.id 
        WHERE m.list_id = ?
    ");
    $stmt->execute([$list["id"]]);
    $movie_comments = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT c.*, u.display_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        JOIN seasons s ON c.media_type = 'season' AND c.media_id = s.id 
        JOIN series sr ON s.series_id = sr.id 
        WHERE sr.list_id = ?
    ");
    $stmt->execute([$list["id"]]);
    $season_comments = $stmt->fetchAll();

    jsonResponse(true, "", [
        "list" => $list,
        "movies" => $movies,
        "series" => $series,
        "prequels" => [
            "movies" => $movie_prequels,
            "series" => $series_prequels,
        ],
        "comments" => [
            "movies" => $movie_comments,
            "seasons" => $season_comments,
        ],
        "is_owner" => isLoggedIn() && $_SESSION["user_id"] == $list["user_id"],
    ]);
}


/* ============================================================
   بخش ۱۶: کامنت‌های لیست
   ============================================================ */

function getListComments($pdo, $list_id, $limit = 50, $offset = 0)
{
    $stmt = $pdo->prepare("
        SELECT lc.*, u.display_name, u.username 
        FROM list_comments lc 
        JOIN users u ON lc.user_id = u.id 
        WHERE lc.list_id = ? 
        ORDER BY lc.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $list_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countListComments($pdo, $list_id)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM list_comments WHERE list_id = ?"
    );
    $stmt->execute([$list_id]);
    return $stmt->fetchColumn();
}

function canDeleteListComment($pdo, $comment_id, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT lc.id FROM list_comments lc 
        JOIN lists l ON lc.list_id = l.id 
        WHERE lc.id = ? AND (lc.user_id = ? OR l.user_id = ?)
    ");
    $stmt->execute([$comment_id, $user_id, $user_id]);
    return $stmt->fetch() !== false;
}

function canEditListComment($pdo, $comment_id, $user_id)
{
    $stmt = $pdo->prepare(
        "SELECT id FROM list_comments WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$comment_id, $user_id]);
    return $stmt->fetch() !== false;
}

// ==================== GET LIST COMMENTS ====================
if ($action === "get_list_comments") {
    $list_id = (int) ($_REQUEST["list_id"] ?? 0);
    $share_code = trim($_REQUEST["share_code"] ?? "");

    if (!empty($share_code)) {
        $stmt = $pdo->prepare("SELECT l.*, u.display_name as owner_name 
                               FROM lists l 
                               JOIN users u ON l.user_id = u.id 
                               WHERE l.share_code = ?");
        $stmt->execute([$share_code]);
        $list = $stmt->fetch();
        if (!$list) {
            jsonResponse(false, "لیست مورد نظر یافت نشد");
        }
        $list_id = $list["id"];
    }

    if ($list_id <= 0) {
        jsonResponse(false, "شناسه لیست نامعتبر است");
    }

    $limit = (int) ($_REQUEST["limit"] ?? 50);
    $offset = (int) ($_REQUEST["offset"] ?? 0);

    $comments = getListComments($pdo, $list_id, $limit, $offset);
    $total = countListComments($pdo, $list_id);

    if (isLoggedIn()) {
        $user_id = $_SESSION["user_id"];
        foreach ($comments as &$comment) {
            $comment["can_edit"] = $comment["user_id"] == $user_id;
            $comment["can_delete"] = canDeleteListComment(
                $pdo,
                $comment["id"],
                $user_id
            );
        }
    }

    jsonResponse(true, "", [
        "comments" => $comments,
        "total" => $total,
        "has_more" => $offset + $limit < $total,
    ]);
}

// ==================== ADD LIST COMMENT ====================
if ($action === "add_list_comment") {
    if (!isLoggedIn()) {
        jsonResponse(false, "برای ثبت کامنت باید وارد شوید");
    }

    $share_code = trim($_POST["share_code"] ?? "");
    $list_id = (int) ($_POST["list_id"] ?? 0);
    $comment_text = trim($_POST["comment"] ?? "");

    if (!empty($share_code)) {
        $stmt = $pdo->prepare(
            "SELECT id, user_id FROM lists WHERE share_code = ?"
        );
        $stmt->execute([$share_code]);
        $list = $stmt->fetch();
        if (!$list) {
            jsonResponse(false, "لیست مورد نظر یافت نشد");
        }
        $list_id = $list["id"];
        $list_owner_id = $list["user_id"];
    } elseif ($list_id > 0) {
        $stmt = $pdo->prepare("SELECT user_id FROM lists WHERE id = ?");
        $stmt->execute([$list_id]);
        $list = $stmt->fetch();
        $list_owner_id = $list ? $list["user_id"] : 0;
    }

    if ($list_id <= 0) {
        jsonResponse(false, "شناسه لیست نامعتبر است");
    }

    if (empty($comment_text)) {
        jsonResponse(false, "متن کامنت نمی‌تواند خالی باشد");
    }

    $comment_length = mb_strlen($comment_text, "UTF-8");
    if ($comment_length > 1000) {
        jsonResponse(false, "کامنت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد");
    }

    $stmt = $pdo->prepare("
        INSERT INTO list_comments (list_id, user_id, comment) 
        VALUES (?, ?, ?)
    ");

    if ($stmt->execute([$list_id, $_SESSION["user_id"], $comment_text])) {
        $user_stmt = $pdo->prepare(
            "SELECT display_name, username FROM users WHERE id = ?"
        );
        $user_stmt->execute([$_SESSION["user_id"]]);
        $user = $user_stmt->fetch();

        jsonResponse(true, "کامنت با موفقیت ثبت شد", [
            "id" => $pdo->lastInsertId(),
            "display_name" => $user["display_name"],
            "username" => $user["username"],
            "created_at" => date("Y-m-d H:i:s"),
            "comment" => $comment_text,
        ]);
    } else {
        jsonResponse(false, "خطا در ثبت کامنت");
    }
}

// ==================== EDIT LIST COMMENT ====================
if ($action === "edit_list_comment") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $comment_id = (int) ($_POST["comment_id"] ?? 0);
    $comment_text = trim($_POST["comment"] ?? "");

    if ($comment_id <= 0) {
        jsonResponse(false, "شناسه کامنت نامعتبر است");
    }

    if (empty($comment_text)) {
        jsonResponse(false, "متن کامنت نمی‌تواند خالی باشد");
    }

    $comment_length = mb_strlen($comment_text, "UTF-8");
    if ($comment_length > 1000) {
        jsonResponse(false, "کامنت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد");
    }

    if (!canEditListComment($pdo, $comment_id, $_SESSION["user_id"])) {
        jsonResponse(false, "شما اجازه ویرایش این کامنت را ندارید");
    }

    $stmt = $pdo->prepare("UPDATE list_comments SET comment = ? WHERE id = ?");
    if ($stmt->execute([$comment_text, $comment_id])) {
        jsonResponse(true, "کامنت با موفقیت ویرایش شد");
    } else {
        jsonResponse(false, "خطا در ویرایش کامنت");
    }
}

// ==================== DELETE LIST COMMENT ====================
if ($action === "delete_list_comment") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $comment_id = (int) ($_POST["comment_id"] ?? 0);

    if ($comment_id <= 0) {
        jsonResponse(false, "شناسه کامنت نامعتبر است");
    }

    if (!canDeleteListComment($pdo, $comment_id, $_SESSION["user_id"])) {
        jsonResponse(false, "شما اجازه حذف این کامنت را ندارید");
    }

    $stmt = $pdo->prepare("DELETE FROM list_comments WHERE id = ?");
    if ($stmt->execute([$comment_id])) {
        jsonResponse(true, "کامنت با موفقیت حذف شد");
    } else {
        jsonResponse(false, "خطا در حذف کامنت");
    }
}


/* ============================================================
   بخش ۱۷: پروفایل کاربران و جستجو
   ============================================================ */

// ==================== GET PUBLIC PROFILE STATUS ====================
if ($action === "get_public_profile_status") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_public'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0"
            );
        }

        $stmt = $pdo->prepare("SELECT is_public FROM users WHERE id = ?");
        $stmt->execute([$_SESSION["user_id"]]);
        $result = $stmt->fetch();

        jsonResponse(true, "", [
            "is_public" => $result ? (int) $result["is_public"] : 0,
        ]);
    } catch (Exception $e) {
        jsonResponse(true, "", ["is_public" => 0]);
    }
}

// ==================== TOGGLE PUBLIC PROFILE ====================
if ($action === "toggle_public_profile") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $is_public = (int) ($_POST["is_public"] ?? 0);

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_public'");
        $stmt->execute();
        $exists = $stmt->fetch();

        if (!$exists) {
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0"
            );
        }

        $stmt = $pdo->prepare("UPDATE users SET is_public = ? WHERE id = ?");
        if ($stmt->execute([$is_public, $_SESSION["user_id"]])) {
            // ✅ چک کردن دستاورد Social بعد از تغییر
            checkSpecialAchievements($pdo, $_SESSION["user_id"]);

            jsonResponse(
                true,
                $is_public ? "پروفایل عمومی شد" : "پروفایل خصوصی شد"
            );
        } else {
            jsonResponse(false, "خطا در تغییر وضعیت");
        }
    } catch (Exception $e) {
        jsonResponse(false, "خطا در سرور: " . $e->getMessage());
    }
}

// ==================== SEARCH USERS ====================
if ($action === "search_users") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $query = trim($_REQUEST["query"] ?? "");
    if (strlen($query) < 2) {
        jsonResponse(false, "حداقل ۲ کاراکتر وارد کنید");
    }

    $stmt = $pdo->prepare("
        SELECT id, username, display_name, created_at, is_public
        FROM users 
        WHERE (username LIKE ? OR display_name LIKE ?) 
        AND is_public = 1 
        AND is_active = 1
        AND id != ?
        LIMIT 10
    ");
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm, $_SESSION["user_id"]]);
    $users = $stmt->fetchAll();

    foreach ($users as &$user) {
        $user["member_since_fa"] = persian_date_from_gregorian(
            date("Y", strtotime($user["created_at"])),
            date("m", strtotime($user["created_at"])),
            date("d", strtotime($user["created_at"]))
        );
    }

    jsonResponse(true, "", $users);
}

// ==================== GET PUBLIC PROFILE ====================
if ($action === "get_public_profile") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $target_user_id = (int) ($_REQUEST["user_id"] ?? 0);
    $username = trim($_REQUEST["username"] ?? "");

    if ($target_user_id > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, username, display_name, created_at, is_active, is_public FROM users WHERE id = ?"
        );
        $stmt->execute([$target_user_id]);
    } elseif (!empty($username)) {
        $stmt = $pdo->prepare(
            "SELECT id, username, display_name, created_at, is_active, is_public FROM users WHERE username = ?"
        );
        $stmt->execute([$username]);
    } else {
        jsonResponse(false, "اطلاعات نامعتبر");
    }

    $user = $stmt->fetch();
    if (!$user) {
        jsonResponse(false, "کاربر یافت نشد");
    }

    if ($user["is_public"] != 1 && $user["id"] != $_SESSION["user_id"]) {
        jsonResponse(false, "این کاربر پروفایل خود را عمومی نکرده است");
    }

    if ($user["is_active"] != 1 && $user["id"] != $_SESSION["user_id"]) {
        jsonResponse(false, "حساب کاربری غیرفعال است");
    }

    $stats = [];

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM lists WHERE user_id = ?"
    );
    $stmt->execute([$user["id"]]);
    $stats["lists"] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM movies 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)
    ");
    $stmt->execute([$user["id"]]);
    $stats["movies"] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM series 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)
    ");
    $stmt->execute([$user["id"]]);
    $stats["series"] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sr.id) as count 
        FROM seasons s
        JOIN series sr ON s.series_id = sr.id
        WHERE sr.list_id IN (SELECT id FROM lists WHERE user_id = ?) 
        AND s.watched = 1
    ");
    $stmt->execute([$user["id"]]);
    $stats["completed_series"] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(rating) as rated_count
        FROM movies 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND rating IS NOT NULL
    ");
    $stmt->execute([$user["id"]]);
    $ratingData = $stmt->fetch();
    $stats["avg_rating"] = round($ratingData["avg_rating"] ?? 0, 1);
    $stats["rated_count"] = (int) $ratingData["rated_count"];

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM comments WHERE user_id = ?"
    );
    $stmt->execute([$user["id"]]);
    $stats["comments"] = (int) $stmt->fetchColumn();

    // ✅ چک کردن دستاوردهای ویژه قبل از برگرداندن
    checkSpecialAchievements($pdo, $user["id"]);

    $stmt = $pdo->prepare("
        SELECT title, description, earned_at, icon, achievement_key, type
        FROM achievements 
        WHERE user_id = ? 
        ORDER BY earned_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$user["id"]]);
    $achievements = $stmt->fetchAll();

    $member_since_fa = persian_date_from_gregorian(
        date("Y", strtotime($user["created_at"])),
        date("m", strtotime($user["created_at"])),
        date("d", strtotime($user["created_at"]))
    );

    jsonResponse(true, "", [
        "user" => [
            "id" => $user["id"],
            "username" => $user["username"],
            "display_name" => $user["display_name"],
            "created_at" => $user["created_at"],
            "member_since_fa" => $member_since_fa,
        ],
        "stats" => $stats,
        "achievements" => $achievements,
    ]);
}


/* ============================================================
   بخش ۱۸: آمار کاربر
   ============================================================ */

// ==================== GET USER STATS ====================
if ($action === "get_user_stats") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $user_id = $_SESSION["user_id"];

    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM lists WHERE user_id = ?) as lists,
            (SELECT COUNT(*) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)) as movies,
            (SELECT COUNT(*) FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)) as series,
            (SELECT COUNT(*) FROM seasons WHERE series_id IN (SELECT id FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?))) as seasons,
            (SELECT SUM(episode_count) FROM seasons WHERE series_id IN (SELECT id FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?))) as total_episodes,
            (SELECT COUNT(*) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND watched = 1) as watched_movies,
            (SELECT COUNT(*) FROM seasons WHERE series_id IN (SELECT id FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)) AND watched = 1) as watched_series,
            (SELECT COUNT(*) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND downloaded = 1) as downloaded_movies,
            (SELECT COUNT(*) FROM seasons WHERE series_id IN (SELECT id FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)) AND downloaded = 1) as downloaded_seasons,
            (SELECT COUNT(*) FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND status = 'completed') as completed_series,
            (SELECT AVG(rating) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND rating > 0) as avg_rating,
            (SELECT COUNT(*) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND rating > 0) as total_ratings,
            (SELECT COUNT(*) FROM comments WHERE user_id = ?) as total_comments
    ");
    $stmt->execute([
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
    ]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stats["lists"] = (int) ($stats["lists"] ?? 0);
    $stats["movies"] = (int) ($stats["movies"] ?? 0);
    $stats["series"] = (int) ($stats["series"] ?? 0);
    $stats["seasons"] = (int) ($stats["seasons"] ?? 0);
    $stats["total_episodes"] = (int) ($stats["total_episodes"] ?? 0);
    $stats["watched_movies"] = (int) ($stats["watched_movies"] ?? 0);
    $stats["watched_series"] = (int) ($stats["watched_series"] ?? 0);
    $stats["downloaded_movies"] = (int) ($stats["downloaded_movies"] ?? 0);
    $stats["downloaded_seasons"] = (int) ($stats["downloaded_seasons"] ?? 0);
    $stats["completed_series"] = (int) ($stats["completed_series"] ?? 0);
    $stats["avg_rating"] = round($stats["avg_rating"] ?? 0, 1);
    $stats["total_ratings"] = (int) ($stats["total_ratings"] ?? 0);
    $stats["total_comments"] = (int) ($stats["total_comments"] ?? 0);

    jsonResponse(true, "", $stats);
}

// ==================== GET WATCH STATS ====================
if ($action === "get_watch_stats") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $user_id = $_SESSION["user_id"];

    $monthlyStats = [];
    for ($i = 1; $i <= 12; $i++) {
        $monthlyStats[$i] = 0;
    }

    $stmt = $pdo->prepare("
        SELECT watch_date FROM movies 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) 
        AND watched = 1 AND watch_date IS NOT NULL AND watch_date > 0
        UNION ALL
        SELECT watch_date FROM seasons s
        JOIN series sr ON s.series_id = sr.id
        WHERE sr.list_id IN (SELECT id FROM lists WHERE user_id = ?)
        AND s.watched = 1 AND s.watch_date IS NOT NULL AND s.watch_date > 0
    ");
    $stmt->execute([$user_id, $user_id]);
    $allDates = $stmt->fetchAll();

    foreach ($allDates as $date) {
        $watchDate = (string) $date["watch_date"];
        if (strlen($watchDate) == 8) {
            $month = (int) substr($watchDate, 4, 2);
            if ($month >= 1 && $month <= 12) {
                $monthlyStats[$month]++;
            }
        }
    }

    $yearlyStats = [];
    foreach ($allDates as $date) {
        $watchDate = (string) $date["watch_date"];
        if (strlen($watchDate) == 8) {
            $year = (int) substr($watchDate, 0, 4);
            if (!isset($yearlyStats[$year])) {
                $yearlyStats[$year] = 0;
            }
            $yearlyStats[$year]++;
        }
    }
    ksort($yearlyStats);

    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)) +
            (SELECT COUNT(*) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id IN (SELECT id FROM lists WHERE user_id = ?)) as total_items,
            (SELECT SUM(watched) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)) +
            (SELECT SUM(watched) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id IN (SELECT id FROM lists WHERE user_id = ?)) as watched_items
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $totals = $stmt->fetch();

    $totalItems = (int) ($totals["total_items"] ?? 0);
    $watchedItems = (int) ($totals["watched_items"] ?? 0);
    $watchPercentage =
        $totalItems > 0 ? round(($watchedItems / $totalItems) * 100, 1) : 0;

    jsonResponse(true, "", [
        "monthly" => $monthlyStats,
        "yearly" => $yearlyStats,
        "watch_percentage" => $watchPercentage,
        "watched_items" => $watchedItems,
        "total_items" => $totalItems,
    ]);
}

// ==================== GET GENRE STATS ====================
if ($action === "get_genre_stats") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $user_id = $_SESSION["user_id"];
    $genres = [];

    $stmt = $pdo->prepare("
        SELECT genre FROM movies 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND genre IS NOT NULL AND genre != ''
    ");
    $stmt->execute([$user_id]);
    $movieGenres = $stmt->fetchAll();

    foreach ($movieGenres as $row) {
        $genreList = explode(",", $row["genre"]);
        foreach ($genreList as $genre) {
            $genre = trim($genre);
            if (!empty($genre)) {
                if (!isset($genres[$genre])) {
                    $genres[$genre] = 0;
                }
                $genres[$genre]++;
            }
        }
    }

    $stmt = $pdo->prepare("
        SELECT genre FROM series 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND genre IS NOT NULL AND genre != ''
    ");
    $stmt->execute([$user_id]);
    $seriesGenres = $stmt->fetchAll();

    foreach ($seriesGenres as $row) {
        $genreList = explode(",", $row["genre"]);
        foreach ($genreList as $genre) {
            $genre = trim($genre);
            if (!empty($genre)) {
                if (!isset($genres[$genre])) {
                    $genres[$genre] = 0;
                }
                $genres[$genre]++;
            }
        }
    }

    arsort($genres);
    $total = array_sum($genres);

    jsonResponse(true, "", [
        "genres" => $genres,
        "total" => $total,
    ]);
}

// ==================== GET LISTS COMPARISON ====================
if ($action === "get_lists_comparison") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $user_id = $_SESSION["user_id"];

    $stmt = $pdo->prepare(
        "SELECT id, name, color FROM lists WHERE user_id = ? ORDER BY id ASC"
    );
    $stmt->execute([$user_id]);
    $lists = $stmt->fetchAll();

    $comparison = [];
    foreach ($lists as $list) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as movies, SUM(watched) as watched_movies FROM movies WHERE list_id = ?"
        );
        $stmt->execute([$list["id"]]);
        $movieStats = $stmt->fetch();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as series FROM series WHERE list_id = ?"
        );
        $stmt->execute([$list["id"]]);
        $seriesCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as seasons, SUM(watched) as watched_seasons
            FROM seasons s
            JOIN series sr ON s.series_id = sr.id
            WHERE sr.list_id = ?
        ");
        $stmt->execute([$list["id"]]);
        $seasonStats = $stmt->fetch();

        $totalItems =
            ($movieStats["movies"] ?? 0) + ($seasonStats["seasons"] ?? 0);
        $watchedItems =
            ($movieStats["watched_movies"] ?? 0) +
            ($seasonStats["watched_seasons"] ?? 0);
        $percentage =
            $totalItems > 0 ? round(($watchedItems / $totalItems) * 100, 1) : 0;

        $comparison[] = [
            "id" => $list["id"],
            "name" => $list["name"],
            "color" => $list["color"],
            "movies" => (int) $movieStats["movies"],
            "series" => $seriesCount,
            "seasons" => (int) $seasonStats["seasons"],
            "total_items" => $totalItems,
            "watched_items" => $watchedItems,
            "percentage" => $percentage,
        ];
    }

    jsonResponse(true, "", $comparison);
}

// ==================== GET CURRENT YEAR HEATMAP ====================
if ($action === "get_current_year_heatmap") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $dateResult = getPersianDateFromTimeIR();
    if (!$dateResult["ok"]) {
        $currentYear = 1405;
    } else {
        $currentYear = $dateResult["data"]["year"];
    }

    $user_id = $_SESSION["user_id"];

    $startDate = $currentYear . "0101";
    $endDate = $currentYear . "1231";

    $activities = [];

    $stmt = $pdo->prepare("
        SELECT watch_date FROM movies 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) 
        AND watched = 1 AND watch_date IS NOT NULL AND watch_date > 0
        AND watch_date >= ? AND watch_date <= ?
    ");
    $stmt->execute([$user_id, $startDate, $endDate]);
    $movieDates = $stmt->fetchAll();

    foreach ($movieDates as $date) {
        $watchDate = (string) $date["watch_date"];
        if (strlen($watchDate) == 8) {
            $day = (int) substr($watchDate, 6, 2);
            $dayOfYear = getDayOfYear(
                $currentYear,
                (int) substr($watchDate, 4, 2),
                $day
            );
            if (!isset($activities[$dayOfYear])) {
                $activities[$dayOfYear] = 0;
            }
            $activities[$dayOfYear]++;
        }
    }

    $stmt = $pdo->prepare("
        SELECT watch_date FROM seasons s
        JOIN series sr ON s.series_id = sr.id
        WHERE sr.list_id IN (SELECT id FROM lists WHERE user_id = ?)
        AND s.watched = 1 AND s.watch_date IS NOT NULL AND s.watch_date > 0
        AND s.watch_date >= ? AND s.watch_date <= ?
    ");
    $stmt->execute([$user_id, $startDate, $endDate]);
    $seasonDates = $stmt->fetchAll();

    foreach ($seasonDates as $date) {
        $watchDate = (string) $date["watch_date"];
        if (strlen($watchDate) == 8) {
            $day = (int) substr($watchDate, 6, 2);
            $dayOfYear = getDayOfYear(
                $currentYear,
                (int) substr($watchDate, 4, 2),
                $day
            );
            if (!isset($activities[$dayOfYear])) {
                $activities[$dayOfYear] = 0;
            }
            $activities[$dayOfYear]++;
        }
    }

    $daysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
    $isLeap =
        $currentYear % 33 === 1 ||
        $currentYear % 33 === 5 ||
        $currentYear % 33 === 9 ||
        $currentYear % 33 === 13 ||
        $currentYear % 33 === 17 ||
        $currentYear % 33 === 22 ||
        $currentYear % 33 === 26 ||
        $currentYear % 33 === 30;
    if ($isLeap) {
        $daysInMonth[11] = 30;
    }

    jsonResponse(true, "", [
        "year" => $currentYear,
        "activities" => $activities,
        "days_in_month" => $daysInMonth,
        "is_leap" => $isLeap,
    ]);
}

// ==================== GET ACTIVITY HEATMAP ====================
if ($action === "get_activity_heatmap") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $user_id = $_SESSION["user_id"];
    $year = (int) ($_REQUEST["year"] ?? date("Y"));

    $activities = [];

    $stmt = $pdo->prepare("
        SELECT watch_date FROM movies 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) 
        AND watched = 1 AND watch_date IS NOT NULL AND watch_date > 0
    ");
    $stmt->execute([$user_id]);
    $watchDates = $stmt->fetchAll();

    foreach ($watchDates as $date) {
        $dateStr = (string) $date["watch_date"];
        if (strlen($dateStr) == 8) {
            $yearDate = (int) substr($dateStr, 0, 4);
            if ($yearDate == $year) {
                $key = substr($dateStr, 4, 4);
                if (!isset($activities[$key])) {
                    $activities[$key] = 0;
                }
                $activities[$key]++;
            }
        }
    }

    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM user_activity_log 
        WHERE user_id = ? AND YEAR(created_at) = ? AND activity_type = 'login'
        GROUP BY DATE(created_at)
    ");
    $stmt->execute([$user_id, $year]);
    $loginDates = $stmt->fetchAll();

    foreach ($loginDates as $date) {
        $dateObj = new DateTime($date["date"]);
        $persian = persian_date_from_gregorian(
            $dateObj->format("Y"),
            $dateObj->format("m"),
            $dateObj->format("d")
        );
        if ($persian) {
            $key = substr($persian, 4, 4);
            if (!isset($activities[$key])) {
                $activities[$key] = 0;
            }
            $activities[$key] += $date["count"];
        }
    }

    jsonResponse(true, "", [
        "year" => $year,
        "activities" => $activities,
    ]);
}


/* ============================================================
   بخش ۱۹: سیستم دستاوردها
   ============================================================ */

// ==================== تابع اعطای دستاورد ====================
function awardAchievement(
    $pdo,
    $user_id,
    $achievement_key,
    $title,
    $description,
    $type = "global",
    $target_id = null,
    $icon = "trophy"
) {
    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM achievements WHERE user_id = ? AND achievement_key = ?"
        );
        $stmt->execute([$user_id, $achievement_key]);
        if ($stmt->fetch()) {
            return false;
        }

        $stmt = $pdo->prepare("
            INSERT INTO achievements (user_id, type, target_id, achievement_key, title, description, icon, earned_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $user_id,
            $type,
            $target_id,
            $achievement_key,
            $title,
            $description,
            $icon,
        ]);
    } catch (Exception $e) {
        error_log("awardAchievement error: " . $e->getMessage());
        return false;
    }
}

// بررسی دستاوردهای ویژه (Social, Collector, Completionist)
function checkSpecialAchievements($pdo, $user_id)
{
    try {
        // ===== ۱. Completionist: حداقل یک لیست با ۱۰۰٪ تکمیل =====
        $stmt = $pdo->prepare("
            SELECT l.id, l.name,
                (SELECT COUNT(*) FROM movies WHERE list_id = l.id) +
                (SELECT COUNT(*) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id = l.id) as total_items,
                (SELECT COALESCE(SUM(watched), 0) FROM movies WHERE list_id = l.id) +
                (SELECT COALESCE(SUM(watched), 0) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id = l.id) as watched_items
            FROM lists l
            WHERE l.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $lists = $stmt->fetchAll();

        foreach ($lists as $list) {
            $total = (int) ($list['total_items'] ?? 0);
            $watched = (int) ($list['watched_items'] ?? 0);
            if ($total > 0 && $watched >= $total) {
                awardAchievement(
                    $pdo,
                    $user_id,
                    'completionist',
                    'تکمیل‌کننده نهایی',
                    'یک لیست رو ۱۰۰٪ تکمیل کردی! 💎',
                    'special',
                    $list['id'],
                    'completionist'
                );
                break;
            }
        }

        // ===== ۲. Collector: حداقل یک collection در prequels =====
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM prequels p
            WHERE p.relation_type = 'collection' AND (
                (p.media_type = 'movie' AND p.media_id IN (
                    SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE l.user_id = ?
                )) OR (p.media_type = 'series' AND p.media_id IN (
                    SELECT s.id FROM series s JOIN lists l ON s.list_id = l.id WHERE l.user_id = ?
                ))
            )
        ");
        $stmt->execute([$user_id, $user_id]);
        $collectionCount = (int) $stmt->fetchColumn();

        if ($collectionCount >= 1) {
            awardAchievement(
                $pdo,
                $user_id,
                'collector',
                'کالکشن‌فن!',
                'حداقل یک مجموعه فیلم ساختی! 🃏',
                'special',
                null,
                'collector'
            );
        }

        // ===== ۳. Social: پروفایل عمومی =====
        $stmt = $pdo->prepare("SELECT is_public FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $isPublic = (int) $stmt->fetchColumn();

        if ($isPublic === 1) {
            awardAchievement(
                $pdo,
                $user_id,
                'social',
                'اجتماعی',
                'پروفایلت رو عمومی کردی! 🤝',
                'special',
                null,
                'social'
            );
        }
    } catch (Exception $e) {
        error_log("checkSpecialAchievements error: " . $e->getMessage());
    }
}

// بررسی و اعطای دستاوردهای مربوط به تعداد فیلم‌های دیده شده
function checkMovieCountAchievements($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM movies 
        WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND watched = 1
    ");
    $stmt->execute([$user_id]);
    $count = (int) $stmt->fetchColumn();

    $milestones = [
        1 => ["first_movie", "قدم اول", "اولین فیلم خود را تماشا کردی! 🎬", "movie_first"],
        10 => ["ten_movies", "فیلم‌بین فعال", "۱۰ فیلم را تماشا کردی! 🍿", "movie_10"],
        25 => ["twenty_five_movies", "عاشق سینما", "۲۵ فیلم را تماشا کردی! 🎥", "movie_25"],
        50 => ["fifty_movies", "سینمارو حرفه‌ای", "۵۰ فیلم را تماشا کردی! ⭐", "movie_50"],
        100 => ["hundred_movies", "سلطان سینما", "۱۰۰ فیلم را تماشا کردی! 🏆", "movie_100"],
        200 => ["two_hundred_movies", "افسانه سینما", "۲۰۰ فیلم را تماشا کردی! 👑", "movie_200"],
        500 => ["five_hundred_movies", "سینما به ذات", "۵۰۰ فیلم را تماشا کردی! 🔥", "movie"],
    ];

    foreach ($milestones as $milestone => $data) {
        if ($count >= $milestone) {
            awardAchievement(
                $pdo,
                $user_id,
                $data[0],
                $data[1],
                $data[2],
                "global",
                null,
                $data[3]
            );
        }
    }
    return $count;
}

// بررسی و اعطای دستاوردهای مربوط به سریال‌ها و فصل‌های دیده شده
function checkSeriesCompleteAchievements($pdo, $user_id)
{
    // ۱) تعداد فصل‌های دیده شده (منطبق بر مراحلی که در پروفایل کاربری نمایش داده می‌شود)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM seasons s
        JOIN series sr ON s.series_id = sr.id
        WHERE sr.list_id IN (SELECT id FROM lists WHERE user_id = ?) AND s.watched = 1
    ");
    $stmt->execute([$user_id]);
    $seasonCount = (int) $stmt->fetchColumn();

    $seasonMilestones = [
        1 => ["first_season", "فصل اول", "اولین فصل سریال را تماشا کردی! 📺", "series_first"],
        5 => ["five_seasons", "سریال‌بین فعال", "۵ فصل سریال را تماشا کردی! 🎯", "series_5"],
        10 => ["ten_seasons", "عاشق سریال", "۱۰ فصل سریال را تماشا کردی! 🔥", "series_10"],
        20 => ["twenty_seasons", "سریال‌باز", "۲۰ فصل سریال را تماشا کردی! 🎬", "series_20"],
        50 => ["fifty_seasons", "سلطان سریال", "۵۰ فصل سریال را تماشا کردی! 👑", "series_50"],
    ];

    foreach ($seasonMilestones as $milestone => $data) {
        if ($seasonCount >= $milestone) {
            awardAchievement(
                $pdo,
                $user_id,
                $data[0],
                $data[1],
                $data[2],
                "global",
                null,
                $data[3]
            );
        }
    }

    // ۲) تعداد سریال‌های کامل شده (که تمام فصل‌هایشان دیده شده)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sr.id) as count 
        FROM series sr
        WHERE sr.list_id IN (SELECT id FROM lists WHERE user_id = ?)
        AND (
            sr.status = 'completed' OR (
                (SELECT COUNT(*) FROM seasons WHERE series_id = sr.id) > 0 AND
                (SELECT COUNT(*) FROM seasons WHERE series_id = sr.id AND watched = 0) = 0
            )
        )
    ");
    $stmt->execute([$user_id]);
    $seriesCount = (int) $stmt->fetchColumn();

    $seriesMilestones = [
        1 => ["first_series", "سریال‌بین", "اولین سریال را کامل کردی! 📺", "series"],
        3 => ["three_series", "ماراتن سریال", "۳ سریال را کامل کردی! 🎯", "series"],
        5 => ["five_series", "سریال‌خوار", "۵ سریال را کامل کردی! 🔥", "series"],
        10 => ["ten_series", "استاد سریال", "۱۰ سریال را کامل کردی! 🏆", "series"],
    ];

    foreach ($seriesMilestones as $milestone => $data) {
        if ($seriesCount >= $milestone) {
            awardAchievement(
                $pdo,
                $user_id,
                $data[0],
                $data[1],
                $data[2],
                "global",
                null,
                $data[3]
            );
        }
    }

    return $seasonCount;
}

// بررسی و اعطای دستاوردهای مربوط به تعداد لیست‌ها
function checkListCountAchievements($pdo, $user_id)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM lists WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    $count = (int) $stmt->fetchColumn();

    $milestones = [
        1 => ["first_list", "اولین لیست", "اولین لیست خود را ساختی! 📋", "list_first"],
        3 => ["three_lists", "دسته‌بند حرفه‌ای", "۳ لیست ساختی! 📂", "list_5"],
        5 => ["five_lists", "دسته‌بند", "۵ لیست ساختی! 🗂️", "list_5"],
        10 => ["ten_lists", "مدیر محتوا", "۱۰ لیست ساختی! 📚", "list_10"],
        20 => ["twenty_lists", "آرشیویست", "۲۰ لیست! فوق‌العاده است! 👑", "list_20"],
    ];

    foreach ($milestones as $milestone => $data) {
        if ($count >= $milestone) {
            awardAchievement(
                $pdo,
                $user_id,
                $data[0],
                $data[1],
                $data[2],
                "global",
                null,
                $data[3]
            );
        }
    }
    return $count;
}

// بررسی و اعطای دستاوردهای مربوط به تکمیل یک لیست
function checkListCompleteAchievement($pdo, $user_id, $list_id, $list_name)
{
    // بررسی تعداد کل آیتم‌های لیست
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM movies WHERE list_id = ?) +
            (SELECT COUNT(*) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id = ?) as total_items
    ");
    $stmt->execute([$list_id, $list_id]);
    $total = (int) $stmt->fetchColumn();

    if ($total == 0) {
        return false;
    }

    // بررسی تعداد آیتم‌های مشاهده شده (با COALESCE برای جلوگیری از نتیجه NULL در صورت نبود فیلم یا سریال)
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COALESCE(SUM(watched), 0) FROM movies WHERE list_id = ?) +
            (SELECT COALESCE(SUM(watched), 0) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id = ?) as watched_items
    ");
    $stmt->execute([$list_id, $list_id]);
    $watched = (int) $stmt->fetchColumn();

    if ($total > 0 && $watched >= $total) {
        $key = "list_complete_" . $list_id;
        $title = "کامل‌کننده لیست";
        $desc = 'لیست "' . $list_name . '" را کامل کردی! 🎉';
        awardAchievement(
            $pdo,
            $user_id,
            $key,
            $title,
            $desc,
            "list_complete",
            $list_id,
            "list"
        );
        return true;
    }
    return false;
}

// بررسی وضعیت تکمیل تمام لیست‌های کاربر و اعطای دستاوردهای ویژه تکمیل
function checkAllListCompleteAchievements($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT id, name FROM lists WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $lists = $stmt->fetchAll();

    foreach ($lists as $list) {
        checkListCompleteAchievement($pdo, $user_id, $list['id'], $list['name']);
    }

    // بررسی تعداد کل لیست‌های ۱۰۰٪ کامل شده
    $stmt2 = $pdo->prepare("
        SELECT COUNT(DISTINCT l.id) FROM lists l
        WHERE l.user_id = ?
        AND (
            (SELECT COUNT(*) FROM movies WHERE list_id = l.id) + 
            (SELECT COUNT(*) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id = l.id)
        ) > 0
        AND (
            (SELECT COUNT(*) FROM movies WHERE list_id = l.id AND watched = 0) + 
            (SELECT COUNT(*) FROM seasons s JOIN series sr ON s.series_id = sr.id WHERE sr.list_id = l.id AND watched = 0)
        ) = 0
    ");
    $stmt2->execute([$user_id]);
    $completedCount = (int) $stmt2->fetchColumn();

    if ($completedCount >= 1) {
        awardAchievement(
            $pdo,
            $user_id,
            "completionist",
            "تکمیل‌کننده نهایی",
            "حداقل یک لیست با ۱۰۰٪ تکمیل داری! 🎉",
            "special",
            null,
            "list"
        );
    }
    if ($completedCount >= 5) {
        awardAchievement(
            $pdo,
            $user_id,
            "completionist_master",
            "استاد تکمیل",
            "۵ لیست را ۱۰۰٪ کامل کردی! 🏆",
            "special",
            null,
            "list"
        );
    }
    return $completedCount;
}

// بررسی و اعطای دستاوردهای مربوط به کامنت
function checkCommentAchievements($pdo, $user_id)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM comments WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    $count = (int) $stmt->fetchColumn();

    $milestones = [
        1 => ["first_comment", "اولین نظر", "اولین کامنت خود را ثبت کردی! 💬", "comment_first"],
        10 => ["ten_comments", "منتقد فعال", "۱۰ کامنت ثبت کردی! 🗣️", "comment_10"],
        50 => ["fifty_comments", "نویسنده", "۵۰ کامنت ثبت کردی! ✍️", "comment_50"],
    ];

    foreach ($milestones as $milestone => $data) {
        if ($count >= $milestone) {
            awardAchievement(
                $pdo,
                $user_id,
                $data[0],
                $data[1],
                $data[2],
                "global",
                null,
                $data[3]
            );
        }
    }
    return $count;
}

// بررسی و اعطای دستاوردهای مربوط به امتیازدهی
function checkRatingAchievements($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM movies WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?) AND rating > 0) +
            (SELECT COUNT(*) FROM seasons WHERE series_id IN (SELECT id FROM series WHERE list_id IN (SELECT id FROM lists WHERE user_id = ?)) AND rating > 0) as count
    ");
    $stmt->execute([$user_id, $user_id]);
    $count = (int) $stmt->fetchColumn();

    $milestones = [
        1 => ["first_rating", "اولین امتیاز", "اولین امتیاز خود را ثبت کردی! ⭐", "rating_first"],
        10 => ["ten_ratings", "منتقد", "۱۰ امتیاز ثبت کردی! 🎬", "rating_10"],
        20 => ["twenty_ratings", "منتقد حرفه‌ای", "۲۰ امتیاز ثبت کردی! 🌟", "rating_10"],
        50 => ["fifty_ratings", "داور جشنواره", "۵۰ امتیاز ثبت کردی! 🏆", "rating_50"],
    ];

    foreach ($milestones as $milestone => $data) {
        if ($count >= $milestone) {
            awardAchievement(
                $pdo,
                $user_id,
                $data[0],
                $data[1],
                $data[2],
                "global",
                null,
                $data[3]
            );
        }
    }
    return $count;
}

// بررسی و اعطای دستاوردهای مربوط به اشتراک‌گذاری
function checkShareAchievements($pdo, $user_id)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM lists WHERE user_id = ? AND share_code IS NOT NULL"
    );
    $stmt->execute([$user_id]);
    $count = (int) $stmt->fetchColumn();

    // بررسی عمومی بودن پروفایل کاربر
    $stmt2 = $pdo->prepare("SELECT is_public FROM users WHERE id = ?");
    $stmt2->execute([$user_id]);
    $isPublic = (int) $stmt2->fetchColumn();

    if ($count >= 1) {
        awardAchievement(
            $pdo,
            $user_id,
            "first_share",
            "اشتراک‌گذار",
            "اولین لیست خود را به اشتراک گذاشتی! 🔗",
            "global",
            null,
            "share"
        );
    }
    if ($count >= 5) {
        awardAchievement(
            $pdo,
            $user_id,
            "social_master",
            "اجتماعی حرفه‌ای",
            "۵ لیست را به اشتراک گذاشتی! 🌟",
            "special",
            null,
            "share"
        );
    }
    if ($isPublic == 1) {
        awardAchievement(
            $pdo,
            $user_id,
            "public_profile",
            "اجتماعی",
            "پروفایل کاربری خود را عمومی کردی! 🤝",
            "special",
            null,
            "share"
        );
    }

    return $count;
}

// بررسی و اعطای دستاوردهای مربوط به prequel/sequel/collection
function checkPrequelAchievements($pdo, $user_id)
{
    // محاسبه تعداد کل prequelهایی که کاربر تعریف کرده
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM prequels p
        WHERE (p.media_type = 'movie' AND p.media_id IN (
            SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE l.user_id = ?
        )) OR (p.media_type = 'series' AND p.media_id IN (
            SELECT s.id FROM series s JOIN lists l ON s.list_id = l.id WHERE l.user_id = ?
        ))
    ");
    $stmt->execute([$user_id, $user_id]);
    $totalCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM prequels p
        WHERE p.relation_type = 'collection' AND (
            (p.media_type = 'movie' AND p.media_id IN (
                SELECT m.id FROM movies m JOIN lists l ON m.list_id = l.id WHERE l.user_id = ?
            )) OR (p.media_type = 'series' AND p.media_id IN (
                SELECT s.id FROM series s JOIN lists l ON s.list_id = l.id WHERE l.user_id = ?
            ))
        )
    ");
    $stmt->execute([$user_id, $user_id]);
    $collectionCount = (int) $stmt->fetchColumn();

    if ($totalCount >= 1) {
        awardAchievement(
            $pdo,
            $user_id,
            "first_prequel",
            "تاریخ‌دان",
            "اولین عنوان مرتبط را تعریف کردی! 📖",
            "special",
            null,
            "prequel"
        );
    }
    if ($totalCount >= 5) {
        awardAchievement(
            $pdo,
            $user_id,
            "prequel_master",
            "استاد تاریخ",
            "۵ عنوان مرتبط تعریف کردی! 🎓",
            "special",
            null,
            "prequel"
        );
    }
    if ($totalCount >= 10) {
        awardAchievement(
            $pdo,
            $user_id,
            "prequel_legend",
            "افسانه تاریخ",
            "۱۰ عنوان مرتبط تعریف کردی! 👑",
            "special",
            null,
            "prequel"
        );
    }

    if ($collectionCount >= 1) {
        awardAchievement(
            $pdo,
            $user_id,
            "first_collection",
            "کالکشن فن!",
            "حداقل یک مجموعه فیلم/سریال داری! 📚",
            "special",
            null,
            "collection"
        );
    }
    if ($collectionCount >= 3) {
        awardAchievement(
            $pdo,
            $user_id,
            "collection_master",
            "کلکسیونر حرفه‌ای",
            "۳ مجموعه ساختی! 🏆",
            "special",
            null,
            "collection"
        );
    }
    if ($collectionCount >= 10) {
        awardAchievement(
            $pdo,
            $user_id,
            "collection_legend",
            "سلطان مجموعه",
            "۱۰ مجموعه ساختی! 👑",
            "special",
            null,
            "collection"
        );
    }

    return $totalCount;
}

function checkAllAchievements(
    $pdo,
    $user_id,
    $trigger_type = "all",
    $extra_data = null
) {
    static $is_running = false;
    if ($is_running) {
        return;
    }
    $is_running = true;

    try {
        switch ($trigger_type) {
            case "watch_movie":
                checkMovieCountAchievements($pdo, $user_id);
                checkAllListCompleteAchievements($pdo, $user_id);
                break;
            case "watch_season":
                checkSeriesCompleteAchievements($pdo, $user_id);
                checkAllListCompleteAchievements($pdo, $user_id);
                break;
            case "add_list":
                checkListCountAchievements($pdo, $user_id);
                break;
            case "complete_list":
                if (
                    $extra_data &&
                    isset($extra_data["list_id"]) &&
                    isset($extra_data["list_name"])
                ) {
                    checkListCompleteAchievement(
                        $pdo,
                        $user_id,
                        $extra_data["list_id"],
                        $extra_data["list_name"]
                    );
                }
                checkAllListCompleteAchievements($pdo, $user_id);
                break;
            case "add_comment":
                checkCommentAchievements($pdo, $user_id);
                break;
            case "add_rating":
                checkRatingAchievements($pdo, $user_id);
                break;
            case "share_list":
                checkShareAchievements($pdo, $user_id);
                break;
            case "add_prequel":
                checkPrequelAchievements($pdo, $user_id);
                break;
            case "toggle_public":
                checkSpecialAchievements($pdo, $user_id);
                break;
            default:
                // بررسی تمام دستاوردها
                checkMovieCountAchievements($pdo, $user_id);
                checkSeriesCompleteAchievements($pdo, $user_id);
                checkListCountAchievements($pdo, $user_id);
                checkCommentAchievements($pdo, $user_id);
                checkRatingAchievements($pdo, $user_id);
                checkShareAchievements($pdo, $user_id);
                checkPrequelAchievements($pdo, $user_id);
                checkSpecialAchievements($pdo, $user_id);
                checkAllListCompleteAchievements($pdo, $user_id);
                break;
        }

        // لاگ ایمن فعالیت (تنها برای انواع تعریف‌شده در enum دیتابیس)
        if ($trigger_type != "all") {
            try {
                $enum_map = [
                    "watch_movie" => "watch_movie",
                    "watch_season" => "watch_season",
                    "add_comment" => "comment",
                    "share_list" => "share",
                ];
                if (isset($enum_map[$trigger_type])) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO user_activity_log (user_id, activity_type, created_at) VALUES (?, ?, NOW())"
                    );
                    $stmt->execute([$user_id, $enum_map[$trigger_type]]);
                }
            } catch (Exception $e) {
                error_log("user_activity_log insert notice: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("checkAllAchievements error: " . $e->getMessage());
    } finally {
        $is_running = false;
    }
}

if ($action === "get_achievements") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $user_id = $_SESSION["user_id"];

    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'achievements'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `achievements` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `type` enum('list_complete','global','special') NOT NULL DEFAULT 'global',
                    `target_id` int(11) DEFAULT NULL,
                    `achievement_key` varchar(100) NOT NULL,
                    `title` varchar(200) NOT NULL,
                    `description` text DEFAULT NULL,
                    `icon` varchar(100) DEFAULT 'trophy',
                    `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_achievement` (`user_id`,`achievement_key`),
                    KEY `user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
            ");
        }

        // ✅ چک کردن همه دستاوردها قبل از برگرداندن
        checkAllAchievements($pdo, $user_id, "all");

        $stmt = $pdo->prepare(
            "SELECT * FROM achievements WHERE user_id = ? ORDER BY earned_at DESC"
        );
        $stmt->execute([$user_id]);
        $achievements = $stmt->fetchAll();

        jsonResponse(true, "", $achievements);
    } catch (Exception $e) {
        jsonResponse(true, "", []);
    }
}

// ==================== ✅ GET USER ACHIEVEMENTS (نام جدید) ====================
if ($action === "get_user_achievements") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $user_id = $_SESSION["user_id"];

    try {
        // بررسی وجود جدول
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'achievements'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `achievements` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `type` enum('list_complete','global','special') NOT NULL DEFAULT 'global',
                    `target_id` int(11) DEFAULT NULL,
                    `achievement_key` varchar(100) NOT NULL,
                    `title` varchar(200) NOT NULL,
                    `description` text DEFAULT NULL,
                    `icon` varchar(100) DEFAULT 'trophy',
                    `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_achievement` (`user_id`,`achievement_key`),
                    KEY `user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
            ");
        }

        // ✅ چک کردن همه دستاوردها قبل از برگرداندن
        checkAllAchievements($pdo, $user_id, "all");

        // دریافت دستاوردها
        $stmt = $pdo->prepare(
            "SELECT id, type, target_id, achievement_key, title, description, icon, earned_at 
             FROM achievements 
             WHERE user_id = ? 
             ORDER BY earned_at DESC"
        );
        $stmt->execute([$user_id]);
        $achievements = $stmt->fetchAll();

        jsonResponse(true, "", $achievements);
    } catch (Exception $e) {
        jsonResponse(true, "", []);
    }
}


/* ============================================================
   بخش ۲۰: مدیریت حساب کاربری
   ============================================================ */

// ==================== DELETE ACCOUNT ====================
if ($action === "delete_account") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$_SESSION["user_id"]])) {
            session_destroy();
            jsonResponse(true, "حساب کاربری با موفقیت حذف شد");
        } else {
            jsonResponse(false, "خطا در حذف حساب کاربری");
        }
    } catch (Exception $e) {
        jsonResponse(false, "خطا در حذف حساب کاربری");
    }
}

// ==================== DEACTIVATE ACCOUNT ====================
if ($action === "deactivate_account") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $stmt = $pdo->prepare(
        "UPDATE users SET is_active = 0, deactivated_at = NOW() WHERE id = ?"
    );
    if ($stmt->execute([$_SESSION["user_id"]])) {
        session_destroy();
        jsonResponse(
            true,
            "حساب کاربری شما غیرفعال شد. برای فعالسازی مجدد با ادمین تماس بگیرید."
        );
    } else {
        jsonResponse(false, "خطا در غیرفعال سازی حساب");
    }
}

// ==================== ACTIVATE ACCOUNT (ادمین) ====================
if ($action === "activate_account") {
    $user_id = (int) ($_POST["user_id"] ?? 0);
    $admin_token = $_POST["admin_token"] ?? "";

    if ($admin_token !== "ADMIN_SECRET_TOKEN") {
        jsonResponse(false, "دسترسی غیرمجاز");
    }

    $stmt = $pdo->prepare(
        "UPDATE users SET is_active = 1, deactivated_at = NULL WHERE id = ?"
    );
    if ($stmt->execute([$user_id])) {
        jsonResponse(true, "حساب کاربری فعال شد");
    } else {
        jsonResponse(false, "خطا در فعالسازی حساب");
    }
}


/* ============================================================
   بخش ۲۱: پیشنهاد ژانر (بدون API Key)
   ============================================================ */

// ==================== FETCH GENRE SUGGESTIONS ====================
if ($action === "fetch_genre_suggestions") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $query = trim($_REQUEST["query"] ?? "");
    $lang = $_REQUEST["lang"] ?? "fa";

    $defaultGenres = [
        'fa' => [
            'اکشن', 'درام', 'کمدی', 'ترسناک', 'علمی-تخیلی', 'فانتزی', 'عاشقانه',
            'جنایی', 'معمایی', 'ماجراجویی', 'انیمیشن', 'مستند', 'جنگی', 'تاریخی',
            'بیوگرافی', 'خانوادگی', 'موزیکال', 'ورزشی', 'وسترن', 'نوآر', 'تریلر',
            'هیجان‌انگیز', 'سوپرنچرال', 'روانشناختی', 'سیاسی', 'اجتماعی'
        ],
        'en' => [
            'Action', 'Drama', 'Comedy', 'Horror', 'Sci-Fi', 'Fantasy', 'Romance',
            'Crime', 'Mystery', 'Adventure', 'Animation', 'Documentary', 'War',
            'History', 'Biography', 'Family', 'Musical', 'Sport', 'Western', 'Noir',
            'Thriller', 'Supernatural', 'Psychological', 'Political', 'Social'
        ]
    ];

    $genres = $defaultGenres[$lang] ?? $defaultGenres['fa'];

    if (!empty($query)) {
        $query_lower = mb_strtolower($query, 'UTF-8');
        $genres = array_filter($genres, function($g) use ($query_lower) {
            return mb_strpos(mb_strtolower($g, 'UTF-8'), $query_lower) !== false;
        });
        $genres = array_values($genres);
    }

    $results = [];
    foreach (array_slice($genres, 0, 15) as $genre) {
        $results[] = [
            'name' => $genre,
            'source' => 'predefined'
        ];
    }

    jsonResponse(true, "", [
        'suggestions' => $results,
        'total' => count($results)
    ]);
}

// ==================== FETCH GENRE INFO (Wikipedia) ====================
if ($action === "fetch_genre_info") {
    if (!isLoggedIn()) {
        jsonResponse(false, "لطفاً وارد شوید");
    }

    $genre = trim($_REQUEST["genre"] ?? "");
    if (empty($genre)) {
        jsonResponse(false, "نام ژانر الزامی است");
    }

    $url = "https://fa.wikipedia.org/api/rest_v1/page/summary/" . urlencode($genre);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MovieManager/1.0 (https://movielist.mhdr-shpr.ir)');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        jsonResponse(true, "", [
            'name' => $genre,
            'title' => $data['title'] ?? $genre,
            'extract' => $data['extract'] ?? '',
            'description' => $data['description'] ?? '',
            'thumbnail' => $data['thumbnail']['source'] ?? null,
            'url' => $data['content_urls']['desktop']['page'] ?? null
        ]);
    } else {
        jsonResponse(true, "", [
            'name' => $genre,
            'title' => $genre,
            'extract' => '',
            'description' => '',
            'thumbnail' => null,
            'url' => null
        ]);
    }
}


/* ============================================================
   بخش ۲۲: توابع کمکی
   ============================================================ */

// ==================== GET LIST BY SHARE CODE ====================
function getListByShareCode($share_code)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT l.*, u.display_name as owner_name 
                           FROM lists l 
                           JOIN users u ON l.user_id = u.id 
                           WHERE l.share_code = ?");
    $stmt->execute([$share_code]);
    return $stmt->fetch();
}

// ==================== GENERATE UNIQUE SHARE CODE ====================
function generateUniqueShareCode()
{
    global $pdo;
    $characters =
        "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $charactersLength = strlen($characters);

    do {
        $code = "";
        for ($i = 0; $i < 15; $i++) {
            $code .= $characters[random_int(0, $charactersLength - 1)];
        }
        $stmt = $pdo->prepare("SELECT id FROM lists WHERE share_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetch();
    } while ($exists);

    return $code;
}


/* ============================================================
   پایان - پاسخ پیش‌فرض
   ============================================================ */
jsonResponse(false, "اکشن نامعتبر: " . $action);
?>