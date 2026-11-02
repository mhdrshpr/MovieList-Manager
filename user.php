<?php
// user.php
require_once 'api/db_connect.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$display_name = $_SESSION['display_name'];

$stmt = $pdo->prepare("SELECT is_active, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch();
$is_active = $userInfo['is_active'] ?? 1;
$member_since = $userInfo['created_at'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>پروفایل کاربر | مدیریت فیلم و سریال</title>

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

    <script src="static/js/html2canvas.min.js"></script>
    <script>window.html2canvas || document.write('<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"><\/script>')</script>
    <script src="static/js/jspdf.umd.min.js"></script>
    <script>(window.jspdf && window.jspdf.jsPDF) || document.write('<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"><\/script>')</script>

    <style>
      /* ==================== FONTS ==================== */
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

      * { font-family: 'IRANSansX', system-ui, -apple-system, Tahoma, sans-serif; }

      /* ==================== LUCIDE ICON HELPERS ==================== */
      [data-lucide] {
        display: inline-block;
        width: 15px;
        height: 15px;
        stroke-width: 2;
        vertical-align: middle;
        flex-shrink: 0;
      }
      .icon { width: 20px; height: 20px; }
      .icon-small { width: 16px; height: 16px; }
      .icon-mini { width: 14px; height: 14px; }
      .icon-h3 { width: 24px; height: 24px; }
      .icon-large { width: 60px; height: 60px; opacity: 0.4; }

      :root {
        --font-size: 16px;
        --background: #f8fafc;
        --header-background: rgba(248, 250, 252, 0.5);
        --foreground: #0f172a;
        --card: #ffffff;
        --card-foreground: #0f172a;
        --primary: #0891b2;
        --primary-foreground: #ffffff;
        --primary-light: #06b6d4;
        --primary-dark: #0e7490;
        --secondary: #f1f5f9;
        --secondary-foreground: #334155;
        --muted: #f8fafc;
        --muted-foreground: #475569;
        --accent: #ecfeff;
        --accent-foreground: #0e7490;
        --destructive: #dc2626;
        --destructive-foreground: #ffffff;
        --success: #10b981;
        --warning: #f59e0b;
        --purple: #8b5cf6;
        --border: #e2e8f0;
        --input-background: #ffffff;
        --radius: 1rem;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow: 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1);
      }

      .dark {
        --background: #0b1120;
        --header-background: rgba(11, 17, 32, 0.5);
        --foreground: #f1f5f9;
        --card: #111827;
        --card-foreground: #f1f5f9;
        --primary: #22d3ee;
        --primary-foreground: #0b1120;
        --primary-light: #67e8f9;
        --primary-dark: #0891b2;
        --secondary: #1e293b;
        --secondary-foreground: #cbd5e1;
        --muted: #1e293b;
        --muted-foreground: #94a3b8;
        --accent: #0c2d3b;
        --accent-foreground: #67e8f9;
        --destructive: #f87171;
        --destructive-foreground: #0b1120;
        --success: #34d399;
        --warning: #fbbf24;
        --purple: #a78bfa;
        --border: #1e293b;
        --input-background: #111827;
      }

      * { margin: 0; padding: 0; box-sizing: border-box; }

      body {
        background-color: var(--background);
        color: var(--foreground);
        font-size: var(--font-size);
        line-height: 1.5;
        transition: background-color 0.3s, color 0.3s;
        min-height: 100vh;
      }

      /* ==================== UNIFIED TOOLTIP (از dash.php) ==================== */
      [data-tooltip] {
        position: relative;
      }

      [data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 10px);
        right: 50%;
        transform: translateX(50%) scale(0.9);
        background: var(--foreground);
        color: var(--background);
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s, transform 0.2s;
        z-index: 3000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }

      [data-tooltip]::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 4px);
        right: 50%;
        transform: translateX(50%);
        border: 6px solid transparent;
        border-top-color: var(--foreground);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
        z-index: 3000;
      }

      /* Smart flip: نمایش در پایین در صورت نبود فضای کافی در بالا */
      [data-tooltip].tooltip-bottom::after {
        bottom: auto !important;
        top: calc(100% + 10px) !important;
      }
      [data-tooltip].tooltip-bottom::before {
        bottom: auto !important;
        top: calc(100% + 4px) !important;
        border-top-color: transparent !important;
        border-bottom-color: var(--foreground) !important;
      }

      [data-tooltip]:hover::after,
      [data-tooltip]:hover::before {
        opacity: 1;
        transform: translateX(50%) scale(1);
      }

      /* غیرفعال کردن tooltip در موبایل */
      @media (max-width: 768px) {
        [data-tooltip]::after,
        [data-tooltip]::before {
          display: none !important;
        }
      }

      .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1.5rem;
      }

      /* ==================== HEADER ==================== */
      .header {
        position: sticky;
        top: 0;
        z-index: 50;
        background: var(--header-background);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--border);
      }

      .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 0;
      }

      .logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.25rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
      }

      .logo [data-lucide] {
        color: var(--primary);
        stroke: var(--primary);
      }

      .nav {
        display: flex;
        align-items: center;
        gap: 1rem;
      }

      .nav-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        background: transparent;
        color: var(--foreground);
        text-decoration: none;
        border-bottom: 2px solid transparent;
      }

      .nav-link:hover,
      .nav-link.active {
        border-bottom-color: var(--primary);
        color: var(--primary);
      }

      .nav-link-logout { color: var(--destructive); }
      .nav-link-logout:hover {
        border-bottom-color: var(--destructive);
        color: var(--destructive);
      }

      /* ==================== BUTTONS ==================== */
      .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
      }

      .btn-primary { background: var(--primary); color: #fff; }
      .btn-primary:hover { background: var(--primary-light); }
      .btn-secondary {
        background: var(--secondary);
        color: var(--secondary-foreground);
        border-color: var(--border);
      }
      .btn-secondary:hover { background: var(--accent); }
      .btn-outline {
        background: transparent;
        border-color: var(--border);
        color: var(--foreground);
      }
      .btn-outline:hover { background: var(--secondary); }
      .btn-danger {
        background: var(--destructive);
        color: white;
        border-color: var(--destructive);
      }
      .btn-danger:hover { background: #b91c1c; }
      .btn-warning {
        background: var(--warning);
        color: white;
        border-color: var(--warning);
      }
      .btn-warning:hover { background: #d97706; }
      .btn-sm { padding: 0.35rem 0.85rem; font-size: 0.75rem; }

      .btn-icon {
        padding: 0.5rem;
        border-radius: 12px;
        background: transparent;
        color: var(--foreground);
        border: 1px solid var(--border);
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }
      .btn-icon:hover { background: var(--secondary); }

      /* ==================== LAYOUT ==================== */
      .profile-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2rem;
        margin-top: 2rem;
      }

      .sidebar {
        background: var(--card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        overflow: hidden;
        position: sticky;
        top: 90px;
        height: fit-content;
      }

      .sidebar-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        padding: 1.75rem 1.5rem;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
      }

      .sidebar-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
      }

      .avatar {
        width: 88px;
        height: 88px;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        border: 3px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        position: relative;
        z-index: 1;
      }

      .avatar [data-lucide] {
        color: white;
        stroke: white;
        width: 42px;
        height: 42px;
      }

      .sidebar-header h3 {
        color: white;
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
        position: relative;
        z-index: 1;
      }

      .sidebar-header p {
        opacity: 0.9;
        font-size: 0.75rem;
        position: relative;
        z-index: 1;
      }

      .sidebar-nav { padding: 0.5rem 0; }

      .sidebar-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--foreground);
        border-right: 3px solid transparent;
        position: relative;
      }

      .sidebar-item:hover { background: var(--secondary); }
      .sidebar-item.active {
        background: var(--accent);
        border-right-color: var(--primary);
        color: var(--primary);
      }
      .sidebar-item [data-lucide] { width: 20px; height: 20px; }

      .sidebar-item.danger-item.active {
        background: #ef444415;
        border-right-color: var(--destructive);
        color: var(--destructive);
      }
      .sidebar-item.danger-item:hover { color: var(--destructive); }

      .main-content { min-width: 0; }

      .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
      }
      .tab-content.active { display: block; }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }

      /* ==================== CARD ==================== */
      .card {
        background: var(--card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        overflow: hidden;
        margin-bottom: 1.5rem;
      }

      .card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
      }

      .card-title {
        font-size: 1.05rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .card-title [data-lucide] {
        color: var(--primary);
        stroke: var(--primary);
      }

      .card-body { padding: 1.25rem; }

      /* ==================== INFO ROWS ==================== */
      .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--border);
        gap: 1rem;
      }

      .info-row:last-child { border-bottom: none; }

      .info-label {
        font-weight: 500;
        color: var(--muted-foreground);
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .info-label [data-lucide] {
        width: 18px;
        height: 18px;
        opacity: 0.7;
      }

      .info-value {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
      }

      /* ==================== FORM ==================== */
      .form-group { margin-bottom: 1.25rem; }
      .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--foreground);
      }
      .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--input-background);
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 0.875rem;
        color: var(--foreground);
        transition: all 0.2s;
      }
      .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
      }

      /* ==================== ALERTS ==================== */
      .alert {
        padding: 0.75rem 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        display: none;
        font-size: 0.85rem;
        align-items: center;
        gap: 0.5rem;
      }
      .alert.show { display: flex; }
      .alert-success {
        background: #10b98120;
        color: var(--success);
        border: 1px solid #10b98130;
      }
      .alert-error {
        background: #ef444420;
        color: var(--destructive);
        border: 1px solid #ef444430;
      }
      .alert-warning {
        background: #f59e0b20;
        color: var(--warning);
        border: 1px solid #f59e0b30;
      }

      /* ==================== TOGGLE ==================== */
      .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
        flex-shrink: 0;
      }
      .toggle-switch input { opacity: 0; width: 0; height: 0; }
      .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--border);
        transition: 0.3s;
        border-radius: 26px;
      }
      .toggle-slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      }
      input:checked + .toggle-slider { background-color: var(--primary); }
      input:checked + .toggle-slider:before { transform: translateX(24px); }

      /* ==================== STATS ==================== */
      .stats-overview { display: flex; flex-direction: column; gap: 1rem; }
      .stats-row-primary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
      }
      .stats-row-secondary {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }
      .stats-row-tertiary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
      }
      .stats-row-quaternary {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }

      .stat-main-card {
        background: var(--card);
        border-radius: var(--radius);
        padding: 1.1rem 1rem;
        text-align: center;
        border: 1px solid var(--border);
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
      }
      .stat-main-card::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
      }
      .stat-main-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow);
        border-color: var(--primary);
      }
      .stat-main-number {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        line-height: 1.2;
      }
      .stat-main-label {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
      }
      .stat-child-card {
        background: var(--secondary);
        border-radius: var(--radius);
        padding: 0.85rem;
        text-align: center;
        border-right: 3px solid var(--primary);
      }
      .stat-child-number {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--primary);
      }
      .stat-child-label {
        font-size: 0.65rem;
        color: var(--muted-foreground);
        margin-top: 0.2rem;
      }

      .stat-watched-card,
      .stat-downloaded-card,
      .stat-completed-card,
      .stat-rating-card,
      .stat-comment-card {
        border-radius: var(--radius);
        padding: 1rem;
        text-align: center;
        transition: all 0.2s;
      }
      .stat-watched-card {
        background: linear-gradient(135deg, #10b98120 0%, #10b98108 100%);
        border: 1px solid #10b98130;
      }
      .stat-downloaded-card {
        background: linear-gradient(135deg, #3b82f620 0%, #3b82f608 100%);
        border: 1px solid #3b82f630;
      }
      .stat-completed-card {
        background: linear-gradient(135deg, #8b5cf620 0%, #8b5cf608 100%);
        border: 1px solid #8b5cf630;
      }
      .stat-rating-card {
        background: linear-gradient(135deg, #f59e0b20 0%, #f59e0b08 100%);
        border: 1px solid #f59e0b30;
      }
      .stat-comment-card {
        background: linear-gradient(135deg, #ec489a20 0%, #ec489a08 100%);
        border: 1px solid #ec489a30;
      }
      .stat-watched-card:hover,
      .stat-downloaded-card:hover,
      .stat-completed-card:hover,
      .stat-rating-card:hover,
      .stat-comment-card:hover {
        transform: translateY(-2px);
      }
      .stat-watched-number { font-size: 1.6rem; font-weight: 700; color: var(--success); }
      .stat-downloaded-number { font-size: 1.6rem; font-weight: 700; color: #3b82f6; }
      .stat-completed-number { font-size: 1.6rem; font-weight: 700; color: var(--purple); }
      .stat-rating-number { font-size: 1.6rem; font-weight: 700; color: var(--warning); }
      .stat-comment-number { font-size: 1.6rem; font-weight: 700; color: #ec489a; }

      .stat-label-sm {
        font-size: 0.7rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
      }

      /* ==================== CHARTS ==================== */
      .donut-chart-container {
        position: relative;
        width: 200px;
        height: 200px;
        margin: 0 auto;
      }
      .donut-center-text {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
      }
      .pie-chart-container {
        position: relative;
        width: 260px;
        height: 260px;
        margin: 0 auto;
      }
      .pie-chart-svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
        filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.1));
      }
      .chart-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.6rem;
        margin-top: 1.25rem;
        max-height: 220px;
        overflow-y: auto;
        padding: 0.25rem;
      }
      .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.72rem;
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        background: var(--secondary);
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border);
      }
      .legend-item:hover {
        background: var(--accent);
        transform: scale(1.03);
        border-color: var(--primary);
      }
      .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 4px;
        flex-shrink: 0;
      }
      .bar-chart { margin-top: 1rem; }
      .bar-item { margin-bottom: 1rem; }
      .bar-label {
        font-size: 0.75rem;
        margin-bottom: 0.35rem;
        display: flex;
        justify-content: space-between;
        font-weight: 500;
      }
      .bar-container {
        background: var(--secondary);
        border-radius: 12px;
        overflow: hidden;
        height: 28px;
      }
      .bar-fill {
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        height: 100%;
        width: 0%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-left: 8px;
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        border-radius: 12px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
      }

      /* ==================== HEATMAP ==================== */
      .heatmap-container {
        overflow-x: auto;
        padding-bottom: 0.5rem;
      }
      .heatmap-grid {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
      }
      .heatmap-day {
        width: 22px;
        height: 22px;
        background: var(--secondary);
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid transparent;
      }
      .heatmap-day:hover {
        transform: scale(1.25);
        border-color: var(--primary);
        z-index: 2;
        position: relative;
      }
      .heatmap-level-0 { background: var(--secondary); }
      .heatmap-level-1 { background: #9ae6b4; }
      .heatmap-level-2 { background: #48bb78; }
      .heatmap-level-3 { background: #276749; }
      .dark .heatmap-level-1 { background: #22543d; }
      .dark .heatmap-level-2 { background: #2f855a; }
      .dark .heatmap-level-3 { background: #38a169; }
      .heatmap-legend {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
      }
      .heatmap-legend-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        color: var(--muted-foreground);
      }

      /* ==================== ACHIEVEMENTS ==================== */
      .achievements-header-card {
        background: linear-gradient(135deg, var(--card) 0%, var(--accent) 100%);
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid var(--border);
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
      }
      .achievements-header-card::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle at 30% 30%, rgba(8, 145, 178, 0.08) 0%, transparent 60%);
        pointer-events: none;
      }
      .achievements-header-content {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
      }
      .achievements-header-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(8, 145, 178, 0.35);
      }
      .achievements-header-icon [data-lucide] {
        color: white;
        stroke: white;
        width: 28px;
        height: 28px;
      }
      .achievements-header-info { flex: 1; min-width: 200px; }
      .achievements-header-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 0.25rem;
      }
      .achievements-header-subtitle {
        font-size: 0.75rem;
        color: var(--muted-foreground);
      }
      .achievements-header-progress {
        flex: 1;
        min-width: 180px;
        max-width: 320px;
      }
      .progress-bar-bg {
        background: var(--border);
        border-radius: 12px;
        height: 10px;
        overflow: hidden;
        margin-bottom: 0.5rem;
      }
      .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: 12px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 12px rgba(8, 145, 178, 0.4);
      }
      .achievements-header-percent {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary);
        white-space: nowrap;
      }

      /* ==================== SPECIAL ACHIEVEMENTS (بازطراحی) ==================== */
      .special-achievements-section { margin-bottom: 2rem; }
      .special-achievements-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--primary);
      }
      .special-achievements-title [data-lucide] {
        width: 18px;
        height: 18px;
      }
      .special-achievements-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
        margin-right: 0.5rem;
      }
      .special-achievements-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1rem;
      }
      .special-ach-card {
        position: relative;
        border-radius: 18px;
        padding: 1.25rem 1rem;
        text-align: center;
        color: white;
        overflow: hidden;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: default;
        border: 1px solid rgba(255, 255, 255, 0.2);
      }
      .special-ach-card:hover {
        transform: translateY(-4px) scale(1.02);
      }
      .special-ach-card.earned {
        box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.25);
      }
      .special-ach-card.locked {
        filter: grayscale(100%);
        opacity: 0.7;
        background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
      }
      .special-ach-card.locked::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.15);
        pointer-events: none;
      }
      .special-ach-card.purple {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.95) 0%, rgba(109, 40, 217, 0.95) 100%);
        box-shadow: 0 10px 30px -8px rgba(139, 92, 246, 0.5);
      }
      .special-ach-card.gold {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.95) 0%, rgba(217, 119, 6, 0.95) 100%);
        box-shadow: 0 10px 30px -8px rgba(245, 158, 11, 0.5);
      }
      .special-ach-card.green {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.95) 100%);
        box-shadow: 0 10px 30px -8px rgba(16, 185, 129, 0.5);
      }
      .special-ach-card::before {
        content: '';
        position: absolute;
        top: -50%; right: -50%;
        width: 200%; height: 200%;
        background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.15) 0%, transparent 60%);
        pointer-events: none;
      }
      .special-ach-card-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.25));
      }
      .special-ach-card.locked .special-ach-card-icon {
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.25)) grayscale(100%);
      }
      .special-ach-card-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }
      .special-ach-card-title {
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        position: relative;
        z-index: 1;
      }
      .special-ach-card-desc {
        font-size: 0.65rem;
        opacity: 0.95;
        line-height: 1.5;
        position: relative;
        z-index: 1;
      }
      .special-ach-card.locked .special-ach-card-desc {
        opacity: 1;
        font-weight: 500;
      }
      .special-ach-card-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        margin-top: 0.6rem;
        padding: 0.2rem 0.6rem;
        font-size: 0.6rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 20px;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 1;
      }
      .special-ach-card.locked .special-ach-card-badge {
        background: rgba(255, 255, 255, 0.35);
      }

      /* ==================== ACHIEVEMENT CATEGORIES ==================== */
      .achievement-categories-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }
      .achievement-category-card {
        background: var(--card);
        border-radius: 18px;
        padding: 1.25rem;
        border: 1px solid var(--border);
        transition: all 0.25s ease;
        position: relative;
      }
      .achievement-category-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow);
      }
      .achievement-category-header {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        flex-wrap: wrap;
      }
      .achievement-category-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
      }
      .achievement-category-icon-wrap img {
        width: 36px;
        height: 36px;
        object-fit: contain;
      }
      .achievement-category-info { flex: 1; min-width: 150px; }
      .achievement-category-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--foreground);
      }
      .achievement-category-desc {
        font-size: 0.7rem;
        color: var(--muted-foreground);
        margin-top: 0.15rem;
      }
      .achievement-category-progress {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 140px;
      }
      .achievement-category-progress-info {
        text-align: left;
        flex-shrink: 0;
      }
      .achievement-category-stage-num {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--primary);
      }
      .achievement-category-progress-bar {
        width: 100px;
        height: 6px;
        background: var(--secondary);
        border-radius: 12px;
        overflow: hidden;
        margin-top: 4px;
      }
      .achievement-category-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: 12px;
        transition: width 0.6s ease;
      }

      /* ==================== ACCORDION (فقط موبایل) ==================== */
      .accordion-toggle-btn {
        display: none;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: var(--secondary);
        border: 1px solid var(--border);
        color: var(--foreground);
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
      }
      .accordion-toggle-btn:hover {
        background: var(--accent);
        border-color: var(--primary);
        color: var(--primary);
      }
      .accordion-toggle-btn [data-lucide] {
        width: 20px;
        height: 20px;
        transition: transform 0.3s ease;
      }

      .achievement-category-body { display: block; }

      /* ==================== STAGE SLIDER ==================== */
      .stage-slider-wrap {
        position: relative;
        margin-top: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .stage-slider-track {
        flex: 1;
        overflow: hidden;
        position: relative;
        border-radius: 14px;
      }
      .stage-slider-inner {
        display: flex;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        gap: 0.75rem;
      }
      .stage-card {
        flex: 0 0 calc(50% - 0.375rem);
        padding: 1rem 0.75rem;
        border-radius: 14px;
        text-align: center;
        background: var(--secondary);
        border: 1.5px solid var(--border);
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
      }
      .stage-card.earned {
        background: linear-gradient(135deg, var(--accent) 0%, rgba(8, 145, 178, 0.08) 100%);
        border-color: var(--primary);
      }
      .stage-card.earned::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
      }
      .stage-card.current {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15);
      }
      .stage-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow);
      }
      .stage-card-icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.15));
        transition: transform 0.25s ease;
      }
      .stage-card:hover .stage-card-icon {
        transform: scale(1.08) rotate(2deg);
      }
      .stage-card.locked .stage-card-icon {
        filter: grayscale(100%) opacity(0.35);
      }
      .stage-card-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }
      .stage-card-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--foreground);
      }
      .stage-card-desc {
        font-size: 0.62rem;
        color: var(--muted-foreground);
        margin-top: 0.2rem;
        line-height: 1.5;
      }
      .stage-card-status {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.6rem;
        padding: 0.2rem 0.55rem;
        font-size: 0.6rem;
        font-weight: 600;
        border-radius: 20px;
      }
      .stage-card.earned .stage-card-status {
        background: #10b98120;
        color: var(--success);
      }
      .stage-card.locked .stage-card-status {
        background: var(--secondary);
        color: var(--muted-foreground);
      }
      .stage-card-progress-mini {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid var(--border);
      }
      .stage-progress-bar-bg {
        background: var(--border);
        border-radius: 8px;
        height: 4px;
        overflow: hidden;
      }
      .stage-progress-bar-fill {
        height: 100%;
        background: var(--primary);
        border-radius: 8px;
        transition: width 0.4s ease;
      }
      .stage-remaining {
        font-size: 0.55rem;
        color: var(--primary);
        margin-top: 0.35rem;
        font-weight: 500;
      }

      /* Stage Navigation */
      .stage-nav-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--secondary);
        border: 1px solid var(--border);
        color: var(--foreground);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
      }
      .stage-nav-btn:hover:not(:disabled) {
        background: var(--accent);
        border-color: var(--primary);
        color: var(--primary);
      }
      .stage-nav-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
      }
      .stage-nav-btn [data-lucide] {
        width: 18px;
        height: 18px;
      }

      .stage-dots {
        display: flex;
        justify-content: center;
        gap: 0.35rem;
        margin-top: 0.75rem;
      }
      .stage-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--border);
        cursor: pointer;
        transition: all 0.3s;
      }
      .stage-dot.active {
        background: var(--primary);
        width: 20px;
        border-radius: 4px;
      }

      /* ==================== DANGER ZONE ==================== */
      .danger-zone-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }
      .danger-card {
        border-radius: 18px;
        padding: 1.25rem 1.5rem;
        border: 1.5px solid;
        position: relative;
        overflow: hidden;
        transition: all 0.25s ease;
      }
      .danger-card::before {
        content: '';
        position: absolute;
        top: -30%; left: -30%;
        width: 160%; height: 160%;
        background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.4) 0%, transparent 50%);
        pointer-events: none;
      }
      .danger-card.warning {
        background: linear-gradient(135deg, #f59e0b08 0%, #f59e0b15 100%);
        border-color: #f59e0b60;
      }
      .danger-card.destructive {
        background: linear-gradient(135deg, #dc262608 0%, #dc262615 100%);
        border-color: #dc262660;
      }
      .danger-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -8px rgba(0, 0, 0, 0.15);
      }
      .danger-card-content {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
      }
      .danger-card-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .danger-card.warning .danger-card-icon {
        background: linear-gradient(135deg, #f59e0b30 0%, #f59e0b15 100%);
        color: #f59e0b;
        border: 1.5px solid #f59e0b40;
      }
      .danger-card.destructive .danger-card-icon {
        background: linear-gradient(135deg, #dc262630 0%, #dc262615 100%);
        color: #dc2626;
        border: 1.5px solid #dc262640;
      }
      .danger-card-icon [data-lucide] {
        width: 26px;
        height: 26px;
      }
      .danger-card-info { flex: 1; min-width: 200px; }
      .danger-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
      }
      .danger-card.warning .danger-card-title { color: #b45309; }
      .danger-card.destructive .danger-card-title { color: #b91c1c; }
      .dark .danger-card.warning .danger-card-title { color: #fbbf24; }
      .dark .danger-card.destructive .danger-card-title { color: #f87171; }
      .danger-card-description {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        line-height: 1.6;
        margin-bottom: 0.75rem;
      }

      /* ==================== MODALS ==================== */
      .modal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
      }
      .modal.show { display: flex; }
      .modal-content {
        background: var(--card);
        border-radius: 20px;
        max-width: 500px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        border: 1px solid var(--border);
        animation: modalIn 0.3s ease;
      }
      .modal-content.large { max-width: 800px; }
      @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
      }
      .modal-header {
        padding: 1.25rem 1.5rem;
        background: var(--card);
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 10;
      }
      .modal-header h3 {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
      }
      .modal-header h3 [data-lucide] {
        color: var(--primary);
        stroke: var(--primary);
        width: 22px;
        height: 22px;
      }
      .modal-body { padding: 1.5rem; }
      .modal-footer {
        padding: 1rem 1.5rem;
        background: var(--card);
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        position: sticky;
        bottom: 0;
      }

      /* ==================== SEARCH USER RESULTS ==================== */
      .search-results-list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
      }
      .user-result-card {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.85rem 1rem;
        background: var(--secondary);
        border: 1px solid var(--border);
        border-radius: 14px;
        transition: all 0.2s;
        cursor: pointer;
      }
      .user-result-card:hover {
        background: var(--accent);
        border-color: var(--primary);
        transform: translateX(-3px);
      }
      .user-result-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: white;
        box-shadow: 0 4px 10px rgba(8, 145, 178, 0.3);
      }
      .user-result-avatar [data-lucide] {
        width: 24px;
        height: 24px;
        color: white;
        stroke: white;
      }
      .user-result-info { flex: 1; min-width: 0; }
      .user-result-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--foreground);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .user-result-username {
        font-size: 0.7rem;
        color: var(--muted-foreground);
        direction: ltr;
        text-align: right;
      }
      .user-result-action {
        padding: 0.45rem 0.85rem;
        border-radius: 10px;
        background: var(--primary);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s;
        flex-shrink: 0;
      }
      .user-result-card:hover .user-result-action { background: var(--primary-light); }

      /* ==================== FOOTER ==================== */
      .site-footer {
        margin-top: 3rem;
        padding: 1.5rem 0;
        border-top: 1px solid var(--border);
        text-align: center;
      }
      .footer-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        flex-wrap: wrap;
      }
      .footer-text {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
      }
      .footer-dot {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--muted-foreground);
        opacity: 0.5;
      }
      .footer-donate-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.75rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border);
        background: var(--secondary);
        color: var(--muted-foreground);
        text-decoration: none;
      }
      .footer-donate-btn:hover {
        color: var(--primary);
        border-color: var(--primary);
      }

      /* ==================== LOADING ==================== */
      .loading {
        display: inline-block;
        width: 22px;
        height: 22px;
        border: 2.5px solid var(--border);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
      .loading-small {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
      @keyframes spin { to { transform: rotate(360deg); } }
      .loading-center {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        gap: 0.75rem;
        color: var(--muted-foreground);
        font-size: 0.8rem;
      }

      /* ==================== SKELETON ==================== */
      @keyframes skeletonPulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
      }
      @keyframes skeletonLoading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
      }
      .skeleton-line {
        background: linear-gradient(90deg, var(--border) 25%, var(--secondary) 50%, var(--border) 75%);
        background-size: 200% 100%;
        animation: skeletonLoading 1.5s ease-in-out infinite;
        border-radius: 8px;
        height: 14px;
        margin-bottom: 8px;
      }
      .skeleton-row {
        height: 48px;
        background: linear-gradient(90deg, var(--border) 25%, var(--secondary) 50%, var(--border) 75%);
        background-size: 200% 100%;
        animation: skeletonLoading 1.5s ease-in-out infinite;
        border-radius: 10px;
        margin-bottom: 10px;
      }
      .skeleton-stat-card {
        height: 100px;
        background: linear-gradient(90deg, var(--border) 25%, var(--secondary) 50%, var(--border) 75%);
        background-size: 200% 100%;
        animation: skeletonLoading 1.5s ease-in-out infinite;
        border-radius: 14px;
      }
      .skeleton-chart {
        width: 180px;
        height: 180px;
        background: var(--border);
        border-radius: 50%;
        margin: 0 auto;
        animation: skeletonPulse 1.5s ease-in-out infinite;
      }
      .skeleton-ach-card {
        height: 180px;
        background: var(--border);
        border-radius: 16px;
        animation: skeletonPulse 1.5s ease-in-out infinite;
      }

      /* ==================== HELPERS ==================== */
      .text-center { text-align: center; }
      .mt-2 { margin-top: 0.5rem; }
      .mt-4 { margin-top: 1rem; }
      .mb-2 { margin-bottom: 0.5rem; }
      .mb-4 { margin-bottom: 1rem; }
      .flex { display: flex; }
      .items-center { align-items: center; }
      .justify-between { justify-content: space-between; }
      .justify-center { justify-content: center; }
      .justify-end { justify-content: flex-end; }
      .gap-2 { gap: 0.5rem; }
      .gap-3 { gap: 0.75rem; }
      .w-full { width: 100%; }

      /* ==================== RESPONSIVE ==================== */
      @media (max-width: 900px) {
        .profile-layout {
          grid-template-columns: 1fr;
          gap: 1.25rem;
        }
        .sidebar { position: static; }
        .sidebar-nav {
          display: flex;
          flex-wrap: wrap;
          padding: 0.5rem;
          gap: 0.35rem;
        }
        .sidebar-item {
          flex: 1;
          justify-content: center;
          border-right: none;
          border-bottom: 3px solid transparent;
          border-radius: 10px;
          padding: 0.75rem 0.5rem;
          font-size: 0.75rem;
          flex-direction: column;
          gap: 0.25rem;
        }
        .sidebar-item.active {
          border-right-color: transparent;
          border-bottom-color: var(--primary);
        }
        .sidebar-item span { font-size: 0.65rem; }
      }

      @media (max-width: 768px) {
        .container { padding: 0 1rem; }

        .header-content {
          flex-direction: column;
          gap: 5px;
        }
        .logo span { font-size: 1rem; }
        .nav { gap: 0.25rem; }
        .nav-link {
          padding: 0.5rem;
          gap: 0;
          border-radius: 8px;
          font-size: 0;
        }
        .nav-link span { display: none; }
        .nav-link [data-lucide] {
          width: 15px;
          height: 15px;
        }

        .stats-row-primary { grid-template-columns: repeat(2, 1fr); }
        .stats-row-secondary { grid-template-columns: 1fr; }
        .stats-row-tertiary { grid-template-columns: 1fr; }
        .stats-row-quaternary { grid-template-columns: repeat(2, 1fr); }
        .stat-main-number { font-size: 1.6rem; }
        .stat-watched-number,
        .stat-downloaded-number,
        .stat-completed-number,
        .stat-rating-number,
        .stat-comment-number { font-size: 1.35rem; }

        .special-achievements-grid { grid-template-columns: 1fr; }

        /* آکاردئون فقط موبایل */
        .accordion-toggle-btn { display: inline-flex; }
        .achievement-category-card.collapsed .achievement-category-body {
          display: none;
        }
        .achievement-category-card:not(.collapsed) .achievement-category-header {
          margin-bottom: 1rem;
          padding-bottom: 1rem;
          border-bottom: 1px solid var(--border);
        }
        .achievement-category-progress {
          min-width: auto;
          flex-basis: 100%;
          margin-top: 0.5rem;
        }
        .achievement-category-progress-bar { width: 100%; }

        /* اسلایدر در موبایل: یک آیتم در هر بار */
        .stage-card { flex: 0 0 100%; }

        .stage-card-icon { width: 52px; height: 52px; }
        .stage-card { padding: 0.85rem 0.6rem; }
        .stage-card-title { font-size: 0.72rem; }
        .stage-card-desc { font-size: 0.58rem; }

        .danger-card-content {
          flex-direction: column;
          align-items: stretch;
        }
        .danger-card-icon { align-self: flex-start; }
        .danger-card .btn { width: 100%; justify-content: center; }

        .modal {
          padding: 0;
          align-items: flex-end;
        }
        .modal-content {
          border-radius: 20px 20px 0 0;
          max-height: 92vh;
        }
        .modal-header { padding: 1rem 1.25rem; }
        .modal-body { padding: 1.25rem; }
        .modal-footer { padding: 0.85rem 1.25rem; }
        .modal-footer .btn { flex: 1; justify-content: center; }

        .heatmap-day { width: 18px; height: 18px; }
        .pie-chart-container { width: 220px; height: 220px; }
        .donut-chart-container { width: 170px; height: 170px; }

        .user-result-card { padding: 0.7rem 0.85rem; }
        .user-result-avatar { width: 40px; height: 40px; }
        .user-result-avatar [data-lucide] { width: 20px; height: 20px; }
        .user-result-name { font-size: 0.8rem; }
      }

      @media (max-width: 480px) {
        .stats-row-primary { grid-template-columns: 1fr 1fr; }
        .stats-row-quaternary { grid-template-columns: 1fr; }
        .achievements-header-content {
          flex-direction: column;
          text-align: center;
        }
        .achievements-header-icon { margin: 0 auto; }
        .achievements-header-progress { width: 100%; max-width: 100%; }
        .special-ach-card { padding: 1rem 0.85rem; }
      }

      /* در دسکتاپ: آکاردئون غیرفعال، همه باز */
      @media (min-width: 769px) {
        .accordion-toggle-btn { display: none !important; }
        .achievement-category-card.collapsed .achievement-category-body {
          display: block !important;
        }
        .achievement-category-header { margin-bottom: 1rem; }
      }
      /* ==================== PWA OFFLINE BANNER ==================== */
      .pwa-offline-banner {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.28);
        border-radius: 14px;
        padding: 0.65rem 1.25rem;
        margin-bottom: 1.25rem;
        font-size: 0.85rem;
        font-weight: 600;
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s ease;
      }
      .dark .pwa-offline-banner {
        background: rgba(239, 68, 68, 0.18);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.35);
      }
      body.is-offline .hide-offline {
        opacity: 0.45;
        pointer-events: none;
      }
    </style>
