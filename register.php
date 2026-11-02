<?php
// register.php
require_once 'api/db_connect.php';
if (isLoggedIn()) {
    header('Location: dash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>ثبت نام | مدیریت فیلم و سریال</title>
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="application-name" content="MovieManager">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MovieMgr">
    <link rel="apple-touch-icon" href="/static/icons/icon-192.png">
    <meta name="theme-color" content="#0891b2">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>

    <style>
      @font-face {
        font-family: 'IRANSansX';
        src: url('static/fonts/IRANSansX-Thin.woff2') format('woff2');
        font-weight: 300;
      }
      @font-face {
        font-family: 'IRANSansX';
        src: url('static/fonts/IRANSansX-Regular.woff2') format('woff2');
        font-weight: 400;
      }
      @font-face {
        font-family: 'IRANSansX';
        src: url('static/fonts/IRANSansX-Bold.woff2') format('woff2');
        font-weight: 700;
      }

      /* ==================== LUCIDE ICON HELPERS ==================== */
      [data-lucide] {
        display: inline-block;
        width: 18px;
        height: 18px;
        stroke-width: 2;
        vertical-align: middle;
        flex-shrink: 0;
      }

      :root {
        --background: #f8fafc;
        --foreground: #0f172a;
        --card: #ffffff;
        --card-foreground: #0f172a;
        --primary: #0891b2;
        --primary-light: #06b6d4;
        --primary-dark: #0e7490;
        --secondary: #f1f5f9;
        --secondary-foreground: #334155;
        --muted-foreground: #475569;
        --accent: #ecfeff;
        --destructive: #dc2626;
        --border: #e2e8f0;
        --input-background: #ffffff;
      }

      .dark {
        --background: #0b1120;
        --foreground: #f1f5f9;
        --card: #111827;
        --card-foreground: #f1f5f9;
        --primary: #22d3ee;
        --primary-light: #67e8f9;
        --primary-dark: #0891b2;
        --secondary: #1e293b;
        --secondary-foreground: #cbd5e1;
        --muted-foreground: #94a3b8;
        --accent: #0c2d3b;
        --destructive: #f87171;
        --border: #1e293b;
        --input-background: #111827;
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'IRANSansX', system-ui, -apple-system, Tahoma, sans-serif;
      }

      body {
        background: linear-gradient(135deg, #0891b2 0%, #06b6d4 50%, #22d3ee 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        transition: background 0.3s ease;
        position: relative;
        overflow-x: hidden;
      }

      .dark body {
        background: linear-gradient(135deg, #0b1120 0%, #0c2d3b 50%, #083344 100%);
      }

      body::before,
      body::after {
        content: '';
        position: fixed;
        border-radius: 50%;
        pointer-events: none;
        opacity: 0.15;
        filter: blur(60px);
        z-index: 0;
      }

      body::before {
        width: 500px;
        height: 500px;
        background: #22d3ee;
        top: -200px;
        right: -200px;
      }

      body::after {
        width: 400px;
        height: 400px;
        background: #06b6d4;
        bottom: -150px;
        left: -150px;
      }

      .theme-toggle {
        position: fixed;
        top: 1rem;
        left: 1rem;
        padding: 0.55rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #ffffff;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
      }

      .theme-toggle:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
      }

      .register-container {
        width: 100%;
        max-width: 400px;
        position: relative;
        z-index: 1;
      }

      .register-card {
        background: var(--card);
        color: var(--card-foreground);
        border-radius: 20px;
        padding: 1.75rem 1.5rem;
        border: 1px solid var(--border);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: fadeIn 0.5s ease;
        transition: background 0.3s, color 0.3s, border-color 0.3s;
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .logo {
        text-align: center;
        margin-bottom: 1.25rem;
      }

      .logo-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: #ffffff;
        margin-bottom: 0.5rem;
        box-shadow: 0 10px 20px -5px rgba(8, 145, 178, 0.4);
      }

      .logo-icon [data-lucide] {
        width: 28px;
        height: 28px;
      }

      .logo h1 {
        font-size: 1.15rem;
        color: var(--foreground);
        margin-top: 0.25rem;
        transition: color 0.3s;
        font-weight: 700;
      }

      .logo p {
        color: var(--muted-foreground);
        font-size: 0.78rem;
        margin-top: 0.25rem;
        transition: color 0.3s;
      }

      .form-group {
        margin-bottom: 0.85rem;
      }

      .form-label {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--foreground);
        transition: color 0.3s;
      }

      .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-icon {
        position: absolute;
        right: 0.85rem;
        color: var(--muted-foreground);
        pointer-events: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
      }

      .input-icon [data-lucide] {
        width: 16px;
        height: 16px;
      }

      .form-input {
        width: 100%;
        padding: 0.65rem 2.5rem 0.65rem 2.5rem;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        font-size: 0.82rem;
        font-family: inherit;
        transition: all 0.2s;
        background: var(--input-background);
        color: var(--foreground);
      }

      .form-input::placeholder {
        color: var(--muted-foreground);
        opacity: 0.6;
        font-size: 0.78rem;
      }

      .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15);
      }

      .form-input:focus ~ .input-icon {
        color: var(--primary);
      }

      .toggle-password {
        position: absolute;
        left: 8px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--muted-foreground);
        transition: color 0.2s;
        border-radius: 6px;
      }

      .toggle-password [data-lucide] {
        width: 16px;
        height: 16px;
      }

      .toggle-password:hover {
        color: var(--primary);
        background: var(--secondary);
      }

      .btn {
        width: 100%;
        padding: 0.7rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px -2px rgba(8, 145, 178, 0.4);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
      }

      .btn [data-lucide] {
        width: 16px;
        height: 16px;
      }

      .btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -2px rgba(8, 145, 178, 0.5);
      }

      .btn:active:not(:disabled) {
        transform: translateY(0);
      }

      .btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }

      .btn-login {
        width: 100%;
        padding: 0.7rem;
        background: transparent;
        color: var(--primary);
        border: 1.5px solid var(--primary);
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
      }

      .btn-login [data-lucide] {
        width: 16px;
        height: 16px;
      }

      .btn-login:hover {
        background: var(--accent);
        transform: translateY(-2px);
      }

      .alert {
        padding: 0.7rem 0.85rem;
        border-radius: 10px;
        margin-bottom: 0.85rem;
        display: none;
        font-size: 0.78rem;
        font-weight: 500;
        align-items: center;
        gap: 0.5rem;
        animation: slideDown 0.3s ease;
      }

      .alert [data-lucide] {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
      }

      @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .alert.show {
        display: flex;
      }

      .alert-success {
        background: #10b98120;
        color: #10b981;
        border: 1px solid #10b98140;
      }

      .alert-error {
        background: #ef444420;
        color: #dc2626;
        border: 1px solid #ef444440;
      }

      .dark .alert-error {
        color: #f87171;
      }

      .login-link {
        text-align: center;
        margin-top: 1rem;
        font-size: 0.78rem;
        color: var(--muted-foreground);
      }

      .loading {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }

      @keyframes spin {
        to { transform: rotate(360deg); }
      }

      .password-strength {
        font-size: 0.7rem;
        margin-top: 0.3rem;
        font-weight: 500;
      }

      .strength-weak { color: #ef4444; }
      .strength-medium { color: #f59e0b; }
      .strength-strong { color: #10b981; }

      .hint-text {
        font-size: 0.68rem;
        color: var(--muted-foreground);
        margin-top: 0.3rem;
      }

      /* Responsive */
      @media (max-width: 480px) {
        .register-card {
          padding: 1.5rem 1.15rem;
          border-radius: 16px;
        }

        .logo h1 { font-size: 1rem; }
        .logo p { font-size: 0.72rem; }

        .logo-icon {
          width: 48px;
          height: 48px;
        }

        .logo-icon [data-lucide] {
          width: 24px;
          height: 24px;
        }

        .theme-toggle {
          top: 0.75rem;
          left: 0.75rem;
          padding: 0.45rem;
        }
      }
    </style>
  </head>
  <body>
    <!-- دکمه تغییر تم -->
    <button id="themeToggle" class="theme-toggle" title="تغییر تم" data-tooltip="تغییر تم">
      <i data-lucide="moon"></i>
    </button>

    <div class="register-container">
      <div class="register-card">
        <div class="logo">
          <div class="logo-icon">
            <i data-lucide="user-plus"></i>
          </div>
          <h1>عضویت</h1>
          <p>حساب کاربری جدید بسازید</p>
        </div>

        <div id="alert" class="alert"></div>

        <form id="registerForm">
          <div class="form-group">
            <label class="form-label" for="username">نام کاربری</label>
            <div class="input-wrapper">
              <input type="text" id="username" class="form-input" placeholder="فقط حروف انگلیسی و اعداد" required autocomplete="username">
              <span class="input-icon">
                <i data-lucide="user"></i>
              </span>
            </div>
            <div class="hint-text">نام کاربری پس از ثبت قابل تغییر نیست</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="display_name">نام نمایشی</label>
            <div class="input-wrapper">
              <input type="text" id="display_name" class="form-input" placeholder="نامی که دیگران می‌بینند" required autocomplete="name">
              <span class="input-icon">
                <i data-lucide="user-circle"></i>
              </span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">رمز عبور</label>
            <div class="input-wrapper">
              <input type="password" id="password" class="form-input" placeholder="حداقل ۶ کاراکتر" required autocomplete="new-password">
              <span class="input-icon">
                <i data-lucide="lock"></i>
              </span>
              <button type="button" class="toggle-password" data-target="password" aria-label="نمایش رمز عبور">
                <i data-lucide="eye-off" class="eye-icon"></i>
              </button>
            </div>
            <div id="passwordStrength" class="password-strength"></div>
          </div>

          <div class="form-group">
            <label class="form-label" for="confirm_password">تکرار رمز عبور</label>
            <div class="input-wrapper">
              <input type="password" id="confirm_password" class="form-input" placeholder="رمز عبور را دوباره وارد کنید" required autocomplete="new-password">
              <span class="input-icon">
                <i data-lucide="lock"></i>
              </span>
              <button type="button" class="toggle-password" data-target="confirm_password" aria-label="نمایش رمز عبور">
                <i data-lucide="eye-off" class="eye-icon"></i>
              </button>
            </div>
          </div>

          <button type="submit" id="registerBtn" class="btn">
            <i data-lucide="user-plus"></i>
            <span>ثبت نام</span>
          </button>
        </form>

        <div class="login-link">قبلاً ثبت نام کرده‌اید؟</div>
        <button class="btn-login" onclick="window.location.href='index.php'">
          <i data-lucide="log-in"></i>
          <span>ورود به حساب کاربری</span>
        </button>
      </div>
    </div>

    <script>
      /* ============================================================
         ICON REFRESH HELPER
         ============================================================ */
      function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
          try {
            window.lucide.createIcons();
          } catch (e) {
            console.warn('Lucide refresh failed:', e);
          }
        }
      }

      /* ============================================================
         THEME TOGGLE
         ============================================================ */
      const themeToggle = document.getElementById('themeToggle');
      if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
        themeToggle.innerHTML = '<i data-lucide="sun"></i>';
        refreshIcons();
      }
      themeToggle.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        const isDark = document.documentElement.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        themeToggle.innerHTML = isDark
          ? '<i data-lucide="sun"></i>'
          : '<i data-lucide="moon"></i>';
        refreshIcons();
      });

      /* ============================================================
         TOGGLE PASSWORD VISIBILITY
         ============================================================ */
      document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
          const targetId = this.getAttribute('data-target');
          const passwordField = document.getElementById(targetId);
          const icon = this.querySelector('.eye-icon');
          const isHidden = passwordField.getAttribute('type') === 'password';

          passwordField.setAttribute('type', isHidden ? 'text' : 'password');
          icon.outerHTML = isHidden
            ? '<i data-lucide="eye" class="eye-icon"></i>'
            : '<i data-lucide="eye-off" class="eye-icon"></i>';
          refreshIcons();
        });
      });

      /* ============================================================
         ALERT
         ============================================================ */
      const alertDiv = document.getElementById('alert');
      function showAlert(message, type) {
        const icons = {
          success: '<i data-lucide="check-circle"></i>',
          error: '<i data-lucide="alert-circle"></i>'
        };
        alertDiv.innerHTML = `${icons[type] || ''}<span>${message}</span>`;
        alertDiv.className = `alert alert-${type} show`;
        refreshIcons();
        setTimeout(() => {
          alertDiv.classList.remove('show');
        }, 4000);
      }

      /* ============================================================
         PASSWORD STRENGTH
         ============================================================ */
      const passwordInput = document.getElementById('password');
      const strengthDiv = document.getElementById('passwordStrength');

      function checkPasswordStrength(password) {
        if (password.length === 0) {
          strengthDiv.innerHTML = '';
          return;
        }
        let strength = 'weak',
            text = 'ضعیف';
        if (password.length >= 8 && /[a-z]/.test(password) && /[0-9]/.test(password)) {
          strength = 'medium';
          text = 'متوسط';
        }
        if (password.length >= 10 && /[a-z]/.test(password) && /[0-9]/.test(password) && /[!@#$%^&*]/.test(password)) {
          strength = 'strong';
          text = 'قوی';
        }
        strengthDiv.innerHTML = `<span class="strength-${strength}">قدرت رمز: ${text}</span>`;
      }

      passwordInput.addEventListener('input', () => checkPasswordStrength(passwordInput.value));

      /* ============================================================
         FORM SUBMIT
         ============================================================ */
      const form = document.getElementById('registerForm');
      const registerBtn = document.getElementById('registerBtn');

      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const display_name = document.getElementById('display_name').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (!username || !display_name || !password) {
          showAlert('لطفاً تمام فیلدها را پر کنید', 'error');
          return;
        }
        if (username.length < 3) {
          showAlert('نام کاربری باید حداقل ۳ کاراکتر باشد', 'error');
          return;
        }
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
          showAlert('نام کاربری فقط می‌تواند شامل حروف انگلیسی، اعداد و زیرخط باشد', 'error');
          return;
        }
        if (password.length < 6) {
          showAlert('رمز عبور باید حداقل ۶ کاراکتر باشد', 'error');
          return;
        }
        if (password !== confirmPassword) {
          showAlert('رمز عبور و تکرار آن مطابقت ندارند', 'error');
          return;
        }

        const originalHTML = registerBtn.innerHTML;
        registerBtn.innerHTML = '<span class="loading"></span><span>در حال ثبت نام...</span>';
        registerBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('username', username);
        formData.append('display_name', display_name);
        formData.append('password', password);

        try {
          const response = await fetch('api/api.php', {
            method: 'POST',
            body: formData
          });
          const result = await response.json();

          if (result.ok) {
            showAlert(result.message || 'ثبت نام موفق! در حال انتقال به صفحه ورود...', 'success');
            setTimeout(() => {
              window.location.href = 'index.php';
            }, 1500);
          } else {
            showAlert(result.message || 'خطا در ثبت نام', 'error');
          }
        } catch (error) {
          showAlert('خطا در ارتباط با سرور', 'error');
        } finally {
          registerBtn.innerHTML = originalHTML;
          registerBtn.disabled = false;
          refreshIcons();
        }
      });

      /* ============================================================
         SERVICE WORKER
         ============================================================ */
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then((registration) => {
              console.log('Service Worker registered:', registration.scope);
            })
            .catch((error) => {
              console.error('Service Worker registration failed:', error);
            });
        });
      }

      /* ============================================================
         INITIAL LOAD
         ============================================================ */
      document.addEventListener('DOMContentLoaded', refreshIcons);
      setTimeout(refreshIcons, 300);
      setTimeout(refreshIcons, 1000);
    </script>
  </body>
</html>