</head>
<body>
    <!-- ==================== HEADER ==================== -->
    <header class="header">
      <div class="container">
        <div class="header-content">
          <div class="logo">
            <i data-lucide="layers"></i>
            <span>MovieManager</span>
          </div>
          <div class="nav">
            <button id="themeToggle" class="btn-icon" data-tooltip="تغییر تم" title="تغییر تم">
              <i data-lucide="moon"></i>
            </button>
            <button id="searchUserBtn" class="btn-icon" data-tooltip="جستجوی کاربران" title="جستجوی کاربران">
              <i data-lucide="search"></i>
            </button>
            <a href="all_titles.php" class="nav-link">
              <i data-lucide="list"></i><span>همه عناوین</span>
            </a>
            <a href="dash.php" class="nav-link">
              <i data-lucide="layout-grid"></i><span>داشبورد</span>
            </a>
            <a href="user.php" class="nav-link active">
              <i data-lucide="user"></i><span><?php echo htmlspecialchars($display_name); ?></span>
            </a>
            <a href="logout.php" class="nav-link nav-link-logout">
              <i data-lucide="log-out"></i><span>خروج</span>
            </a>
          </div>
        </div>
      </div>
    </header>

    <main class="container">
      <div id="pwaOfflineBanner" class="pwa-offline-banner">
        <i data-lucide="wifi-off"></i>
        <span>شما در حالت آفلاین هستید. اطلاعات، دستاوردها و آمار به صورت فقط‌خواندنی نمایش داده می‌شوند.</span>
      </div>
      <div class="profile-layout">
        <!-- ==================== SIDEBAR ==================== -->
        <aside class="sidebar">
          <div class="sidebar-header">
            <div class="avatar">
              <i data-lucide="user"></i>
            </div>
            <h3><?php echo htmlspecialchars($display_name); ?></h3>
            <p>@<?php echo htmlspecialchars($username); ?></p>
          </div>
          <nav class="sidebar-nav">
            <div class="sidebar-item active" data-tab="profile">
              <i data-lucide="user"></i>
              <span>اطلاعات کاربری</span>
            </div>
            <div class="sidebar-item" data-tab="stats">
              <i data-lucide="chart-line"></i>
              <span>آمار</span>
            </div>
            <div class="sidebar-item" data-tab="achievements">
              <i data-lucide="trophy"></i>
              <span>دستاوردها</span>
            </div>
            <div class="sidebar-item danger-item" data-tab="danger">
              <i data-lucide="alert-triangle"></i>
              <span>منطقه خطر</span>
            </div>
          </nav>
        </aside>

        <!-- ==================== MAIN CONTENT ==================== -->
        <div class="main-content">

          <!-- ==================== TAB: PROFILE ==================== -->
          <div id="profileTab" class="tab-content active">
            <div id="profileSkeleton" style="display: block;">
              <div class="card">
                <div class="card-header">
                  <div class="skeleton-line" style="width: 150px; height: 24px;"></div>
                </div>
                <div class="card-body">
                  <div class="skeleton-row"></div>
                  <div class="skeleton-row"></div>
                  <div class="skeleton-row"></div>
                  <div class="skeleton-row"></div>
                </div>
              </div>
              <div class="card">
                <div class="card-header">
                  <div class="skeleton-line" style="width: 120px; height: 24px;"></div>
                </div>
                <div class="card-body">
                  <div class="skeleton-row"></div>
                  <div class="skeleton-row"></div>
                  <div class="skeleton-row"></div>
                </div>
              </div>
            </div>

            <div id="profileContent" style="display: none;">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="user"></i>
                    <span>اطلاعات حساب کاربری</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="profileAlert" class="alert"></div>
                  <div class="info-row">
                    <span class="info-label">
                      <i data-lucide="at-sign"></i>
                      <span>نام کاربری</span>
                    </span>
                    <span class="info-value" style="direction: ltr;"><?php echo htmlspecialchars($username); ?></span>
                  </div>
                  <div class="info-row">
                    <span class="info-label">
                      <i data-lucide="calendar"></i>
                      <span>تاریخ عضویت</span>
                    </span>
                    <span class="info-value"><?php echo date('Y/m/d', strtotime($member_since)); ?></span>
                  </div>
                  <div class="info-row">
                    <span class="info-label">
                      <i data-lucide="shield-check"></i>
                      <span>وضعیت حساب</span>
                    </span>
                    <span class="info-value">
                      <?php if ($is_active == 1): ?>
                        <span style="color: var(--success); display: inline-flex; align-items: center; gap: 0.35rem;">
                          <i data-lucide="check-circle"></i>
                          فعال
                        </span>
                      <?php else: ?>
                        <span style="color: var(--destructive); display: inline-flex; align-items: center; gap: 0.35rem;">
                          <i data-lucide="lock"></i>
                          غیرفعال (فریز)
                        </span>
                      <?php endif; ?>
                    </span>
                  </div>
                  <div class="info-row">
                    <span class="info-label">
                      <i data-lucide="eye"></i>
                      <span>نمایش عمومی پروفایل</span>
                    </span>
                    <span class="info-value">
                      <label class="toggle-switch">
                        <input type="checkbox" id="publicProfileToggle" <?php echo ($is_active == 1) ? '' : 'disabled'; ?>>
                        <span class="toggle-slider"></span>
                      </label>
                    </span>
                  </div>
                  <div style="font-size: 0.7rem; color: var(--muted-foreground); margin-top: 0.75rem; padding: 0.75rem; background: var(--secondary); border-radius: 10px; display: flex; gap: 0.5rem; align-items: flex-start;">
                    <i data-lucide="info" style="width:16px;height:16px; color: var(--primary); flex-shrink: 0; margin-top: 2px;"></i>
                    <span>با فعال کردن این گزینه، پروفایل شما برای سایر کاربران قابل مشاهده خواهد بود.</span>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="pencil"></i>
                    <span>ویرایش پروفایل</span>
                  </div>
                </div>
                <div class="card-body">
                  <form id="profileForm">
                    <div class="form-group">
                      <label class="form-label">نام نمایشی</label>
                      <input type="text" id="display_name" class="form-input" value="<?php echo htmlspecialchars($display_name); ?>" required>
                    </div>
                    <div class="form-group">
                      <label class="form-label">رمز عبور جدید</label>
                      <input type="password" id="new_password" class="form-input" placeholder="برای تغییر رمز عبور وارد کنید، در غیر این صورت خالی بگذارید">
                      <div style="font-size: 0.7rem; color: var(--muted-foreground); margin-top: 0.35rem; display: flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="info" style="width:14px;height:14px;"></i>
                        حداقل ۶ کاراکتر
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">تکرار رمز عبور جدید</label>
                      <input type="password" id="confirm_password" class="form-input" placeholder="رمز عبور جدید را دوباره وارد کنید">
                    </div>
                    <div class="flex justify-end">
                      <button type="submit" id="saveProfileBtn" class="btn btn-primary">
                        <i data-lucide="check"></i>
                        <span>ذخیره تغییرات</span>
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- ==================== TAB: STATS ==================== -->
          <div id="statsTab" class="tab-content">
            <div id="statsSkeleton" style="display: none;">
              <div class="card">
                <div class="card-header">
                  <div class="skeleton-line" style="width: 100px; height: 24px;"></div>
                </div>
                <div class="card-body">
                  <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <div class="skeleton-stat-card"></div>
                    <div class="skeleton-stat-card"></div>
                    <div class="skeleton-stat-card"></div>
                  </div>
                  <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <div class="skeleton-stat-card" style="height: 80px;"></div>
                    <div class="skeleton-stat-card" style="height: 80px;"></div>
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header">
                  <div class="skeleton-line" style="width: 140px; height: 24px;"></div>
                </div>
                <div class="card-body text-center">
                  <div class="skeleton-chart"></div>
                </div>
              </div>
            </div>

            <div id="statsContent" style="display: none;">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="chart-line"></i>
                    <span>آمار کلی</span>
                  </div>
                  <button id="exportStatsBtn" class="btn btn-outline btn-sm">
                    <i data-lucide="file-text"></i>
                    <span>خروجی PDF</span>
                  </button>
                </div>
                <div class="card-body">
                  <div id="statsGrid" class="stats-overview"></div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="check-circle"></i>
                    <span>درصد مشاهده کلی</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="watchPercentageContainer" class="donut-chart-container" data-tooltip="درصد کل آیتم‌های مشاهده شده"></div>
                  <div id="watchPercentageText" class="text-center mt-2" style="font-size: 0.8rem; color: var(--muted-foreground);"></div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="clock"></i>
                    <span>هر ماه چقدر فیلم دیدی؟</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="monthlyChartContainer" class="bar-chart" data-tooltip="نمودار توزیع تماشا در ماه‌های سال"></div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="calendar"></i>
                    <span>هر سال چقدر فیلم دیدی؟</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="yearlyChartContainer" class="bar-chart" data-tooltip="نمودار تماشا در سال‌های مختلف"></div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="tags"></i>
                    <span>بیشتر از چه ژانری داری؟</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="genreChartContainer" class="pie-chart-container" data-tooltip="توزیع ژانرهای فیلم و سریال"></div>
                  <div id="genreLegend" class="chart-legend"></div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="list"></i>
                    <span>مقایسه لیست‌ها</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="listsComparisonContainer"></div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="calendar"></i>
                    <span>تقویم حرارتی امسال</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="heatmapContainer" class="heatmap-container"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- ==================== TAB: ACHIEVEMENTS ==================== -->
          <div id="achievementsTab" class="tab-content">
            <div id="achievementsSkeleton" style="display: none;">
              <div class="card">
                <div class="card-header">
                  <div class="skeleton-line" style="width: 120px; height: 24px;"></div>
                </div>
                <div class="card-body">
                  <div class="skeleton-ach-card" style="margin-bottom: 1rem;"></div>
                  <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <div class="skeleton-ach-card" style="height: 120px;"></div>
                    <div class="skeleton-ach-card" style="height: 120px;"></div>
                    <div class="skeleton-ach-card" style="height: 120px;"></div>
                  </div>
                  <div class="skeleton-ach-card"></div>
                  <div class="skeleton-ach-card"></div>
                </div>
              </div>
            </div>

            <div id="achievementsContent" style="display: none;">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <i data-lucide="trophy"></i>
                    <span>دستاوردهای من</span>
                  </div>
                  <button id="exportAchievementsBtn" class="btn btn-outline btn-sm">
                    <i data-lucide="file-text"></i>
                    <span>خروجی PDF</span>
                  </button>
                </div>
                <div class="card-body">
                  <div id="achievementsGrid"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- ==================== TAB: DANGER ==================== -->
          <div id="dangerTab" class="tab-content">
            <div id="dangerSkeleton" style="display: none;">
              <div class="card">
                <div class="card-header">
                  <div class="skeleton-line" style="width: 100px; height: 24px;"></div>
                </div>
                <div class="card-body">
                  <div class="skeleton-row" style="height: 120px;"></div>
                  <div class="skeleton-row" style="height: 120px;"></div>
                </div>
              </div>
            </div>

            <div id="dangerContent" style="display: none;">
              <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #dc262608 0%, #dc262615 100%); border-bottom-color: #dc262630;">
                  <div class="card-title" style="color: var(--destructive);">
                    <i data-lucide="alert-triangle"></i>
                    <span>منطقه خطر</span>
                  </div>
                </div>
                <div class="card-body">
                  <div id="dangerAlert" class="alert"></div>
                  <div class="danger-zone-container">
                    <div class="danger-card warning">
                      <div class="danger-card-content">
                        <div class="danger-card-icon">
                          <i data-lucide="pause-circle"></i>
                        </div>
                        <div class="danger-card-info">
                          <div class="danger-card-title">
                            <i data-lucide="info" style="width:16px;height:16px;"></i>
                            <span>غیرفعال کردن حساب (فریز موقت)</span>
                          </div>
                          <p class="danger-card-description">
                            با غیرفعال کردن حساب، دسترسی شما به سیستم به‌طور موقت قطع می‌شود و نمی‌توانید وارد شوید. تمام داده‌های شما حفظ می‌شوند و در صورت نیاز می‌توانید با ادمین برای فعال‌سازی مجدد تماس بگیرید.
                          </p>
                          <button id="deactivateAccountBtn" class="btn btn-warning">
                            <i data-lucide="pause-circle"></i>
                            <span>غیرفعال کردن حساب</span>
                          </button>
                        </div>
                      </div>
                    </div>

                    <div class="danger-card destructive">
                      <div class="danger-card-content">
                        <div class="danger-card-icon">
                          <i data-lucide="trash-2"></i>
                        </div>
                        <div class="danger-card-info">
                          <div class="danger-card-title">
                            <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
                            <span>حذف دائمی حساب کاربری</span>
                          </div>
                          <p class="danger-card-description">
                            با حذف حساب کاربری، تمام لیست‌ها، فیلم‌ها، سریال‌ها، نظرات و امتیازات شما برای همیشه حذف می‌شوند. <strong style="color: var(--destructive);">این عمل غیرقابل بازگشت است.</strong>
                          </p>
                          <button id="deleteAccountBtn" class="btn btn-danger">
                            <i data-lucide="trash-2"></i>
                            <span>حذف حساب کاربری</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- ==================== FOOTER ==================== -->
    <footer class="site-footer">
      <div class="container">
        <div class="footer-content">
          <span class="footer-text">
            <strong style="color: var(--primary);">MAHDYAR SHAHPARI - 2026</strong>
            <span class="footer-dot"></span>
            © <?php echo date('Y'); ?> MovieManager
          </span>
          <a href="https://pay.mhdr-shpr.ir" target="_blank" rel="noopener noreferrer" class="footer-donate-btn" id="donateBtn">
            BUY ME A COFFEE
            <i data-lucide="heart"></i>
          </a>
        </div>
      </div>
    </footer>

    <!-- ==================== MODAL: SEARCH USER ==================== -->
    <div id="searchUserModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>
            <i data-lucide="search"></i>
            <span>جستجوی کاربران</span>
          </h3>
          <button class="btn-icon close-search-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="searchUserAlert" class="alert"></div>
          <div class="form-group">
            <label class="form-label">نام کاربری یا نام نمایشی</label>
            <div style="position: relative;">
              <input type="text" id="searchUsername" class="form-input" placeholder="حداقل ۲ کاراکتر وارد کنید" style="padding-right: 2.75rem;">
              <i data-lucide="search" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted-foreground); width: 18px; height: 18px; pointer-events: none;"></i>
            </div>
            <small style="color: var(--muted-foreground); font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem;">
              <i data-lucide="info" style="width:14px;height:14px;"></i>
              فقط کاربرانی نمایش داده می‌شوند که پروفایل عمومی دارند
            </small>
          </div>
          <div id="searchUserResults"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-search-modal">بستن</button>
          <button id="searchUserSubmitBtn" class="btn btn-primary">
            <i data-lucide="search"></i>
            <span>جستجو</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: USER PROFILE ==================== -->
    <div id="userProfileModal" class="modal">
      <div class="modal-content large">
        <div class="modal-header">
          <h3 id="userProfileTitle">
            <i data-lucide="user"></i>
            <span>پروفایل کاربر</span>
          </h3>
          <button class="btn-icon close-profile-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body" id="userProfileBody">
          <div class="loading-center">
            <div class="loading"></div>
            <p>در حال بارگذاری...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-profile-modal">بستن</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: DELETE ACCOUNT ==================== -->
    <div id="deleteModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 style="color: var(--destructive);">
            <i data-lucide="alert-triangle"></i>
            <span>هشدار! حذف حساب کاربری</span>
          </h3>
          <button class="btn-icon close-delete-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div style="background: #ef444415; border: 1px solid #ef444440; border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
              <i data-lucide="alert-circle" style="width: 24px; height: 24px; color: var(--destructive); flex-shrink: 0;"></i>
              <div style="font-size: 0.85rem; line-height: 1.6;">
                <strong style="display: block; margin-bottom: 0.35rem; color: var(--destructive);">آیا از حذف حساب کاربری خود مطمئن هستید؟</strong>
                این عمل غیرقابل بازگشت است و تمام لیست‌ها، فیلم‌ها، سریال‌ها، نظرات و امتیازات شما برای همیشه حذف خواهند شد.
              </div>
            </div>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">برای تایید، عبارت <code style="background: var(--secondary); padding: 0.1rem 0.4rem; border-radius: 6px; color: var(--destructive); font-weight: 700;">DELETE</code> را وارد کنید</label>
            <input type="text" id="confirmDelete" class="form-input" placeholder="DELETE" style="text-align: center; letter-spacing: 3px; font-family: monospace; font-weight: 700; font-size: 1rem;">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-delete-modal">انصراف</button>
          <button id="confirmDeleteBtn" class="btn btn-danger">
            <i data-lucide="trash-2"></i>
            <span>تایید و حذف</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: DEACTIVATE ACCOUNT ==================== -->
    <div id="deactivateModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 style="color: var(--warning);">
            <i data-lucide="alert-triangle"></i>
            <span>غیرفعال کردن حساب</span>
          </h3>
          <button class="btn-icon close-deactivate-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div style="background: #f59e0b15; border: 1px solid #f59e0b40; border-radius: 12px; padding: 1rem;">
            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
              <i data-lucide="alert-circle" style="width: 24px; height: 24px; color: var(--warning); flex-shrink: 0;"></i>
              <div style="font-size: 0.85rem; line-height: 1.6;">
                <strong style="display: block; margin-bottom: 0.35rem; color: var(--warning);">آیا از غیرفعال کردن حساب کاربری خود مطمئن هستید؟</strong>
                پس از غیرفعال سازی، نمی‌توانید وارد شوید. تمام داده‌های شما حفظ می‌شوند و برای فعال‌سازی مجدد باید با ادمین تماس بگیرید.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-deactivate-modal">انصراف</button>
          <button id="confirmDeactivateBtn" class="btn btn-warning">
            <i data-lucide="pause-circle"></i>
            <span>تایید و غیرفعال کردن</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== TOAST CONTAINER ==================== -->
    <div id="toastContainer" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem; pointer-events: none;"></div>

<script>
  /* ============================================================
   SECTION 1: STATE, THEME & TOAST
   ============================================================ */
let currentAchievementIndex = 0;
let achievementCategories = [];
let currentStageIndices = {};
let specialSlideInterval = null;
let currentSpecialSlide = 0;

// ===== ICON REFRESH HELPER =====
function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        try {
            window.lucide.createIcons();
        } catch (e) {
            console.warn('Lucide refresh failed:', e);
        }
    }
}

// ===== THEME =====
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
   TOAST NOTIFICATION
   ============================================================ */
function showToast(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        warning: 'alert-circle',
        success: 'check-circle',
        error: 'x-circle',
        info: 'info'
    };

    const bgColors = {
        warning: 'rgba(245, 158, 11, 0.95)',
        success: 'rgba(16, 185, 129, 0.95)',
        error: 'rgba(220, 38, 38, 0.95)',
        info: 'rgba(8, 145, 178, 0.95)'
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        min-width: 280px;
        max-width: 500px;
        pointer-events: auto;
        position: relative;
        background: ${bgColors[type] || bgColors.info};
        animation: toastIn 0.3s ease;
    `;

    toast.innerHTML = `
        <span style="display:inline-flex;"><i data-lucide="${icons[type] || icons.info}" style="width:22px;height:22px;"></i></span>
        <span style="flex: 1;">${message}</span>
        <span onclick="this.parentElement.remove()" style="cursor: pointer; opacity: 0.7; font-size: 1rem;">✕</span>
    `;

    if (!document.getElementById('toastKeyframes')) {
        const style = document.createElement('style');
        style.id = 'toastKeyframes';
        style.textContent = `
            @keyframes toastIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes toastOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
        document.head.appendChild(style);
    }

    container.appendChild(toast);
    refreshIcons();

    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration - 300);
}

/* ============================================================
   SECTION 2: HELPERS
   ============================================================ */

// ===== ALERT =====
function showAlert(elementId, message, type) {
    const alertDiv = document.getElementById(elementId);
    if (!alertDiv) return;

    const icons = {
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-circle'
    };

    alertDiv.innerHTML = `<i data-lucide="${icons[type] || 'info'}" style="width:20px;height:20px;"></i><span>${message}</span>`;
    alertDiv.className = `alert alert-${type} show`;
    refreshIcons();
    setTimeout(() => alertDiv.classList.remove('show'), 4000);
}

// ===== ESCAPE HTML =====
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[m]);
}

// ===== PWA OFFLINE STORAGE & API CALL =====
const USER_READ_ACTIONS = [
    'get_user_stats',
    'get_watch_stats',
    'get_genre_stats',
    'get_lists_comparison',
    'get_current_year_heatmap',
    'get_user_achievements',
    'get_achievements',
    'get_public_profile_status'
];

function getUserPwaCacheKey(action, data = {}) {
    if (USER_READ_ACTIONS.includes(action)) {
        return 'pwa_cached_' + action;
    }
    return null;
}

function getUserPwaCachedData(action, data = {}) {
    const key = getUserPwaCacheKey(action, data);
    if (!key) return null;
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    } catch (e) {
        return null;
    }
}

function setUserPwaCachedData(action, data = {}, resData) {
    const key = getUserPwaCacheKey(action, data);
    if (!key || !resData) return;
    try {
        localStorage.setItem(key, JSON.stringify(resData));
    } catch (e) {}
}

function updatePwaOnlineStatus() {
    const banner = document.getElementById('pwaOfflineBanner');
    const isOffline = !navigator.onLine;
    if (isOffline) {
        if (banner) banner.style.display = 'flex';
        document.body.classList.add('is-offline');
    } else {
        if (banner) banner.style.display = 'none';
        document.body.classList.remove('is-offline');
    }
    refreshIcons();
}

window.addEventListener('online', () => {
    updatePwaOnlineStatus();
    showToast('ارتباط با اینترنت برقرار شد. در حال به‌روزرسانی آمار...', 'success');
    loadAllStats();
    loadAchievements();
});

window.addEventListener('offline', () => {
    updatePwaOnlineStatus();
    showToast('ارتباط با اینترنت قطع شد. آمار و دستاوردها در حالت فقط‌خواندنی نمایش داده می‌شوند.', 'warning');
});

async function apiCall(action, data = {}) {
    const isOnline = navigator.onLine;

    // گارد محافظتی برای عملیات تغییر داده در حالت آفلاین
    if (!isOnline && !USER_READ_ACTIONS.includes(action)) {
        showToast('در حالت آفلاین امکان انجام این تغییرات وجود ندارد.', 'warning');
        return { ok: false, message: 'در حالت آفلاین امکان ثبت تغییرات وجود ندارد.', offline: true };
    }

    // بازیابی فوری از حافظه کش برای اکشن‌های مجاز در حالت آفلاین
    if (!isOnline && USER_READ_ACTIONS.includes(action)) {
        const cached = getUserPwaCachedData(action, data);
        if (cached !== null) {
            return { ok: true, data: cached, from_cache: true };
        }
        return { ok: false, message: 'اطلاعات در حافظه آفلاین موجود نیست.', offline: true };
    }

    const formData = new FormData();
    formData.append('action', action);
    for (let key in data) formData.append(key, data[key]);
    try {
        const response = await fetch('api/api.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        if (!response.ok) {
            let errorMsg = `HTTP ${response.status}`;
            let rawBody = '';
            try {
                rawBody = await response.text();
                try {
                    const errData = JSON.parse(rawBody);
                    if (errData && errData.message) errorMsg = errData.message;
                } catch (pe) {
                    if (rawBody && rawBody.trim()) errorMsg = rawBody.trim();
                }
            } catch (e) {}
            const err = new Error(errorMsg);
            err.status = response.status;
            err.rawBody = rawBody;
            err.action = action;
            throw err;
        }
        const result = await response.json();

        // ذخیره خودکار داده‌ها در حافظه کش PWA
        if (result.ok && result.data && USER_READ_ACTIONS.includes(action)) {
            setUserPwaCachedData(action, data, result.data);
        }

        return result;
    } catch (error) {
        if (USER_READ_ACTIONS.includes(action)) {
            const cached = getUserPwaCachedData(action, data);
            if (cached !== null) {
                return { ok: true, data: cached, from_cache: true };
            }
        }
        console.error('API Error:', error);
        return { ok: false, message: error.message };
    }
}

// ===== FORMAT DATE =====
function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    return `${d.getFullYear()}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getDate().toString().padStart(2, '0')}`;
}


/* ============================================================
   SECTION 3: CHARTS
   ============================================================ */

// ===== COLOR PALETTE =====
function generateChartColors(count) {
    const palette = [
        '#0891b2', '#06b6d4', '#0e7490', '#22d3ee',
        '#8b5cf6', '#a78bfa', '#ec4899', '#f472b6',
        '#f59e0b', '#fbbf24', '#10b981', '#34d399',
        '#3b82f6', '#60a5fa', '#ef4444', '#f87171'
    ];
    const colors = [];
    for (let i = 0; i < count; i++) {
        colors.push(palette[i % palette.length]);
    }
    return colors;
}

// ===== TOOLTIP =====
let tooltipDiv = null;
function showTooltip(event, text) {
    if (!tooltipDiv) {
        tooltipDiv = document.createElement('div');
        tooltipDiv.style.cssText = `
            position: fixed;
            background: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
            pointer-events: none;
            z-index: 1001;
            white-space: nowrap;
            backdrop-filter: blur(4px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        `;
        document.body.appendChild(tooltipDiv);
    }
    tooltipDiv.innerHTML = text;
    tooltipDiv.style.left = (event.clientX + 15) + 'px';
    tooltipDiv.style.top = (event.clientY - 40) + 'px';
    tooltipDiv.style.display = 'block';
}
function hideTooltip() {
    if (tooltipDiv) tooltipDiv.style.display = 'none';
}

// ===== PIE CHART =====
function drawPieChart(containerId, data, labels) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const total = data.reduce((a, b) => a + b, 0);
    if (total === 0 || data.length === 0) {
        container.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--muted-foreground);">داده‌ای برای نمایش وجود ندارد</div>';
        return;
    }

    const MIN_PERCENTAGE = 2;
    const otherData = [], otherLabels = [], mainData = [], mainLabels = [];

    for (let i = 0; i < data.length; i++) {
        const percentage = (data[i] / total) * 100;
        if (percentage < MIN_PERCENTAGE) {
            otherData.push(data[i]);
            otherLabels.push(labels[i]);
        } else {
            mainData.push(data[i]);
            mainLabels.push(labels[i]);
        }
    }

    let finalData = [...mainData];
    let finalLabels = [...mainLabels];
    if (otherData.length > 0) {
        finalData.push(otherData.reduce((a, b) => a + b, 0));
        finalLabels.push('سایر ژانرها');
    }

    const colors = generateChartColors(finalData.length);
    let currentAngle = 0;
    let svgHtml = `<svg viewBox="-110 -110 220 220" class="pie-chart-svg" style="cursor:pointer;">`;

    for (let i = 0; i < finalData.length; i++) {
        const percentage = finalData[i] / total;
        const angle = percentage * 360;
        const startAngle = currentAngle;
        const endAngle = currentAngle + angle;

        const startRad = (startAngle - 90) * Math.PI / 180;
        const endRad = (endAngle - 90) * Math.PI / 180;

        const x1 = 100 * Math.cos(startRad);
        const y1 = 100 * Math.sin(startRad);
        const x2 = 100 * Math.cos(endRad);
        const y2 = 100 * Math.sin(endRad);
        const largeArcFlag = angle > 180 ? 1 : 0;

        let fillColor = finalLabels[i] === 'سایر ژانرها' ? '#9ca3af' : colors[i % colors.length];
        const tooltipText = `${escapeHtml(finalLabels[i])}: ${finalData[i]} (${(percentage * 100).toFixed(1)}%)`;

        svgHtml += `<path 
            d="M 0 0 L ${x1} ${y1} A 100 100 0 ${largeArcFlag} 1 ${x2} ${y2} Z" 
            fill="${fillColor}" 
            stroke="var(--card)" 
            stroke-width="2"
            style="transition: filter 0.2s; cursor: pointer;"
            onmouseover="showTooltip(event, '${tooltipText.replace(/'/g, '&#39;')}')"
            onmouseout="hideTooltip()"
        />`;

        currentAngle += angle;
    }
    svgHtml += `</svg>`;
    container.innerHTML = svgHtml;

    // Legend
    const legendContainer = document.getElementById('genreLegend');
    if (legendContainer) {
        let legendHtml = '';
        for (let i = 0; i < finalData.length; i++) {
            const color = finalLabels[i] === 'سایر ژانرها' ? '#9ca3af' : colors[i % colors.length];
            legendHtml += `
                <div class="legend-item">
                    <div class="legend-color" style="background: ${color};"></div>
                    <span>${escapeHtml(finalLabels[i])} (${finalData[i]})</span>
                </div>
            `;
        }
        legendContainer.innerHTML = legendHtml;
    }
}

// ===== DONUT CHART =====
function drawDonutChart(containerId, percentage) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const radius = 75;
    const strokeWidth = 20;
    const circumference = 2 * Math.PI * radius;
    const dashArray = (percentage / 100) * circumference;

    container.innerHTML = `
        <div class="donut-chart-container">
            <svg viewBox="0 0 200 200" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                <defs>
                    <linearGradient id="donutGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="var(--primary)" />
                        <stop offset="100%" stop-color="var(--primary-light)" />
                    </linearGradient>
                </defs>
                <circle cx="100" cy="100" r="${radius}" fill="none" stroke="var(--secondary)" stroke-width="${strokeWidth}" />
                <circle 
                    cx="100" cy="100" r="${radius}" 
                    fill="none" 
                    stroke="url(#donutGradient)" 
                    stroke-width="${strokeWidth}" 
                    stroke-dasharray="${dashArray} ${circumference}" 
                    stroke-linecap="round" 
                    style="transition: stroke-dasharray 0.8s cubic-bezier(0.4, 0, 0.2, 1);"
                />
            </svg>
            <div class="donut-center-text">
                <div style="font-size: 2rem; font-weight: 700; color: var(--primary); line-height: 1;">${percentage}%</div>
                <div style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.35rem;">مشاهده</div>
            </div>
        </div>
    `;
}

// ===== BAR CHART =====
function drawBarChart(containerId, data, labels) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const values = labels.map(label => data[parseInt(label)] || data[label] || 0);
    const maxValue = Math.max(...values, 1);

    if (values.every(v => v === 0)) {
        container.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--muted-foreground);">داده‌ای برای نمایش وجود ندارد</div>';
        return;
    }

    let html = '<div class="bar-chart">';
    labels.forEach((label, i) => {
        const value = values[i];
        const widthPercent = (value / maxValue) * 100;
        html += `
            <div class="bar-item">
                <div class="bar-label">
                    <span>${escapeHtml(String(label))}</span>
                    <span style="color: var(--primary); font-weight: 700;">${value}</span>
                </div>
                <div class="bar-container">
                    <div class="bar-fill" style="width: ${widthPercent}%;">
                        ${value > 0 && widthPercent > 15 ? value : ''}
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

// ===== STACKED BAR CHART (ماهانه) =====
let monthTooltipDiv = null;
function showMonthTooltip(event, monthName, value, percent) {
    if (!monthTooltipDiv) {
        monthTooltipDiv = document.createElement('div');
        monthTooltipDiv.style.cssText = `
            position: fixed;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            pointer-events: none;
            z-index: 1001;
            white-space: nowrap;
            backdrop-filter: blur(8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255,255,255,0.1);
        `;
        document.body.appendChild(monthTooltipDiv);
    }
    monthTooltipDiv.innerHTML = `<strong>${monthName}</strong><br>${value} تماشا (${percent}%)`;
    monthTooltipDiv.style.left = (event.clientX + 15) + 'px';
    monthTooltipDiv.style.top = (event.clientY - 50) + 'px';
    monthTooltipDiv.style.display = 'block';
}
function hideMonthTooltip() {
    if (monthTooltipDiv) monthTooltipDiv.style.display = 'none';
}

function drawStackedBarChart(containerId, monthlyData, monthNames) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let total = 0;
    for (let i = 1; i <= 12; i++) total += monthlyData[i] || 0;

    if (total === 0) {
        container.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--muted-foreground);">داده‌ای برای نمایش وجود ندارد</div>';
        return;
    }

    const seasonalColors = {
        spring: ['#22d3ee', '#06b6d4', '#0891b2'],
        summer: ['#fbbf24', '#f59e0b', '#d97706'],
        autumn: ['#a78bfa', '#8b5cf6', '#7c3aed'],
        winter: ['#60a5fa', '#3b82f6', '#2563eb']
    };

    const monthColors = {
        0: seasonalColors.spring[0], 1: seasonalColors.spring[1], 2: seasonalColors.spring[2],
        3: seasonalColors.summer[0], 4: seasonalColors.summer[1], 5: seasonalColors.summer[2],
        6: seasonalColors.autumn[0], 7: seasonalColors.autumn[1], 8: seasonalColors.autumn[2],
        9: seasonalColors.winter[0], 10: seasonalColors.winter[1], 11: seasonalColors.winter[2]
    };

    let html = `
        <div style="margin: 1rem 0;">
            <div style="display: flex; height: 52px; width: 100%; border-radius: 14px; overflow: hidden; direction: ltr; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
    `;

    for (let i = 0; i < 12; i++) {
        const value = monthlyData[i + 1] || 0;
        if (value > 0) {
            const percent = (value / total) * 100;
            html += `
                <div 
                    style="width: ${percent}%; background: ${monthColors[i]}; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.25s ease;"
                    onmouseover="this.style.filter='brightness(1.15)'; showMonthTooltip(event, '${monthNames[i]}', ${value}, ${percent.toFixed(1)})"
                    onmouseout="this.style.filter=''; hideMonthTooltip()"
                >
                    ${percent > 6 ? value : ''}
                </div>
            `;
        }
    }
    html += `</div>`;

    html += `<div style="display: flex; justify-content: center; gap: 1.5rem; margin-top: 1.25rem; flex-wrap: wrap;">`;
    ['بهار', 'تابستان', 'پاییز', 'زمستان'].forEach((season, si) => {
        const key = ['spring', 'summer', 'autumn', 'winter'][si];
        html += `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <div style="display: flex; gap: 2px;">
                    ${seasonalColors[key].map((c, ci) => `
                        <div style="width: 18px; height: 14px; background: ${c}; border-radius: ${
                            ci === 0 ? '4px 0 0 4px' : ci === 2 ? '0 4px 4px 0' : '0'
                        };"></div>
                    `).join('')}
                </div>
                <span style="font-size: 0.7rem; color: var(--muted-foreground); font-weight: 500;">${season}</span>
            </div>
        `;
    });
    html += `</div>`;

    html += `<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.6rem; margin-top: 1.25rem; padding: 1rem; background: var(--secondary); border-radius: 14px;">`;

    for (let i = 0; i < 12; i++) {
        const value = monthlyData[i + 1] || 0;
        const percent = (value / total) * 100;
        html += `
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.6rem; background: var(--card); border-radius: 10px; border-right: 3px solid ${monthColors[i]};">
                <div style="width: 8px; height: 8px; background: ${monthColors[i]}; border-radius: 50%; flex-shrink: 0;"></div>
                <div style="flex: 1; font-size: 0.7rem; min-width: 0;">
                    <div style="font-weight: 600;">${monthNames[i]}</div>
                    <div style="display: flex; justify-content: space-between; margin-top: 2px;">
                        <span style="color: var(--primary); font-weight: 700;">${value}</span>
                        <span style="color: var(--muted-foreground);">${percent.toFixed(1)}%</span>
                    </div>
                </div>
            </div>
        `;
    }
    html += `</div>`;

    html += `
        <div style="text-align: center; margin-top: 1rem; padding: 0.65rem; background: var(--accent); border-radius: 12px; border: 1px solid var(--border);">
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary); display: inline-flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="check-circle" style="width:18px;height:18px;"></i>
                مجموع کل تماشاها: ${total}
            </span>
        </div>
    `;

    container.innerHTML = html;
    refreshIcons();
}
/* ============================================================
   SECTION 7: TROPHY ICONS MAPPING (static/trophies/)
   ============================================================ */
const TROPHY_ICONS = {
    // ===== فیلم‌بین =====
    'movie_first':   'icons8-education-a-plus-grade-sign-on-school-test-sheet-100.png',
    'movie_10':      'icons8-sprout-growing-from-orange-bowl-symbolizing-healthy-organic-food-choices-100.png',
    'movie_25':      'icons8-plant-pot-leaf-and-dollar-symbol-representing-growing-investment-profits-100.png',
    'movie_50':      'icons8-tree-with-bright-green-canopy-and-orange-trunk-shape-100.png',
    'movie_100':     'icons8-mountain-peak-summit-with-snow-cap-and-orange-achievement-flag-100.png',
    'movie_200':     'icons8-sterling-pound-money-symbol-centered-on-round-plate-surface-100.png',

    // ===== سریال‌بین =====
    'series_first':  'icons8-running-shoe-minimalist-profile-with-purple-cushion-and-orange-outsole-100.png',
    'series_5':      'icons8-truck-toy-with-yellow-container-and-gray-wheels-for-kids-100.png',
    'series_10':     'icons8-delivery-truck-icon-with-motion-trails-for-shipping-100.png',
    'series_20':     'icons8-navigation-blue-arrow-symbols-indicating-movement-in-four-directions-100.png',
    'series_50':     'icons8-mountain-peak-summit-with-snow-cap-and-orange-achievement-flag-100.png',

    // ===== لیست‌ساز =====
    'list_first':    'icons8-hourglass-timer-beside-document-list-symbolizing-pending-order-history-status-100.png',
    'list_5':        'icons8-floppy-disk-save-icon-with-blue-square-shape-and-notch-100.png',
    'list_10':       'icons8-office-building-facade-with-windows-and-staff-profile-icon-nearby-100.png',
    'list_20':       'icons8-minimalist-factory-building-icon-with-chimney-and-rounded-windows-100.png',

    // ===== منتقد (کامنت) =====
    'comment_first': 'icons8-message-bubble-icon-with-shiny-stars-indicating-ai-assistant-chat-activity-100.png',
    'comment_10':    'icons8-exclamation-mark-inside-pink-cloud-shape-symbolizing-important-alert-notification-100.png',
    'comment_50':    'icons8-microphone-symbol-made-from-purple-capsule-and-blue-curved-shape-100.png',

    // ===== امتیاز دهنده =====
    'rating_first':  'icons8-sterling-pound-money-symbol-centered-on-round-plate-surface-100.png',
    'rating_10':     'icons8-discount-badge-with-white-percent-symbol-on-orange-flower-shape-100.png',
    'rating_50':     'icons8-mountain-peak-summit-with-snow-cap-and-orange-achievement-flag-100.png',

    // ===== دستاوردهای ویژه =====
    'completionist': 'icons8-data-protection-shield-icon-with-secure-lock-emphasizing-online-safety-100.png',
    'collector':     'icons8-gallery-app-icon-with-green-mountain-and-yellow-sun-symbol-100.png',
    'social':        'icons8-users-group-symbol-with-three-colorful-avatars-arranged-diagonally-100.png',

    // ===== پیش‌فرض =====
    'default':       'icons8-mountain-peak-summit-with-snow-cap-and-orange-achievement-flag-100.png'
};

const TROPHY_BASE_PATH = 'static/trophies/';

function trophyIcon(key, size = '64px', extraStyle = '') {
    const file = TROPHY_ICONS[key] || TROPHY_ICONS['default'];
    const url = TROPHY_BASE_PATH + file;

    return `<img 
        src="${url}" 
        alt="${key}" 
        loading="lazy"
        style="width: ${size}; height: ${size}; object-fit: contain; display: inline-block; ${extraStyle}"
        onerror="this.onerror=null; this.src='${TROPHY_BASE_PATH}${TROPHY_ICONS['default']}';"
    >`;
}


/* ============================================================
   SECTION 8: ACHIEVEMENTS DATA
   ============================================================ */
const achievementsData = {
    movie_watch: {
        title: 'فیلم‌بین',
        icon: 'movie_first',
        description: 'تماشای فیلم',
        stages: [
            { threshold: 1,   title: 'شروع سینمایی',      icon: 'movie_first', desc: 'اولین فیلم رو تماشا کردی!' },
            { threshold: 10,  title: 'فیلم‌بین تازه‌کار',  icon: 'movie_10',    desc: '۱۰ فیلم رو تماشا کردی' },
            { threshold: 25,  title: 'عاشق سینما',        icon: 'movie_25',    desc: '۲۵ فیلم تماشا شده' },
            { threshold: 50,  title: 'کارشناس سینما',     icon: 'movie_50',    desc: '۵۰ فیلم تماشا شده' },
            { threshold: 100, title: 'استاد سینما',       icon: 'movie_100',   desc: '۱۰۰ فیلم تماشا شده' },
            { threshold: 200, title: 'افسانه سینما',      icon: 'movie_200',   desc: '۲۰۰ فیلم تماشا شده' }
        ]
    },
    series_watch: {
        title: 'سریال‌بین',
        icon: 'series_first',
        description: 'تماشای سریال',
        stages: [
            { threshold: 1,  title: 'اولین سریال',          icon: 'series_first', desc: 'اولین سریال رو شروع کردی' },
            { threshold: 5,  title: 'سریال‌بین مبتدی',      icon: 'series_5',     desc: '۵ سریال رو دنبال کردی' },
            { threshold: 10, title: 'سریال‌بین حرفه‌ای',    icon: 'series_10',    desc: '۱۰ سریال رو تماشا کردی' },
            { threshold: 20, title: 'سریال‌خور',            icon: 'series_20',    desc: '۲۰ سریال کامل کردی' },
            { threshold: 50, title: 'استاد سریال',          icon: 'series_50',    desc: '۵۰ سریال! باورنکردنی!' }
        ]
    },
    list_create: {
        title: 'لیست‌ساز',
        icon: 'list_first',
        description: 'ساخت لیست',
        stages: [
            { threshold: 1,  title: 'اولین لیست',      icon: 'list_first', desc: 'اولین لیست رو ساختی' },
            { threshold: 5,  title: 'لیست‌ساز',        icon: 'list_5',     desc: '۵ لیست ساخته شده' },
            { threshold: 10, title: 'سازنده حرفه‌ای',  icon: 'list_10',    desc: '۱۰ لیست ساخته شده' },
            { threshold: 20, title: 'کتابخانه‌دار',    icon: 'list_20',    desc: '۲۰ لیست!' }
        ]
    },
    comment_master: {
        title: 'منتقد',
        icon: 'comment_first',
        description: 'نوشتن کامنت',
        stages: [
            { threshold: 1,  title: 'اولین نظر',   icon: 'comment_first', desc: 'اولین کامنت رو نوشتی' },
            { threshold: 10, title: 'نظر‌دهنده',    icon: 'comment_10',    desc: '۱۰ کامنت ثبت شده' },
            { threshold: 50, title: 'منتقد',        icon: 'comment_50',    desc: '۵۰ کامنت!' }
        ]
    },
    rating_master: {
        title: 'امتیاز دهنده',
        icon: 'rating_first',
        description: 'امتیاز دادن',
        stages: [
            { threshold: 1,  title: 'اولین امتیاز',   icon: 'rating_first', desc: 'اولین امتیاز رو دادی' },
            { threshold: 10, title: 'ارزیاب',         icon: 'rating_10',    desc: '۱۰ امتیاز ثبت شده' },
            { threshold: 50, title: 'منتقد حرفه‌ای',  icon: 'rating_50',    desc: '۵۰ امتیاز!' }
        ]
    }
};

// ===== دستاوردهای ویژه (تعریف کامل با شرط و راهنمای کسب) =====
const specialAchievementsData = {
    'completionist': {
        title: 'تکمیل‌کننده نهایی',
        desc: 'یک لیست رو ۱۰۰٪ تکمیل کن',
        color: 'purple',
        badge: '✨ خاص',
        icon: 'completionist'
    },
    'collector': {
        title: 'کالکشن‌فن!',
        desc: 'یه کالکشن فیلم بساز (مجموعه)',
        color: 'gold',
        badge: '🎴 نادر',
        icon: 'collector'
    },
    'social': {
        title: 'اجتماعی',
        desc: 'پروفایلت رو عمومی کن',
        color: 'green',
        badge: '🌟 کمیاب',
        icon: 'social'
    }
};


/* ============================================================
   SECTION 9: ACHIEVEMENT CALCULATION
   ============================================================ */
function getCategoryCurrentValue(catKey, userStats) {
    switch (catKey) {
        case 'movie_watch':    return userStats.movies_watched || 0;
        case 'series_watch':   return userStats.series_watched || 0;
        case 'list_create':    return userStats.lists_created || 0;
        case 'comment_master': return userStats.comments_count || 0;
        case 'rating_master':  return userStats.ratings_count || 0;
        default: return 0;
    }
}

function getCurrentStage(stages, currentValue) {
    let currentStage = -1;
    for (let i = stages.length - 1; i >= 0; i--) {
        if (currentValue >= stages[i].threshold) {
            currentStage = i;
            break;
        }
    }
    return currentStage;
}

function getProgressToNext(stages, currentStage, currentValue) {
    if (currentStage === -1) {
        return Math.min((currentValue / stages[0].threshold) * 100, 100);
    }
    if (currentStage >= stages.length - 1) {
        return 100;
    }
    const current = stages[currentStage].threshold;
    const next = stages[currentStage + 1].threshold;
    return ((currentValue - current) / (next - current)) * 100;
}

function calculateOverallProgress(userStats) {
    let totalStages = 0;
    let earnedStages = 0;

    for (const catKey in achievementsData) {
        const stages = achievementsData[catKey].stages;
        totalStages += stages.length;
        const currentValue = getCategoryCurrentValue(catKey, userStats);

        for (const stage of stages) {
            if (currentValue >= stage.threshold) earnedStages++;
        }
    }

    return {
        total: totalStages,
        earned: earnedStages,
        percent: totalStages > 0 ? (earnedStages / totalStages) * 100 : 0
    };
}


/* ============================================================
   SECTION 10: LOAD ACHIEVEMENTS (Main Render)
   ============================================================ */
async function loadAchievements() {
    const container = document.getElementById('achievementsGrid');
    if (!container) return;

    try {
        const statsResult = await apiCall('get_user_stats');

        if (!statsResult.ok) {
            container.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--destructive);">خطا در دریافت آمار کاربر</div>';
            return;
        }

        const userStats = {
            movies_watched:   statsResult.data.watched_movies || 0,
            series_watched:   statsResult.data.watched_series || 0,
            lists_created:    statsResult.data.lists || 0,
            comments_count:   statsResult.data.total_comments || 0,
            ratings_count:    statsResult.data.total_ratings || 0,
            completed_series: statsResult.data.completed_series || 0
        };

        const overall = calculateOverallProgress(userStats);

        let html = `
            <!-- هدر پیشرفت کلی -->
            <div class="achievements-header-card">
                <div class="achievements-header-content">
                    <div class="achievements-header-icon">
                        <i data-lucide="trophy"></i>
                    </div>
                    <div class="achievements-header-info">
                        <div class="achievements-header-title">پیشرفت کلی دستاوردها</div>
                        <div class="achievements-header-subtitle">${overall.earned} از ${overall.total} مرحله تکمیل شده</div>
                    </div>
                    <div class="achievements-header-progress">
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: ${overall.percent}%;"></div>
                        </div>
                    </div>
                    <div class="achievements-header-percent">${overall.percent.toFixed(0)}%</div>
                </div>
            </div>

            <!-- دستاوردهای ویژه -->
            <div id="specialAchievementsSection"></div>

            <!-- دسته‌بندی‌های عادی -->
            <div class="achievement-categories-container">
        `;

        achievementCategories = [];
        currentStageIndices = {};
        let catIndex = 0;

        for (const [catKey, catData] of Object.entries(achievementsData)) {
            const stages = catData.stages;
            const currentValue = getCategoryCurrentValue(catKey, userStats);
            const currentStage = getCurrentStage(stages, currentValue);
            const nextStage = currentStage < stages.length - 1 ? stages[currentStage + 1] : null;
            const progressPercent = getProgressToNext(stages, currentStage, currentValue);
            const displayStage = currentStage === -1 ? 0 : currentStage;
            const remainingToNext = nextStage ? nextStage.threshold - currentValue : null;

            currentStageIndices[catKey] = displayStage;
            achievementCategories.push(catKey);

            // در دسکتاپ اصلاً collapsed نمی‌کنیم؛ فقط موبایل
            const isCollapsed = catIndex !== 0 ? 'collapsed' : '';
            const ariaExpanded = catIndex === 0 ? 'true' : 'false';

            html += `
                <div class="achievement-category-card ${isCollapsed}" data-category="${catKey}">
                    <div class="achievement-category-header">
                        <div class="achievement-category-icon-wrap">
                            ${trophyIcon(catData.icon, '36px')}
                        </div>
                        <div class="achievement-category-info">
                            <div class="achievement-category-title">${catData.title}</div>
                            <div class="achievement-category-desc">${catData.description}</div>
                        </div>
                        <div class="achievement-category-progress">
                            <div class="achievement-category-progress-info">
                                <div class="achievement-category-stage-num">مرحله ${displayStage + 1} از ${stages.length}</div>
                                <div class="achievement-category-progress-bar">
                                    <div class="achievement-category-progress-fill" style="width: ${progressPercent}%;"></div>
                                </div>
                            </div>
                            <button class="accordion-toggle-btn" aria-expanded="${ariaExpanded}" aria-label="باز/بسته کردن">
                                <i data-lucide="chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="achievement-category-body">
                        <div class="stage-slider-wrap">
                            <button class="stage-nav-btn stage-prev" data-category="${catKey}" aria-label="قبلی">
                                <i data-lucide="chevron-right"></i>
                            </button>
                            <div class="stage-slider-track">
                                <div class="stage-slider-inner" data-category="${catKey}">
            `;

            for (let i = 0; i < stages.length; i++) {
                const stage = stages[i];
                const isEarned = currentValue >= stage.threshold;
                const isCurrent = i === displayStage;

                let stageCardClass = 'stage-card';
                if (isEarned) stageCardClass += ' earned';
                else stageCardClass += ' locked';
                if (isCurrent) stageCardClass += ' current';

                html += `
                    <div class="${stageCardClass}" data-stage-index="${i}">
                        <div class="stage-card-icon">
                            ${trophyIcon(stage.icon, '100px')}
                        </div>
                        <div class="stage-card-title">${stage.title}</div>
                        <div class="stage-card-desc">${stage.desc}</div>
                        ${isEarned ? `
                            <div class="stage-card-status">
                                <i data-lucide="check-circle" style="width:13px;height:13px;"></i>
                                <span>کسب شده</span>
                            </div>
                        ` : `
                            <div class="stage-card-status">
                                <i data-lucide="lock" style="width:13px;height:13px;"></i>
                                <span>نیاز: ${stage.threshold}</span>
                            </div>
                        `}
                        ${isCurrent && nextStage ? `
                            <div class="stage-card-progress-mini">
                                <div class="stage-progress-bar-bg">
                                    <div class="stage-progress-bar-fill" style="width: ${progressPercent}%;"></div>
                                </div>
                                <div class="stage-remaining">${remainingToNext} تا مرحله بعدی</div>
                            </div>
                        ` : ''}
                    </div>
                `;
            }

            html += `
                                </div>
                            </div>
                            <button class="stage-nav-btn stage-next" data-category="${catKey}" aria-label="بعدی">
                                <i data-lucide="chevron-left"></i>
                            </button>
                        </div>
                        <div class="stage-dots" data-category="${catKey}">
            `;

            for (let i = 0; i < stages.length; i++) {
                html += `<div class="stage-dot ${i === displayStage ? 'active' : ''}" data-stage-index="${i}"></div>`;
            }

            html += `
                        </div>
                    </div>
                </div>
            `;
            catIndex++;
        }

        html += `</div>`;

        container.innerHTML = html;
        refreshIcons();

        // اتصال event listeners
        attachAccordionEvents();
        attachStageSliderEvents();

        // بارگذاری دستاوردهای ویژه
        await loadSpecialAchievements();

    } catch (error) {
        console.error('Error loading achievements:', error);
        container.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--destructive);">خطا در بارگذاری دستاوردها</div>';
    }
}


/* ============================================================
   SECTION 11: ACCORDION & STAGE SLIDER EVENTS
   ============================================================ */
function attachAccordionEvents() {
    document.querySelectorAll('.accordion-toggle-btn').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const card = newBtn.closest('.achievement-category-card');
            if (!card) return;

            const wasCollapsed = card.classList.contains('collapsed');

            // در موبایل، فقط یک آکاردئون باز باشد
            if (window.innerWidth <= 768) {
                document.querySelectorAll('.achievement-category-card').forEach(c => {
                    if (c !== card) c.classList.add('collapsed');
                });
            }

            card.classList.toggle('collapsed', !wasCollapsed);
            newBtn.setAttribute('aria-expanded', wasCollapsed ? 'true' : 'false');
        });
    });
}

// ===== Stage Slider =====
let stageSliderIndices = {};

function attachStageSliderEvents() {
    // مقداردهی اولیه ایندکس اسلایدر
    document.querySelectorAll('.stage-slider-inner').forEach(inner => {
        const catKey = inner.dataset.category;
        if (stageSliderIndices[catKey] === undefined) {
            stageSliderIndices[catKey] = currentStageIndices[catKey] || 0;
        }
    });

    // دکمه‌های قبلی/بعدی
    document.querySelectorAll('.stage-nav-btn').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const catKey = newBtn.dataset.category;
            const direction = newBtn.classList.contains('stage-next') ? 1 : -1;
            navigateStageSlider(catKey, direction);
        });
    });

    // نقطه‌ها
    document.querySelectorAll('.stage-dot').forEach(dot => {
        const newDot = dot.cloneNode(true);
        dot.parentNode.replaceChild(newDot, dot);

        newDot.addEventListener('click', (e) => {
            e.stopPropagation();
            const parent = newDot.closest('.stage-dots');
            if (!parent) return;
            const catKey = parent.dataset.category;
            const index = parseInt(newDot.dataset.stageIndex);
            navigateStageSliderTo(catKey, index);
        });
    });

    // اعمال ایندکس اولیه
    document.querySelectorAll('.stage-slider-inner').forEach(inner => {
        const catKey = inner.dataset.category;
        updateStageSliderPosition(catKey);
    });
}

function navigateStageSlider(catKey, direction) {
    const inner = document.querySelector(`.stage-slider-inner[data-category="${catKey}"]`);
    if (!inner) return;

    const totalStages = inner.querySelectorAll('.stage-card').length;
    if (totalStages === 0) return;

    let currentIndex = stageSliderIndices[catKey] || 0;
    let newIndex = currentIndex + direction;

    if (newIndex < 0) newIndex = 0;
    if (newIndex >= totalStages) newIndex = totalStages - 1;

    if (newIndex === currentIndex) return;

    stageSliderIndices[catKey] = newIndex;
    updateStageSliderPosition(catKey);
}

function navigateStageSliderTo(catKey, index) {
    const inner = document.querySelector(`.stage-slider-inner[data-category="${catKey}"]`);
    if (!inner) return;

    const totalStages = inner.querySelectorAll('.stage-card').length;
    if (index < 0 || index >= totalStages) return;

    stageSliderIndices[catKey] = index;
    updateStageSliderPosition(catKey);
}

function updateStageSliderPosition(catKey) {
    const inner = document.querySelector(`.stage-slider-inner[data-category="${catKey}"]`);
    if (!inner) return;

    const cards = inner.querySelectorAll('.stage-card');
    const totalStages = cards.length;
    if (totalStages === 0) return;

    const currentIndex = stageSliderIndices[catKey] || 0;

    // محاسبه offset با توجه به اندازه کارت
    const isMobile = window.innerWidth <= 768;
    const cardWidth = isMobile ? 100 : 50; // درصد
    const gap = 0.75; // rem
    const offsetPercent = currentIndex * cardWidth;
    const offsetRem = currentIndex * gap;

    inner.style.transform = `translateX(${offsetPercent}%)`;

    // اگه در موبایل تک‌کارتی هستیم، gap رو حذف کنیم
    if (isMobile) {
        inner.style.transform = `translateX(${currentIndex * 100}%)`;
    }

    // آپدیت نقطه‌ها
    const dotsContainer = document.querySelector(`.stage-dots[data-category="${catKey}"]`);
    if (dotsContainer) {
        dotsContainer.querySelectorAll('.stage-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === currentIndex);
        });
    }

    // آپدیت دکمه‌ها
    const prevBtn = document.querySelector(`.stage-prev[data-category="${catKey}"]`);
    const nextBtn = document.querySelector(`.stage-next[data-category="${catKey}"]`);
    if (prevBtn) prevBtn.disabled = currentIndex === 0;
    if (nextBtn) nextBtn.disabled = currentIndex === totalStages - 1;
}


/* ============================================================
   SECTION 12: SPECIAL ACHIEVEMENTS (خاکستری + رنگی)
   ============================================================ */
async function loadSpecialAchievements() {
    const container = document.getElementById('specialAchievementsSection');
    if (!container) return;

    try {
        // دریافت دستاوردهای کاربر از دیتابیس
        const result = await apiCall('get_user_achievements');

        const earnedKeys = new Set();
        const earnedDates = {};

        if (result.ok && result.data && result.data.length > 0) {
            for (const ach of result.data) {
                if (specialAchievementsData[ach.achievement_key]) {
                    earnedKeys.add(ach.achievement_key);
                    earnedDates[ach.achievement_key] = ach.earned_at;
                }
            }
        }

        // ساخت کارت‌ها برای همه (چه کسب‌شده، چه نشده)
        let html = `
            <div class="special-achievements-section">
                <div class="special-achievements-title">
                    <i data-lucide="star"></i>
                    <span>دستاوردهای ویژه</span>
                </div>
                <div class="special-achievements-grid">
        `;

        for (const [key, data] of Object.entries(specialAchievementsData)) {
            const isEarned = earnedKeys.has(key);
            const cardClass = isEarned ? `special-ach-card earned ${data.color}` : `special-ach-card locked`;

            html += `
                <div class="${cardClass}">
                    <div class="special-ach-card-icon">
                        ${trophyIcon(data.icon, '100%')}
                    </div>
                    <div class="special-ach-card-title">${data.title}</div>
                    <div class="special-ach-card-desc">${data.desc}</div>
                    <div class="special-ach-card-badge">
                        ${isEarned 
                            ? `${data.badge} • ${formatDate(earnedDates[key])}` 
                            : '<i data-lucide="lock" style="width:12px;height:12px;"></i> قفل'}
                    </div>
                </div>
            `;
        }

        html += `</div></div>`;
        container.innerHTML = html;
        refreshIcons();

    } catch (error) {
        console.error('Error loading special achievements:', error);
        container.innerHTML = '';
    }
}

/* ============================================================
   SECTION 13: SEARCH USER
   ============================================================ */
const searchUserBtn = document.getElementById('searchUserBtn');
const searchUserModal = document.getElementById('searchUserModal');
const userProfileModal = document.getElementById('userProfileModal');
const searchUsername = document.getElementById('searchUsername');
const searchUserSubmitBtn = document.getElementById('searchUserSubmitBtn');
const searchUserResults = document.getElementById('searchUserResults');

if (searchUserBtn) {
    searchUserBtn.addEventListener('click', () => {
        if (searchUsername) searchUsername.value = '';
        if (searchUserResults) searchUserResults.innerHTML = '';
        const alertDiv = document.getElementById('searchUserAlert');
        if (alertDiv) alertDiv.classList.remove('show');
        if (searchUserModal) searchUserModal.classList.add('show');
        refreshIcons();
        setTimeout(() => searchUsername?.focus(), 200);
    });
}

document.querySelectorAll('.close-search-modal, .close-profile-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        if (searchUserModal) searchUserModal.classList.remove('show');
        if (userProfileModal) userProfileModal.classList.remove('show');
    });
});

// ===== Live Search با debounce =====
let searchDebounceTimer = null;

if (searchUsername) {
    searchUsername.addEventListener('input', (e) => {
        clearTimeout(searchDebounceTimer);
        const query = e.target.value.trim();

        if (query.length < 2) {
            if (searchUserResults) searchUserResults.innerHTML = '';
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            performUserSearch(query);
        }, 500);
    });

    searchUsername.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = searchUsername.value.trim();
            if (query.length >= 2) {
                clearTimeout(searchDebounceTimer);
                performUserSearch(query);
            }
        }
    });
}

if (searchUserSubmitBtn) {
    searchUserSubmitBtn.addEventListener('click', async () => {
        const query = searchUsername ? searchUsername.value.trim() : '';
        if (!query || query.length < 2) {
            showAlert('searchUserAlert', 'حداقل ۲ کاراکتر وارد کنید', 'error');
            return;
        }
        clearTimeout(searchDebounceTimer);
        await performUserSearch(query);
    });
}

async function performUserSearch(query) {
    if (!searchUserResults) return;

    searchUserResults.innerHTML = `
        <div class="loading-center" style="padding: 1.5rem;">
            <div class="loading"></div>
            <p style="font-size: 0.8rem;">در حال جستجو...</p>
        </div>
    `;

    const result = await apiCall('search_users', { query });

    if (result.ok && result.data && result.data.length > 0) {
        let html = '<div class="search-results-list">';
        for (const user of result.data) {
            html += `
                <div class="user-result-card" data-user-id="${user.id}" data-username="${escapeHtml(user.username)}">
                    <div class="user-result-avatar">
                        <i data-lucide="user"></i>
                    </div>
                    <div class="user-result-info">
                        <div class="user-result-name">${escapeHtml(user.display_name)}</div>
                        <div class="user-result-username">@${escapeHtml(user.username)}</div>
                    </div>
                    <div class="user-result-action">
                        <i data-lucide="eye"></i>
                        <span>مشاهده</span>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        searchUserResults.innerHTML = html;
        refreshIcons();

        searchUserResults.querySelectorAll('.user-result-card').forEach(card => {
            card.addEventListener('click', async () => {
                const userId = card.dataset.userId;
                if (searchUserModal) searchUserModal.classList.remove('show');
                await loadPublicProfile(userId);
            });
        });

        const alertDiv = document.getElementById('searchUserAlert');
        if (alertDiv) alertDiv.classList.remove('show');
    } else {
        searchUserResults.innerHTML = `
            <div style="padding: 2rem 1rem; text-align: center; color: var(--muted-foreground);">
                <i data-lucide="user-x" style="width: 40px; height: 40px; opacity: 0.4; display: block; margin: 0 auto 0.75rem;"></i>
                <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">کاربری یافت نشد</div>
                <div style="font-size: 0.7rem;">جستجوی دیگری را امتحان کنید</div>
            </div>
        `;
        refreshIcons();
    }
}


/* ============================================================
   SECTION 14: PUBLIC PROFILE MODAL
   ============================================================ */
async function loadPublicProfile(userId) {
    const profileBody = document.getElementById('userProfileBody');
    const profileModal = document.getElementById('userProfileModal');
    if (!profileBody || !profileModal) return;

    profileBody.innerHTML = `
        <div style="padding: 1rem;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div style="width: 80px; height: 80px; background: var(--border); border-radius: 50%; margin: 0 auto 0.75rem; animation: skeletonPulse 1.5s ease-in-out infinite;"></div>
                <div style="height: 18px; width: 60%; background: var(--border); border-radius: 8px; margin: 0 auto 0.5rem; animation: skeletonPulse 1.5s ease-in-out infinite;"></div>
                <div style="height: 12px; width: 40%; background: var(--border); border-radius: 6px; margin: 0 auto; animation: skeletonPulse 1.5s ease-in-out infinite;"></div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                <div style="height: 70px; background: var(--border); border-radius: 14px; animation: skeletonPulse 1.5s ease-in-out infinite;"></div>
                <div style="height: 70px; background: var(--border); border-radius: 14px; animation: skeletonPulse 1.5s ease-in-out infinite;"></div>
                <div style="height: 70px; background: var(--border); border-radius: 14px; animation: skeletonPulse 1.5s ease-in-out infinite;"></div>
            </div>
        </div>
    `;

    profileModal.classList.add('show');

    const result = await apiCall('get_public_profile', { user_id: userId });

    if (result.ok && result.data) {
        const data = result.data;
        const user = data.user;
        const stats = data.stats || {};
        const achievements = data.achievements || [];

        let totalStages = 0;
        let earnedStages = 0;

        for (const catKey in achievementsData) {
            const stages = achievementsData[catKey].stages;
            totalStages += stages.length;
            const cv = getCategoryCurrentValue(catKey, {
                movies_watched: stats.movies_watched || 0,
                series_watched: stats.series_watched || 0,
                lists_created: stats.lists_created || 0,
                comments_count: stats.comments_count || 0,
                ratings_count: stats.ratings_count || 0
            });

            for (const stage of stages) {
                if (cv >= stage.threshold) earnedStages++;
            }
        }

        const overallProgress = totalStages > 0 ? (earnedStages / totalStages) * 100 : 0;

        let html = `
            <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); padding: 1.75rem 1.5rem; text-align: center; border-radius: 20px; margin-bottom: 1.5rem; color: white; position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 60%); pointer-events: none;"></div>
                <div style="background: rgba(255,255,255,0.25); backdrop-filter: blur(10px); width: 88px; height: 88px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; border: 3px solid rgba(255,255,255,0.5); position: relative; z-index: 1;">
                    <i data-lucide="user" style="width: 44px; height: 44px;"></i>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.35rem; font-weight: 700; position: relative; z-index: 1;">${escapeHtml(user.display_name)}</h3>
                <p style="font-size: 0.8rem; opacity: 0.9; direction: ltr; position: relative; z-index: 1;">@${escapeHtml(user.username)}</p>
                <p style="font-size: 0.7rem; opacity: 0.75; margin-top: 0.6rem; position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                    عضویت: ${user.member_since_fa || formatDate(user.created_at)}
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 1.5rem;">
                <div style="background: var(--card); border-radius: 14px; padding: 0.85rem 0.5rem; text-align: center; border: 1px solid var(--border);">
                    <div style="font-size: 1.6rem; font-weight: 700; color: var(--primary); line-height: 1;">${stats.lists || 0}</div>
                    <div style="font-size: 0.65rem; color: var(--muted-foreground); margin-top: 0.4rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                        <i data-lucide="list" style="width: 13px; height: 13px;"></i>
                        لیست
                    </div>
                </div>
                <div style="background: var(--card); border-radius: 14px; padding: 0.85rem 0.5rem; text-align: center; border: 1px solid var(--border);">
                    <div style="font-size: 1.6rem; font-weight: 700; color: var(--success); line-height: 1;">${stats.movies || 0}</div>
                    <div style="font-size: 0.65rem; color: var(--muted-foreground); margin-top: 0.4rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                        <i data-lucide="film" style="width: 13px; height: 13px;"></i>
                        فیلم
                    </div>
                </div>
                <div style="background: var(--card); border-radius: 14px; padding: 0.85rem 0.5rem; text-align: center; border: 1px solid var(--border);">
                    <div style="font-size: 1.6rem; font-weight: 700; color: var(--purple); line-height: 1;">${stats.series || 0}</div>
                    <div style="font-size: 0.65rem; color: var(--muted-foreground); margin-top: 0.4rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                        <i data-lucide="tv" style="width: 13px; height: 13px;"></i>
                        سریال
                    </div>
                </div>
            </div>

            <div style="background: var(--card); border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; border: 1px solid var(--border);">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 32px; height: 32px; background: var(--accent); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                            <i data-lucide="trophy" style="width: 18px; height: 18px;"></i>
                        </div>
                        <span style="font-size: 0.75rem; font-weight: 600;">دستاوردها</span>
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <div style="background: var(--border); border-radius: 10px; height: 8px; overflow: hidden;">
                            <div style="width: ${overallProgress}%; background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%); height: 100%; border-radius: 10px; transition: width 0.6s ease;"></div>
                        </div>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">${earnedStages}/${totalStages}</span>
                </div>
            </div>
        `;

        if (achievements && achievements.length > 0) {
            html += `
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.85rem;">
                        <div style="width: 24px; height: 2px; background: var(--primary); border-radius: 2px;"></div>
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--foreground);">دستاوردهای کسب‌شده</span>
                        <div style="flex: 1; height: 1px; background: var(--border);"></div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; max-height: 400px; overflow-y: auto; padding: 0.25rem;">
            `;

            for (const ach of achievements) {
                let trophyKey = 'default';
                if (specialAchievementsData[ach.achievement_key]) {
                    trophyKey = specialAchievementsData[ach.achievement_key].icon;
                } else if (ach.icon && TROPHY_ICONS[ach.icon]) {
                    trophyKey = ach.icon;
                }

                html += `
                    <div style="background: linear-gradient(135deg, var(--accent) 0%, rgba(8, 145, 178, 0.06) 100%); border-radius: 14px; padding: 0.85rem 0.6rem; text-align: center; border: 1.5px solid var(--primary); position: relative; overflow: hidden;">
                        <div style="width: 52px; height: 52px; margin: 0 auto 0.5rem; display: flex; align-items: center; justify-content: center; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.15));">
                            ${trophyIcon(trophyKey, '52px')}
                        </div>
                        <div style="font-weight: 700; font-size: 0.72rem; color: var(--foreground); line-height: 1.3;">${escapeHtml(ach.title)}</div>
                        <div style="font-size: 0.6rem; color: var(--muted-foreground); margin-top: 0.35rem;">${formatDate(ach.earned_at)}</div>
                    </div>
                `;
            }

            html += `</div></div>`;
        } else {
            html += `
                <div style="text-align: center; padding: 2rem 1rem; background: var(--secondary); border-radius: 14px;">
                    <i data-lucide="trophy" style="width: 40px; height: 40px; opacity: 0.4; display: block; margin: 0 auto 0.75rem; color: var(--muted-foreground);"></i>
                    <div style="font-size: 0.8rem; color: var(--muted-foreground);">هنوز دستاوردی کسب نشده</div>
                </div>
            `;
        }

        profileBody.innerHTML = html;
        refreshIcons();

    } else {
        profileBody.innerHTML = `
            <div style="text-align: center; padding: 2rem 1rem;">
                <i data-lucide="lock" style="width: 50px; height: 50px; opacity: 0.4; display: block; margin: 0 auto 1rem; color: var(--destructive);"></i>
                <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.35rem;">پروفایل در دسترس نیست</div>
                <div style="font-size: 0.75rem; color: var(--muted-foreground);">${escapeHtml(result.message || 'این کاربر پروفایل خود را عمومی نکرده است')}</div>
            </div>
        `;
        refreshIcons();
    }
}


/* ============================================================
   SECTION 15: PROFILE FORM & PUBLIC TOGGLE
   ============================================================ */
const profileForm = document.getElementById('profileForm');
const saveProfileBtn = document.getElementById('saveProfileBtn');

if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const display_name = document.getElementById('display_name').value.trim();
        const new_password = document.getElementById('new_password').value;
        const confirm_password = document.getElementById('confirm_password').value;

        if (!display_name) {
            showAlert('profileAlert', 'نام نمایشی نمی‌تواند خالی باشد', 'error');
            return;
        }
        if (new_password !== confirm_password) {
            showAlert('profileAlert', 'رمز عبور جدید و تکرار آن مطابقت ندارند', 'error');
            return;
        }
        if (new_password && new_password.length < 6) {
            showAlert('profileAlert', 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد', 'error');
            return;
        }

        const originalHTML = saveProfileBtn.innerHTML;
        saveProfileBtn.innerHTML = '<span class="loading-small"></span><span>در حال ذخیره...</span>';
        saveProfileBtn.disabled = true;

        const data = { display_name };
        if (new_password) data.password = new_password;

        const result = await apiCall('update_profile', data);

        if (result.ok) {
            showAlert('profileAlert', 'پروفایل با موفقیت به‌روزرسانی شد', 'success');
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';

            const sidebarName = document.querySelector('.sidebar-header h3');
            if (sidebarName) sidebarName.textContent = display_name;

            const headerName = document.querySelector('.nav-link.active span');
            if (headerName) headerName.textContent = display_name;
        } else {
            showAlert('profileAlert', result.message || 'خطا در به‌روزرسانی پروفایل', 'error');
        }

        saveProfileBtn.innerHTML = originalHTML;
        saveProfileBtn.disabled = false;
        refreshIcons();
    });
}

async function loadPublicProfileStatus() {
    const result = await apiCall('get_public_profile_status');
    if (result.ok && result.data) {
        const toggle = document.getElementById('publicProfileToggle');
        if (toggle) toggle.checked = result.data.is_public === 1;
    }
}

async function savePublicProfileStatus(isPublic) {
    const result = await apiCall('toggle_public_profile', { is_public: isPublic ? 1 : 0 });
    if (result.ok) {
        showToast(result.message || 'وضعیت پروفایل تغییر کرد', 'success');
    } else {
        showToast(result.message || 'خطا در تغییر وضعیت', 'error');
        const toggle = document.getElementById('publicProfileToggle');
        if (toggle) toggle.checked = !isPublic;
    }
}

const publicToggle = document.getElementById('publicProfileToggle');
if (publicToggle) {
    const newToggle = publicToggle.cloneNode(true);
    publicToggle.parentNode.replaceChild(newToggle, publicToggle);
    newToggle.addEventListener('change', (e) => savePublicProfileStatus(e.target.checked));
}


/* ============================================================
   SECTION 16: STATS LOADING
   ============================================================ */
async function loadAllStats() {
    const statsGrid = document.getElementById('statsGrid');
    if (!statsGrid) return;

    const statsResult = await apiCall('get_user_stats');
    if (statsResult.ok && statsGrid) {
        const s = statsResult.data;
        statsGrid.innerHTML = `
            <div class="stats-row-primary">
                <div class="stat-main-card" data-tooltip="تعداد کل لیست‌ها">
                    <div class="stat-main-number">${s.lists || 0}</div>
                    <div class="stat-main-label"><i data-lucide="list"></i>لیست‌ها</div>
                </div>
                <div class="stat-main-card" data-tooltip="تعداد کل فیلم‌ها">
                    <div class="stat-main-number">${s.movies || 0}</div>
                    <div class="stat-main-label"><i data-lucide="film"></i>فیلم‌ها</div>
                </div>
                <div class="stat-main-card" data-tooltip="تعداد کل سریال‌ها">
                    <div class="stat-main-number">${s.series || 0}</div>
                    <div class="stat-main-label"><i data-lucide="tv"></i>سریال‌ها</div>
                </div>
            </div>
            <div class="stats-row-secondary">
                <div class="stat-child-card" data-tooltip="تعداد کل فصل‌ها">
                    <div class="stat-child-number">${s.seasons || 0}</div>
                    <div class="stat-child-label">فصل‌ها</div>
                </div>
                <div class="stat-child-card" data-tooltip="تعداد کل قسمت‌ها">
                    <div class="stat-child-number">${s.total_episodes || 0}</div>
                    <div class="stat-child-label">قسمت‌ها</div>
                </div>
            </div>
            <div class="stats-row-tertiary">
                <div class="stat-watched-card" data-tooltip="آیتم‌های مشاهده شده">
                    <div class="stat-watched-number">${(s.watched_movies || 0) + (s.watched_series || 0)}</div>
                    <div class="stat-label-sm">مشاهده شده</div>
                </div>
                <div class="stat-downloaded-card" data-tooltip="آیتم‌های دانلود شده">
                    <div class="stat-downloaded-number">${(s.downloaded_movies || 0) + (s.downloaded_seasons || 0)}</div>
                    <div class="stat-label-sm">دانلود شده</div>
                </div>
                <div class="stat-completed-card" data-tooltip="سریال‌های کامل دیده‌شده">
                    <div class="stat-completed-number">${s.completed_series || 0}</div>
                    <div class="stat-label-sm">سریال کامل</div>
                </div>
            </div>
            <div class="stats-row-quaternary">
                <div class="stat-rating-card" data-tooltip="میانگین امتیازهای ثبت‌شده">
                    <div class="stat-rating-number">${s.avg_rating || 0}</div>
                    <div class="stat-label-sm">میانگین امتیاز</div>
                    <div class="stat-label-sm" style="font-size: 0.6rem; opacity: 0.7;">از ${s.total_ratings || 0} فیلم</div>
                </div>
                <div class="stat-comment-card" data-tooltip="تعداد کل کامنت‌ها">
                    <div class="stat-comment-number">${s.total_comments || 0}</div>
                    <div class="stat-label-sm">کامنت‌ها</div>
                </div>
            </div>
        `;
        refreshIcons();
    }

    const watchResult = await apiCall('get_watch_stats');
    if (watchResult.ok) {
        const w = watchResult.data;
        drawDonutChart('watchPercentageContainer', w.watch_percentage || 0);

        const percentageText = document.getElementById('watchPercentageText');
        if (percentageText) {
            percentageText.innerHTML = `${w.watch_percentage || 0}% از ${w.total_items || 0} آیتم مشاهده شده (${w.watched_items || 0} آیتم)`;
        }

        const monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        drawStackedBarChart('monthlyChartContainer', w.monthly || {}, monthNames);
        drawBarChart('yearlyChartContainer', w.yearly || {}, Object.keys(w.yearly || {}).sort());
    }

    const genreResult = await apiCall('get_genre_stats');
    if (genreResult.ok) {
        const genres = genreResult.data.genres || {};
        const genreLabels = Object.keys(genres);
        const genreValues = Object.values(genres);
        if (genreLabels.length > 0) {
            drawPieChart('genreChartContainer', genreValues, genreLabels);
        } else {
            const gc = document.getElementById('genreChartContainer');
            if (gc) gc.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--muted-foreground);">داده‌ای برای نمایش وجود ندارد</div>';
        }
    }

    const listsResult = await apiCall('get_lists_comparison');
    if (listsResult.ok) {
        const listsContainer = document.getElementById('listsComparisonContainer');
        if (listsContainer) {
            const lists = listsResult.data || [];
            if (lists.length === 0) {
                listsContainer.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--muted-foreground);">لیستی برای نمایش وجود ندارد</div>';
            } else {
                let listsHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">';
                for (const list of lists) {
                    listsHtml += `
                        <div style="border-right: 4px solid ${list.color || '#0891b2'}; background: var(--secondary); border-radius: 14px; padding: 1rem; text-align: center;" data-tooltip="${escapeHtml(list.name)}">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">${list.percentage || 0}%</div>
                            <div style="font-size: 0.75rem; font-weight: 600; margin-top: 0.35rem;">${escapeHtml(list.name)}</div>
                            <div style="font-size: 0.65rem; margin-top: 0.35rem; color: var(--muted-foreground);">${list.watched_items || 0}/${list.total_items || 0} آیتم</div>
                            <div style="font-size: 0.6rem; color: var(--muted-foreground); margin-top: 0.2rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                <span><i data-lucide="film" style="width:12px;height:12px;"></i> ${list.movies || 0}</span>
                                <span>•</span>
                                <span><i data-lucide="tv" style="width:12px;height:12px;"></i> ${list.series || 0}</span>
                            </div>
                        </div>
                    `;
                }
                listsHtml += '</div>';
                listsContainer.innerHTML = listsHtml;
                refreshIcons();
            }
        }
    }
}


/* ============================================================
   SECTION 17: HEATMAP
   ============================================================ */
/* ============================================================
   SECTION 17: HEATMAP (با Tooltip سفارشی)
   ============================================================ */
async function loadHeatmap() {
    const container = document.getElementById('heatmapContainer');
    if (!container) return;

    const result = await apiCall('get_current_year_heatmap');

    if (result.ok && result.data) {
        const activities = result.data.activities || {};
        const daysInMonth = result.data.days_in_month;
        const year = result.data.year;

        const monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

        const getHeatmapLevel = (count) => {
            if (count === 0) return 0;
            if (count === 1) return 1;
            if (count <= 5) return 2;
            return 3;
        };

        let html = `
            <div style="text-align: center; margin-bottom: 1rem; color: var(--primary); font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i data-lucide="calendar" style="width:18px;height:18px;"></i>
                فعالیت‌های سال ${year}
            </div>
            <div class="heatmap-grid">
        `;

        let dayCounter = 1;
        for (let m = 0; m < 12; m++) {
            html += `
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                    <div style="width: 55px; font-size: 0.7rem; font-weight: 600; color: var(--muted-foreground); flex-shrink: 0;">${monthNames[m]}</div>
                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
            `;

            for (let d = 1; d <= daysInMonth[m]; d++) {
                const activityCount = activities[dayCounter] || 0;
                const level = getHeatmapLevel(activityCount);

                // ساخت متن tooltip
                let tooltipText;
                if (activityCount === 0) {
                    tooltipText = `${monthNames[m]} ${d} • بدون فعالیت`;
                } else if (activityCount === 1) {
                    tooltipText = `${monthNames[m]} ${d} • ۱ عنوان`;
                } else {
                    tooltipText = `${monthNames[m]} ${d} • ${activityCount} عنوان`;
                }

                html += `<div class="heatmap-day heatmap-level-${level}" 
                    data-heatmap-day="${dayCounter}" 
                    data-heatmap-month="${monthNames[m]}" 
                    data-heatmap-date="${d}" 
                    data-heatmap-count="${activityCount}"
                    data-heatmap-tooltip="${tooltipText}"></div>`;
                dayCounter++;
            }

            html += `</div></div>`;
        }

        html += `
            </div>
            <div class="heatmap-legend">
                <div class="heatmap-legend-item">
                    <span class="heatmap-day heatmap-level-0" style="width: 14px; height: 14px;"></span>
                    بدون فعالیت
                </div>
                <div class="heatmap-legend-item">
                    <span class="heatmap-day heatmap-level-1" style="width: 14px; height: 14px;"></span>
                    ۱ عنوان
                </div>
                <div class="heatmap-legend-item">
                    <span class="heatmap-day heatmap-level-2" style="width: 14px; height: 14px;"></span>
                    ۲ تا ۵ عنوان
                </div>
                <div class="heatmap-legend-item">
                    <span class="heatmap-day heatmap-level-3" style="width: 14px; height: 14px;"></span>
                    بیشتر از ۵ عنوان
                </div>
            </div>
        `;

        container.innerHTML = html;
        refreshIcons();

        // ===== اتصال Tooltip سفارشی =====
        container.querySelectorAll('.heatmap-day[data-heatmap-tooltip]').forEach(day => {
            // حذف event listener قدیمی (در صورت وجود) با clone
            const newDay = day.cloneNode(true);
            day.parentNode.replaceChild(newDay, day);

            newDay.addEventListener('mouseenter', (e) => {
                const count = parseInt(newDay.dataset.heatmapCount) || 0;
                const month = newDay.dataset.heatmapMonth;
                const date = newDay.dataset.heatmapDate;

                // متن با هایلایت تعداد
                let innerText;
                if (count === 0) {
                    innerText = `<strong>${month} ${date}</strong><br><span style="opacity:0.7;">بدون فعالیت</span>`;
                } else {
                    innerText = `<strong>${month} ${date}</strong><br><span style="color:#22d3ee; font-weight:700;">${count}</span> عنوان تماشا شده`;
                }

                showTooltip(e, innerText);
            });

            newDay.addEventListener('mousemove', (e) => {
                // حرکت tooltip با موس
                if (tooltipDiv && tooltipDiv.style.display === 'block') {
                    tooltipDiv.style.left = (e.clientX + 15) + 'px';
                    tooltipDiv.style.top = (e.clientY - 45) + 'px';
                }
            });

            newDay.addEventListener('mouseleave', () => {
                hideTooltip();
            });
        });

    } else {
        container.innerHTML = '<div class="text-center" style="padding: 2rem; color: var(--muted-foreground);">خطا در بارگذاری تقویم فعالیت‌ها</div>';
    }
}


/* ============================================================
   SECTION 18: DANGER ZONE
   ============================================================ */
const deleteAccountBtn = document.getElementById('deleteAccountBtn');
const deleteModal = document.getElementById('deleteModal');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const confirmDeleteInput = document.getElementById('confirmDelete');

if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener('click', () => {
        if (confirmDeleteInput) confirmDeleteInput.value = '';
        if (deleteModal) deleteModal.classList.add('show');
        refreshIcons();
        setTimeout(() => confirmDeleteInput?.focus(), 200);
    });
}

document.querySelectorAll('.close-delete-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        if (deleteModal) deleteModal.classList.remove('show');
    });
});

if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', async () => {
        if (!confirmDeleteInput || confirmDeleteInput.value.trim() !== 'DELETE') {
            showAlert('dangerAlert', 'لطفاً عبارت DELETE را برای تایید وارد کنید', 'error');
            return;
        }

        const originalHTML = confirmDeleteBtn.innerHTML;
        confirmDeleteBtn.innerHTML = '<span class="loading-small"></span><span>در حال حذف...</span>';
        confirmDeleteBtn.disabled = true;

        const result = await apiCall('delete_account');

        if (result.ok) {
            showAlert('dangerAlert', 'حساب کاربری شما حذف شد. در حال انتقال...', 'success');
            setTimeout(() => window.location.href = 'logout.php', 1500);
        } else {
            showAlert('dangerAlert', result.message || 'خطا در حذف حساب کاربری', 'error');
            if (deleteModal) deleteModal.classList.remove('show');
            confirmDeleteBtn.innerHTML = originalHTML;
            confirmDeleteBtn.disabled = false;
            refreshIcons();
        }
    });
}

const deactivateAccountBtn = document.getElementById('deactivateAccountBtn');
const deactivateModal = document.getElementById('deactivateModal');
const confirmDeactivateBtn = document.getElementById('confirmDeactivateBtn');

if (deactivateAccountBtn) {
    deactivateAccountBtn.addEventListener('click', () => {
        if (deactivateModal) deactivateModal.classList.add('show');
        refreshIcons();
    });
}

document.querySelectorAll('.close-deactivate-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        if (deactivateModal) deactivateModal.classList.remove('show');
    });
});

if (confirmDeactivateBtn) {
    confirmDeactivateBtn.addEventListener('click', async () => {
        const originalHTML = confirmDeactivateBtn.innerHTML;
        confirmDeactivateBtn.innerHTML = '<span class="loading-small"></span><span>در حال غیرفعال‌سازی...</span>';
        confirmDeactivateBtn.disabled = true;

        const result = await apiCall('deactivate_account');

        if (result.ok) {
            showAlert('dangerAlert', result.message || 'حساب کاربری شما غیرفعال شد', 'success');
            setTimeout(() => window.location.href = 'logout.php', 1500);
        } else {
            showAlert('dangerAlert', result.message || 'خطا در غیرفعال‌سازی حساب', 'error');
            if (deactivateModal) deactivateModal.classList.remove('show');
            confirmDeactivateBtn.innerHTML = originalHTML;
            confirmDeactivateBtn.disabled = false;
            refreshIcons();
        }
    });
}


/* ============================================================
   SECTION 19: DONATE & EXPORT
   ============================================================ */
document.getElementById('donateBtn')?.addEventListener('click', (e) => {
    e.preventDefault();
    window.open('https://pay.mhdr-shpr.ir', '_blank');
});

document.getElementById('exportStatsBtn')?.addEventListener('click', () => {
    window.print();
});

document.getElementById('exportAchievementsBtn')?.addEventListener('click', () => {
    const originalTitle = document.title;
    document.title = 'دستاوردهای من';
    window.print();
    document.title = originalTitle;
});


/* ============================================================
   SECTION 20: SKELETON LOADER SYSTEM
   ============================================================ */
const TAB_ELEMENTS = {
    profile:      { skeleton: 'profileSkeleton',      content: 'profileContent' },
    stats:        { skeleton: 'statsSkeleton',        content: 'statsContent' },
    achievements: { skeleton: 'achievementsSkeleton', content: 'achievementsContent' },
    danger:       { skeleton: 'dangerSkeleton',       content: 'dangerContent' }
};

function showSkeleton(tabId) {
    for (const key in TAB_ELEMENTS) {
        const s = document.getElementById(TAB_ELEMENTS[key].skeleton);
        const c = document.getElementById(TAB_ELEMENTS[key].content);
        if (s) s.style.display = 'none';
        if (c) c.style.display = 'none';
    }
    const cur = TAB_ELEMENTS[tabId];
    if (cur) {
        const s = document.getElementById(cur.skeleton);
        if (s) s.style.display = 'block';
    }
}

function hideSkeleton(tabId) {
    const cur = TAB_ELEMENTS[tabId];
    if (!cur) return;

    const s = document.getElementById(cur.skeleton);
    const c = document.getElementById(cur.content);

    if (s) s.style.display = 'none';
    if (c) {
        c.style.display = 'block';
        c.style.opacity = '0';
        c.style.transform = 'translateY(10px)';
        c.style.transition = 'opacity 0.35s ease-out, transform 0.35s ease-out';

        requestAnimationFrame(() => {
            c.style.opacity = '1';
            c.style.transform = 'translateY(0)';
        });
    }
    refreshIcons();
}

async function loadTabWithSkeleton(tabId) {
    showSkeleton(tabId);

    const minDisplayTime = 800 + Math.random() * 400;
    const minTimePromise = new Promise(resolve => setTimeout(resolve, minDisplayTime));

    switch (tabId) {
        case 'profile':
            await minTimePromise;
            hideSkeleton(tabId);
            break;

        case 'stats':
            await Promise.all([loadAllStats(), loadHeatmap(), minTimePromise]);
            hideSkeleton(tabId);
            break;

        case 'achievements':
            await Promise.all([loadAchievements(), minTimePromise]);
            hideSkeleton(tabId);
            break;

        case 'danger':
            await minTimePromise;
            hideSkeleton(tabId);
            break;
    }
}


/* ============================================================
   SECTION 21: TAB EVENTS
   ============================================================ */
const sidebarItems = document.querySelectorAll('.sidebar-item');
const tabContentMap = {
    'profile':      document.getElementById('profileTab'),
    'stats':        document.getElementById('statsTab'),
    'achievements': document.getElementById('achievementsTab'),
    'danger':       document.getElementById('dangerTab')
};

sidebarItems.forEach(item => {
    const newItem = item.cloneNode(true);
    item.parentNode.replaceChild(newItem, item);

    newItem.addEventListener('click', async () => {
        const tabId = newItem.dataset.tab;

        document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
        newItem.classList.add('active');

        for (const key in tabContentMap) {
            if (tabContentMap[key]) tabContentMap[key].classList.remove('active');
        }
        if (tabContentMap[tabId]) tabContentMap[tabId].classList.add('active');

        await loadTabWithSkeleton(tabId);

        if (window.innerWidth <= 900) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
});


/* ============================================================
   SECTION 22: RESIZE HANDLER (debounced)
   ============================================================ */
let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        // رفتار آکاردئون
        if (window.innerWidth > 768) {
            // دسکتاپ: همه آکاردئون‌ها باز
            document.querySelectorAll('.achievement-category-card').forEach(card => {
                card.classList.remove('collapsed');
                const btn = card.querySelector('.accordion-toggle-btn');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            });
        } else {
            // موبایل: فقط اولی باز
            const cards = document.querySelectorAll('.achievement-category-card');
            cards.forEach((card, i) => {
                if (i === 0) {
                    card.classList.remove('collapsed');
                    const btn = card.querySelector('.accordion-toggle-btn');
                    if (btn) btn.setAttribute('aria-expanded', 'true');
                } else {
                    card.classList.add('collapsed');
                    const btn = card.querySelector('.accordion-toggle-btn');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // آپدیت اسلایدرها
        document.querySelectorAll('.stage-slider-inner').forEach(inner => {
            const catKey = inner.dataset.category;
            updateStageSliderPosition(catKey);
        });
    }, 250);
});


/* ============================================================
   SECTION 23: INIT
   ============================================================ */
async function init() {
    updatePwaOnlineStatus();
    showSkeleton('profile');
    await loadPublicProfileStatus();

    setTimeout(() => {
        hideSkeleton('profile');
    }, 1200);

    refreshIcons();
}

init();

window.addEventListener('load', () => {
    refreshIcons();
    setTimeout(refreshIcons, 300);
    setTimeout(refreshIcons, 1000);
});


/* ============================================================
   SECTION 24: SERVICE WORKER
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
   SECTION 25: GLOBAL KEYBOARD SHORTCUTS
   ============================================================ */
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modals = ['searchUserModal', 'userProfileModal', 'deleteModal', 'deactivateModal'];
        for (const id of modals) {
            const modal = document.getElementById(id);
            if (modal && modal.classList.contains('show')) {
                modal.classList.remove('show');
                break;
            }
        }
    }
});


/* ============================================================
   SECTION 26: AUTO-FOCUS IN MODALS
   ============================================================ */
const searchUserModalObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            const modal = mutation.target;
            if (modal.classList.contains('show')) {
                setTimeout(() => searchUsername?.focus(), 200);
            }
        }
    });
});

if (searchUserModal) {
    searchUserModalObserver.observe(searchUserModal, { attributes: true });
}

if (deleteModal) {
    const deleteModalObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                if (deleteModal.classList.contains('show')) {
                    setTimeout(() => confirmDeleteInput?.focus(), 200);
                }
            }
        });
    });
    deleteModalObserver.observe(deleteModal, { attributes: true });
}
</script>
</body>
</html>