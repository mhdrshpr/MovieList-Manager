<?php
// dash.php
require_once 'api/db_connect.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$display_name = $_SESSION['display_name'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>داشبورد | مدیریت فیلم و سریال</title>
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="application-name" content="MovieManager">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MovieMgr">
    <link rel="apple-touch-icon" href="/static/icons/icon-192.png">
    <meta name="theme-color" content="#0891b2">
    <meta name="mobile-web-app-capable" content="yes">

    <script src="static/js/html2canvas.min.js"></script>
    <script>window.html2canvas || document.write('<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"><\/script>')</script>
    <script src="static/js/jspdf.umd.min.js"></script>
    <script>(window.jspdf && window.jspdf.jsPDF) || document.write('<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"><\/script>')</script>

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

      * {
        font-family: 'IRANSansX', system-ui, -apple-system, Tahoma, sans-serif;
      }

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
        --secondary: #f1f5f9;
        --secondary-foreground: #334155;
        --muted: #f8fafc;
        --muted-foreground: #475569;
        --accent: #ecfeff;
        --accent-foreground: #0e7490;
        --destructive: #dc2626;
        --destructive-foreground: #ffffff;
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
        --secondary: #1e293b;
        --secondary-foreground: #cbd5e1;
        --muted: #1e293b;
        --muted-foreground: #94a3b8;
        --accent: #0c2d3b;
        --accent-foreground: #67e8f9;
        --destructive: #f87171;
        --destructive-foreground: #0b1120;
        --border: #1e293b;
        --input-background: #111827;
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        background-color: var(--background);
        color: var(--foreground);
        font-size: var(--font-size);
        line-height: 1.5;
        transition: background-color 0.3s, color 0.3s;
        min-height: 100vh;
      }

      /* ==================== MODAL Z-INDEX HIERARCHY ==================== */
      .modal { z-index: 1050; }

      /* Layer 1: Background list/group/shared viewers */
      #listDetailModal { z-index: 1050; }
      #groupDetailModal { z-index: 1050; }
      #sharedListDetailModal { z-index: 1050; }

      /* Layer 2: Management & Primary forms */
      #listModal { z-index: 1100; }
      #groupModal { z-index: 1100; }
      #groupManageModal { z-index: 1110; }
      #sharedListModal { z-index: 1100; }
      #shareModal { z-index: 1100; }

      /* Layer 3: Title (Movie / Series) editor - above listDetail & groupDetail */
      #movieModal { z-index: 1150; }
      #seriesModal { z-index: 1150; }
      #seasonModal { z-index: 1170; }
      #singleSeasonModal { z-index: 1180; }

      /* Layer 4: Sub-dialogs opened from titles or list items */
      #genreModal { z-index: 1200; }
      #prequelModal { z-index: 1200; }
      #watchDateModal { z-index: 1220; }
      #ratingModal { z-index: 1220; }
      #commentModal { z-index: 1220; }

      /* Layer 5: Critical confirmation / alerts - always on top of EVERYTHING */
      #deleteModal { z-index: 1300 !important; }

      /* ==================== UNIFIED TOOLTIP (اضافه شده) ==================== */
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

      /* Smart flip & Tooltip bottom (برای دکمه‌های ویرایش لیست، حذف لیست و موارد دارای tooltip-bottom) */
      .edit-list::after,
      .delete-list::after,
      .card-actions [data-tooltip]::after,
      [data-tooltip].tooltip-bottom::after {
        bottom: auto !important;
        top: calc(100% + 10px) !important;
      }
      .edit-list::before,
      .delete-list::before,
      .card-actions [data-tooltip]::before,
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

      /* ==================== CONTEXT MENU ==================== */
      .context-menu {
        display: none;
        position: fixed;
        z-index: 2000;
        min-width: 200px;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
        padding: 0.4rem;
        animation: contextIn 0.15s ease;
      }
      .context-menu.show { display: block; }
      @keyframes contextIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
      }
      .context-menu-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem 0.85rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--foreground);
        cursor: pointer;
        transition: all 0.15s;
        border: none;
        background: transparent;
        width: 100%;
        text-align: right;
        white-space: nowrap;
      }
      .context-menu-item:hover {
        background: var(--accent);
        color: var(--primary);
      }
      .context-menu-item.destructive { color: var(--destructive); }
      .context-menu-item.destructive:hover {
        background: #ef444415;
        color: var(--destructive);
      }
      .context-menu-item [data-lucide] {
        width: 18px;
        height: 18px;
      }
      .context-menu-separator {
        height: 1px;
        background: var(--border);
        margin: 0.3rem 0.5rem;
      }
      .context-menu-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 600;
        margin-right: auto;
      }
      .context-menu-badge.downloaded { background: #10b98120; color: #10b981; }
      .context-menu-badge.watched { background: #6366f120; color: #6366f1; }
      .context-menu-badge.rated { background: #f59e0b20; color: #f59e0b; }
      .context-menu-badge.commented { background: #8b5cf620; color: #8b5cf6; }
      .context-menu-badge.prequel { background: #3b82f620; color: #3b82f6; }

      /* ==================== SKELETON ==================== */
      @keyframes skeletonPulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
      }
      .skeleton {
        background: var(--secondary);
        border-radius: 12px;
        animation: skeletonPulse 1.6s ease-in-out infinite;
      }
      .skeleton-card {
        height: 200px;
        border-radius: 12px;
        background: var(--card);
        border: 1px solid var(--border);
        overflow: hidden;
      }
      .skeleton-card-banner {
        width: 100%;
        height: 140px;
        background: var(--secondary);
        animation: skeletonPulse 1.6s ease-in-out infinite;
      }
      .skeleton-card-body {
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .skeleton-line {
        height: 14px;
        border-radius: 6px;
        background: var(--secondary);
        animation: skeletonPulse 1.6s ease-in-out infinite;
      }
      .skeleton-line-sm { width: 60%; height: 12px; }
      .skeleton-line-md { width: 80%; height: 14px; }
      .skeleton-line-lg { width: 40%; height: 14px; }
      .skeleton-media-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-bottom: 1px solid var(--border);
      }
      .skeleton-media-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--secondary);
        animation: skeletonPulse 1.6s ease-in-out infinite;
        flex-shrink: 0;
      }
      .skeleton-media-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
      }
      .skeleton-media-actions {
        display: flex;
        gap: 0.4rem;
      }
      .skeleton-media-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: var(--secondary);
        animation: skeletonPulse 1.6s ease-in-out infinite;
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
      .nav-link:hover { border-bottom-color: var(--primary); color: var(--primary); }
      .nav-link.active { border-bottom-color: var(--primary); color: var(--primary); }
      .nav-link-logout { color: var(--destructive); }
      .nav-link-logout:hover { border-bottom-color: var(--destructive); color: var(--destructive); }

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

      /* ==================== ACTIVE STATE COLORS ==================== */
      .btn-downloaded {
        background: #10b98120 !important;
        border-color: #10b981 !important;
        color: #10b981 !important;
      }
      .btn-downloaded [data-lucide] { color: #10b981 !important; stroke: #10b981 !important; }

      .btn-watched {
        background: #10b98120 !important;
        border-color: #10b981 !important;
        color: #10b981 !important;
      }
      .btn-watched [data-lucide] { color: #10b981 !important; stroke: #10b981 !important; }

      .rating-btn.has-rating {
        background: #f59e0b20 !important;
        border-color: #f59e0b !important;
        color: #f59e0b !important;
        gap: 0.5rem;
      }
      .rating-btn.has-rating [data-lucide] { color: #f59e0b !important; stroke: #f59e0b !important; }

      .comment-btn.has-comment {
        background: #8b5cf620 !important;
        border-color: #8b5cf6 !important;
        color: #8b5cf6 !important;
      }
      .comment-btn.has-comment [data-lucide] { color: #8b5cf6 !important; stroke: #8b5cf6 !important; }

      .prequel-btn.btn-downloaded {
        background: #3b82f620 !important;
        border-color: #3b82f6 !important;
        color: #3b82f6 !important;
      }
      .prequel-btn.btn-downloaded [data-lucide] { color: #3b82f6 !important; stroke: #3b82f6 !important; }

      .collection-badge {
        background: #8b5cf620 !important;
        border-color: #8b5cf6 !important;
        color: #8b5cf6 !important;
      }
      .collection-badge [data-lucide] { color: #8b5cf6 !important; stroke: #8b5cf6 !important; }

      /* ==================== BTN-MORE ==================== */
      .btn-more {
        padding: 0.4rem 0.2rem;
        border-radius: 8px;
        background: transparent;
        color: var(--muted-foreground);
        border: 1px solid var(--border);
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
      }
      .btn-more:hover {
        background: var(--secondary);
        color: var(--foreground);
      }
      .btn-more [data-lucide] {
        width: 18px;
        height: 18px;
      }

      /* ==================== LISTS GRID ==================== */
      .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
      }
      .list-card {
        position: relative;
        background: var(--card);
        border-radius: 12px;
        border: 1px solid var(--border);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
      }
      .list-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
      }
      .card-header {
        padding: 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--card);
      }
      .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .color-dot {
        width: 16px;
        height: 16px;
        border-radius: 12px;
        display: inline-block;
      }
      .card-actions {
        display: flex;
        gap: 0.5rem;
      }
      .card-content { padding: 1rem; }
      .list-stats {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        font-size: 0.75rem;
        color: var(--muted-foreground);
        flex-wrap: wrap;
      }
      .list-stats [data-lucide] {
        width: 14px;
        height: 14px;
      }

      .list-progress {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-top: 0.6rem;
        padding: 0.4rem 0.6rem;
        background: var(--secondary);
        border-radius: 12px;
      }
      .list-progress-bar {
        flex: 1;
        height: 7px;
        background: var(--border);
        border-radius: 12px;
        overflow: hidden;
        position: relative;
      }
      .list-progress-fill {
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        border-radius: 12px;
        transition: width 0.6s ease;
      }
      .list-progress-label {
        font-size: 0.68rem;
        font-weight: 600;
        color: var(--primary);
        white-space: nowrap;
      }
      .list-description {
        font-size: 0.8rem;
        color: var(--muted-foreground);
        margin-top: 0.5rem;
        padding: 0.5rem;
        background: var(--secondary);
        border-radius: 12px;
      }

      .search-input {
        flex: 1;
        padding: 0.75rem 1rem;
        background: var(--input-background);
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 0.875rem;
        color: var(--foreground);
      }
      .search-input:focus {
        outline: none;
        border-color: var(--primary);
      }

      /* ==================== MEDIA ITEMS ==================== */
      .media-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        border-radius: 12px;
        transition: background 0.2s;
        border-bottom: 1px solid var(--border);
      }
      .media-item:last-child { border-bottom: none; }
      .media-item:hover { background: var(--accent); }
      .media-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
        cursor: pointer;
      }
      .media-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--secondary);
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        color: var(--primary);
      }
      .media-icon [data-lucide] {
        width: 20px;
        height: 20px;
      }
      .media-details { flex: 1; }
      .media-title { font-weight: 700; font-size: 0.9rem; }
      .media-meta {
        font-size: 0.7rem;
        color: var(--muted-foreground);
        margin-top: 0.2rem;
      }

      .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
      }
      .status-badge [data-lucide] { width: 12px; height: 12px; }
      .badge-downloaded { background: #10b98120; color: #10b981; }
      .badge-watched { background: #6366f120; color: #6366f1; }

      .seasons-container {
        margin-top: 0.75rem;
        padding-right: 2rem;
        border-right: 2px solid var(--border);
      }
      .season-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border);
      }
      .season-item:last-child { border-bottom: none; }

      .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--muted-foreground);
      }
      .empty-state [data-lucide] {
        width: 40px;
        height: 40px;
        margin: 0 auto 0.75rem;
        display: block;
        opacity: 0.5;
      }

      /* ==================== MODALS ==================== */
      .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
      }
      .modal.show { display: flex; }
      .modal-content {
        background: var(--card);
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
        border: 1px solid var(--border);
        animation: modalIn 0.25s ease;
      }
      @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
      }
      #listDetailModal .modal-header {
        flex-direction: column !important;
        align-items: stretch !important;
        padding-bottom: 0.5rem;
        width: 100%;
        box-sizing: border-box;
      }
      #listDetailModal .modal-header > div:first-child {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      #listDetailModal .modal-header > div:first-child > div {
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      #listDetailModal .modal-header .flex.gap-2 {
        width: 100% !important;
        margin: 0.75rem 0 0 0 !important;
      }
      #listDetailModal .modal-header .tabs {
        width: 100% !important;
        margin: 0.5rem 0 -0.5rem 0 !important;
        border-bottom: 1px solid var(--border);
      }
      .modal-header {
        padding: 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background: var(--card);
        z-index: 10;
      }
      .modal-body { padding: 1.25rem; }
      .modal-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        position: sticky;
        bottom: 0;
        background: var(--card);
      }

      .form-group { margin-bottom: 1.25rem; }
      .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
      }
      .form-input,
      .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--input-background);
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 0.875rem;
        color: var(--foreground);
        transition: all 0.2s;
      }
      .form-input:focus,
      .form-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
      }

      .tabs {
        display: flex;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border);
        margin-bottom: 1.5rem;
      }
      .tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.6rem 1.25rem;
        background: transparent;
        border: none;
        color: var(--muted-foreground);
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
      }
      .tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
      }
      .tab [data-lucide] { width: 18px; height: 18px; }

      .alert {
        padding: 0.75rem 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        display: none;
        font-size: 0.875rem;
      }
      .alert-success {
        background: #10b98120;
        color: #10b981;
        border: 1px solid #10b98130;
      }
      .alert-error {
        background: #ef444420;
        color: #ef4444;
        border: 1px solid #ef444430;
      }

      .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid var(--border);
        border-top-color: var(--primary);
        border-radius: 40px;
        animation: spin 0.6s linear infinite;
      }
      .loading-small {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
      @keyframes spin { to { transform: rotate(360deg); } }

      .flex { display: flex; }
      .items-center { align-items: center; }
      .justify-between { justify-content: space-between; }
      .justify-end { justify-content: flex-end; }
      .gap-2 { gap: 0.5rem; }
      .gap-3 { gap: 0.75rem; }
      .gap-4 { gap: 1rem; }
      .mt-2 { margin-top: 0.5rem; }
      .mt-4 { margin-top: 1rem; }
      .mb-2 { margin-bottom: 0.5rem; }
      .mb-4 { margin-bottom: 1rem; }
      .text-center { text-align: center; }
      .w-full { width: 100%; }

      h3 {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 0.6rem 1.25rem;
      }

      /* ==================== LISTS HEADER ==================== */
      .lists-header { margin-top: 1rem; }
      .lists-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
      }
      .lists-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .lists-title h2 { margin: 0; font-size: 1.25rem; }
      .lists-header-bottom { width: 100%; }
      .lists-header-bottom .search-input {
        width: 100%;
        box-sizing: border-box;
      }
      .media-number {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary);
        background: var(--accent);
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
      }

      .watch-btn.has-date {
        width: auto !important;
        padding: 0.5rem 0.75rem !important;
        gap: 0.5rem !important;
        border-radius: 12px !important;
        white-space: nowrap;
      }
      .watch-date-label {
        font-size: 0.7rem;
        font-weight: 500;
        color: #10b981;
        white-space: nowrap;
        direction: ltr;
      }
      .btn-watched.has-date {
        background: #10b98120 !important;
        border-color: #10b981 !important;
        min-width: max-content;
        color: #10b981 !important;
      }
      .btn-watched.has-date [data-lucide],
      .btn-watched.has-date .watch-date-label {
        color: #10b981 !important;
        stroke: #10b981 !important;
      }

      /* ==================== STAR RATING ==================== */
      .star {
        font-size: 2rem;
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--border);
        user-select: none;
      }
      .star:hover { transform: scale(1.2); }
      .star.active { color: #f59e0b; text-shadow: 0 0 1px rgba(245, 158, 11); }
      .star.hover { color: #fbbf24; }
      #ratingValue {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
        min-height: 1.5rem;
        transition: all 0.2s;
      }
      .rating-label {
        font-size: 0.7rem;
        font-weight: 500;
        color: #f59e0b;
        white-space: nowrap;
        direction: ltr;
      }

      /* ==================== COMMENT BADGE ==================== */
      .list-card-comment-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 12px;
        height: 12px;
        background-color: #ef4444;
        border-radius: 50%;
        z-index: 15;
        box-shadow: 0 0 0 2px var(--card);
        animation: pulse 1.5s infinite;
      }
      @keyframes pulse {
        0% { transform: scale(0.95); opacity: 0.7; }
        70% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.7; }
      }
      .list-card.has-banner .list-card-comment-badge { top: 15px; right: 15px; }

      .list-card-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 12px;
        animation: fadeInOverlay 0.2s ease;
      }
      .list-card-loading-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        background: var(--card);
        padding: 1.5rem 2rem;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        animation: scaleIn 0.3s ease;
      }
      .list-card-loading-content .loading {
        width: 32px;
        height: 32px;
        border-width: 3px;
      }
      .list-card-loading-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
        white-space: nowrap;
      }
      @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }
      @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
      }

      /* ==================== BANNER ==================== */
      .list-banner {
        position: relative;
        width: 100%;
        height: 250px;
        overflow: hidden;
        background: var(--secondary);
      }
      .list-banner-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }
      .list-banner-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 70%;
        background: linear-gradient(to top, var(--banner-color, rgba(8, 145, 178, 0.85)) 0%, transparent 50%);
        display: flex;
        align-items: flex-end;
        padding: 1rem 1.25rem;
      }
      .list-banner-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, var(--banner-color, #0891b2) 0%, var(--banner-color-light, #06b6d4) 100%);
        opacity: 0.3;
      }
      .list-banner-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #ffffff;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .list-banner-title .color-dot {
        width: 14px;
        height: 14px;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
      }
      .list-banner-actions {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        display: flex;
        gap: 0.5rem;
      }
      .list-banner-edit-btn,
      .list-banner-remove-btn {
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.3);
        background: rgba(0, 0, 0, 0.5);
        color: #ffffff;
        backdrop-filter: blur(4px);
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
      }
      .list-banner-edit-btn:hover,
      .list-banner-remove-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.5);
      }
      .list-banner-actions [data-lucide] {
        width: 14px;
        height: 14px;
      }
      .list-card.has-banner .card-header { display: none; }
      .list-card.has-banner .card-content { padding: 0.75rem 1rem 1rem 1rem; }
      .list-card:not(.has-banner) .card-header { display: flex; }

      /* ==================== GROUP CARDS ==================== */
      .group-card {
        position: relative;
        background: var(--card);
        border-radius: 16px;
        border: 1px solid var(--border);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        margin-bottom: 0;
      }
      .group-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
      }
      .group-banner {
        position: relative;
        width: 100%;
        height: 180px;
        overflow: hidden;
        background: var(--secondary);
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .group-banner-faded {
        position: absolute;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: var(--shadow);
        opacity: 0.35;
        filter: blur(1px);
      }
      .group-banner-faded:nth-child(1) {
        width: 52%;
        height: 70%;
        top: 12%;
        right: 6%;
        transform: rotate(-8deg);
        opacity: 0.3;
      }
      .group-banner-faded:nth-child(2) {
        width: 52%;
        height: 70%;
        top: 12%;
        left: 6%;
        transform: rotate(8deg);
        opacity: 0.3;
      }
      .group-banner-center {
        position: relative;
        z-index: 3;
        width: 58%;
        height: 78%;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        border: 2px solid rgba(255, 255, 255, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--banner-color, #0891b2) 0%, var(--banner-color-light, #06b6d4) 100%);
      }
      .group-banner-center img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }
      .group-banner-center [data-lucide] {
        width: 60px;
        height: 60px;
        color: #ffffff;
        stroke: #ffffff;
        opacity: 0.9;
      }
      .group-banner-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 4;
        padding: 0.75rem 1rem;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
      }
      .group-banner-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.05rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .group-banner-actions {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        display: flex;
        gap: 0.5rem;
        z-index: 5;
      }
      .group-banner-actions .btn-icon {
        background: rgba(0, 0, 0, 0.5);
        border-color: rgba(255, 255, 255, 0.3);
        color: white;
        backdrop-filter: blur(4px);
      }
      .group-card-body {
        padding: 0.9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
      }
      .group-card-info {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
      }
      .group-card-count {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.6rem;
        background: var(--accent);
        color: var(--primary);
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
        white-space: nowrap;
      }

      /* ==================== COMMENT SECTION ==================== */
      .comment-section {
        margin-top: 0.5rem;
        padding: 0.75rem;
        background: var(--secondary);
        border-radius: 12px;
        font-size: 0.8rem;
      }
      .comment-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border);
      }
      .comment-header [data-lucide] {
        width: 16px;
        height: 16px;
        opacity: 0.7;
      }
      .comment-author {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--primary);
      }
      .comment-date {
        font-size: 0.7rem;
        color: var(--muted-foreground);
      }
      .comment-text {
        color: var(--foreground);
        line-height: 1.6;
        white-space: pre-wrap;
        word-wrap: break-word;
        font-size: 0.8rem;
      }
      #commentCharCount {
        font-size: 0.75rem;
        color: var(--muted-foreground);
      }
      #commentCharCount.warning { color: #f59e0b; }
      #commentCharCount.danger { color: var(--destructive); font-weight: 700; }

      /* ==================== EXPORT ==================== */
      .export-container {
        position: fixed;
        left: -9999px;
        top: 0;
        width: 800px;
        background: #ffffff;
        z-index: -1;
        font-family: 'IRANSansX', Tahoma, sans-serif !important;
        direction: rtl;
        color: #1f2937;
        padding: 0;
      }
      .export-pdf-page {
        width: 800px;
        padding: 30px 40px;
        page-break-after: always;
        background: #ffffff;
      }
      .export-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #0891b2;
      }
      .export-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .export-header-left .site-name {
        font-size: 16px;
        font-weight: 700;
        color: #0891b2;
      }
      .export-header-right {
        text-align: left;
        font-size: 11px;
        color: #6b7280;
      }
      .export-list-title {
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
      }
      .export-list-desc {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 20px;
        padding: 10px 15px;
        background: #f3f4f6;
        border-radius: 12px;
        border-right: 3px solid #0891b2;
      }
      .export-section-title {
        font-size: 16px;
        font-weight: 700;
        color: #0891b2;
        margin: 20px 0 12px 0;
        padding-bottom: 8px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .export-movie-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 12px;
      }
      .export-movie-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
      }
      .export-movie-number {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ecfeff;
        color: #0891b2;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
      }
      .export-movie-info .export-movie-title {
        font-weight: 700;
        font-size: 13px;
        color: #1f2937;
      }
      .export-movie-info .export-movie-meta {
        font-size: 10px;
        color: #6b7280;
        margin-top: 2px;
      }
      .export-movie-right {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .export-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 500;
      }
      .export-badge-downloaded { background: #d1fae5; color: #059669; }
      .export-badge-watched { background: #dbeafe; color: #2563eb; }
      .export-badge-rating { background: #fef3c7; color: #d97706; }
      .export-badge-comment { background: #ede9fe; color: #7c3aed; }
      .export-badge-prequel { background: #fce7f3; color: #db2777; }
      .export-prequels-list {
        margin-right: 40px;
        font-size: 10px;
        color: #6b7280;
        margin-top: 4px;
        padding: 4px 10px;
        background: #f9fafb;
        border-radius: 12px;
      }
      .export-prequel-item {
        display: inline;
        margin-left: 10px;
      }
      .export-prequel-item .prequel-tag { font-weight: 600; }
      .export-prequel-item .prequel-tag.prequel { color: #f59e0b; }
      .export-prequel-item .prequel-tag.sequel { color: #8b5cf6; }
      .export-comment-box {
        margin-right: 40px;
        margin-top: 6px;
        padding: 8px 12px;
        background: #f5f3ff;
        border-radius: 12px;
        border-right: 3px solid #8b5cf6;
        font-size: 10px;
      }
      .export-comment-header {
        color: #7c3aed;
        font-weight: 600;
        margin-bottom: 4px;
      }
      .export-comment-text {
        color: #4b5563;
        line-height: 1.5;
      }
      .export-footer {
        margin-top: 20px;
        padding-top: 10px;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        font-size: 10px;
        color: #9ca3af;
      }
      #exportContainer {
        position: fixed;
        left: 0;
        top: 0;
        width: 850px;
        z-index: -9999;
        opacity: 1;
        visibility: visible;
        pointer-events: none;
      }
      .export-png-container,
      .export-jpg-container,
      .export-pdf-wrapper {
        width: 850px;
        background: #ffffff;
        font-family: 'IRANSansX', Tahoma, sans-serif !important;
        direction: rtl;
        color: #1f2937;
        box-sizing: border-box;
      }
      .export-png-inner,
      .export-jpg-inner { padding: 0; background: #ffffff; }
      .export-banner-area {
        width: 100%;
        overflow: hidden;
        position: relative;
      }
      .export-banner-area img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .export-banner-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 20px 30px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
        color: #ffffff;
      }
      .export-banner-title {
        font-size: 24px;
        font-weight: 700;
        color: #ffffff;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
      }
      .export-png-content,
      .export-jpg-content { padding: 20px 30px; }
      .export-buttons {
        display: flex;
        gap: 0.5rem;
        margin-right: auto;
      }

      /* ==================== TOAST ==================== */
      .toast-container {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        pointer-events: none;
      }
      .toast {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1.25rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        animation: toastIn 0.3s ease, toastOut 0.3s ease 9.7s forwards;
        pointer-events: auto;
        backdrop-filter: blur(8px);
        min-width: 300px;
        max-width: 500px;
        text-align: center;
        justify-content: center;
      }
      .toast-warning { background: rgba(245, 158, 11, 0.95); }
      .toast-success { background: rgba(16, 185, 129, 0.95); }
      .toast-error { background: rgba(239, 68, 68, 0.95); }
      .toast-info { background: rgba(8, 145, 178, 0.95); }
      .toast .toast-icon [data-lucide] {
        width: 22px;
        height: 22px;
      }
      .toast .toast-close {
        cursor: pointer;
        opacity: 0.7;
        font-size: 1rem;
        margin-right: auto;
      }
      .toast .toast-close:hover { opacity: 1; }
      .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 0 0 0 12px;
        animation: toastProgress 10s linear forwards;
      }
      @keyframes toastIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
      }
      @keyframes toastOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-20px); }
      }
      @keyframes toastProgress {
        from { width: 100%; }
        to { width: 0%; }
      }

      /* ==================== GENRE TAGS & AUTOCOMPLETE ==================== */
      .genre-field-wrapper {
        position: relative;
        width: 100%;
      }
      .genre-tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        padding: 0.45rem 0.6rem;
        min-height: 44px;
        background: var(--input-background);
        border: 1px solid var(--border);
        border-radius: 12px;
        cursor: text;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        align-items: center;
        position: relative;
      }
      .genre-tags-container:focus-within {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15);
      }
      .genre-tags-container.is-full {
        background: var(--card);
        border-color: var(--border);
      }
      .genre-suggestions {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        left: 0;
        z-index: 1050;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 14px 35px rgba(0, 0, 0, 0.22);
        max-height: 250px;
        overflow-y: auto;
        display: none;
        padding: 0.35rem 0;
      }
      .genre-suggestion-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.82rem;
        cursor: pointer;
        transition: all 0.15s ease;
        border-radius: 8px;
        margin: 0.15rem 0.35rem;
      }
      .genre-suggestion-item:hover,
      .genre-suggestion-item.active {
        background: var(--accent);
        color: var(--primary);
      }
      .genre-suggestion-item .genre-name-group {
        display: flex;
        align-items: center;
        gap: 0.55rem;
      }
      .genre-suggestion-item .genre-fa-name {
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--foreground);
      }
      .genre-suggestion-item:hover .genre-fa-name,
      .genre-suggestion-item.active .genre-fa-name {
        color: var(--primary);
      }
      .genre-suggestion-item .genre-en-name {
        font-size: 0.72rem;
        color: var(--muted-foreground);
        direction: ltr;
        display: inline-block;
      }
      .genre-suggestion-item .genre-badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.45rem;
        border-radius: 6px;
        background: var(--secondary);
        color: var(--muted-foreground);
        white-space: nowrap;
      }
      .genre-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.65rem;
        background: var(--accent);
        color: var(--primary);
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        border: 1px solid rgba(8, 145, 178, 0.25);
        white-space: nowrap;
        animation: tagIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        user-select: none;
      }
      .genre-tag .color-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
      }
      .genre-tag .tag-remove {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        font-size: 0.68rem;
        color: var(--primary);
        opacity: 0.7;
        transition: all 0.15s;
        margin-right: 0.15rem;
      }
      .genre-tag .tag-remove:hover {
        opacity: 1;
        background: rgba(8, 145, 178, 0.25);
        transform: scale(1.15);
      }
      .genre-input {
        border: none !important;
        outline: none !important;
        background: transparent !important;
        flex: 1;
        min-width: 140px;
        padding: 0.3rem 0.5rem !important;
        font-size: 0.85rem;
        color: var(--foreground);
        box-shadow: none !important;
      }
      .genre-input::placeholder { color: var(--muted-foreground); }
      .genre-input:disabled {
        cursor: not-allowed;
        opacity: 0.6;
      }
      .genre-counter {
        font-size: 0.65rem;
        color: var(--muted-foreground);
        margin-top: 0.3rem;
        text-align: left;
      }
      .genre-counter.warning { color: #f59e0b; }
      .genre-counter.full { color: var(--destructive); }
      .genre-display {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        margin-top: 0.2rem;
      }
      .genre-display-tag {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        background: var(--accent);
        color: var(--primary);
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 500;
        border: 1px solid rgba(8, 145, 178, 0.15);
      }
      @keyframes tagIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
      }

      /* ==================== GENRE LIBRARY MODAL ==================== */
      .genre-suggestions-box {
        max-height: 300px;
        overflow-y: auto;
        background: var(--secondary);
        border-radius: 12px;
        padding: 0.75rem;
        margin-top: 1rem;
        display: none;
      }
      .genre-suggestions-box.show { display: block; }
      .genre-suggestion-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.85rem;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 10px;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
        cursor: pointer;
      }
      .genre-suggestion-card:hover {
        border-color: var(--primary);
        background: var(--accent);
        transform: translateX(-3px);
      }
      .genre-suggestion-card-info {
        flex: 1;
        min-width: 0;
      }
      .genre-suggestion-card-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--foreground);
      }
      .genre-suggestion-card-desc {
        font-size: 0.65rem;
        color: var(--muted-foreground);
        margin-top: 0.15rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .genre-suggestion-card-add {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.7rem;
        border-radius: 8px;
        background: var(--primary);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        flex-shrink: 0;
        border: none;
        cursor: pointer;
        font-family: inherit;
      }
      .genre-suggestion-card-add [data-lucide] {
        width: 14px;
        height: 14px;
      }

      /* ==================== PREQUEL MODAL ==================== */
      .prequel-type-selector {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
      }
      .prequel-type-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        padding: 0.6rem 0.5rem;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border);
        background: var(--secondary);
        color: var(--muted-foreground);
        text-align: center;
        justify-content: center;
        gap: 0.3rem;
      }
      .prequel-type-btn.active {
        background: var(--foreground);
        color: white;
        border-color: var(--primary);
      }
      .prequel-type-btn.collection-btn.active {
        background: rgba(139, 92, 246, 0.1);
        color: #8b5cf6;
        border-color: #8b5cf6;
      }
      .prequel-type-btn.prequel-btn-type.active {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border-color: #f59e0b;
      }
      .prequel-type-btn.sequel-btn-type.active {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        border-color: #3b82f6;
      }
      .prequel-related-type-selector {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
      }
      .prequel-related-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        padding: 0.6rem 0.5rem;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border);
        background: var(--secondary);
        color: var(--muted-foreground);
        text-align: center;
        justify-content: center;
        gap: 0.3rem;
      }
      .prequel-related-btn.active {
        background: var(--accent);
        color: var(--primary);
        border-color: var(--primary);
      }

      /* ==================== DATE PICKER ==================== */
      .wheel-container {
        position: relative;
        width: 80px;
        height: 200px;
        overflow: hidden;
        background: var(--card);
        border-radius: 12px;
        border: 1px solid var(--border);
      }
      .wheel-label {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 28px;
        text-align: center;
        color: var(--primary);
        font-size: 0.7rem;
        font-weight: 600;
        line-height: 28px;
        background: var(--card);
        z-index: 3;
        border-bottom: 1px solid var(--border);
      }
      .wheel-container::before,
      .wheel-container::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        height: 75px;
        z-index: 1;
        pointer-events: none;
      }
      .wheel-container::before {
        top: 28px;
        background: linear-gradient(to bottom, var(--card) 0%, transparent 100%);
      }
      .wheel-container::after {
        bottom: 0;
        background: linear-gradient(to top, var(--card) 0%, transparent 100%);
      }
      .selection-indicator {
        position: absolute;
        top: 50%;
        left: 5%;
        right: 5%;
        height: 36px;
        transform: translateY(calc(-50% + 14px));
        border-top: 2px solid var(--primary);
        border-bottom: 2px solid var(--primary);
        pointer-events: none;
        z-index: 2;
        margin-top: 14px;
      }
      .wheel {
        position: absolute;
        top: 28px;
        bottom: 0;
        left: 0;
        right: 0;
        overflow-y: auto;
        scroll-snap-type: y mandatory;
        scrollbar-width: none;
        -ms-overflow-style: none;
      }
      .wheel::-webkit-scrollbar { display: none; }
      .wheel-item {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        min-height: 36px;
        font-size: 0.9rem;
        color: var(--muted-foreground);
        scroll-snap-align: center;
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
        filter: blur(2px);
        opacity: 0.7;
      }
      .wheel-item.selected {
        color: var(--primary) !important;
        font-size: 1.1rem !important;
        font-weight: 700 !important;
        filter: blur(0px) !important;
        opacity: 1 !important;
      }

      /* ==================== LIST COMMENTS ==================== */
      .comment-item {
        background: var(--secondary);
        border-radius: 12px;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
      }
      .comment-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border);
      }
      .comment-author-name {
        font-weight: 600;
        color: var(--primary);
        font-size: 0.8rem;
      }
      .comment-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
      }
      .comment-form {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 0.75rem;
        margin: 1rem 0;
      }
      .comment-form textarea {
        width: 100%;
        padding: 0.6rem;
        background: var(--input-background);
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 0.8rem;
        color: var(--foreground);
        resize: vertical;
        min-height: 80px;
      }
      .comment-form textarea:focus {
        outline: none;
        border-color: var(--primary);
      }
      .comment-char-count {
        font-size: 0.65rem;
        color: var(--muted-foreground);
        margin-top: 0.35rem;
        text-align: left;
      }
      .edit-comment-form {
        display: none;
        margin-top: 0.75rem;
      }
      .edit-comment-form.active { display: block; }

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
      .footer-donate-btn [data-lucide] {
        width: 14px;
        height: 14px;
      }

      /* ==================== RESPONSIVE ==================== */
      @media (max-width: 768px) {
        .header-content {
          flex-direction: column;
          gap: 5px;
        }
        .logo span { font-size: 1rem; }
        .nav { gap: 0.25rem; }
        .nav-link {
          padding: 0.5rem;
          gap: 0;
          font-size: 0;
          border-radius: 8px;
        }
        .nav-link span,
        .nav-link .icon-text { display: none; }
        .nav-link [data-lucide] {
          width: 15px;
          height: 15px;
        }
        #themeToggle { padding: 0.5rem; }

        .lists-header-top {
          flex-wrap: wrap;
          gap: 0.75rem;
        }
        .lists-title { flex: 1; }
        .lists-title h2 { font-size: 1.1rem; }
        .btn-primary { padding: 0.5rem 1rem; font-size: 0.8rem; }
        .lists-header-bottom { margin-top: 0.25rem; }
        .lists-header-bottom .search-input {
          padding: 0.6rem 0.9rem;
          font-size: 0.8rem;
        }
        .cards-grid { grid-template-columns: 1fr; }
        .container { padding: 0 1rem; }
        .watch-btn.has-date { padding: 0.5rem 0.6rem !important; }
        .watch-date-label { font-size: 0.65rem; }
        .media-item .flex.gap-2 {
          flex-wrap: wrap;
          justify-content: flex-end;
        }

        #listDetailModal .tab span.tab-text,
        #sharedListDetailModal .tab span.tab-text,
        .prequel-tabs .tab span.tab-text { display: none; }
        #listDetailModal .tab,
        #sharedListDetailModal .tab,
        .prequel-tabs .tab {
          padding: 0.6rem 0.8rem;
          gap: 6px;
          font-size: 0;
        }
        #listDetailModal .tab #listCommentsCount,
        #sharedListDetailModal .tab #listCommentsCount { font-size: 0.75rem; }

        .media-item .btn-icon:not(.btn-more),
        .season-item .btn-icon:not(.btn-more),
        .media-item .download-btn,
        .media-item .watch-btn,
        .media-item .rating-btn,
        .media-item .comment-btn,
        .media-item .prequel-btn,
        .media-item .edit-movie,
        .media-item .delete-movie,
        .media-item .edit-series,
        .media-item .delete-series,
        .season-item .download-btn,
        .season-item .watch-btn,
        .season-item .rating-btn,
        .season-item .comment-btn,
        .season-item .edit-season,
        .season-item .delete-season {
          display: none !important;
        }
        .btn-more { display: inline-flex !important; }

        #starRating { gap: 0.25rem !important; }
        #starRating .star { font-size: 1.35rem !important; }
        #ratingModal .modal-content { max-width: 380px; }

        #listDetailModal .modal-footer #shareListBtn span.share-btn-text,
        #listDetailModal .modal-footer #shareListBtn .share-text { display: none; }
        #listDetailModal .modal-footer #shareListBtn {
          padding: 0.5rem 0.6rem;
          gap: 0;
          font-size: 0;
        }

        .modal-content {
          max-width: 95% !important;
          max-height: 90vh;
        }
        .prequel-timeline-modal {
          max-width: 95% !important;
          max-height: 90vh;
          border-radius: 12px;
        }
        .prequel-header-badges {
          flex-direction: column !important;
          align-items: flex-start !important;
          gap: 0.35rem !important;
        }
        .prequel-tabs .tab {
          padding: 0.5rem 0.75rem;
          font-size: 0.8rem;
          gap: 6px;
        }
        .compact-prequel-drawer {
          padding: 0.85rem;
        }
        .compact-type-selector .prequel-type-btn,
        .compact-related-selector .prequel-related-btn {
          padding: 0.4rem 0.5rem;
          font-size: 0.75rem;
        }
        .prequel-release-item .release-watch-btn,
        .prequel-release-item .release-rating-btn,
        .prequel-release-item .release-comment-btn,
        .prequel-release-item .release-delete-btn {
          display: none !important;
        }
        .prequel-release-item .release-more-btn {
          display: inline-flex !important;
        }
        .season-item { flex-wrap: wrap; }
        .season-item .flex.gap-2 { flex-shrink: 0; }
      }

      @media (min-width: 769px) {
        .btn-more { display: none !important; }
        .media-item .btn-icon,
        .season-item .btn-icon,
        .media-item .download-btn,
        .media-item .watch-btn,
        .media-item .rating-btn,
        .media-item .comment-btn,
        .media-item .prequel-btn,
        .media-item .edit-movie,
        .media-item .delete-movie,
        .media-item .edit-series,
        .media-item .delete-series,
        .season-item .download-btn,
        .season-item .watch-btn,
        .season-item .rating-btn,
        .season-item .comment-btn,
        .season-item .edit-season,
        .season-item .delete-season {
          display: inline-flex !important;
        }
        #listDetailModal .tab span.tab-text,
        #sharedListDetailModal .tab span.tab-text { display: inline; }
        #listDetailModal .tab,
        #sharedListDetailModal .tab {
          font-size: 0.875rem;
          gap: 10px;
        }
      }
    /* ==================== PREQUEL TIMELINE STYLES ==================== */
      .prequel-timeline-modal {
        width: 100%;
        max-width: 750px !important;
        max-height: 90vh;
        border-radius: 12px;
      }

      .prequel-tabs {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border);
        margin-bottom: 1.25rem;
      }

      .prequel-tabs .tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.6rem 1.25rem;
        background: transparent;
        border: none;
        color: var(--muted-foreground);
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        border-radius: 0;
      }

      .prequel-tabs .tab:hover {
        color: var(--foreground);
      }

      .prequel-tabs .tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        font-weight: 600;
        background: transparent;
        box-shadow: none;
      }

      .compact-prequel-drawer {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.15rem;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        animation: drawerSlideDown 0.2s ease-out;
      }

      @keyframes drawerSlideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .compact-type-selector,
      .compact-related-selector {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0;
      }

      .compact-type-selector .prequel-type-btn,
      .compact-related-selector .prequel-related-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--secondary);
        color: var(--foreground);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s ease;
      }

      .compact-type-selector .prequel-type-btn:hover,
      .compact-related-selector .prequel-related-btn:hover {
        border-color: var(--primary);
      }

      .compact-type-selector .prequel-type-btn.active,
      .compact-related-selector .prequel-related-btn.active {
        background: var(--primary);
        color: #ffffff;
        border-color: var(--primary);
      }

      .prequel-release-item {
        border-radius: 12px !important;
        border: 1px solid var(--border) !important;
        background: var(--card) !important;
        padding: 0.85rem 1rem !important;
        transition: border-color 0.2s, box-shadow 0.2s;
      }
      .prequel-release-item:hover {
        border-color: var(--primary) !important;
      }
      .prequel-release-item .media-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
      }
      .prequel-release-item .release-more-btn {
        display: none;
      }

      /* SVG Timeline */
      .timeline-canvas-wrap {
        position: relative;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0.5rem 0;
      }

      .vertical-s-svg {
        width: 100%;
        max-width: 520px;
        height: auto;
        overflow: visible;
      }

      /* تمیز و بدون هاور پرتاب‌شونده! بدون transform scale */
      .s-node {
        cursor: pointer;
        outline: none;
      }

      .s-node circle.core {
        transition: stroke 0.25s ease, stroke-width 0.25s ease, filter 0.25s ease;
      }

      .s-node:hover circle.core,
      .s-node:focus circle.core {
        stroke: #ffffff !important;
        stroke-width: 4.5px !important;
        filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.85));
      }

      .s-node.active circle.core {
        stroke: #ffffff !important;
        stroke-width: 5px !important;
        filter: drop-shadow(0 0 12px rgba(99, 102, 241, 1));
      }

      .s-node .node-text-title {
        font-size: 11px;
        font-weight: 500;
        fill: var(--foreground);
        text-anchor: middle;
        pointer-events: none;
        transition: fill 0.2s ease, font-weight 0.2s ease;
      }

      .s-node:hover .node-text-title {
        fill: var(--primary) !important;
        font-weight: 700;
      }

      .s-node .node-text-meta {
        font-size: 9px;
        fill: var(--muted-foreground);
        text-anchor: middle;
        pointer-events: none;
      }

      /* Popover Tooltip */
      .popover-tooltip {
        position: absolute;
        z-index: 1050;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
        padding: 0.85rem;
        width: 280px;
        max-width: calc(100vw - 32px);
        direction: rtl;
        text-align: right;
        display: none;
        animation: popoverFadeIn 0.18s ease-out;
      }

      @keyframes popoverFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
      }

      /* Drag & Drop Story List */
      .story-reorder-list {
        max-height: 220px;
        overflow-y: auto;
      }

      .story-drag-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.45rem 0.65rem;
        background: var(--secondary);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.8rem;
        transition: all 0.15s;
        cursor: grab;
        user-select: none;
      }

      .story-drag-row:hover {
        border-color: var(--primary);
        background: var(--card);
      }

      .story-drag-row.dragging {
        opacity: 0.35;
        border: 2px dashed var(--primary);
      }

      .story-drag-row.drag-over {
        border-color: var(--primary);
        transform: translateY(2px);
      }

      .story-drag-row .drag-handle {
        color: var(--muted-foreground);
        font-size: 1.1rem;
        cursor: grab;
        padding-left: 0.3rem;
        line-height: 1;
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
            <button id="openSharedListModalBtn" class="btn-icon" data-tooltip="مشاهده لیست اشتراکی" title="مشاهده لیست اشتراکی">
              <i data-lucide="search"></i>
            </button>
            <a href="all_titles.php" class="nav-link">
              <i data-lucide="list"></i><span>همه عناوین</span>
            </a>
            <a href="dash.php" class="nav-link active">
              <i data-lucide="layout-grid"></i><span>داشبورد</span>
            </a>
            <a href="user.php" class="nav-link">
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
        <span>شما در حال حاضر در حالت آفلاین هستید. لیست‌ها و عناوین به صورت فقط‌خواندنی نمایش داده می‌شوند.</span>
      </div>
      <div class="lists-header">
        <div class="lists-header-top">
          <div class="lists-title">
            <i data-lucide="list"></i>
            <h2>لیست‌های من</h2>
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <button id="openGenreModalBtn" class="btn-icon" data-tooltip="کتابخانه ژانرها" title="کتابخانه ژانرها">
              <i data-lucide="tag"></i>
            </button>
            <button id="addGroupBtn" class="btn btn-secondary" data-tooltip="گروه‌بندی چند لیست" title="گروه‌بندی چند لیست">
              <i data-lucide="link-2"></i>گروه جدید
            </button>
            <button id="addListBtn" class="btn btn-primary" data-tooltip="ساخت لیست جدید" title="ساخت لیست جدید">
              <i data-lucide="plus-square"></i>لیست جدید
            </button>
          </div>
        </div>
        <div class="lists-header-bottom">
          <input type="text" id="globalSearch" class="search-input" placeholder="جستجو عناوین و ژانر در بین همه لیست‌ها...">
        </div>
      </div>
      <div id="listsContainer" class="cards-grid">
        <div class="loading-center">
          <div class="loading"></div>
          <p class="mt-2">در حال بارگذاری...</p>
        </div>
      </div>
    </main>

    <!-- ==================== FOOTER ==================== -->
    <footer class="site-footer">
      <div class="container">
        <div class="footer-content">
          <span class="footer-text">
            <strong style="color: var(--primary);">MAHDYAR SHAHPARI - 2026</strong>
            DEVELOPED BY
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

    <!-- ==================== MODAL: ADD/EDIT LIST ==================== -->
    <div id="listModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="listModalTitle">
            <i data-lucide="layers"></i>
            <span>ساخت لیست جدید</span>
          </h3>
          <button class="btn-icon close-list-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="listModalAlert" class="alert"></div>
          <input type="hidden" id="editListId">
          <div class="form-group">
            <label class="form-label">نام لیست</label>
            <input type="text" id="listName" class="form-input" placeholder="مثلاً: فیلم های دیدنی">
          </div>
          <div class="form-group">
            <label class="form-label">رنگ شاخص لیست</label>
            <div style="display: flex; align-items: center; gap: 10px;">
              <input type="color" id="listColor" value="#0891b2" style="width: 50px; height: 40px; border-radius: 12px; border: 1px solid var(--border);">
              <span id="colorPreview" style="padding: 0.5rem 1.5rem; background: #0891b2; color: white; border-radius: 12px;">پیش‌نمایش</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">توضیحات (اختیاری)</label>
            <textarea id="listDescription" class="form-input" rows="3" placeholder="توضیحات درباره این لیست..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-list-modal">انصراف</button>
          <button id="saveListBtn" class="btn btn-primary">ذخیره</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: LIST DETAIL ==================== -->
    <div id="listDetailModal" class="modal">
      <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header" style="flex-direction: column; align-items: stretch;">
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <i data-lucide="layers"></i>
              <h3 id="detailListTitle">جزئیات لیست</h3>
            </div>
            <button class="btn-icon close-detail-modal">
              <i data-lucide="x"></i>
            </button>
          </div>
          <div class="flex gap-2" style="width: 100%; margin-top: 0.75rem;">
            <input type="text" id="listSearch" class="search-input" placeholder="جستجو در این لیست...">
          </div>
          <div class="tabs" style="width: 100%; margin-top: 0.5rem; margin-bottom: -1rem; border-bottom: none;">
            <button class="tab active" data-tab="movies">
              <i data-lucide="film"></i> <span class="tab-text">فیلم‌ها</span>
            </button>
            <button class="tab" data-tab="series">
              <i data-lucide="tv"></i> <span class="tab-text">سریال‌ها</span>
            </button>
            <button class="tab" data-tab="list-comments">
              <i data-lucide="message-square"></i> <span class="tab-text">کامنت‌ها</span> (<span id="listCommentsCount">0</span>)
            </button>
          </div>
        </div>
        <div class="modal-body">
          <div id="detailAlert" class="alert"></div>
          <div id="moviesTab">
            <div class="flex justify-end mb-4">
              <button id="addMovieBtn" class="btn btn-secondary">
                <i data-lucide="plus-square"></i>افزودن فیلم
              </button>
            </div>
            <div id="moviesList"></div>
          </div>
          <div id="seriesTab" style="display:none;">
            <div class="flex justify-end mb-4">
              <button id="addSeriesBtn" class="btn btn-secondary">
                <i data-lucide="plus-square"></i>افزودن سریال
              </button>
            </div>
            <div id="seriesList"></div>
          </div>
          <div id="listCommentsTab" style="display:none;">
            <div id="listCommentsList">
              <div class="loading-center">
                <div class="loading"></div>
                <p>در حال بارگذاری نظرات...</p>
              </div>
            </div>
            <div id="listCommentsPagination" class="flex justify-center gap-2 mt-4"></div>
            <?php if (isLoggedIn()): ?>
            <div class="comment-form" id="listCommentForm">
              <textarea id="listNewComment" maxlength="1000" placeholder="نظر خود را برای این لیست بنویسید... (حداکثر ۱۰۰۰ کاراکتر)"></textarea>
              <div class="comment-char-count">
                <span id="listCharCount">0</span> / 1000
              </div>
              <div class="flex justify-end mt-2">
                <button id="listSubmitCommentBtn" class="btn btn-primary">ارسال نظر</button>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="modal-footer">
          <div class="export-buttons">
            <button class="btn btn-secondary" id="exportPngBtn" onclick="exportList('png')" data-tooltip="خروجی تصویر باکیفیت PNG" title="خروجی تصویر باکیفیت PNG">
              <i data-lucide="image"></i> PNG
            </button>
            <button class="btn btn-secondary" id="exportPdfBtn" onclick="exportList('pdf')" data-tooltip="خروجی فایل چندصفحه‌ای PDF" title="خروجی فایل چندصفحه‌ای PDF">
              <i data-lucide="file-text"></i> PDF
            </button>
          </div>
          <button class="btn btn-secondary" id="shareListBtn" style="background: #8b5cf620; border-color: #8b5cf6; color: #8b5cf6;">
            <i data-lucide="share-2"></i> <span class="share-btn-text">اشتراک‌گذاری</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: SHARE CODE ==================== -->
    <div id="shareModal" class="modal">
      <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
          <h3>
            <i data-lucide="share-2"></i>
            <span>اشتراک‌گذاری لیست</span>
          </h3>
          <button class="btn-icon close-share-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="shareAlert" class="alert"></div>
          <p style="margin-bottom: 1rem; font-size: 0.85rem; color: var(--muted-foreground);">
            با اشتراک‌گذاری کد زیر، دیگران می‌توانند لیست شما را به صورت فقط خواندنی مشاهده کنند.
          </p>
          <div style="background: var(--secondary); border-radius: 12px; padding: 1rem; text-align: center;">
            <code id="shareCodeDisplay" style="font-size: 1.2rem; font-weight: 700; letter-spacing: 1px; direction: ltr; display: inline-block;"></code>
            <button id="copyShareCodeBtn" class="btn-icon" style="margin-right: 0.5rem; padding: 0.25rem 0.5rem;">
              <i data-lucide="copy"></i>
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button id="regenerateShareCodeBtn" class="btn btn-secondary" style="color: var(--destructive);">بازسازی کد</button>
          <button class="btn btn-primary close-share-modal">بستن</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: GROUP (ADD/EDIT) ==================== -->
    <div id="groupModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="groupModalTitle">
            <i data-lucide="link-2"></i>
            <span>ساخت گروه جدید</span>
          </h3>
          <button class="btn-icon close-group-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="groupModalAlert" class="alert"></div>
          <input type="hidden" id="editGroupId">
          <div class="form-group">
            <label class="form-label">نام گروه</label>
            <input type="text" id="groupName" class="form-input" placeholder="مثلاً: دنیای مارول">
          </div>
          <div class="form-group">
            <label class="form-label">رنگ شاخص گروه</label>
            <div style="display: flex; align-items: center; gap: 10px;">
              <input type="color" id="groupColor" value="#0891b2" style="width: 50px; height: 40px; border-radius: 12px; border: 1px solid var(--border);">
              <span id="groupColorPreview" style="padding: 0.5rem 1.5rem; background: #0891b2; color: white; border-radius: 12px;">پیش‌نمایش</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">توضیحات (اختیاری)</label>
            <textarea id="groupDescription" class="form-input" rows="3" placeholder="توضیحات درباره این گروه..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-group-modal">انصراف</button>
          <button id="saveGroupBtn" class="btn btn-primary">ذخیره</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: GROUP DETAIL ==================== -->
    <div id="groupDetailModal" class="modal">
      <div class="modal-content" style="max-width: 720px;">
        <div class="modal-header">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="link-2"></i>
            <h3 id="groupDetailTitle">جزئیات گروه</h3>
          </div>
          <button class="btn-icon close-group-detail-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="groupDetailAlert" class="alert"></div>
          <div id="groupDetailLists" class="cards-grid" style="margin-top: 0; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));"></div>
        </div>
        <div class="modal-footer">
          <button id="editGroupFromDetailBtn" class="btn btn-secondary">
            <i data-lucide="pencil"></i> ویرایش گروه
          </button>
          <button class="btn btn-secondary close-group-detail-modal">بستن</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: GROUP MANAGE ==================== -->
    <div id="groupManageModal" class="modal">
      <div class="modal-content" style="max-width: 620px;">
        <div class="modal-header">
          <h3 id="groupManageTitle">
            <i data-lucide="pencil"></i>
            <span>مدیریت گروه</span>
          </h3>
          <button class="btn-icon close-group-manage-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="groupManageAlert" class="alert"></div>

          <div style="background: var(--secondary); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
            <div style="font-size: 0.8rem; font-weight: 600; color: var(--primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
              <i data-lucide="pencil" style="width:16px;height:16px;"></i>
              ویرایش اطلاعات گروه
            </div>
            <input type="hidden" id="manageGroupId">
            <div class="form-group" style="margin-bottom: 0.75rem;">
              <label class="form-label" style="font-size: 0.8rem;">نام گروه</label>
              <input type="text" id="manageGroupName" class="form-input" placeholder="نام گروه">
            </div>
            <div class="form-group" style="margin-bottom: 0.75rem;">
              <label class="form-label" style="font-size: 0.8rem;">رنگ شاخص</label>
              <div style="display: flex; align-items: center; gap: 10px;">
                <input type="color" id="manageGroupColor" value="#0891b2" style="width: 45px; height: 36px; border-radius: 10px; border: 1px solid var(--border);">
                <span id="manageGroupColorPreview" style="padding: 0.4rem 1.25rem; background: #0891b2; color: white; border-radius: 10px; font-size: 0.75rem;">پیش‌نمایش</span>
              </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label" style="font-size: 0.8rem;">توضیحات (اختیاری)</label>
              <textarea id="manageGroupDescription" class="form-input" rows="2" placeholder="توضیحات گروه..."></textarea>
            </div>
          </div>

          <div style="font-size: 0.8rem; font-weight: 600; color: var(--primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
            <i data-lucide="list" style="width:16px;height:16px;"></i>
            لیست‌های عضو گروه
          </div>
          <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.75rem;">
            لیست‌های موردنظر را برای عضویت در این گروه انتخاب کنید.
          </p>
          <div id="groupManageLists"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-group-manage-modal">انصراف</button>
          <button id="saveGroupManageBtn" class="btn btn-primary">
            <i data-lucide="check"></i>
            ذخیره تغییرات
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: GENRE LIBRARY ==================== -->
    <!-- مودال کتابخانه و مدیریت ژانرها -->
    <div id="genreModal" class="modal">
      <div class="modal-content" style="max-width: 800px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="flex-shrink: 0; padding-bottom: 0.75rem;">
          <div style="display: flex; align-items: center; gap: 0.6rem;">
            <i data-lucide="folder"></i>
            <div>
              <h3 style="margin: 0; font-size: 1.15rem;">کتابخانه و مدیریت ژانرها</h3>
              <p style="margin: 0; font-size: 0.72rem; color: var(--muted-foreground);">۳۰ دسته ژانر استاندارد سینمایی (فارسی و انگلیسی) و ابزار هوشمند یکپارچه‌سازی عناوین</p>
            </div>
          </div>
          <button class="btn-icon close-genre-modal">
            <i data-lucide="x"></i>
          </button>
        </div>

        <!-- تب‌های سوئیچ -->
        <div style="display: flex; gap: 0.5rem; padding: 0.4rem 1.25rem 0; border-bottom: 1px solid var(--border); flex-shrink: 0; background: var(--secondary)/30;">
          <button id="genreTabLibraryBtn" class="genre-tab-btn active" style="padding: 0.5rem 1rem; border: none; background: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; border-bottom: 2px solid var(--primary); color: var(--primary); display: flex; align-items: center; gap: 0.4rem;">
            <i data-lucide="layers-2"></i> ۳۰ ژانر استاندارد
          </button>
          <button id="genreTabAuditBtn" class="genre-tab-btn" style="padding: 0.5rem 1rem; border: none; background: none; font-size: 0.85rem; font-weight: 500; cursor: pointer; border-bottom: 2px solid transparent; color: var(--muted-foreground); display: flex; align-items: center; gap: 0.4rem;">
            <i data-lucide="check-circle"></i> بررسی و یکپارچه‌سازی عناوین
            <span id="genreAuditBadge" style="display: none; background: #ef4444; color: #fff; font-size: 0.65rem; padding: 0.1rem 0.45rem; border-radius: 10px; font-weight: 700;">۰</span>
          </button>
        </div>

        <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 1.25rem;">
          <div id="genreModalAlert" class="alert"></div>

          <!-- پنل تب ۱: ۳۰ ژانر استاندارد -->
          <div id="genreLibraryTabPane">
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; align-items: center; flex-wrap: wrap;">
              <div style="position: relative; flex: 1; min-width: 220px;">
                <input type="text" id="searchGenreLibraryInput" class="form-input" placeholder="جستجو در ۳۰ ژانر (فارسی، انگلیسی یا معادل)..." style="width: 100%; padding-left: 2rem;">
                <i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--muted-foreground); width: 16px; height: 16px;"></i>
              </div>
              <div style="display: flex; gap: 0.35rem;">
                <button type="button" class="btn btn-secondary btn-sm genre-filter-btn active" data-filter="all">همه (۳۰)</button>
                <button type="button" class="btn btn-secondary btn-sm genre-filter-btn" data-filter="used">استفاده‌شده‌ها</button>
              </div>
            </div>
            <div id="genreLibraryList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.65rem;"></div>
          </div>

          <!-- پنل تب ۲: بررسی و یکپارچه‌سازی عناوین (Audit & Fixer) -->
          <div id="genreAuditTabPane" style="display: none;">
            <div id="genreAuditHeader" style="margin-bottom: 1rem; padding: 0.9rem 1.1rem; border-radius: 12px; border: 1px solid var(--border); background: var(--card);">
              <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                  <div id="genreAuditSummaryTitle" style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.25rem;">در حال بررسی عناوین...</div>
                  <div id="genreAuditSummarySub" style="font-size: 0.75rem; color: var(--muted-foreground);">بررسی تفاوت‌های املایی، نگارشی و ژانرهای خارج از ۳۰ دسته اصلی</div>
                </div>
                <div id="genreAuditActions" style="display: flex; gap: 0.5rem; align-items: center;">
                  <button id="refreshGenreAuditBtn" class="btn btn-secondary btn-sm" title="بررسی مجدد">
                    <i data-lucide="refresh-cw"></i> بازبینی
                  </button>
                  <button id="batchFixAllGenresBtn" class="btn btn-primary btn-sm" style="display: none; background: #10b981; border-color: #10b981;">
                    <i data-lucide="check"></i> اصلاح خودکار همه موارد پیشنهادی
                  </button>
                </div>
              </div>
            </div>

            <div id="genreAuditResultsContainer"></div>
          </div>
        </div>
      </div>
    </div>

    <div id="deleteModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>
            <i data-lucide="alert-triangle" style="color: var(--destructive);"></i>
            <span>تایید حذف</span>
          </h3>
          <button class="btn-icon close-delete-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <p id="deleteMessage">آیا از حذف این مورد مطمئن هستید؟</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-delete-modal">انصراف</button>
          <button id="confirmDeleteBtn" class="btn btn-primary" style="background: var(--destructive);">حذف</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: MOVIE ==================== -->
    <div id="movieModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="movieModalTitle">
            <i data-lucide="plus-square"></i>
            <span>افزودن فیلم</span>
          </h3>
          <button class="btn-icon close-movie-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="movieModalAlert" class="alert"></div>
          <input type="hidden" id="editMovieId">
          <div class="form-group">
            <label class="form-label">عنوان فیلم *</label>
            <input type="text" id="movieTitle" class="form-input" placeholder="مثلاً: Inception">
          </div>
          <div class="form-group">
            <label class="form-label">سال ساخت</label>
            <input type="number" id="movieYear" class="form-input" placeholder="مثلاً: 2010">
          </div>
          <div class="form-group">
            <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
              <span>ژانرها (حداکثر ۳ مورد)</span>
              <span class="genre-counter" id="movieGenreCounter" style="margin:0; font-size:0.75rem;">۰ از ۳</span>
            </label>
            <div class="genre-field-wrapper" style="position: relative;">
              <div class="genre-tags-container" id="movieGenreContainer">
                <input type="text" class="genre-input" id="movieGenreInput" placeholder="تایپ ۲-۳ حرف فارسی یا انگلیسی..." autocomplete="off">
              </div>
            </div>
            <small style="display:block; margin-top:0.35rem; font-size:0.72rem; color:var(--muted-foreground);">
              💡 با تایپ ۲ یا ۳ حرف (فارسی یا انگلیسی) ژانر را جستجو و انتخاب کنید.
            </small>
            <input type="hidden" id="movieGenre" value="">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-movie-modal">انصراف</button>
          <button id="saveMovieBtn" class="btn btn-primary">ذخیره</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: SERIES ==================== -->
    <div id="seriesModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="seriesModalTitle">
            <i data-lucide="plus-square"></i>
            <span>افزودن سریال</span>
          </h3>
          <button class="btn-icon close-series-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="seriesModalAlert" class="alert"></div>
          <input type="hidden" id="editSeriesId">
          <div class="form-group">
            <label class="form-label">عنوان سریال *</label>
            <input type="text" id="seriesTitle" class="form-input" placeholder="مثلاً: Stranger Things">
          </div>
          <div class="form-group">
            <label class="form-label">سال شروع</label>
            <input type="number" id="seriesStartYear" class="form-input" placeholder="مثلاً: 2016">
          </div>
          <div class="form-group">
            <label class="form-label">سال پایان</label>
            <input type="number" id="seriesEndYear" class="form-input" placeholder="در حال پخش: خالی">
          </div>
          <div class="form-group">
            <label class="form-label">وضعیت پخش</label>
            <select id="seriesStatus" class="form-select">
              <option value="ongoing">در حال پخش</option>
              <option value="completed">پایان یافته</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
              <span>ژانرها (حداکثر ۳ مورد)</span>
              <span class="genre-counter" id="seriesGenreCounter" style="margin:0; font-size:0.75rem;">۰ از ۳</span>
            </label>
            <div class="genre-field-wrapper" style="position: relative;">
              <div class="genre-tags-container" id="seriesGenreContainer">
                <input type="text" class="genre-input" id="seriesGenreInput" placeholder="تایپ ۲-۳ حرف فارسی یا انگلیسی..." autocomplete="off">
              </div>
            </div>
            <small style="display:block; margin-top:0.35rem; font-size:0.72rem; color:var(--muted-foreground);">
              💡 با تایپ ۲ یا ۳ حرف (فارسی یا انگلیسی) ژانر را جستجو و انتخاب کنید.
            </small>
            <input type="hidden" id="seriesGenre" value="">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-series-modal">انصراف</button>
          <button id="saveSeriesBtn" class="btn btn-primary">ذخیره</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: SEASONS ==================== -->
    <div id="seasonModal" class="modal">
      <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
          <h3 id="seasonModalTitle">
            <i data-lucide="plus-square"></i>
            <span>افزودن فصل‌ها</span>
          </h3>
          <button class="btn-icon close-season-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="seasonModalAlert" class="alert"></div>
          <input type="hidden" id="editSeasonId">
          <input type="hidden" id="seasonSeriesId">
          <div id="duplicateSeasonWarning" class="alert alert-error" style="display: none; margin-bottom: 1rem;">⚠️ شماره فصل تکراری! لطفاً شماره‌های منحصربه‌فرد وارد کنید.</div>
          <div id="existingSeasonsList" style="margin-bottom: 1.5rem;"></div>
          <div id="seasonsInputsContainer">
            <div class="season-row flex gap-2 mb-2" style="align-items: flex-end;">
              <div class="form-group" style="width: 120px; margin-bottom: 0;">
                <label class="form-label">شماره فصل</label>
                <input type="number" class="form-input season-number" placeholder="مثلاً 1" min="1" step="1">
              </div>
              <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label class="form-label">تعداد قسمت‌ها</label>
                <input type="number" class="form-input season-episodes" placeholder="مثلاً 10" min="0" step="1">
              </div>
              <button type="button" class="btn-icon remove-season-row" style="color: var(--destructive); flex-shrink: 0; visibility: hidden;">
                <i data-lucide="x"></i>
              </button>
            </div>
          </div>
          <button id="addMoreSeasonBtn" class="btn btn-outline w-full" style="margin-top: 0.75rem;">
            <i data-lucide="plus-square"></i> افزودن فصل دیگر
          </button>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-season-modal">انصراف</button>
          <button id="saveSeasonsBtn" class="btn btn-primary">ذخیره همه فصل‌ها</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: WATCH DATE ==================== -->
    <div id="watchDateModal" class="modal">
      <div class="modal-content" style="max-width: 420px;">
        <div class="modal-header">
          <h3>
            <i data-lucide="eye"></i>
            <span>تاریخ تماشا</span>
          </h3>
          <button class="btn-icon close-watchdate-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body" style="text-align: center;">
          <div id="watchDateAlert" class="alert"></div>
          <input type="hidden" id="watchTargetType">
          <input type="hidden" id="watchTargetId">
          <input type="hidden" id="watchAction">
          <div class="date-picker-container" style="margin: 0 auto;">
            <div class="date-picker-label" style="color: var(--foreground); margin-bottom: 1rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 10px;">
              <i data-lucide="calendar"></i>
              <span>تاریخ تماشا را انتخاب کنید</span>
            </div>
            <div class="picker-wheels" style="display: flex; gap: 0.5rem; justify-content: center;">
              <div class="wheel-container" style="position: relative; width: 80px; height: 180px; overflow: hidden; background: var(--card); border-radius: 12px;">
                <div class="wheel-label">روز</div>
                <div class="selection-indicator" style="position: absolute; top: 50%; left: 10%; right: 10%; height: 36px; transform: translateY(-50%); border-top: 1px solid var(--primary); border-bottom: 1px solid var(--primary); pointer-events: none; z-index: 2;"></div>
                <div class="wheel" id="dayWheel" style="height: calc(100% - 28px); overflow-y: auto; scroll-snap-type: y mandatory; padding: 72px 0; scrollbar-width: none; -ms-overflow-style: none;"></div>
              </div>
              <div class="wheel-container" style="position: relative; width: 100px; height: 180px; overflow: hidden; background: var(--card); border-radius: 12px;">
                <div class="wheel-label">ماه</div>
                <div class="selection-indicator" style="position: absolute; top: 50%; left: 10%; right: 10%; height: 36px; transform: translateY(-50%); border-top: 1px solid var(--primary); border-bottom: 1px solid var(--primary); pointer-events: none; z-index: 2;"></div>
                <div class="wheel" id="monthWheel" style="height: calc(100% - 28px); overflow-y: auto; scroll-snap-type: y mandatory; padding: 72px 0; scrollbar-width: none; -ms-overflow-style: none;"></div>
              </div>
              <div class="wheel-container" style="position: relative; width: 90px; height: 180px; overflow: hidden; background: var(--card); border-radius: 12px;">
                <div class="wheel-label">سال</div>
                <div class="selection-indicator" style="position: absolute; top: 50%; left: 10%; right: 10%; height: 36px; transform: translateY(-50%); border-top: 1px solid var(--primary); border-bottom: 1px solid var(--primary); pointer-events: none; z-index: 2;"></div>
                <div class="wheel" id="yearWheel" style="height: calc(100% - 28px); overflow-y: auto; scroll-snap-type: y mandatory; padding: 72px 0; scrollbar-width: none; -ms-overflow-style: none;"></div>
              </div>
            </div>
            <div id="watchDateDisplay" style="margin: 1rem 0; font-size: 1.1rem; font-weight: 700; color: var(--primary); min-height: 1.5rem;">۱۵ فروردین ۱۴۰۴</div>
          </div>
        </div>
        <div class="modal-footer">
          <button id="watchWithoutDateBtn" class="btn btn-secondary">بدون تاریخ</button>
          <button id="watchTodayBtn" class="btn btn-secondary">امروز</button>
          <button id="confirmWatchBtn" class="btn btn-primary">ثبت</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: PREQUEL / TIMELINE ==================== -->
    <div id="prequelModal" class="modal">
      <div class="modal-content prequel-timeline-modal" style="display: flex; flex-direction: column;">
        <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
          <div class="flex items-center gap-2.5" style="min-width: 0; flex: 1;">
            <i data-lucide="link-2" class="text-primary" style="flex-shrink: 0; width: 22px; height: 22px;"></i>
            <div style="min-width: 0; flex: 1;">
              <h3 id="prequelModalTitle" style="margin: 0; font-size: 1.05rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">خط زمانی و عناوین مرتبط</h3>
              <div class="prequel-header-badges flex flex-wrap items-center gap-2" style="margin-top: 0.35rem;">
                <span class="badge" id="prequelMainBadge" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.73rem; padding: 0.2rem 0.55rem; border-radius: 6px; background: var(--secondary); color: var(--foreground); border: 1px solid var(--border);">
                  <i data-lucide="film" style="width: 13px; height: 13px;"></i>
                  <span id="prequelMainTitleText">عنوان مرجع</span>
                </span>
                <span class="badge" id="prequelCountBadgeHeader" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.73rem; padding: 0.2rem 0.55rem; border-radius: 6px; background: var(--secondary); color: var(--foreground); border: 1px solid var(--border);">
                  <i data-lucide="layers" style="width: 13px; height: 13px;"></i>
                  <span id="prequelCountBadge">۰ عنوان مرتبط</span>
                </span>
              </div>
            </div>
          </div>
          <button type="button" class="btn-icon close-prequel-modal" title="بستن" style="flex-shrink: 0;">
            <i data-lucide="x"></i>
          </button>
        </div>

        <div class="modal-body" style="flex: 1; overflow-y: auto; padding: 1.25rem; position: relative;">
          <!-- نوار بالایی: تب‌ها و دکمه افزودن عنوان -->
          <div class="prequel-top-bar flex items-center justify-between gap-3 mb-4" style="flex-wrap: wrap;">
            <div class="prequel-tabs">
              <button type="button" class="tab active" id="tabStoryTimeline" data-tooltip="خط زمانی داستانی">
                <i data-lucide="layers-2"></i>
                <span class="tab-text">خط زمانی داستانی (مارپیچ)</span>
              </button>
              <button type="button" class="tab" id="tabReleaseTimeline" data-tooltip="ترتیب تاریخ انتشار">
                <i data-lucide="calendar"></i>
                <span class="tab-text">ترتیب تاریخ انتشار (لیست)</span>
              </button>
            </div>
            <button type="button" id="toggleAddPrequelBtn" class="btn btn-primary" style="padding: 0.5rem 0.95rem; font-size: 0.85rem; gap: 0.4rem; border-radius: 8px;">
              <i data-lucide="plus"></i>
              <span id="toggleAddPrequelText">افزودن عنوان</span>
            </button>
          </div>

          <div id="prequelAlert" class="alert" style="display: none; margin-bottom: 1rem;"></div>
          <input type="hidden" id="prequelMediaType">
          <input type="hidden" id="prequelMediaId">

          <!-- فرم کشویی فشرده افزودن عنوان -->
          <div id="compactAddPrequelDrawer" class="compact-prequel-drawer" style="display: none; margin-bottom: 1.25rem;">
            <div class="flex items-center justify-between mb-3">
              <span style="font-weight: 600; font-size: 0.88rem; color: var(--foreground); display: flex; align-items: center; gap: 0.4rem;">
                <i data-lucide="plus-circle"></i>
                افزودن عنوان جدید به مجموعه و خط زمانی
              </span>
              <button type="button" id="closeAddPrequelDrawerBtn" class="btn-icon" style="width: 28px; height: 28px;">
                <i data-lucide="x"></i>
              </button>
            </div>

            <!-- انتخاب نوع ارتباط و نوع اثر -->
            <div class="grid grid-cols-2 gap-3 mb-3">
              <div>
                <label class="form-label" style="font-size: 0.78rem; margin-bottom: 0.3rem;">نوع ارتباط با اثر اصلی</label>
                <div class="compact-type-selector">
                  <button type="button" class="prequel-type-btn prequel-btn-type active" data-type="prequel">
                    <i data-lucide="corner-down-left"></i>
                    <span>پیش‌درآمد</span>
                  </button>
                  <button type="button" class="prequel-type-btn sequel-btn-type" data-type="sequel">
                    <i data-lucide="corner-down-right"></i>
                    <span>دنباله</span>
                  </button>
                  <button type="button" class="prequel-type-btn collection-btn" data-type="collection">
                    <i data-lucide="layers-2"></i>
                    <span>مجموعه</span>
                  </button>
                </div>
              </div>
              <div>
                <label class="form-label" style="font-size: 0.78rem; margin-bottom: 0.3rem;">نوع اثر</label>
                <div class="compact-related-selector">
                  <button type="button" class="prequel-related-btn active" data-type="movie">
                    <i data-lucide="film"></i>
                    <span>فیلم</span>
                  </button>
                  <button type="button" class="prequel-related-btn" data-type="series">
                    <i data-lucide="tv"></i>
                    <span>سریال</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- مشخصات عنوان -->
            <div class="grid grid-cols-3 gap-3 mb-3">
              <div style="grid-column: span 2;">
                <label class="form-label" style="font-size: 0.78rem; margin-bottom: 0.3rem;">نام فیلم یا سریال</label>
                <input type="text" id="newPrequelTitle" class="form-input" placeholder="نام عنوان جدید...">
              </div>
              <div>
                <label class="form-label" style="font-size: 0.78rem; margin-bottom: 0.3rem;">سال تولید</label>
                <input type="number" id="newPrequelYear" class="form-input" placeholder="مثال: 2024">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
              <div>
                <label class="form-label" style="font-size: 0.78rem; margin-bottom: 0.3rem;">ژانر</label>
                <input type="text" id="newPrequelGenre" class="form-input" placeholder="ژانرها با کاما">
              </div>
              <div>
                <label class="form-label" style="font-size: 0.78rem; margin-bottom: 0.3rem;">امتیاز (از ۱۰)</label>
                <select id="newPrequelRating" class="form-input">
                  <option value="">بدون امتیاز</option>
                  <option value="10">۱۰ - شاهکار</option>
                  <option value="9">۹ - عالی</option>
                  <option value="8">۸ - بسیار خوب</option>
                  <option value="7">۷ - خوب</option>
                  <option value="6">۶ - بالاتر از متوسط</option>
                  <option value="5">۵ - متوسط</option>
                  <option value="4">۴ - ضعیف</option>
                  <option value="3">۳ - خیلی ضعیف</option>
                  <option value="2">۲ - بد</option>
                  <option value="1">۱ - فاجعه</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" style="font-size: 0.78rem; margin-bottom: 0.3rem;">دیدگاه یا یادداشت</label>
              <textarea id="newPrequelComment" class="form-input" rows="2" placeholder="دیدگاه اختیاری درباره این عنوان..."></textarea>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2" style="font-size: 0.82rem; cursor: pointer;">
                <input type="checkbox" id="newPrequelWatched" style="accent-color: var(--primary); width: 16px; height: 16px;">
                <span>تماشا شده است</span>
              </label>
              <button type="button" id="submitNewPrequelBtn" class="btn btn-primary" style="padding: 0.5rem 1.1rem; font-size: 0.85rem; gap: 0.4rem;">
                <i data-lucide="plus"></i>
                <span>ثبت و افزودن به خط زمانی</span>
              </button>
            </div>
          </div>

          <!-- ویو ۱: خط زمانی داستانی (مارپیچ S عمودی) -->
          <div id="storyTimelineView" class="story-timeline-wrap">
            <div class="timeline-canvas-wrap" id="spiralCanvasWrap">
              <svg id="verticalSvg" class="vertical-s-svg" viewBox="0 0 520 450">
                <defs>
                  <linearGradient id="verticalSGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.8"/>
                    <stop offset="45%" stop-color="#0891b2" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.8"/>
                  </linearGradient>
                </defs>

                <!-- خط مارپیچی عمودی مسیر اصلی -->
                <path id="sCurveMain"
                      d="M 120,40 C 390,40 400,160 250,225 C 95,290 100,410 380,415"
                      fill="none" stroke="url(#verticalSGradient)" stroke-width="2.5" stroke-dasharray="4,4" opacity="0.45" stroke-linecap="round"/>

                <!-- لایه نودها روی مارپیچ -->
                <g id="svgNodesLayer"></g>
              </svg>

              <!-- پاپ‌اور تعاملی نودها -->
              <div class="popover-tooltip" id="nodePopover">
                <div class="popover-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                  <span class="popover-type-badge" id="popoverTypeBadge" style="font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 6px; font-weight: 600; background: var(--secondary); color: var(--foreground);">پیش‌درآمد</span>
                  <button type="button" id="closePopoverBtn" class="btn-icon" style="width: 26px; height: 26px; border-radius: 6px;">
                    <i data-lucide="x"></i>
                  </button>
                </div>

                <div class="popover-title" id="popoverTitle" style="font-size: 0.95rem; font-weight: 700; color: var(--foreground); margin-bottom: 0.2rem;">عنوان فیلم</div>
                <div class="popover-meta" id="popoverMeta" style="font-size: 0.75rem; color: var(--muted-foreground); margin-bottom: 0.65rem;">
                  <span id="popoverYear">2022</span> • <span id="popoverGenres">اکشن</span>
                </div>

                <div class="popover-actions" style="display: flex; flex-direction: column; gap: 0.5rem;">
                  <button type="button" class="btn btn-outline w-full" id="popoverWatchBtn" style="justify-content: center; gap: 0.4rem; padding: 0.55rem 0.9rem; font-size: 0.82rem; border-radius: 8px;">
                    <i data-lucide="eye" style="width: 15px; height: 15px;"></i>
                    <span id="popoverWatchText">تماشا نشده</span>
                  </button>

                  <button type="button" class="btn btn-outline w-full" id="popoverRatingBtn" style="justify-content: space-between; align-items: center; padding: 0.55rem 0.85rem; font-size: 0.82rem; border-radius: 8px;">
                    <span style="display: flex; align-items: center; gap: 0.4rem;">
                      <i data-lucide="star" style="width: 15px; height: 15px; color: #f59e0b;"></i>
                      <span>امتیاز:</span>
                    </span>
                    <span id="popoverRatingText" style="font-weight: 700; color: #f59e0b;">بدون امتیاز</span>
                  </button>

                  <button type="button" class="btn btn-outline w-full" id="popoverCommentBtn" style="justify-content: space-between; align-items: center; padding: 0.55rem 0.85rem; font-size: 0.82rem; border-radius: 8px;">
                    <span style="display: flex; align-items: center; gap: 0.4rem;">
                      <i data-lucide="message-square" style="width: 15px; height: 15px; color: #8b5cf6;"></i>
                      <span>یادداشت:</span>
                    </span>
                    <span id="popoverCommentText" style="font-weight: 500; font-size: 0.78rem; color: var(--muted-foreground); max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">بدون یادداشت (ثبت)</span>
                  </button>

                  <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.6rem; margin-top: 0.35rem;">
                    <button type="button" class="btn btn-destructive" id="popoverDeleteBtn" style="width: 38px; height: 38px; padding: 0; display: inline-flex; justify-content: center; align-items: center; border-radius: 8px; flex-shrink: 0;" title="حذف">
                      <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="popoverSaveBtn" style="flex: 1; justify-content: center; padding: 0.55rem 0.9rem; font-size: 0.85rem; font-weight: 600; border-radius: 8px; gap: 0.35rem;">
                      <i data-lucide="check" style="width: 15px; height: 15px;"></i>
                      <span>ذخیره</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- بخش درگ و دراپ برای تغییر ترتیب عناوین داستانی -->
            <div class="reorder-section" style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 0.85rem;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <h4 style="font-size: 0.82rem; font-weight: 700; color: var(--foreground); display: flex; align-items: center; gap: 0.35rem;">
                  <i data-lucide="grip-vertical"></i>
                  <span>ترتیب خط داستانی (با Drag & Drop ترتیب را تغییر دهید):</span>
                </h4>
                <span style="font-size: 0.7rem; color: var(--muted-foreground);">جابجایی ردیف‌ها = تغییر ترتیب روی مارپیچ</span>
              </div>
              <div class="story-reorder-list" id="storyReorderList" style="display: flex; flex-direction: column; gap: 0.35rem; max-height: 200px; overflow-y: auto;"></div>
            </div>
          </div>

          <!-- ویو ۲: ترتیب تاریخ انتشار (لیست استاندارد) -->
          <div id="releaseTimelineView" class="release-timeline-wrap" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
              <span style="font-size: 0.78rem; color: var(--muted-foreground);">عناوین بر اساس تاریخ اکران (سال انتشار):</span>
              <span id="prequelCountBadge" class="badge" style="background: var(--secondary); font-size: 0.72rem; padding: 0.2rem 0.6rem; border-radius: 8px; font-weight: 700;">۰ عنوان</span>
            </div>
            <div id="releaseItemsList" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
          </div>

        </div>

        <div class="modal-footer" style="flex-shrink: 0; padding: 0.75rem 1.25rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 0.72rem; color: var(--muted-foreground); display: inline-flex; align-items: center; gap: 0.35rem;"><i data-lucide="lightbulb" style="width: 14px; height: 14px; color: #f59e0b; flex-shrink: 0;"></i> روی هر نقطه در مارپیچ یا ردیف‌ها در لیست کلیک کنید تا جزئیات را مدیریت نمایید.</span>
          <button type="button" class="btn btn-secondary close-prequel-modal" style="padding: 0.4rem 0.9rem; font-size: 0.8rem;">بستن</button>
        </div>
      </div>
    </div>

    <div id="ratingModal" class="modal">
      <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
          <h3>
            <i data-lucide="star"></i>
            <span>امتیازدهی</span>
          </h3>
          <button class="btn-icon close-rating-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="ratingAlert" class="alert"></div>
          <input type="hidden" id="ratingTargetType">
          <input type="hidden" id="ratingTargetId">
          <input type="hidden" id="ratingAction">
          <div style="text-align: center; margin-bottom: 1.5rem;">
            <label class="form-label" style="text-align: center; font-size: 1rem;">امتیاز شما</label>
            <div id="starRating" style="display: flex; justify-content: center; gap: 0.5rem; margin: 1rem 0; direction: ltr;">
              <span class="star" data-value="1">★</span>
              <span class="star" data-value="2">★</span>
              <span class="star" data-value="3">★</span>
              <span class="star" data-value="4">★</span>
              <span class="star" data-value="5">★</span>
              <span class="star" data-value="6">★</span>
              <span class="star" data-value="7">★</span>
              <span class="star" data-value="8">★</span>
              <span class="star" data-value="9">★</span>
              <span class="star" data-value="10">★</span>
            </div>
            <div id="ratingValue" style="font-size: 1.2rem; font-weight: 700; color: var(--primary); min-height: 1.5rem;"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button id="removeRatingBtn" class="btn btn-secondary" style="color: var(--destructive);">حذف امتیاز</button>
          <button id="confirmRatingBtn" class="btn btn-primary">ثبت امتیاز</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: COMMENT ==================== -->
    <div id="commentModal" class="modal">
      <div class="modal-content" style="max-width: 550px;">
        <div class="modal-header">
          <h3 id="commentModalTitle">
            <i data-lucide="message-square"></i>
            <span>کامنت</span>
          </h3>
          <button class="btn-icon close-comment-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="commentAlert" class="alert"></div>
          <input type="hidden" id="commentMediaType">
          <input type="hidden" id="commentMediaId">
          <input type="hidden" id="commentId">
          <div class="form-group">
            <label class="form-label">متن کامنت</label>
            <textarea id="commentText" class="form-input" rows="6" placeholder="نظر خود را بنویسید..." maxlength="1000" style="resize: vertical; min-height: 120px;"></textarea>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
              <span id="commentCharCount" style="font-size: 0.75rem; color: var(--muted-foreground);">۰ / ۱۰۰۰</span>
              <span id="commentCharWarning" style="font-size: 0.75rem; color: var(--destructive); display: none;">کامنت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button id="deleteCommentBtn" class="btn btn-secondary" style="color: var(--destructive); display: none;">
            <i data-lucide="trash-2"></i>
            حذف کامنت
          </button>
          <button class="btn btn-secondary close-comment-modal">انصراف</button>
          <button id="saveCommentBtn" class="btn btn-primary">ثبت کامنت</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: SHARED LIST INPUT ==================== -->
    <div id="sharedListModal" class="modal">
      <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
          <h3>
            <i data-lucide="search"></i>
            <span>مشاهده لیست اشتراکی</span>
          </h3>
          <button class="btn-icon close-shared-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="sharedListAlert" class="alert"></div>
          <div class="form-group">
            <label class="form-label">کد ۱۵ کاراکتری لیست</label>
            <input type="text" id="shareCodeInput" class="form-input" placeholder="مثال: aB3xY9kL2mN5pQ7" maxlength="15" style="text-align: center; letter-spacing: 1px; font-family: monospace; font-size: 1rem;">
            <small style="color: var(--muted-foreground);">کد را از دوست خود دریافت کنید</small>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-shared-modal">انصراف</button>
          <button id="viewSharedListBtn" class="btn btn-primary">مشاهده لیست</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: SHARED LIST DETAIL ==================== -->
    <div id="sharedListDetailModal" class="modal">
      <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header" style="flex-direction: column; align-items: stretch;">
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <i data-lucide="layers"></i>
              <h3 id="sharedDetailListTitle">لیست اشتراکی</h3>
            </div>
            <button class="btn-icon close-shared-detail-modal">
              <i data-lucide="x"></i>
            </button>
          </div>
          <div class="flex gap-2" style="width: 100%; margin-top: 0.75rem;">
            <input type="text" id="sharedListSearch" class="search-input" placeholder="جستجو در این لیست...">
          </div>
          <div class="tabs" style="width: 100%; margin-top: 0.5rem; margin-bottom: -1rem; border-bottom: none;">
            <button class="tab active" data-tab="shared-movies">
              <i data-lucide="film"></i> <span class="tab-text">فیلم‌ها</span>
            </button>
            <button class="tab" data-tab="shared-series">
              <i data-lucide="tv"></i> <span class="tab-text">سریال‌ها</span>
            </button>
            <button class="tab" data-tab="shared-comments">
              <i data-lucide="message-square"></i> <span class="tab-text">نظرات</span>
            </button>
          </div>
        </div>
        <div class="modal-body">
          <div id="sharedDetailAlert" class="alert"></div>
          <div id="sharedMoviesTab">
            <div id="sharedMoviesList"></div>
          </div>
          <div id="sharedSeriesTab" style="display:none;">
            <div id="sharedSeriesList"></div>
          </div>
          <div id="sharedCommentsTab" style="display:none;">
            <div id="sharedCommentsList">
              <div class="loading-center">
                <div class="loading"></div>
                <p>در حال بارگذاری نظرات...</p>
              </div>
            </div>
            <div id="sharedCommentsPagination" class="flex justify-center gap-2 mt-4"></div>
            <?php if (isLoggedIn()): ?>
            <div class="comment-form" id="sharedCommentForm">
              <textarea id="sharedNewComment" maxlength="1000" placeholder="نظر خود را بنویسید... (حداکثر ۱۰۰۰ کاراکتر)"></textarea>
              <div class="comment-char-count">
                <span id="sharedCharCount">0</span> / 1000
              </div>
              <div class="flex justify-end mt-2">
                <button id="sharedSubmitCommentBtn" class="btn btn-primary">ارسال نظر</button>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="modal-footer">
          <div style="flex: 1; font-size: 0.7rem; color: var(--muted-foreground);" id="sharedListOwnerInfo"></div>
          <button class="btn btn-secondary close-shared-detail-modal">بستن</button>
        </div>
      </div>
    </div>

    <!-- ==================== EXPORT CONTAINER ==================== -->
    <div id="exportContainer"></div>

    <!-- ==================== TOAST ==================== -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ==================== CONTEXT MENU ==================== -->
    <div id="contextMenu" class="context-menu"></div>

    <script>
/* ============================================================
   SECTION 1: STATE, THEME & TOAST
   ============================================================ */
let currentListId = null,
    currentSeriesId = null,
    deleteCallback = null,
    allListsData = [];
let allPrequelsCache = {};
let allCommentsCache = {};
let currentContextTarget = null;

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

// ===== TOAST =====
function showToast(message, type = 'info', duration = 10000) {
    const container = document.getElementById('toastContainer');

    const icons = {
        warning: 'alert-circle',
        success: 'check-circle',
        error: 'x-circle',
        info: 'info'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon"><i data-lucide="${icons[type] || icons.info}" style="width:22px;height:22px;"></i></span>
        <span>${message}</span>
        <span class="toast-close" onclick="this.parentElement.remove()">✕</span>
        <div class="toast-progress"></div>
    `;

    container.appendChild(toast);
    refreshIcons();

    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, duration);
}

/* ============================================================
   SECTION 2: HELPERS
   ============================================================ */
function hasValidBanner(banner) {
    return !!(banner && typeof banner === 'string' && banner.trim() !== '' && !banner.includes('${') && !banner.includes('undefined') && !banner.includes('null'));
}
function showAlert(element, message, type) {
    element.textContent = message;
    element.className = `alert alert-${type}`;
    element.style.display = 'block';
    setTimeout(() => element.style.display = 'none', 4000);
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>]/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;'
    } [m]));
}

// ==================== PWA OFFLINE CACHE & SYNC ====================
const READ_ACTIONS = [
    'get_lists',
    'get_groups',
    'get_full_list',
    'get_all_prequels_for_list',
    'get_all_comments_for_list',
    'get_genres',
    'get_persian_date',
    'get_user_stats',
    'get_watch_stats',
    'get_genre_stats',
    'get_titles_by_genre',
    'get_prequels',
    'get_comment',
    'get_shared_list',
    'get_share_code',
    'get_genre_audit',
    'check'
];

function getPwaCacheKey(action, data = {}) {
    if (action === 'get_lists') return 'pwa_cached_lists';
    if (action === 'get_groups') return 'pwa_cached_groups';
    if (action === 'get_full_list' && data.list_id) return 'pwa_cached_list_' + data.list_id;
    if (action === 'get_all_prequels_for_list' && data.list_id) return 'pwa_cached_prequels_' + data.list_id;
    if (action === 'get_all_comments_for_list' && data.list_id) return 'pwa_cached_comments_' + data.list_id;
    if (action === 'get_genres') return 'pwa_cached_genres';
    if (action === 'get_genre_audit') return 'pwa_cached_genre_audit';
    return null;
}

function getPwaCachedData(action, data = {}) {
    const key = getPwaCacheKey(action, data);
    if (!key) return null;
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    } catch (e) {
        console.warn('PWA read cache error:', e);
        return null;
    }
}

function setPwaCachedData(action, data = {}, resData) {
    const key = getPwaCacheKey(action, data);
    if (!key || !resData) return;
    try {
        localStorage.setItem(key, JSON.stringify(resData));
    } catch (e) {
        console.warn('PWA save cache error:', e);
    }
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
    showToast('ارتباط با اینترنت برقرار شد. در حال بارگذاری داده‌های تازه...', 'success');
    loadLists();
});

window.addEventListener('offline', () => {
    updatePwaOnlineStatus();
    showToast('ارتباط با اینترنت قطع شد. امکان مشاهده عناوین در حالت آفلاین فراهم است.', 'warning');
});

async function apiCall(action, data = {}) {
    const isOnline = navigator.onLine;

    // گارد محافظتی برای عملیات تغییر داده در حالت آفلاین
    if (!isOnline && !READ_ACTIONS.includes(action)) {
        showToast('در حالت آفلاین امکان انجام این عملیات (مشاهده، امتیاز، یادداشت یا تغییرات) وجود ندارد.', 'warning');
        return {
            ok: false,
            message: 'در حالت آفلاین امکان ثبت تغییرات وجود ندارد.',
            offline: true
        };
    }

    // بازخوانی فوری از حافظه کش برای اکشن‌های مجاز در حالت آفلاین
    if (!isOnline && READ_ACTIONS.includes(action)) {
        const cached = getPwaCachedData(action, data);
        if (cached !== null) {
            return { ok: true, data: cached, from_cache: true };
        }
        return { ok: false, message: 'داده‌ها در حافظه آفلاین موجود نیست.', offline: true };
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
            err.requestData = data;
            throw err;
        }
        const result = await response.json();

        // ذخیره خودکار داده‌های واکشی شده در حافظه کش PWA
        if (result.ok && result.data && READ_ACTIONS.includes(action)) {
            setPwaCachedData(action, data, result.data);
        }

        return result;
    } catch (error) {
        // در صورت قطع ناگهانی اینترنت، بازیابی داده از حافظه کش
        if (READ_ACTIONS.includes(action)) {
            const cached = getPwaCachedData(action, data);
            if (cached !== null) {
                return { ok: true, data: cached, from_cache: true };
            }
        }
        return {
            ok: false,
            message: error.message
        };
    }
}

function formatDate(dateNum) {
    if (!dateNum || dateNum === 0 || dateNum === '0' || dateNum === '1970-01-01') return '';

    if (typeof dateNum === 'string') {
        const trimmed = dateNum.trim();
        if (trimmed.includes('/')) {
            const parts = trimmed.split('/');
            if (parts.length === 3) {
                return `${parts[0]}/${parts[1].padStart(2, '0')}/${parts[2].padStart(2, '0')}`;
            }
            return trimmed;
        }
        if (trimmed.includes('-')) {
            const parts = trimmed.split('-');
            if (parts.length === 3 && parts[0].length === 4) {
                return `${parts[0]}/${parts[1].padStart(2, '0')}/${parts[2].padStart(2, '0')}`;
            }
            const date = new Date(trimmed);
            if (!isNaN(date.getTime())) {
                return `${date.getFullYear()}/${(date.getMonth()+1).toString().padStart(2,'0')}/${date.getDate().toString().padStart(2,'0')}`;
            }
        }
    }

    const num = parseInt(dateNum);
    if (!isNaN(num) && num > 10000000) {
        const year = Math.floor(num / 10000);
        const month = Math.floor((num % 10000) / 100);
        const day = num % 100;
        return `${year}/${month.toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;
    }

    return dateNum ? String(dateNum) : '';
}

function lightenColor(hex, percent) {
    const num = parseInt(hex.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = Math.min(255, (num >> 16) + amt);
    const G = Math.min(255, ((num >> 8) & 0x00FF) + amt);
    const B = Math.min(255, (num & 0x0000FF) + amt);
    return `rgb(${R}, ${G}, ${B})`;
}


/* ============================================================
   SECTION 3: DELETE MODAL & SKELETON
   ============================================================ */
function confirmDelete(message, callback) {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان حذف آیتم‌ها وجود ندارد.', 'warning');
        return;
    }
    document.getElementById('deleteMessage').textContent = message;
    deleteCallback = callback;
    document.getElementById('deleteModal').classList.add('show');
    refreshIcons();
}

document.querySelectorAll('.close-delete-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deleteModal').classList.remove('show');
    });
});

document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
    if (deleteCallback) deleteCallback();
    document.getElementById('deleteModal').classList.remove('show');
    deleteCallback = null;
});

function showSkeletonCards(count = 3) {
    const container = document.getElementById('listsContainer');
    let html = '';
    for (let i = 0; i < count; i++) {
        html += `
        <div class="skeleton-card">
            <div class="skeleton-card-banner"></div>
            <div class="skeleton-card-body">
                <div class="skeleton-line skeleton-line-md"></div>
                <div class="skeleton-line skeleton-line-sm"></div>
                <div class="skeleton-line skeleton-line-lg"></div>
            </div>
        </div>
        `;
    }
    container.innerHTML = html;
}

function showSkeletonListItems(count = 5, containerId = 'moviesList') {
    const container = document.getElementById(containerId);
    let html = '';
    for (let i = 0; i < count; i++) {
        html += `
        <div class="skeleton-media-item">
            <div class="skeleton-media-icon"></div>
            <div class="skeleton-media-info">
                <div class="skeleton-line skeleton-line-md"></div>
                <div class="skeleton-line skeleton-line-sm"></div>
            </div>
            <div class="skeleton-media-actions">
                <div class="skeleton-media-btn"></div>
                <div class="skeleton-media-btn"></div>
                <div class="skeleton-media-btn"></div>
            </div>
        </div>
        `;
    }
    container.innerHTML = html;
}


/* ============================================================
   SECTION 4: CONTEXT MENU
   ============================================================ */
function hideContextMenu() {
    const menu = document.getElementById('contextMenu');
    menu.classList.remove('show');
    currentContextTarget = null;
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.context-menu') && !e.target.closest('.btn-more')) {
        hideContextMenu();
    }
});

document.addEventListener('scroll', () => {
    hideContextMenu();
}, true);

function showContextMenu(e, targetData, isMovie = true, isSeason = false) {
    e.preventDefault();
    e.stopPropagation();

    const menu = document.getElementById('contextMenu');
    currentContextTarget = targetData;

    let html = '';

    if (!isSeason) {
        if (isMovie) {
            const downloadedIcon = targetData.downloaded == 1 ? 'download' : 'circle-arrow-down';
            const watchedIcon = targetData.watched == 1 ? 'eye' : 'eye-off';
            const commentIcon = targetData.hasComment ? 'message-circle' : 'message-square';

            html += `
            <button class="context-menu-item" data-action="download">
                <i data-lucide="${downloadedIcon}"></i>
                ${targetData.downloaded == 1 ? 'لغو دانلود' : 'دانلود شده'}
                ${targetData.downloaded == 1 ? '<span class="context-menu-badge downloaded">✓</span>' : ''}
            </button>
            <button class="context-menu-item" data-action="watch">
                <i data-lucide="${watchedIcon}"></i>
                ${targetData.watched == 1 ? 'لغو تماشا' : 'تماشا شده'}
                ${targetData.watched == 1 ? '<span class="context-menu-badge watched">✓</span>' : ''}
            </button>
            <button class="context-menu-item" data-action="rating">
                <i data-lucide="star"></i>
                ${targetData.rating ? 'امتیاز: ' + targetData.rating + '/10' : 'امتیازدهی'}
                ${targetData.rating ? '<span class="context-menu-badge rated">★</span>' : ''}
            </button>
            <button class="context-menu-item" data-action="comment">
                <i data-lucide="${commentIcon}"></i>
                ${targetData.hasComment ? 'ویرایش کامنت' : 'افزودن کامنت'}
                ${targetData.hasComment ? '<span class="context-menu-badge commented">💬</span>' : ''}
            </button>
            <button class="context-menu-item" data-action="prequel">
                <i data-lucide="link-2"></i>
                عنوان مرتبط
                ${targetData.hasPrequels ? '<span class="context-menu-badge prequel">🔗</span>' : ''}
            </button>
            <div class="context-menu-separator"></div>
            <button class="context-menu-item" data-action="edit">
                <i data-lucide="pencil"></i> ویرایش
            </button>
            <button class="context-menu-item destructive" data-action="delete">
                <i data-lucide="trash-2"></i> حذف
            </button>
            `;
        } else {
            html += `
            <button class="context-menu-item" data-action="prequel">
                <i data-lucide="link-2"></i>
                عنوان مرتبط
            </button>
            <div class="context-menu-separator"></div>
            <button class="context-menu-item" data-action="edit">
                <i data-lucide="pencil"></i> ویرایش
            </button>
            <button class="context-menu-item destructive" data-action="delete">
                <i data-lucide="trash-2"></i> حذف
            </button>
            `;
        }
    } else {
        const downloadedIcon = targetData.downloaded == 1 ? 'download' : 'circle-arrow-down';
        const watchedIcon = targetData.watched == 1 ? 'eye' : 'eye-off';
        const commentIcon = targetData.hasSeasonComment ? 'message-circle' : 'message-square';

        html += `
        <button class="context-menu-item" data-action="download">
            <i data-lucide="${downloadedIcon}"></i>
            ${targetData.downloaded == 1 ? 'لغو دانلود' : 'دانلود شده'}
            ${targetData.downloaded == 1 ? '<span class="context-menu-badge downloaded">✓</span>' : ''}
        </button>
        <button class="context-menu-item" data-action="watch">
            <i data-lucide="${watchedIcon}"></i>
            ${targetData.watched == 1 ? 'لغو تماشا' : 'تماشا شده'}
            ${targetData.watched == 1 ? '<span class="context-menu-badge watched">✓</span>' : ''}
        </button>
        <button class="context-menu-item" data-action="rating">
            <i data-lucide="star"></i>
            ${targetData.rating ? 'امتیاز: ' + targetData.rating + '/10' : 'امتیازدهی'}
            ${targetData.rating ? '<span class="context-menu-badge rated">★</span>' : ''}
        </button>
        <button class="context-menu-item" data-action="comment">
            <i data-lucide="${commentIcon}"></i>
            ${targetData.hasSeasonComment ? 'ویرایش کامنت' : 'افزودن کامنت'}
            ${targetData.hasSeasonComment ? '<span class="context-menu-badge commented">💬</span>' : ''}
        </button>
        <div class="context-menu-separator"></div>
        <button class="context-menu-item" data-action="edit-season">
            <i data-lucide="pencil"></i> ویرایش
        </button>
        <button class="context-menu-item destructive" data-action="delete-season">
            <i data-lucide="trash-2"></i> حذف
        </button>
        `;
    }

    menu.innerHTML = html;

    let x = e.clientX || (e.touches && e.touches[0].clientX);
    let y = e.clientY || (e.touches && e.touches[0].clientY);

    if (!x && !y && e.target) {
        const rect = e.target.getBoundingClientRect();
        x = rect.left;
        y = rect.bottom + 4;
    }

    const menuWidth = 220;
    const menuHeight = menu.offsetHeight || 350;

    if (x + menuWidth > window.innerWidth) {
        x = window.innerWidth - menuWidth - 8;
    }
    if (y + menuHeight > window.innerHeight) {
        y = y - menuHeight - 8;
    }
    if (x < 8) x = 8;
    if (y < 8) y = 8;

    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
    menu.classList.add('show');
    refreshIcons();

    menu.querySelectorAll('.context-menu-item').forEach(item => {
        item.onclick = function(e) {
            e.stopPropagation();
            const action = this.dataset.action;
            handleContextAction(action, targetData, isMovie, isSeason);
            hideContextMenu();
        };
    });
}

async function handleContextAction(action, targetData, isMovie, isSeason) {
    switch (action) {
        case 'download':
            if (isMovie) {
                const newDownloaded = targetData.downloaded == 1 ? 0 : 1;
                const result = await apiCall('toggle_downloaded_movie', {
                    movie_id: targetData.id,
                    downloaded: newDownloaded
                });
                if (result.ok) await reloadCurrentList();
                else showToast(result.message, 'error');
            } else if (isSeason) {
                const newDownloaded = targetData.downloaded == 1 ? 0 : 1;
                const result = await apiCall('toggle_downloaded_season', {
                    season_id: targetData.id,
                    downloaded: newDownloaded
                });
                if (result.ok) await reloadCurrentList();
                else showToast(result.message, 'error');
            }
            break;

        case 'watch':
            if (isMovie) {
                if (targetData.watched == 1) {
                    await markAsWatched('movie', targetData.id, 0, null);
                } else {
                    document.getElementById('watchTargetType').value = 'movie';
                    document.getElementById('watchTargetId').value = targetData.id;
                    document.getElementById('watchAction').value = 'mark_watched_movie';
                    initDatePicker();
                    document.getElementById('watchDateModal').classList.add('show');
                    refreshIcons();
                }
            } else if (isSeason) {
                if (targetData.watched == 1) {
                    await markAsWatched('season', targetData.id, 0, null);
                } else {
                    document.getElementById('watchTargetType').value = 'season';
                    document.getElementById('watchTargetId').value = targetData.id;
                    document.getElementById('watchAction').value = 'mark_watched_season';
                    initDatePicker();
                    document.getElementById('watchDateModal').classList.add('show');
                    refreshIcons();
                }
            }
            break;

        case 'rating':
            if (isMovie) openRatingModal('movie', targetData.id, targetData.rating || 0);
            else if (isSeason) openRatingModal('season', targetData.id, targetData.rating || 0);
            break;

        case 'comment':
            if (isMovie) openCommentModal('movie', targetData.id, targetData.commentId || '');
            else if (isSeason) openCommentModal('season', targetData.id, targetData.commentId || '');
            break;

        case 'prequel':
            if (isMovie) openPrequelModal('movie', targetData.id);
            else if (!isSeason) openPrequelModal('series', targetData.id);
            break;

        case 'edit':
            if (isMovie) {
                openMovieModal({
                    id: targetData.id,
                    title: targetData.title,
                    genre: targetData.genre || '',
                    year: targetData.release_year || ''
                });
            } else if (!isSeason) {
                openSeriesModal({
                    id: targetData.id,
                    title: targetData.title,
                    genre: targetData.genre || '',
                    startYear: targetData.start_year || '',
                    endYear: targetData.end_year || '',
                    status: targetData.status || 'ongoing'
                });
            }
            break;

        case 'delete':
            if (isMovie) {
                confirmDelete('حذف فیلم؟', async () => {
                    const r = await apiCall('delete_movie', { movie_id: targetData.id });
                    if (r.ok) await reloadCurrentList();
                    else showToast(r.message, 'error');
                });
            } else if (!isSeason) {
                confirmDelete('حذف سریال؟', async () => {
                    const r = await apiCall('delete_series', { series_id: targetData.id });
                    if (r.ok) await reloadCurrentList();
                    else showToast(r.message, 'error');
                });
            }
            break;

        case 'edit-season':
            if (isSeason) {
                currentSeriesId = targetData.seriesId;
                openSingleSeasonModal({
                    id: targetData.id,
                    season: targetData.season_number,
                    episodes: targetData.episode_count
                });
            }
            break;

        case 'delete-season':
            if (isSeason) {
                confirmDelete('حذف فصل؟', async () => {
                    const r = await apiCall('delete_season', { season_id: targetData.id });
                    if (r.ok) await reloadCurrentList();
                    else showToast(r.message, 'error');
                });
            }
            break;
    }
}


/* ============================================================
   SECTION 5: LISTS RENDERING
   ============================================================ */
async function loadLists() {
    showSkeletonCards(3);
    const result = await apiCall('get_lists');
    if (result.ok && result.data) {
        allListsData = result.data;
        await loadGroups();
        renderLists(allListsData);
        await loadAllContentForSearch();
        searchDataLoaded = true;
    } else {
        document.getElementById('listsContainer').innerHTML =
            '<div class="empty-state" style="grid-column: 1/-1;"><i data-lucide="package-open"></i><p>هنوز لیستی نساخته‌اید</p><button id="emptyAddListBtn" class="btn btn-primary mt-4">ساخت اولین لیست</button></div>';
        refreshIcons();
        document.getElementById('emptyAddListBtn')?.addEventListener('click', () => openListModal());
    }
}

function renderLists(lists) {
    const container = document.getElementById('listsContainer');

    const standaloneLists = lists.filter(l => !l.group_id);

    if (!standaloneLists.length && !allGroupsData.length) {
        container.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><i data-lucide="package-open"></i><p>هیچ لیستی یافت نشد</p></div>';
        refreshIcons();
        return;
    }

    const listsHtml = standaloneLists.map(list => renderListCard(list)).join('');
    const groupsHtml = allGroupsData.map(group => renderGroupCard(group)).join('');

    container.innerHTML = listsHtml + groupsHtml;
    refreshIcons();

    standaloneLists.forEach(async list => {
        const stats = await getListStats(list.id);
        const statDiv = document.querySelector(`.list-card[data-list-id="${list.id}"] .list-stats`);
        if (statDiv) {
            statDiv.innerHTML = `<i data-lucide="film"></i> ${stats.movies} فیلم | <i data-lucide="tv"></i> ${stats.series} سریال | <i data-lucide="list"></i> ${stats.total} آیتم`;
            refreshIcons();
        }
        const progressDiv = document.querySelector(`.list-card[data-list-id="${list.id}"] .list-progress`);
        if (progressDiv && stats.total > 0) {
            progressDiv.style.display = 'flex';
            const fill = progressDiv.querySelector('.list-progress-fill');
            const label = progressDiv.querySelector('.list-progress-label');
            if (fill) fill.style.width = stats.watchedPercent + '%';
            if (label) label.textContent = stats.watchedPercent + '% دیده شده';
        }
    });

    loadAllListCommentBadges();
}

function renderListCard(list) {
    const hasBanner = hasValidBanner(list.banner);
    const bannerUrl = hasBanner ? `uploads/banners/${list.banner}` : '';
    const bannerColor = list.color || '#0891b2';
    const bannerColorLight = lightenColor(bannerColor, 20);

    return `
    <div class="list-card ${hasBanner ? 'has-banner' : ''}"
         data-list-id="${list.id}"
         onclick="openListDetailWithLoading(this, '${list.id}', '${escapeHtml(list.name)}')">

        ${hasBanner ? `
        <div class="list-banner" style="--banner-color: ${bannerColor}; --banner-color-light: ${bannerColorLight};">
            <img class="list-banner-image" src="${bannerUrl}" alt="${escapeHtml(list.name)}"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="list-banner-placeholder" style="display: none;"></div>
            <div class="list-banner-overlay">
                <div class="list-banner-title">
                    <span class="color-dot"></span>
                    ${escapeHtml(list.name)}
                </div>
            </div>
            <div class="list-banner-actions">
                <button class="list-banner-edit-btn"
                        onclick="event.stopPropagation();triggerBannerUpload('${list.id}')">
                    <i data-lucide="pencil"></i> تغییر بنر
                </button>
                <button class="list-banner-remove-btn"
                        onclick="event.stopPropagation();removeListBanner('${list.id}')">
                    <i data-lucide="trash-2"></i> حذف بنر
                </button>
            </div>
        </div>
        ` : `
        <div class="list-banner" style="--banner-color: ${bannerColor}; --banner-color-light: ${bannerColorLight}; cursor: pointer;"
             onclick="event.stopPropagation();triggerBannerUpload('${list.id}')">
            <div class="list-banner-placeholder"></div>
            <div class="list-banner-overlay" style="height: 100%; align-items: center; justify-content: center;">
                <div class="list-banner-title" style="font-size: 1.1rem; opacity: 0.8;">
                    <i data-lucide="plus-square" style="filter: brightness(0) invert(1); width:24px; height:24px;"></i>
                    افزودن بنر
                    <sub style="font-weight: 300;">(900x500px)</sub>
                </div>
            </div>
        </div>
        `}

        ${!hasBanner ? `
        <div class="card-header" style="border-right: 4px solid ${list.color};">
            <div class="card-title">
                <span class="color-dot" style="background: ${list.color};"></span>${escapeHtml(list.name)}
            </div>
            <div class="card-actions">
                <button class="btn-icon edit-list tooltip-bottom"
                        data-id="${list.id}"
                        data-color="${list.color}"
                        data-name="${escapeHtml(list.name)}"
                        data-desc="${escapeHtml(list.description || '')}"
                        onclick="event.stopPropagation();openEditListModal(this)"
                        data-tooltip="ویرایش لیست"
                        title="ویرایش لیست">
                    <i data-lucide="pencil"></i>
                </button>
                <button class="btn-icon delete-list tooltip-bottom"
                        data-id="${list.id}"
                        onclick="event.stopPropagation();deleteList('${list.id}')"
                        style="color: var(--destructive);"
                        data-tooltip="حذف لیست"
                        title="حذف لیست">
                    <i data-lucide="trash-2"></i>
                </button>
            </div>
        </div>
        ` : `
        <div class="card-actions" style="position: absolute; top: 0.75rem; right: 0.75rem; z-index: 2; display: flex; gap: 0.5rem;">
            <button class="btn-icon edit-list tooltip-bottom"
                    data-id="${list.id}"
                    data-color="${list.color}"
                    data-name="${escapeHtml(list.name)}"
                    data-desc="${escapeHtml(list.description || '')}"
                    onclick="event.stopPropagation();openEditListModal(this)"
                    style="background: rgba(0,0,0,0.5); border-color: rgba(255,255,255,0.3); color: white; backdrop-filter: blur(4px);"
                    data-tooltip="ویرایش لیست"
                    title="ویرایش لیست">
                <i data-lucide="pencil"></i>
            </button>
            <button class="btn-icon delete-list tooltip-bottom"
                    data-id="${list.id}"
                    onclick="event.stopPropagation();deleteList('${list.id}')"
                    style="color: white; background: rgba(0,0,0,0.5); border-color: rgba(239,68,68, 1); backdrop-filter: blur(4px);"
                    data-tooltip="حذف لیست"
                    title="حذف لیست">
                <i data-lucide="trash-2"></i>
            </button>
        </div>
        `}

        <div class="card-content">
            <div class="list-stats">
                <i data-lucide="layers"></i> آمار در حال بارگذاری...
            </div>
            <div class="list-progress" style="display: none;">
                <div class="list-progress-bar">
                    <div class="list-progress-fill"></div>
                </div>
                <span class="list-progress-label">0% دیده شده</span>
            </div>
            <div class="list-description">${escapeHtml(list.description || 'توضیحاتی ثبت نشده')}</div>
        </div>
    </div>
    `;
}

async function openListDetailWithLoading(cardElement, listId, listName) {
    const overlay = document.createElement('div');
    overlay.className = 'list-card-loading-overlay';
    overlay.innerHTML = `
    <div class="list-card-loading-content">
        <div class="loading"></div>
        <span class="list-card-loading-text">در حال بارگذاری...</span>
    </div>
    `;

    cardElement.appendChild(overlay);
    cardElement.style.pointerEvents = 'none';

    try {
        currentListId = listId;
        document.getElementById('detailListTitle').innerHTML = escapeHtml(listName);
        showSkeletonListItems(4, 'moviesList');
        showSkeletonListItems(4, 'seriesList');
        document.getElementById('listDetailModal').classList.add('show');
        refreshIcons();
        document.getElementById('listSearch').value = '';
        document.getElementById('listSearch').oninput = () => filterListItems();
        await loadFullList(listId);
        updateLastViewedList(listId);
    } catch (error) {
        console.error('Error loading list:', error);
        showToast('خطا در بارگزاری لیست', 'error');
    } finally {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.2s ease';
        setTimeout(() => {
            overlay.remove();
            cardElement.style.pointerEvents = '';
        }, 200);
    }
}

async function getListStats(listId) {
    const result = await apiCall('get_full_list', { list_id: listId });
    if (result.ok) {
        const movies = result.data.movies || [];
        const series = result.data.series || [];

        let watchedItems = 0;
        let totalItems = 0;

        movies.forEach(m => {
            totalItems++;
            if (m.watched == 1) watchedItems++;
        });

        series.forEach(s => {
            const seasons = s.seasons || [];
            if (seasons.length > 0) {
                totalItems += seasons.length;
                seasons.forEach(sea => {
                    if (sea.watched == 1) watchedItems++;
                });
            } else {
                totalItems++;
                if (s.watched == 1) watchedItems++;
            }
        });

        const watchedPercent = totalItems > 0
            ? Math.round((watchedItems / totalItems) * 100)
            : 0;

        return {
            movies: movies.length,
            series: series.length,
            total: movies.length + series.length,
            watchedItems,
            watchedPercent
        };
    }
    return { movies: 0, series: 0, total: 0, watchedItems: 0, watchedPercent: 0 };
}


/* ============================================================
   SECTION 6: GROUPS RENDERING
   ============================================================ */
let allGroupsData = [];
let currentGroupId = null;

function lightenColorGroup(hex, percent) {
    const num = parseInt(hex.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = Math.min(255, (num >> 16) + amt);
    const G = Math.min(255, ((num >> 8) & 0x00FF) + amt);
    const B = Math.min(255, (num & 0x0000FF) + amt);
    return `rgb(${R}, ${G}, ${B})`;
}

async function loadGroups() {
    const result = await apiCall('get_groups');
    if (result.ok && result.data) {
        allGroupsData = result.data;
    } else {
        allGroupsData = [];
    }
}

function renderGroupCard(group) {
    const color = group.color || '#0891b2';
    const colorLight = lightenColorGroup(color, 20);
    const memberLists = group.lists || [];
    const memberBanners = memberLists
        .filter(l => hasValidBanner(l.banner))
        .map(l => `uploads/banners/${l.banner}`);

    let fadedHtml = '';
    if (memberBanners.length >= 3) {
        fadedHtml = `
            <div class="group-banner-faded" style="background: var(--secondary);"><img src="${memberBanners[0]}" style="width:100%; height:100%; object-fit:cover; display:block;"></div>
            <div class="group-banner-faded" style="background: var(--secondary);"><img src="${memberBanners[1]}" style="width:100%; height:100%; object-fit:cover; display:block;"></div>
        `;
    } else if (memberBanners.length >= 1) {
        fadedHtml = `
            <div class="group-banner-faded" style="background: var(--secondary);"><img src="${memberBanners[0]}" style="width:100%; height:100%; object-fit:cover; display:block;"></div>
        `;
    }

    const centerContent = memberBanners.length > 0
        ? `<img src="${memberBanners[memberBanners.length - 1]}" alt="${escapeHtml(group.name)}">`
        : `<i data-lucide="link-2"></i>`;

    return `
    <div class="group-card" data-group-id="${group.id}" onclick="openGroupDetail(${group.id})">
        <div class="group-banner" style="--banner-color: ${color}; --banner-color-light: ${colorLight};">
            ${fadedHtml}
            <div class="group-banner-center">
                ${centerContent}
            </div>
            <div class="group-banner-overlay">
                <div class="group-banner-title">
                    <span class="color-dot" style="background: #fff; box-shadow: 0 0 8px rgba(255,255,255,0.6);"></span>
                    ${escapeHtml(group.name)}
                </div>
            </div>
            <div class="group-banner-actions">
                <button class="btn-icon" data-tooltip="ویرایش گروه" title="ویرایش گروه" onclick="event.stopPropagation();openGroupManage(${group.id})">
                    <i data-lucide="pencil"></i>
                </button>
                <button class="btn-icon" data-tooltip="حذف گروه" title="حذف گروه" onclick="event.stopPropagation();deleteGroup(${group.id})">
                    <i data-lucide="trash-2"></i>
                </button>
            </div>
        </div>
        <div class="group-card-body">
            <span class="group-card-info">${memberLists.length ? memberLists.map(l => escapeHtml(l.name)).join('، ') : 'لیستی در این گروه نیست'}</span>
            <span class="group-card-count">${memberLists.length} لیست</span>
        </div>
    </div>
    `;
}

function openGroupModal() {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان ایجاد یا ویرایش گروه وجود ندارد.', 'warning');
        return;
    }
    document.getElementById('groupModalTitle').innerHTML = '<i data-lucide="link-2"></i><span>ساخت گروه جدید</span>';
    document.getElementById('editGroupId').value = '';
    document.getElementById('groupName').value = '';
    document.getElementById('groupColor').value = '#0891b2';
    document.getElementById('groupColorPreview').style.background = '#0891b2';
    document.getElementById('groupDescription').value = '';
    document.getElementById('groupModalAlert').style.display = 'none';
    document.getElementById('groupModal').classList.add('show');
    refreshIcons();
}

document.getElementById('groupColor')?.addEventListener('input', e => {
    document.getElementById('groupColorPreview').style.background = e.target.value;
});

document.getElementById('saveGroupBtn')?.addEventListener('click', async () => {
    const id = document.getElementById('editGroupId').value;
    const name = document.getElementById('groupName').value.trim();
    const color = document.getElementById('groupColor').value;
    const description = document.getElementById('groupDescription').value;

    if (!name) {
        showAlert(document.getElementById('groupModalAlert'), 'نام گروه الزامی است', 'error');
        return;
    }

    const data = { name, color, description };
    if (id) {
        data.group_id = id;
        const res = await apiCall('update_group', data);
        if (res.ok) showToast(res.message, 'success');
        else showToast(res.message, 'error');
    } else {
        const res = await apiCall('add_group', data);
        if (res.ok) {
            showToast(res.message, 'success');
            if (res.data && res.data.id) await openGroupManage(res.data.id);
        } else {
            showToast(res.message, 'error');
        }
    }
    document.getElementById('groupModal').classList.remove('show');
    await loadLists();
});

document.querySelectorAll('.close-group-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('groupModal').classList.remove('show');
        document.getElementById('groupModalAlert').style.display = 'none';
    });
});

async function openGroupDetail(groupId) {
    const group = allGroupsData.find(g => g.id == groupId);
    if (!group) return;
    currentGroupId = groupId;
    const memberLists = group.lists || [];

    document.getElementById('groupDetailTitle').innerHTML = escapeHtml(group.name);
    document.getElementById('groupDetailAlert').style.display = 'none';

    const container = document.getElementById('groupDetailLists');

    if (!memberLists.length) {
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1/-1;">
                <i data-lucide="package-open"></i>
                <p>لیستی در این گروه نیست</p>
                <button id="groupDetailAddBtn" class="btn btn-primary mt-4">افزودن لیست</button>
            </div>
        `;
        refreshIcons();
        document.getElementById('groupDetailAddBtn')?.addEventListener('click', () => openGroupManage(group.id));
    } else {
        container.innerHTML = memberLists.map(list => {
            const hasBanner = hasValidBanner(list.banner);
            const bannerUrl = hasBanner ? `uploads/banners/${list.banner}` : '';
            const bannerColor = list.color || '#0891b2';
            const bannerColorLight = lightenColorGroup(bannerColor, 200);

            return `
            <div class="list-card ${hasBanner ? 'has-banner' : ''}" style="cursor: pointer; position: relative;"
                 data-list-id="${list.id}"
                 onclick="openListDetailWithLoading(this, '${list.id}', '${escapeHtml(list.name)}')">

                ${hasBanner ? `
                <div class="list-banner" style="--banner-color: ${bannerColor}; --banner-color-light: ${bannerColorLight}; height: 140px;">
                    <img class="list-banner-image" src="${bannerUrl}" alt="${escapeHtml(list.name)}"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="list-banner-placeholder" style="display: none;"></div>
                    <div class="list-banner-overlay">
                        <div class="list-banner-title">
                            <span class="color-dot"></span>${escapeHtml(list.name)}
                        </div>
                    </div>
                </div>
                ` : `
                <div class="card-header" style="border-right: 4px solid ${list.color};">
                    <div class="card-title" style="font-size: 1rem;">
                        <span class="color-dot" style="background: ${list.color};"></span>${escapeHtml(list.name)}
                    </div>
                </div>
                `}

                <div class="card-actions" style="position: absolute; top: ${hasBanner ? '0.5rem' : '0.75rem'}; left: 0.5rem; right: auto; z-index: 5; display: flex; gap: 0.35rem;">
                    <button class="btn-icon edit-list tooltip-bottom"
                            data-id="${list.id}"
                            data-color="${list.color}"
                            data-name="${escapeHtml(list.name)}"
                            data-desc="${escapeHtml(list.description || '')}"
                            onclick="event.stopPropagation();openEditListModal(this)"
                            style="${hasBanner ? 'background: rgba(0,0,0,0.5); border-color: rgba(255,255,255,0.3); color: white; backdrop-filter: blur(4px); padding: 0.35rem;' : 'padding: 0.35rem;'}"
                            data-tooltip="ویرایش لیست"
                            title="ویرایش لیست">
                        <i data-lucide="pencil" style="width: 16px; height: 16px;"></i>
                    </button>
                    <button class="btn-icon delete-list tooltip-bottom"
                            data-id="${list.id}"
                            onclick="event.stopPropagation();deleteListInGroup('${list.id}', ${groupId})"
                            style="${hasBanner ? 'background: rgba(0,0,0,0.5); border-color: rgba(239,68,68,1); color: white; backdrop-filter: blur(4px); padding: 0.35rem;' : 'color: var(--destructive); padding: 0.35rem;'}"
                            data-tooltip="خروج از گروه"
                            title="خروج از گروه">
                        <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>

                <div class="card-content" style="padding: 0.75rem 1rem;">
                    <div class="list-description" style="margin-top: 0;">${escapeHtml(list.description || 'توضیحاتی ثبت نشده')}</div>
                </div>
            </div>
            `;
        }).join('');
        refreshIcons();
    }

    document.getElementById('groupDetailModal').classList.add('show');
}

async function deleteListInGroup(listId, groupId) {
    confirmDelete('آیا می‌خواهید این لیست را از گروه خارج کنید؟ (لیست حذف نمی‌شود)', async () => {
        const result = await apiCall('remove_list_from_group', { list_id: listId });
        if (result.ok) {
            showToast('لیست از گروه خارج شد', 'success');
            await loadLists();
            document.getElementById('groupDetailModal').classList.remove('show');
            await openGroupDetail(groupId);
        } else {
            showToast(result.message, 'error');
        }
    });
}

document.querySelectorAll('.close-group-detail-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('groupDetailModal').classList.remove('show');
        document.getElementById('groupDetailAlert').style.display = 'none';
    });
});

document.getElementById('editGroupFromDetailBtn')?.addEventListener('click', () => {
    if (currentGroupId) openGroupManage(currentGroupId);
});

async function openGroupManage(groupId) {
    const group = allGroupsData.find(g => g.id == groupId);
    if (!group) return;

    document.getElementById('manageGroupId').value = group.id;
    document.getElementById('manageGroupName').value = group.name || '';
    document.getElementById('manageGroupColor').value = group.color || '#0891b2';
    document.getElementById('manageGroupColorPreview').style.background = group.color || '#0891b2';
    document.getElementById('manageGroupDescription').value = group.description || '';

    document.getElementById('groupManageTitle').innerHTML =
        `<i data-lucide="pencil"></i><span>مدیریت گروه: ${escapeHtml(group.name)}</span>`;

    document.getElementById('groupManageAlert').style.display = 'none';

    const memberIds = (group.lists || []).map(l => l.id);
    const result = await apiCall('get_lists');
    if (!result.ok || !result.data) return;

    const container = document.getElementById('groupManageLists');
    if (!result.data.length) {
        container.innerHTML = '<div class="empty-state"><p>لیستی برای اضافه کردن وجود ندارد</p></div>';
    } else {
        container.innerHTML = result.data.map(list => {
            const checked = memberIds.includes(list.id);
            return `
            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 0.5rem; cursor: pointer; background: var(--card);">
                <input type="checkbox" class="group-member-check" value="${list.id}" ${checked ? 'checked' : ''} style="width: 18px; height: 18px; accent-color: var(--primary);">
                <span class="color-dot" style="background: ${list.color}; width: 12px; height: 12px;"></span>
                <span style="font-size: 0.85rem; font-weight: 500;">${escapeHtml(list.name)}</span>
            </label>
            `;
        }).join('');
    }

    const colorInput = document.getElementById('manageGroupColor');
    if (colorInput && !colorInput.dataset.listenerAttached) {
        colorInput.addEventListener('input', e => {
            document.getElementById('manageGroupColorPreview').style.background = e.target.value;
        });
        colorInput.dataset.listenerAttached = 'true';
    }

    document.getElementById('groupManageModal').dataset.groupId = groupId;
    document.getElementById('groupManageModal').classList.add('show');
    refreshIcons();
}

document.querySelectorAll('.close-group-manage-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('groupManageModal').classList.remove('show');
        document.getElementById('groupManageAlert').style.display = 'none';
    });
});

document.getElementById('saveGroupManageBtn')?.addEventListener('click', async () => {
    const modal = document.getElementById('groupManageModal');
    const groupId = modal.dataset.groupId;

    const name = document.getElementById('manageGroupName').value.trim();
    const color = document.getElementById('manageGroupColor').value;
    const description = document.getElementById('manageGroupDescription').value;

    if (!name) {
        showAlert(document.getElementById('groupManageAlert'), 'نام گروه الزامی است', 'error');
        return;
    }

    const checked = Array.from(document.querySelectorAll('.group-member-check'))
        .filter(c => c.checked)
        .map(c => parseInt(c.value));

    const updateRes = await apiCall('update_group', {
        group_id: groupId,
        name,
        color,
        description
    });

    if (!updateRes.ok) {
        showAlert(document.getElementById('groupManageAlert'), updateRes.message || 'خطا در آپدیت گروه', 'error');
        return;
    }

    const tasks = [];
    for (const list of allListsData) {
        const wasMember = (list.group_id != null && list.group_id == groupId);
        const isMember = checked.includes(list.id);
        if (wasMember && !isMember) {
            tasks.push(apiCall('remove_list_from_group', { list_id: list.id }));
        } else if (!wasMember && isMember) {
            tasks.push(apiCall('add_list_to_group', { group_id: groupId, list_id: list.id }));
        }
    }

    await Promise.all(tasks);
    showToast('تغییرات ذخیره شد', 'success');
    modal.classList.remove('show');
    await loadLists();
});

async function deleteGroup(groupId) {
    confirmDelete('آیا از حذف این گروه مطمئن هستید؟ (لیست‌های داخل گروه حذف نمی‌شوند)', async () => {
        const res = await apiCall('delete_group', { group_id: groupId });
        if (res.ok) {
            showToast(res.message, 'success');
            await loadLists();
        } else {
            showToast(res.message, 'error');
        }
    });
}

document.getElementById('addGroupBtn')?.addEventListener('click', () => openGroupModal());


/* ============================================================
   SECTION 7: LIST MODAL & CRUD
   ============================================================ */
function openListModal() {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان ایجاد لیست جدید وجود ندارد.', 'warning');
        return;
    }
    document.getElementById('listModalTitle').innerHTML = '<i data-lucide="layers"></i><span>ساخت لیست جدید</span>';
    document.getElementById('editListId').value = '';
    document.getElementById('listName').value = '';
    document.getElementById('listColor').value = '#0891b2';
    document.getElementById('colorPreview').style.background = '#0891b2';
    document.getElementById('listDescription').value = '';
    document.getElementById('listModal').classList.add('show');
    refreshIcons();
}

function openEditListModal(btn) {
    document.getElementById('listModalTitle').innerHTML = '<i data-lucide="pencil"></i><span>ویرایش لیست</span>';
    document.getElementById('editListId').value = btn.dataset.id;
    document.getElementById('listName').value = btn.dataset.name;
    document.getElementById('listColor').value = btn.dataset.color;
    document.getElementById('colorPreview').style.background = btn.dataset.color;
    document.getElementById('listDescription').value = btn.dataset.desc || '';
    document.getElementById('listModal').classList.add('show');
    refreshIcons();
}

document.getElementById('listColor').addEventListener('input', e => {
    document.getElementById('colorPreview').style.background = e.target.value;
});

document.getElementById('saveListBtn').addEventListener('click', async () => {
    const id = document.getElementById('editListId').value,
        name = document.getElementById('listName').value.trim(),
        color = document.getElementById('listColor').value,
        description = document.getElementById('listDescription').value;

    if (!name) {
        showAlert(document.getElementById('listModalAlert'), 'نام لیست الزامی است', 'error');
        return;
    }

    const action = id ? 'update_list' : 'add_list';
    const result = await apiCall(action, { list_id: id, name, color, description });

    if (result.ok) {
        document.getElementById('listModal').classList.remove('show');
        loadLists();
    } else {
        showAlert(document.getElementById('listModalAlert'), result.message, 'error');
    }
});

async function deleteList(listId) {
    confirmDelete('آیا از حذف این لیست مطمئن هستید؟', async () => {
        const result = await apiCall('delete_list', { list_id: listId });
        if (result.ok) loadLists();
        else showToast(result.message, 'error');
    });
}

document.getElementById('addListBtn').addEventListener('click', openListModal);
document.querySelectorAll('.close-list-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('listModal').classList.remove('show');
    });
});


/* ============================================================
   SECTION 8: GLOBAL SEARCH
   ============================================================ */
let allMoviesGlobal = [];
let allSeriesGlobal = [];
let searchDataLoaded = false;

async function loadAllContentForSearch() {
    allMoviesGlobal = [];
    allSeriesGlobal = [];
    const listsResult = await apiCall('get_lists');
    if (!listsResult.ok || !listsResult.data) return;
    for (const list of listsResult.data) {
        const detailResult = await apiCall('get_full_list', { list_id: list.id });
        if (detailResult.ok && detailResult.data) {
            const moviesWithList = detailResult.data.movies.map(m => ({
                ...m, list_name: list.name, list_id: list.id, list_color: list.color, type: 'movie'
            }));
            const seriesWithList = detailResult.data.series.map(s => ({
                ...s, list_name: list.name, list_id: list.id, list_color: list.color, type: 'series'
            }));
            allMoviesGlobal.push(...moviesWithList);
            allSeriesGlobal.push(...seriesWithList);
        }
    }
}

function performGlobalSearch(term) {
    if (!term || term.length < 2) {
        renderLists(allListsData);
        return;
    }
    term = term.toLowerCase();
    const filteredMovies = allMoviesGlobal.filter(m =>
        m.title.toLowerCase().includes(term) || (m.genre && m.genre.toLowerCase().includes(term))
    );
    const filteredSeries = allSeriesGlobal.filter(s =>
        s.title.toLowerCase().includes(term) || (s.genre && s.genre.toLowerCase().includes(term))
    );

    if (filteredMovies.length === 0 && filteredSeries.length === 0) {
        document.getElementById('listsContainer').innerHTML = `
        <div class="empty-state" style="grid-column: 1/-1;">
            <i data-lucide="search-x"></i>
            <p>هیچ نتیجه‌ای برای "${escapeHtml(term)}" یافت نشد</p>
        </div>
        `;
        refreshIcons();
        return;
    }

    let html = '';
    if (filteredMovies.length > 0) {
        html += `<div style="grid-column: 1/-1; margin-bottom: 1rem;"><h3 style="color: var(--primary); display:flex; align-items:center; gap:0.5rem;"><i data-lucide="film"></i>فیلم‌ها (${filteredMovies.length})</h3></div>`;
        html += filteredMovies.map(movie => `
        <div class="list-card search-result-card" data-list-id="${movie.list_id}" style="cursor: pointer;">
            <div class="card-header" style="border-right: 4px solid ${movie.list_color};">
                <div class="card-title">
                    <span class="color-dot" style="background: ${movie.list_color};"></span>
                    <i data-lucide="film"></i>${escapeHtml(movie.title)}
                </div>
                <div class="card-actions">
                    <button class="btn-icon view-in-list" data-list-id="${movie.list_id}" data-list-name="${escapeHtml(movie.list_name)}" data-type="movie" data-item-id="${movie.id}" onclick="event.stopPropagation();openListAndHighlight('${movie.list_id}', '${escapeHtml(movie.list_name)}', 'movie', '${movie.id}')">
                        <i data-lucide="arrow-left"></i> مشاهده
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="list-description">
                    <strong>${escapeHtml(movie.list_name)}</strong><br>
                    ${movie.genre ? `ژانر: ${escapeHtml(movie.genre)} | ` : ''}سال: ${movie.release_year || 'نامشخص'}<br>
                    ${movie.watched ? '<span class="status-badge badge-downloaded mt-2" style="display: inline-flex; align-items: center;"><i data-lucide="eye"></i></span>' : ''}
                    ${movie.downloaded ? '<span class="status-badge badge-downloaded mt-2" style="display: inline-flex; align-items: center;"><i data-lucide="download"></i></span>' : ''}
                </div>
            </div>
        </div>
        `).join('');
    }

    if (filteredSeries.length > 0) {
        html += `<div style="grid-column: 1/-1; margin: 1rem 0;"><h3 style="color: var(--primary); display:flex; align-items:center; gap:0.5rem;"><i data-lucide="tv"></i> سریال‌ها (${filteredSeries.length})</h3></div>`;
        html += filteredSeries.map(series => `
        <div class="list-card search-result-card" data-list-id="${series.list_id}" style="cursor: pointer;">
            <div class="card-header" style="border-right: 4px solid ${series.list_color};">
                <div class="card-title">
                    <span class="color-dot" style="background: ${series.list_color};"></span>
                    <i data-lucide="tv"></i> ${escapeHtml(series.title)}
                </div>
                <div class="card-actions">
                    <button class="btn-icon view-in-list" data-list-id="${series.list_id}" data-list-name="${escapeHtml(series.list_name)}" data-type="series" data-item-id="${series.id}" onclick="event.stopPropagation();openListAndHighlight('${series.list_id}', '${escapeHtml(series.list_name)}', 'series', '${series.id}')">
                        <i data-lucide="arrow-left"></i> مشاهده
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="list-description">
                    <strong>${escapeHtml(series.list_name)}</strong><br>
                    ${series.genre ? `ژانر: ${escapeHtml(series.genre)} | ` : ''}سال: ${series.start_year || '?'} - ${series.end_year || 'در حال پخش'}
                </div>
            </div>
        </div>
        `).join('');
    }

    document.getElementById('listsContainer').innerHTML = html;
    refreshIcons();

    document.querySelectorAll('.search-result-card').forEach(card => {
        card.addEventListener('click', () => {
            const listId = card.dataset.listId;
            const listName = card.querySelector('.view-in-list')?.dataset.listName || '';
            openListDetail(listId, listName);
        });
    });
}

async function openListAndHighlight(listId, listName, type, itemId) {
    currentListId = listId;
    document.getElementById('detailListTitle').innerHTML = escapeHtml(listName);
    showSkeletonListItems(4, 'moviesList');
    showSkeletonListItems(4, 'seriesList');
    document.getElementById('listDetailModal').classList.add('show');
    refreshIcons();
    await loadFullList(listId);

    if (type === 'series') {
        document.querySelectorAll('#listDetailModal .tab').forEach(t => t.classList.remove('active'));
        const seriesTab = document.querySelector('#listDetailModal .tab[data-tab="series"]');
        if (seriesTab) seriesTab.classList.add('active');
        document.getElementById('moviesTab').style.display = 'none';
        document.getElementById('seriesTab').style.display = 'block';
        document.getElementById('listCommentsTab').style.display = 'none';
    } else {
        document.querySelectorAll('#listDetailModal .tab').forEach(t => t.classList.remove('active'));
        const moviesTab = document.querySelector('#listDetailModal .tab[data-tab="movies"]');
        if (moviesTab) moviesTab.classList.add('active');
        document.getElementById('moviesTab').style.display = 'block';
        document.getElementById('seriesTab').style.display = 'none';
        document.getElementById('listCommentsTab').style.display = 'none';
    }

    setTimeout(() => {
        let targetElement = document.querySelector(`#listDetailModal .media-info[data-id="${itemId}"]`);
        if (targetElement) {
            const mediaItem = targetElement.closest('.media-item');
            if (mediaItem) {
                mediaItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                mediaItem.style.transition = 'all 0.5s ease';
                mediaItem.style.background = 'var(--primary-light)';
                mediaItem.style.boxShadow = '0 0 20px rgba(8, 145, 178, 0.3)';
                mediaItem.style.borderRadius = '12px';
                setTimeout(() => {
                    mediaItem.style.background = '';
                    mediaItem.style.boxShadow = '';
                }, 2500);
            }
        }
    }, 800);
}

document.getElementById('globalSearch').addEventListener('input', async (e) => {
    const term = e.target.value.trim();
    if (term.length >= 2) {
        if (!searchDataLoaded) {
            await loadAllContentForSearch();
            searchDataLoaded = true;
        }
        performGlobalSearch(term);
    } else {
        renderLists(allListsData);
    }
});


/* ============================================================
   SECTION 9: LIST DETAIL MODAL
   ============================================================ */
async function openListDetail(listId, listName) {
    currentListId = listId;
    document.getElementById('detailListTitle').innerHTML = escapeHtml(listName);
    showSkeletonListItems(4, 'moviesList');
    showSkeletonListItems(4, 'seriesList');
    document.getElementById('listDetailModal').classList.add('show');
    refreshIcons();
    document.getElementById('listSearch').value = '';
    document.getElementById('listSearch').oninput = () => filterListItems();
    await loadFullList(listId);
}

async function loadFullList(listId) {
    const prequelResult = await apiCall('get_all_prequels_for_list', { list_id: listId });
    if (prequelResult.ok && prequelResult.data) {
        allPrequelsCache = { movies: {}, series: {} };
        prequelResult.data.movies.forEach(p => {
            if (!allPrequelsCache.movies[p.media_id]) allPrequelsCache.movies[p.media_id] = [];
            allPrequelsCache.movies[p.media_id].push(p);
        });
        prequelResult.data.series.forEach(p => {
            if (!allPrequelsCache.series[p.media_id]) allPrequelsCache.series[p.media_id] = [];
            allPrequelsCache.series[p.media_id].push(p);
        });
    }

    const commentsResult = await apiCall('get_all_comments_for_list', { list_id: listId });
    if (commentsResult.ok && commentsResult.data) {
        allCommentsCache = { movies: {}, seasons: {} };
        commentsResult.data.movies.forEach(c => { allCommentsCache.movies[c.media_id] = c; });
        commentsResult.data.seasons.forEach(c => { allCommentsCache.seasons[c.media_id] = c; });
    }

    const result = await apiCall('get_full_list', { list_id: listId });
    if (result.ok) {
        displayMovies(result.data.movies);
        displaySeries(result.data.series);
        window.allMovies = result.data.movies;
        window.allSeries = result.data.series;
        initListCommentCharCounter();
        loadListComments(true);
    }
}

function filterListItems() {
    const term = document.getElementById('listSearch').value.toLowerCase();
    if (window.allMovies) displayMovies(window.allMovies.filter(m => m.title.toLowerCase().includes(term)));
    if (window.allSeries) displaySeries(window.allSeries.filter(s => s.title.toLowerCase().includes(term)));
}


/* ============================================================
   SECTION 10: MOVIES DISPLAY & EVENTS
   ============================================================ */
function displayMovies(movies) {
    const container = document.getElementById('moviesList');
    if (!movies.length) {
        container.innerHTML = '<div class="empty-state"><i data-lucide="film"></i><p>فیلمی یافت نشد</p></div>';
        refreshIcons();
        return;
    }

    const totalMovies = movies.length;

    container.innerHTML = movies.map((m, index) => {
        const rowNumber = totalMovies - index;
        const isWatched = m.watched == 1;
        const hasWatchDate = m.watch_date && m.watch_date !== 0 && m.watch_date !== '1970-01-01';
        const watchDateFormatted = hasWatchDate ? formatDate(m.watch_date) : '';
        const hasRating = m.rating !== null && m.rating !== undefined;
        const movieComment = (allCommentsCache.movies && allCommentsCache.movies[m.id]) || null;
        const hasComment = movieComment !== null;
        const moviePrequels = (allPrequelsCache.movies && allPrequelsCache.movies[m.id]) || [];
        const hasPrequels = moviePrequels.length > 0;
        const hasCollection = hasPrequels && moviePrequels.some(p => p.relation_type === 'collection');
        const genreTags = m.genre ? m.genre.split(',').map(g => g.trim()).filter(g => g) : [];
        const genreDisplay = genreTags.length > 0
            ? `<span class="genre-display">${genreTags.map(g => `<span class="genre-display-tag">${escapeHtml(g)}</span>`).join('')}</span>`
            : '<span style="font-size:0.65rem; color: var(--muted-foreground);">بدون ژانر</span>';

        const targetData = {
            id: m.id,
            title: m.title,
            genre: m.genre || '',
            release_year: m.release_year || '',
            downloaded: m.downloaded,
            watched: m.watched,
            watch_date: m.watch_date || '',
            rating: m.rating || null,
            hasComment: hasComment,
            commentId: movieComment ? movieComment.id : '',
            hasPrequels: hasPrequels,
            hasCollection: hasCollection
        };

        return `
    <div class="media-item" style="flex-direction: column; align-items: stretch;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div class="media-info" onclick="editMovie(this)"
                 data-id="${m.id}"
                 data-title="${escapeHtml(m.title)}"
                 data-genre="${m.genre || ''}"
                 data-year="${m.release_year || ''}">
                <div class="media-icon" style="position: relative; overflow: hidden;">
                    <span class="media-number">#${rowNumber}</span>
                </div>
                <div class="media-details">
                    <div class="media-title">${escapeHtml(m.title)}</div>
                    <div class="media-meta">
                        <span>${m.release_year || 'سال نامشخص'}</span>
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; margin-right: 0.5rem;">• ${genreDisplay}</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn-icon download-btn ${m.downloaded == 1 ? 'btn-downloaded' : ''}"
                        data-movie-id="${m.id}" data-downloaded="${m.downloaded}"
                        data-tooltip="${m.downloaded == 1 ? 'لغو دانلود' : 'دانلود شده'}">
                    <i data-lucide="${m.downloaded == 1 ? 'download' : 'circle-arrow-down'}"></i>
                </button>
                <button class="btn-icon watch-btn ${isWatched ? 'btn-watched' : ''} ${isWatched && hasWatchDate ? 'has-date' : ''}"
                        data-movie-id="${m.id}"
                        data-watched="${m.watched}"
                        data-watch-date="${m.watch_date || ''}"
                        data-tooltip="${isWatched ? 'لغو تماشا' : 'ثبت تماشا'}">
                    <i data-lucide="${isWatched ? 'eye' : 'eye-off'}"></i>${isWatched && hasWatchDate ? `<span class="watch-date-label">${watchDateFormatted}</span>` : ''}
                </button>
                <button class="btn-icon rating-btn ${hasRating ? 'has-rating' : ''}"
                        data-movie-id="${m.id}" data-rating="${m.rating || ''}"
                        data-tooltip="${hasRating ? 'ویرایش امتیاز' : 'ثبت امتیاز'}">
                    <i data-lucide="star"></i>${hasRating ? `<span class="rating-label">${m.rating}/10</span>` : ''}
                </button>
                <button class="btn-icon comment-btn ${hasComment ? 'has-comment' : ''}"
                        data-media-type="movie" data-media-id="${m.id}" data-comment-id="${hasComment ? movieComment.id : ''}"
                        data-tooltip="${hasComment ? 'ویرایش کامنت' : 'افزودن کامنت'}">
                    <i data-lucide="${hasComment ? 'message-circle' : 'message-square'}"></i>
                </button>
                <button class="btn-icon prequel-btn ${hasCollection ? 'collection-badge' : (hasPrequels ? 'btn-downloaded' : '')}"
                        data-media-type="movie" data-media-id="${m.id}"
                        data-tooltip="${hasPrequels ? `${prequels_count_display(moviePrequels)} عنوان مرتبط` : 'افزودن عنوان مرتبط'}">
                    <i data-lucide="link-2"></i>
                </button>
                <button class="btn-icon edit-movie"
                        data-id="${m.id}" data-title="${escapeHtml(m.title)}" data-genre="${m.genre || ''}" data-year="${m.release_year || ''}"
                        data-tooltip="ویرایش فیلم">
                    <i data-lucide="pencil"></i>
                </button>
                <button class="btn-icon delete-movie" data-id="${m.id}" style="color:var(--destructive);"
                        data-tooltip="حذف فیلم">
                    <i data-lucide="trash-2"></i>
                </button>
                <button class="btn-more" onclick="event.stopPropagation(); showContextMenu(event, ${JSON.stringify(targetData).replace(/"/g, '&quot;')}, true, false)">
                    <i data-lucide="more-horizontal"></i>
                </button>
            </div>
        </div>
        ${hasComment ? `
        <div class="comment-section" style="margin-top: 0.5rem;">
            <div class="comment-header">
                <i data-lucide="message-circle"></i>
                <span class="comment-author">${escapeHtml(movieComment.display_name || 'کاربر')}</span>
                <span class="comment-date">${formatDate(movieComment.created_at)}</span>
            </div>
            <div class="comment-text">${escapeHtml(movieComment.comment)}</div>
        </div>
        ` : ''}
    </div>`;
    }).join('');

    refreshIcons();
    attachMovieEvents();
}

function prequels_count_display(prequels) {
    return prequels.length;
}

function attachMovieEvents() {
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const movieId = btn.dataset.movieId;
            const currentDownloaded = parseInt(btn.dataset.downloaded);
            const newDownloaded = currentDownloaded ? 0 : 1;
            const result = await apiCall('toggle_downloaded_movie', { movie_id: movieId, downloaded: newDownloaded });
            if (result.ok) await reloadCurrentList();
            else showToast(result.message, 'error');
        });
    });

    document.querySelectorAll('.watch-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const movieId = btn.dataset.movieId;
            const currentWatched = parseInt(btn.dataset.watched);
            if (currentWatched) {
                markAsWatched('movie', movieId, 0, null);
            } else {
                document.getElementById('watchTargetType').value = 'movie';
                document.getElementById('watchTargetId').value = movieId;
                document.getElementById('watchAction').value = 'mark_watched_movie';
                initDatePicker();
                document.getElementById('watchDateModal').classList.add('show');
                refreshIcons();
            }
        });
    });

    document.querySelectorAll('.rating-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const movieId = btn.dataset.movieId;
            const currentRating = btn.dataset.rating ? parseInt(btn.dataset.rating) : 0;
            openRatingModal('movie', movieId, currentRating);
        });
    });

    document.querySelectorAll('.prequel-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openPrequelModal(btn.dataset.mediaType, btn.dataset.mediaId);
        });
    });

    document.querySelectorAll('.edit-movie').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openMovieModal(btn.dataset);
        });
    });

    document.querySelectorAll('.delete-movie').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            confirmDelete('حذف فیلم؟', async () => {
                const r = await apiCall('delete_movie', { movie_id: btn.dataset.id });
                if (r.ok) await reloadCurrentList();
                else showToast(r.message, 'error');
            });
        });
    });

    document.querySelectorAll('.comment-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const mediaType = btn.dataset.mediaType;
            const mediaId = btn.dataset.mediaId;
            const commentId = btn.dataset.commentId || '';
            openCommentModal(mediaType, mediaId, commentId);
        });
    });
}

async function reloadCurrentList() {
    if (currentListId) {
        await loadFullList(currentListId);
    }
}


/* ============================================================
   SECTION 11: SERIES DISPLAY & SEASONS DISPLAY
   ============================================================ */
function displaySeries(seriesList) {
    const container = document.getElementById('seriesList');
    if (!seriesList.length) {
        container.innerHTML = '<div class="empty-state"><i data-lucide="tv"></i><p>سریالی یافت نشد</p></div>';
        refreshIcons();
        return;
    }

    const totalSeries = seriesList.length;

    container.innerHTML = seriesList.map((s, index) => {
        const rowNumber = totalSeries - index;
        const seriesPrequels = (allPrequelsCache.series && allPrequelsCache.series[s.id]) || [];
        const hasPrequels = seriesPrequels.length > 0;
        const genreTags = s.genre ? s.genre.split(',').map(g => g.trim()).filter(g => g) : [];
        const genreDisplay = genreTags.length > 0
            ? `<span class="genre-display">${genreTags.map(g => `<span class="genre-display-tag">${escapeHtml(g)}</span>`).join('')}</span>`
            : '<span style="font-size:0.65rem; color: var(--muted-foreground);">بدون ژانر</span>';

        const targetData = {
            id: s.id,
            title: s.title,
            genre: s.genre || '',
            start_year: s.start_year || '',
            end_year: s.end_year || '',
            status: s.status || 'ongoing',
            hasPrequels: hasPrequels
        };

        return `
    <div class="media-item" style="flex-direction:column;align-items:stretch;">
        <div class="flex items-center justify-between">
            <div class="media-info" onclick="editSeries(this)"
                 data-id="${s.id}"
                 data-title="${escapeHtml(s.title)}"
                 data-genre="${s.genre || ''}"
                 data-start-year="${s.start_year || ''}"
                 data-end-year="${s.end_year || ''}"
                 data-status="${s.status}">
                <div class="media-icon" style="position: relative; overflow: hidden;">
                    <span class="media-number">#${rowNumber}</span>
                </div>
                <div class="media-details">
                    <div class="media-title">${escapeHtml(s.title)}</div>
                    <div class="media-meta">
                        <span>${s.start_year || '?'} - ${s.end_year || 'در حال پخش'}</span>
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; margin-right: 0.5rem;">• ${genreDisplay}</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn-icon prequel-btn ${hasPrequels ? 'btn-downloaded' : ''}"
                        data-media-type="series"
                        data-media-id="${s.id}"
                        data-tooltip="${hasPrequels ? `${seriesPrequels.length} عنوان مرتبط` : 'افزودن عنوان مرتبط'}">
                    <i data-lucide="link-2"></i>
                </button>
                <button class="btn-icon edit-series"
                        data-id="${s.id}"
                        data-title="${escapeHtml(s.title)}"
                        data-genre="${s.genre || ''}"
                        data-start-year="${s.start_year || ''}"
                        data-end-year="${s.end_year || ''}"
                        data-status="${s.status}"
                        data-tooltip="ویرایش سریال">
                    <i data-lucide="pencil"></i>
                </button>
                <button class="btn-icon delete-series"
                        data-id="${s.id}"
                        style="color:var(--destructive);"
                        data-tooltip="حذف سریال">
                    <i data-lucide="trash-2"></i>
                </button>
                <button class="btn-more" onclick="event.stopPropagation(); showContextMenu(event, ${JSON.stringify(targetData).replace(/"/g, '&quot;')}, false, false)">
                    <i data-lucide="more-horizontal"></i>
                </button>
            </div>
        </div>
        <div class="seasons-container">
            ${renderSeasons(s.seasons, s.id)}
            <button class="btn btn-outline add-season-btn" data-series-id="${s.id}" style="margin-top:0.75rem;width:100%;">
                <i data-lucide="plus-square"></i> ویرایش فصل‌ها
            </button>
        </div>
    </div>`;
    }).join('');

    refreshIcons();
    attachSeriesEvents();
}

function renderSeasons(seasons, seriesId) {
    if (!seasons?.length) return '<div style="padding:0.5rem 0;">هنوز فصلی اضافه نشده</div>';
    return seasons.map(s => {
        const isWatched = s.watched == 1;
        const hasWatchDate = s.watch_date && s.watch_date !== 0 && s.watch_date !== '1970-01-01';
        const watchDateFormatted = hasWatchDate ? formatDate(s.watch_date) : '';
        const hasRating = s.rating !== null && s.rating !== undefined;
        const seasonComment = (allCommentsCache.seasons && allCommentsCache.seasons[s.id]) || null;
        const hasSeasonComment = seasonComment !== null;

        const targetData = {
            id: s.id,
            seriesId: seriesId,
            season_number: s.season_number,
            episode_count: s.episode_count,
            downloaded: s.downloaded,
            watched: s.watched,
            watch_date: s.watch_date || '',
            rating: s.rating || null,
            hasSeasonComment: hasSeasonComment,
            commentId: seasonComment ? seasonComment.id : ''
        };

        return `
    <div class="season-item" style="flex-direction: column; align-items: stretch; gap: 0.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div><strong>فصل ${s.season_number}</strong> <span style="font-size:0.7rem;">(${s.episode_count} قسمت)</span></div>
            <div class="flex gap-2">
                <button class="btn-icon comment-btn ${hasSeasonComment ? 'has-comment' : ''}"
                        data-media-type="season"
                        data-media-id="${s.id}"
                        data-comment-id="${hasSeasonComment ? seasonComment.id : ''}"
                        data-tooltip="${hasSeasonComment ? 'ویرایش کامنت' : 'افزودن کامنت'}">
                    <i data-lucide="${hasSeasonComment ? 'message-circle' : 'message-square'}"></i>
                </button>
                <button class="btn-icon download-btn ${s.downloaded == 1 ? 'btn-downloaded' : ''}"
                        data-season-id="${s.id}"
                        data-downloaded="${s.downloaded}"
                        data-tooltip="${s.downloaded == 1 ? 'لغو دانلود' : 'دانلود شده'}">
                    <i data-lucide="${s.downloaded == 1 ? 'download' : 'circle-arrow-down'}"></i>
                </button>
                <button class="btn-icon watch-btn ${isWatched ? 'btn-watched' : ''} ${isWatched && hasWatchDate ? 'has-date' : ''}"
                        data-season-id="${s.id}"
                        data-watched="${s.watched}"
                        data-watch-date="${s.watch_date || ''}"
                        data-tooltip="${isWatched ? 'لغو تماشا' : 'ثبت تماشا'}">
                    <i data-lucide="${isWatched ? 'eye' : 'eye-off'}"></i>${isWatched && hasWatchDate ? `<span class="watch-date-label">${watchDateFormatted}</span>` : ''}
                </button>
                <button class="btn-icon rating-btn ${hasRating ? 'has-rating' : ''}"
                        data-season-id="${s.id}"
                        data-rating="${s.rating || ''}"
                        data-tooltip="${hasRating ? 'ویرایش امتیاز' : 'ثبت امتیاز'}">
                    <i data-lucide="star"></i>${hasRating ? `<span class="rating-label">${s.rating}/10</span>` : ''}
                </button>
                <button class="btn-icon edit-season"
                        data-id="${s.id}"
                        data-series-id="${seriesId}"
                        data-season="${s.season_number}"
                        data-episodes="${s.episode_count}"
                        data-tooltip="ویرایش فصل">
                    <i data-lucide="pencil"></i>
                </button>
                <button class="btn-icon delete-season"
                        data-id="${s.id}"
                        style="color:var(--destructive);"
                        data-tooltip="حذف فصل">
                    <i data-lucide="trash-2"></i>
                </button>
                <button class="btn-more" onclick="event.stopPropagation(); showContextMenu(event, ${JSON.stringify(targetData).replace(/"/g, '&quot;')}, false, true)">
                    <i data-lucide="more-horizontal"></i>
                </button>
            </div>
        </div>
        ${hasSeasonComment ? `
        <div class="comment-section">
            <div class="comment-header">
                <i data-lucide="message-circle"></i>
                <span class="comment-author">${escapeHtml(seasonComment.display_name || 'کاربر')}</span>
                <span class="comment-date">${formatDate(seasonComment.created_at)}</span>
            </div>
            <div class="comment-text">${escapeHtml(seasonComment.comment)}</div>
        </div>
        ` : ''}
    </div>`;
    }).join('');
}


/* ============================================================
   SECTION 12: SERIES EVENTS & MOVIE/SERIES MODALS
   ============================================================ */
function attachSeriesEvents() {
    document.querySelectorAll('.edit-series').forEach(btn => {
        btn.addEventListener('click', () => openSeriesModal(btn.dataset));
    });

    document.querySelectorAll('.delete-series').forEach(btn => {
        btn.addEventListener('click', () => {
            confirmDelete('حذف سریال؟', async () => {
                const r = await apiCall('delete_series', { series_id: btn.dataset.id });
                if (r.ok) await reloadCurrentList();
                else showToast(r.message, 'error');
            });
        });
    });

    document.querySelectorAll('.add-season-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentSeriesId = btn.dataset.seriesId;
            openSeasonModal(btn.dataset.seriesId);
        });
    });

    document.querySelectorAll('.edit-season').forEach(btn => {
        btn.addEventListener('click', () => {
            currentSeriesId = btn.dataset.seriesId;
            openSeasonModal(btn.dataset.seriesId, btn.dataset);
        });
    });

    document.querySelectorAll('.delete-season').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            confirmDelete('حذف فصل؟', async () => {
                const r = await apiCall('delete_season', { season_id: btn.dataset.id });
                if (r.ok) await reloadCurrentList();
                else showToast(r.message, 'error');
            });
        });
    });

    document.querySelectorAll('.seasons-container .download-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const seasonId = btn.dataset.seasonId;
            const currentDownloaded = parseInt(btn.dataset.downloaded);
            const newDownloaded = currentDownloaded ? 0 : 1;
            const result = await apiCall('toggle_downloaded_season', { season_id: seasonId, downloaded: newDownloaded });
            if (result.ok) await reloadCurrentList();
            else showToast(result.message, 'error');
        });
    });

    document.querySelectorAll('.seasons-container .watch-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const seasonId = btn.dataset.seasonId;
            const currentWatched = parseInt(btn.dataset.watched);
            if (currentWatched) {
                markAsWatched('season', seasonId, 0, null);
            } else {
                document.getElementById('watchTargetType').value = 'season';
                document.getElementById('watchTargetId').value = seasonId;
                document.getElementById('watchAction').value = 'mark_watched_season';
                initDatePicker();
                document.getElementById('watchDateModal').classList.add('show');
                refreshIcons();
            }
        });
    });

    document.querySelectorAll('.seasons-container .rating-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const seasonId = btn.dataset.seasonId;
            const currentRating = btn.dataset.rating ? parseInt(btn.dataset.rating) : 0;
            openRatingModal('season', seasonId, currentRating);
        });
    });

    document.querySelectorAll('.seasons-container .comment-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const mediaType = btn.dataset.mediaType;
            const mediaId = btn.dataset.mediaId;
            const commentId = btn.dataset.commentId || '';
            openCommentModal(mediaType, mediaId, commentId);
        });
    });
}

function openMovieModal(data = {}) {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان افزودن یا ویرایش فیلم وجود ندارد.', 'warning');
        return;
    }
    const isEdit = data.id;
    document.getElementById('movieModalTitle').innerHTML = isEdit
        ? '<i data-lucide="pencil"></i><span>ویرایش فیلم</span>'
        : '<i data-lucide="plus-square"></i><span>افزودن فیلم</span>';
    document.getElementById('editMovieId').value = data.id || '';
    document.getElementById('movieTitle').value = data.title || '';
    document.getElementById('movieYear').value = data.year || '';

    const existingGenres = data.genre ? data.genre.split(',').map(g => g.trim()).filter(g => g) : [];
    loadGenres().then(() => {
        if (movieGenreManager) {
            movieGenreManager.setTags(existingGenres);
        } else {
            movieGenreManager = initGenreTags('movieGenreContainer', 'movieGenreInput', 'movieGenreCounter', 'movieGenre', existingGenres);
        }
        warnGenreReview(data.title, existingGenres);
    });

    document.getElementById('saveMovieBtn').onclick = saveMovie;
    document.getElementById('movieModal').classList.add('show');
    refreshIcons();
    setTimeout(() => document.getElementById('movieTitle').focus(), 100);
}

function warnGenreReview(title, genres) {
    if (!genres.length) return;
    const libraryNames = new Set(allLibraryGenres.map(g => g.name));
    const unknown = genres.filter(g => !libraryNames.has(g));
    if (unknown.length) {
        showToast(`ژانرهای «${unknown.join('، ')}» در کتابخانه ژانرها نیستند. لطفاً با «کتابخانه ژانرها» بازبینی کنید.`, 'warning');
    }
}

async function saveMovie() {
    const title = document.getElementById('movieTitle').value.trim();
    if (!title) {
        showAlert(document.getElementById('movieModalAlert'), 'عنوان فیلم الزامی است', 'error');
        return;
    }

    const movieId = document.getElementById('editMovieId').value;
    const data = {
        list_id: currentListId,
        title: title,
        genre: document.getElementById('movieGenre').value,
        release_year: document.getElementById('movieYear').value || null
    };

    if (movieId) data.movie_id = movieId;

    const action = movieId ? 'update_movie' : 'add_movie';
    const result = await apiCall(action, data);

    if (result.ok) {
        document.getElementById('movieModal').classList.remove('show');
        document.getElementById('movieModalAlert').style.display = 'none';
        await loadFullList(currentListId);
        await syncGenresToLibrary(data.genre);
        loadGenres();
    } else {
        showAlert(document.getElementById('movieModalAlert'), result.message, 'error');
    }
}

function openSeriesModal(data = {}) {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان افزودن یا ویرایش سریال وجود ندارد.', 'warning');
        return;
    }
    document.getElementById('seriesModalTitle').innerHTML = data.id
        ? '<i data-lucide="pencil"></i><span>ویرایش سریال</span>'
        : '<i data-lucide="plus-square"></i><span>افزودن سریال</span>';
    document.getElementById('editSeriesId').value = data.id || '';
    document.getElementById('seriesTitle').value = data.title || '';
    document.getElementById('seriesStartYear').value = data.startYear || '';
    document.getElementById('seriesEndYear').value = data.endYear || '';
    document.getElementById('seriesStatus').value = data.status || 'ongoing';

    const existingGenres = data.genre ? data.genre.split(',').map(g => g.trim()).filter(g => g) : [];
    loadGenres().then(() => {
        if (seriesGenreManager) {
            seriesGenreManager.setTags(existingGenres);
        } else {
            seriesGenreManager = initGenreTags('seriesGenreContainer', 'seriesGenreInput', 'seriesGenreCounter', 'seriesGenre', existingGenres);
        }
        warnGenreReview(data.title, existingGenres);
    });

    document.getElementById('seriesModal').classList.add('show');
    refreshIcons();
    document.getElementById('saveSeriesBtn').onclick = saveSeries;
}

async function saveSeries() {
    const title = document.getElementById('seriesTitle').value.trim();
    if (!title) {
        showAlert(document.getElementById('seriesModalAlert'), 'عنوان سریال الزامی است', 'error');
        return;
    }
    const result = await apiCall(document.getElementById('editSeriesId').value ? 'update_series' : 'add_series', {
        list_id: currentListId,
        series_id: document.getElementById('editSeriesId').value,
        title,
        genre: document.getElementById('seriesGenre').value,
        start_year: document.getElementById('seriesStartYear').value || null,
        end_year: document.getElementById('seriesEndYear').value || null,
        status: document.getElementById('seriesStatus').value
    });
    if (result.ok) {
        document.getElementById('seriesModal').classList.remove('show');
        loadFullList(currentListId);
        await syncGenresToLibrary(document.getElementById('seriesGenre').value);
        loadGenres();
    } else {
        showAlert(document.getElementById('seriesModalAlert'), result.message, 'error');
    }
}


/* ============================================================
   SECTION 13: SEASONS MANAGEMENT
   ============================================================ */
let currentSeriesIdForSeasons = null;
let existingSeasonsList = [];

async function openSeasonModal(seriesId, editData = null) {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان افزودن یا ویرایش فصل وجود ندارد.', 'warning');
        return;
    }
    currentSeriesIdForSeasons = seriesId;

    if (editData && editData.id) {
        openSingleSeasonModal(editData);
        return;
    }

    document.getElementById('seasonModalTitle').innerHTML =
        '<i data-lucide="plus-square"></i><span>افزودن فصل‌های جدید به سریال</span>';
    document.getElementById('seasonSeriesId').value = seriesId;
    document.getElementById('editSeasonId').value = '';

    await loadExistingSeasons(seriesId);
    resetSeasonsForm();

    document.getElementById('seasonModalAlert').style.display = 'none';
    document.getElementById('duplicateSeasonWarning').style.display = 'none';
    document.getElementById('seasonModal').classList.add('show');
    refreshIcons();
}

async function loadExistingSeasons(seriesId) {
    const result = await apiCall('get_full_list', { list_id: currentListId });
    if (result.ok && result.data) {
        const series = result.data.series.find(s => s.id == seriesId);
        if (series && series.seasons) {
            existingSeasonsList = series.seasons.sort((a, b) => a.season_number - b.season_number);
            displayExistingSeasons(existingSeasonsList);
        } else {
            existingSeasonsList = [];
            document.getElementById('existingSeasonsList').innerHTML =
                '<div style="color: var(--muted-foreground); font-size: 0.8rem;">هیچ فصلی ثبت نشده است</div>';
        }
    }
}

function displayExistingSeasons(seasons) {
    const container = document.getElementById('existingSeasonsList');
    if (!seasons.length) {
        container.innerHTML = '<div style="color: var(--muted-foreground); font-size: 0.8rem;">هیچ فصلی ثبت نشده است</div>';
        return;
    }

    container.innerHTML = `
    <div style="margin-bottom: 0.5rem;">
        <strong style="color: var(--primary); font-size: 0.8rem;">فصل‌های ثبت شده:</strong>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
        ${seasons.map(season => `
            <div class="existing-season-item" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.75rem; background: var(--secondary); border-radius: 8px; font-size: 0.75rem;">
                <span>فصل ${season.season_number}</span>
                <span style="color: var(--muted-foreground);">(${season.episode_count} قسمت)</span>
                ${season.downloaded == 1 ? '<span class="status-badge badge-downloaded" style="padding: 0 0.25rem;"><i data-lucide="download" style="width:14px;height:14px;"></i></span>' : ''}
                ${season.watched == 1 ? '<span class="status-badge badge-watched" style="padding: 0 0.25rem;"><i data-lucide="eye" style="width:14px;height:14px;"></i></span>' : ''}
                <button class="btn-icon edit-existing-season"
                        data-id="${season.id}"
                        data-season="${season.season_number}"
                        data-episodes="${season.episode_count}"
                        style="padding: 0.2rem; width: 24px; height: 24px;"
                        title="ویرایش">
                    <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                </button>
                <button class="btn-icon delete-existing-season"
                        data-id="${season.id}"
                        style="padding: 0.2rem; width: 24px; height: 24px; color: var(--destructive);"
                        title="حذف">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                </button>
            </div>
        `).join('')}
    </div>
`;

    refreshIcons();

    document.querySelectorAll('.edit-existing-season').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openSingleSeasonModal({
                id: btn.dataset.id,
                season: btn.dataset.season,
                episodes: btn.dataset.episodes
            });
        });
    });

    document.querySelectorAll('.delete-existing-season').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            confirmDelete('آیا از حذف این فصل مطمئن هستید؟', async () => {
                const result = await apiCall('delete_season', { season_id: btn.dataset.id });
                if (result.ok) {
                    await loadExistingSeasons(currentSeriesIdForSeasons);
                    await reloadCurrentList();
                } else {
                    showToast(result.message, 'error');
                }
            });
        });
    });
}

function openSingleSeasonModal(data) {
    const modalHtml = `
    <div id="singleSeasonModal" class="modal show" style="z-index: 1070;">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i data-lucide="pencil"></i><span>ویرایش فصل ${data.season}</span></h3>
                <button class="btn-icon close-single-season-modal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="singleSeasonAlert" class="alert"></div>
                <div class="form-group">
                    <label class="form-label">شماره فصل</label>
                    <input type="number" id="singleSeasonNumber" class="form-input" value="${data.season}" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">تعداد قسمت‌ها</label>
                    <input type="number" id="singleEpisodeCount" class="form-input" value="${data.episodes}" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">وضعیت دانلود</label>
                    <select id="singleDownloaded" class="form-select">
                        <option value="0">دانلود نشده</option>
                        <option value="1">دانلود شده</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">وضعیت تماشا</label>
                    <select id="singleWatched" class="form-select">
                        <option value="0">مشاهده نشده</option>
                        <option value="1">مشاهده شده</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close-single-season-modal">انصراف</button>
                <button id="saveSingleSeasonBtn" class="btn btn-primary">ذخیره تغییرات</button>
            </div>
        </div>
    </div>
`;

    const existingModal = document.getElementById('singleSeasonModal');
    if (existingModal) existingModal.remove();
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    refreshIcons();

    const modal = document.getElementById('singleSeasonModal');
    const closeBtns = modal.querySelectorAll('.close-single-season-modal');
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => modal.remove());
    });

    modal.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') modal.remove();
    });

    document.getElementById('saveSingleSeasonBtn').addEventListener('click', async () => {
        const seasonNumber = document.getElementById('singleSeasonNumber').value;
        const episodeCount = document.getElementById('singleEpisodeCount').value;
        const downloaded = document.getElementById('singleDownloaded').value;
        const watched = document.getElementById('singleWatched').value;

        if (!seasonNumber) {
            showAlert(document.getElementById('singleSeasonAlert'), 'شماره فصل الزامی است', 'error');
            return;
        }

        const result = await apiCall('update_season', {
            season_id: data.id,
            season_number: seasonNumber,
            episode_count: episodeCount || 0,
            downloaded: downloaded,
            watched: watched,
            watch_date: null
        });

        if (result.ok) {
            modal.remove();
            await loadExistingSeasons(currentSeriesIdForSeasons);
            await reloadCurrentList();
        } else {
            showAlert(document.getElementById('singleSeasonAlert'), result.message, 'error');
        }
    });

    setTimeout(() => document.getElementById('singleSeasonNumber').focus(), 100);
}

function resetSeasonsForm() {
    const container = document.getElementById('seasonsInputsContainer');
    container.innerHTML = `
    <div class="season-row flex gap-2 mb-2" style="align-items: flex-end;">
        <div class="form-group" style="width: 120px; margin-bottom: 0;">
            <label class="form-label">شماره فصل</label>
            <input type="number" class="form-input season-number" placeholder="مثلاً 1" min="1" step="1">
        </div>
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label class="form-label">تعداد قسمت‌ها</label>
            <input type="number" class="form-input season-episodes" placeholder="مثلاً 10" min="0" step="1">
        </div>
        <button type="button" class="btn-icon remove-season-row" style="color: var(--destructive); flex-shrink: 0; visibility: hidden;">
            <i data-lucide="x"></i>
        </button>
    </div>
`;
    refreshIcons();
}

function addSeasonRow() {
    const container = document.getElementById('seasonsInputsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'season-row flex gap-2 mb-2';
    newRow.style.alignItems = 'flex-end';
    newRow.innerHTML = `
    <div class="form-group" style="width: 120px; margin-bottom: 0;">
        <input type="number" class="form-input season-number" placeholder="شماره فصل" min="1" step="1">
    </div>
    <div class="form-group" style="flex: 1; margin-bottom: 0;">
        <input type="number" class="form-input season-episodes" placeholder="تعداد قسمت‌ها" min="0" step="1">
    </div>
    <button type="button" class="btn-icon remove-season-row" style="color: var(--destructive); flex-shrink: 0;">
        <i data-lucide="x"></i>
    </button>
`;

    container.appendChild(newRow);
    refreshIcons();
    newRow.querySelector('.season-number').focus();

    newRow.querySelector('.remove-season-row').addEventListener('click', () => {
        if (container.children.length > 1) {
            newRow.remove();
        } else {
            newRow.querySelector('.season-number').value = '';
            newRow.querySelector('.season-episodes').value = '';
        }
    });
}

async function saveAllSeasons() {
    const seriesId = document.getElementById('seasonSeriesId').value;
    const rows = document.querySelectorAll('#seasonsInputsContainer .season-row');
    const newSeasons = [];
    const seenNumbers = new Set();
    let hasDuplicate = false;

    rows.forEach(row => {
        const number = parseInt(row.querySelector('.season-number')?.value);
        const episodes = parseInt(row.querySelector('.season-episodes')?.value) || 0;

        if (number && !isNaN(number) && number > 0) {
            if (seenNumbers.has(number)) hasDuplicate = true;
            seenNumbers.add(number);
            newSeasons.push({ season_number: number, episode_count: episodes });
        }
    });

    const existingNumbers = existingSeasonsList.map(s => s.season_number);
    const conflictWithExisting = newSeasons.filter(s => existingNumbers.includes(s.season_number));

    if (conflictWithExisting.length > 0) {
        document.getElementById('duplicateSeasonWarning').innerHTML =
            `⚠️ شماره فصل‌های ${conflictWithExisting.map(s => s.season_number).join(', ')} از قبل وجود دارند!`;
        document.getElementById('duplicateSeasonWarning').style.display = 'block';
        return;
    }

    if (hasDuplicate) {
        document.getElementById('duplicateSeasonWarning').innerHTML =
            '⚠️ شماره فصل تکراری در فرم! لطفاً شماره‌های منحصربه‌فرد وارد کنید.';
        document.getElementById('duplicateSeasonWarning').style.display = 'block';
        return;
    }

    if (newSeasons.length === 0) {
        showAlert(document.getElementById('seasonModalAlert'), 'حداقل یک فصل معتبر وارد کنید', 'error');
        return;
    }

    document.getElementById('duplicateSeasonWarning').style.display = 'none';
    newSeasons.sort((a, b) => a.season_number - b.season_number);

    let allSuccess = true;
    for (const season of newSeasons) {
        const result = await apiCall('add_season', {
            series_id: seriesId,
            season_number: season.season_number,
            episode_count: season.episode_count
        });
        if (!result.ok) {
            allSuccess = false;
            showToast(`خطا در افزودن فصل ${season.season_number}: ${result.message}`, 'error');
            break;
        }
    }

    if (allSuccess) {
        document.getElementById('seasonModal').classList.remove('show');
        await loadExistingSeasons(seriesId);
        await reloadCurrentList();
        showToast(`${newSeasons.length} فصل با موفقیت اضافه شد`, 'success');
    }
}

document.getElementById('addMoreSeasonBtn')?.addEventListener('click', addSeasonRow);
document.getElementById('saveSeasonsBtn')?.addEventListener('click', saveAllSeasons);

document.querySelectorAll('.close-season-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('seasonModal').classList.remove('show');
    });
});


/* ============================================================
   SECTION 14: WATCH DATE MODAL & DATE PICKER
   ============================================================ */
async function markAsWatched(type, id, watched, watchDate) {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان تغییر وضعیت مشاهده وجود ندارد.', 'warning');
        return;
    }
    if (type === 'prequel') {
        const data = {
            prequel_id: id,
            watched: watched ? 1 : 0,
            watch_date: watched ? (watchDate ? watchDate : '') : ''
        };
        const result = await apiCall('update_prequel_item', data);
        if (result.ok) {
            showToast('وضعیت تماشا به‌روزرسانی شد', 'success');
            await loadPrequelData();
            if (currentListId) loadFullList(currentListId);
        } else {
            showToast(result.message || 'خطا در ثبت وضعیت تماشا', 'error');
        }
        return;
    }
    const action = type === 'movie' ? 'mark_watched_movie' : 'mark_watched_season';
    const data = {
        [type === 'movie' ? 'movie_id' : 'season_id']: id,
        watched: watched,
        watch_date: watched ? (watchDate ? watchDate : 0) : 0
    };

    const result = await apiCall(action, data);
    if (result.ok) {
        await loadFullList(currentListId);
        if (document.getElementById('prequelModal')?.classList.contains('show')) {
            await loadPrequelData();
        }
    } else {
        showToast(result.message, 'error');
    }
}

document.getElementById('confirmWatchBtn').addEventListener('click', async () => {
    const type = document.getElementById('watchTargetType').value;
    const id = document.getElementById('watchTargetId').value;
    const date = getWatchDateNumber();

    if (!date) {
        document.getElementById('watchDateAlert').textContent = 'لطفاً تاریخ را انتخاب کنید';
        document.getElementById('watchDateAlert').className = 'alert alert-error';
        document.getElementById('watchDateAlert').style.display = 'block';
        return;
    }

    await markAsWatched(type, id, 1, date);
    document.getElementById('watchDateModal').classList.remove('show');
    document.getElementById('watchDateAlert').style.display = 'none';
});

document.getElementById('watchTodayBtn').addEventListener('click', async () => {
    const type = document.getElementById('watchTargetType').value;
    const id = document.getElementById('watchTargetId').value;

    const btn = document.getElementById('watchTodayBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = 'در حال دریافت تاریخ...';
    btn.disabled = true;

    try {
        const result = await apiCall('get_persian_date');
        if (result.ok && result.data && result.data.date) {
            await markAsWatched(type, id, 1, result.data.date);
            document.getElementById('watchDateModal').classList.remove('show');
            document.getElementById('watchDateAlert').style.display = 'none';
            showToast('تاریخ امروز با موفقیت ثبت شد', 'success');
        } else {
            showToast('خطا در دریافت تاریخ امروز', 'error');
        }
    } catch (error) {
        console.error('Error fetching date:', error);
        showToast('خطا در دریافت تاریخ امروز', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

document.querySelectorAll('.close-watchdate-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('watchDateModal').classList.remove('show');
        document.getElementById('watchDateAlert').style.display = 'none';
    });
});

document.getElementById('watchWithoutDateBtn').addEventListener('click', async () => {
    const type = document.getElementById('watchTargetType').value;
    const id = document.getElementById('watchTargetId').value;
    await markAsWatched(type, id, 1, null);
    document.getElementById('watchDateModal').classList.remove('show');
    document.getElementById('watchDateAlert').style.display = 'none';
});

// ===== DATE PICKER STATE =====
let selectedDay = 15;
let selectedMonth = 1;
let selectedYear = 1404;
let isScrollingProgrammatically = false;

const persianMonths = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
];

function getDaysInPersianMonth(month, year) {
    if (month <= 6) return 31;
    if (month <= 11) return 30;
    const isLeap = (year % 33 === 1) || (year % 33 === 5) || (year % 33 === 9) ||
        (year % 33 === 13) || (year % 33 === 17) || (year % 33 === 22) ||
        (year % 33 === 26) || (year % 33 === 30);
    return isLeap ? 30 : 29;
}

function populateWheel(wheelId, items, selectedValue, onSelectCallback) {
    const wheel = document.getElementById(wheelId);
    if (!wheel) return;
    wheel.innerHTML = '';

    for (let i = 0; i < 5; i++) {
        const spacer = document.createElement('div');
        spacer.style.height = '20px';
        spacer.style.flexShrink = '0';
        spacer.style.pointerEvents = 'none';
        wheel.appendChild(spacer);
    }

    items.forEach((item) => {
        const div = document.createElement('div');
        div.className = 'wheel-item';
        const value = typeof item === 'object' ? item.value : item;
        div.textContent = typeof item === 'object' ? item.label : item;
        div.dataset.value = value;
        if (value == selectedValue) div.classList.add('selected');
        wheel.appendChild(div);
    });

    for (let i = 0; i < 5; i++) {
        const spacer = document.createElement('div');
        spacer.style.height = '30px';
        spacer.style.flexShrink = '0';
        spacer.style.pointerEvents = 'none';
        wheel.appendChild(spacer);
    }

    isScrollingProgrammatically = true;
    setTimeout(() => {
        const selected = wheel.querySelector('.selected');
        if (selected) {
            const container = wheel.parentElement;
            const containerHeight = container.clientHeight;
            const itemHeight = 36;
            const scrollPosition = selected.offsetTop - (containerHeight / 2) + (itemHeight / 2);
            wheel.scrollTop = scrollPosition;
        }
        setTimeout(() => { isScrollingProgrammatically = false; }, 100);
    }, 50);
}

function setupWheelScroll(wheelId, onSelectCallback) {
    const wheel = document.getElementById(wheelId);
    if (!wheel) return;

    let scrollTimeout;
    const itemHeight = 36;
    const centerOffset = 71.5;

    function getSelectedItemFromScroll() {
        const items = wheel.querySelectorAll('.wheel-item:not([style*="height: 30px"])');
        if (!items.length) return null;

        const scrollTop = wheel.scrollTop;
        let closestItem = null;
        let closestDistance = Infinity;

        items.forEach(item => {
            const itemCenter = item.offsetTop + (itemHeight / 2);
            const scrollCenter = scrollTop + centerOffset;
            const distance = Math.abs(itemCenter - scrollCenter);
            if (distance < closestDistance) {
                closestDistance = distance;
                closestItem = item;
            }
        });

        return closestItem;
    }

    function updateSelection() {
        if (isScrollingProgrammatically) return;
        const closestItem = getSelectedItemFromScroll();
        if (!closestItem) return;

        const value = parseInt(closestItem.dataset.value);
        let currentValue = null;
        if (wheelId === 'dayWheel') currentValue = selectedDay;
        else if (wheelId === 'monthWheel') currentValue = selectedMonth;
        else if (wheelId === 'yearWheel') currentValue = selectedYear;

        if (value !== currentValue) {
            wheel.querySelectorAll('.wheel-item').forEach(item => item.classList.remove('selected'));
            closestItem.classList.add('selected');
            if (onSelectCallback) onSelectCallback(value);
        }
    }

    wheel.addEventListener('scroll', () => {
        if (isScrollingProgrammatically) return;
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => { updateSelection(); }, 30);
    });

    wheel.addEventListener('click', (e) => {
        if (isScrollingProgrammatically) return;
        const target = e.target.closest('.wheel-item');
        if (target && !target.style.height) {
            isScrollingProgrammatically = true;
            const scrollPosition = target.offsetTop - 71.5;
            wheel.scrollTo({ top: scrollPosition, behavior: 'smooth' });
            setTimeout(() => {
                updateSelection();
                setTimeout(() => { isScrollingProgrammatically = false; }, 200);
            }, 200);
        }
    });
}

function initDatePicker() {
    const currentYear = 1404;
    const yearItems = Array.from({ length: 111 }, (_, i) => currentYear - 50 + i);
    const monthItems = persianMonths.map((name, i) => ({ label: name, value: i + 1 }));

    let isUpdating = false;

    function updateDays() {
        if (isUpdating) return;
        isUpdating = true;

        const newDays = getDaysInPersianMonth(selectedMonth, selectedYear);
        if (selectedDay > newDays) selectedDay = newDays;

        const dayItems = Array.from({ length: newDays }, (_, i) => i + 1);
        populateWheel('dayWheel', dayItems, selectedDay, (value) => {
            if (!isUpdating) {
                selectedDay = value;
                updateWatchDateDisplay();
            }
        });

        setTimeout(() => { isUpdating = false; }, 150);
    }

    const initialDays = getDaysInPersianMonth(selectedMonth, selectedYear);
    const dayItems = Array.from({ length: initialDays }, (_, i) => i + 1);

    populateWheel('yearWheel', yearItems, selectedYear, (value) => {
        if (!isUpdating) { selectedYear = value; updateDays(); updateWatchDateDisplay(); }
    });

    populateWheel('monthWheel', monthItems, selectedMonth, (value) => {
        if (!isUpdating) { selectedMonth = value; updateDays(); updateWatchDateDisplay(); }
    });

    populateWheel('dayWheel', dayItems, selectedDay, (value) => {
        if (!isUpdating) { selectedDay = value; updateWatchDateDisplay(); }
    });

    setupWheelScroll('yearWheel', (value) => {
        if (!isUpdating) { selectedYear = value; updateDays(); updateWatchDateDisplay(); }
    });

    setupWheelScroll('monthWheel', (value) => {
        if (!isUpdating) { selectedMonth = value; updateDays(); updateWatchDateDisplay(); }
    });

    setupWheelScroll('dayWheel', (value) => {
        if (!isUpdating) { selectedDay = value; updateWatchDateDisplay(); }
    });

    updateWatchDateDisplay();
}

function getWatchDateNumber() {
    return selectedYear * 10000 + selectedMonth * 100 + selectedDay;
}

function updateWatchDateDisplay() {
    const display = document.getElementById('watchDateDisplay');
    if (display) {
        display.textContent = `${selectedDay} ${persianMonths[selectedMonth - 1]} ${selectedYear}`;
    }
}


/* ============================================================
   SECTION 15: RATING MODAL
   ============================================================ */
let selectedRating = 0;
let currentRatingExtra = null;

function openRatingModal(type, id, currentRating, extraData = null) {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان ثبت یا تغییر امتیاز وجود ندارد.', 'warning');
        return;
    }
    document.getElementById('ratingTargetType').value = type;
    document.getElementById('ratingTargetId').value = id;
    document.getElementById('ratingAction').value = type === 'movie' ? 'rate_movie' : (type === 'season' ? 'rate_season' : 'update_prequel_item');
    currentRatingExtra = extraData;

    selectedRating = currentRating || 0;
    updateStars(selectedRating);

    document.getElementById('ratingAlert').style.display = 'none';
    document.getElementById('ratingModal').classList.add('show');
    refreshIcons();
}

function updateStars(rating) {
    const stars = document.querySelectorAll('#starRating .star');
    const ratingValue = document.getElementById('ratingValue');

    stars.forEach(star => {
        const value = parseInt(star.dataset.value);
        if (value <= rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });

    if (rating > 0) {
        ratingValue.textContent = `امتیاز: ${rating} از ۱۰`;
        ratingValue.style.color = '#f59e0b';
    } else {
        ratingValue.textContent = 'بدون امتیاز';
        ratingValue.style.color = 'var(--muted-foreground)';
    }
}

document.getElementById('starRating').addEventListener('click', function(e) {
    if (e.target.classList.contains('star')) {
        selectedRating = parseInt(e.target.dataset.value);
        updateStars(selectedRating);
    }
});

document.getElementById('starRating').addEventListener('mouseover', function(e) {
    if (e.target.classList.contains('star')) {
        const hoverValue = parseInt(e.target.dataset.value);
        const stars = document.querySelectorAll('#starRating .star');
        stars.forEach(s => {
            const value = parseInt(s.dataset.value);
            if (value <= hoverValue && !s.classList.contains('active')) {
                s.classList.add('hover');
            }
        });
    }
});

document.getElementById('starRating').addEventListener('mouseout', function() {
    const stars = document.querySelectorAll('#starRating .star');
    stars.forEach(s => s.classList.remove('hover'));
});

document.getElementById('confirmRatingBtn').addEventListener('click', async () => {
    if (selectedRating === 0) {
        showAlert(document.getElementById('ratingAlert'), 'لطفاً امتیاز را انتخاب کنید', 'error');
        return;
    }

    const type = document.getElementById('ratingTargetType').value;
    const id = document.getElementById('ratingTargetId').value;

    if (type === 'prequel') {
        const isMain = currentRatingExtra && currentRatingExtra.is_main;
        if (isMain) {
            if (currentPrequelMediaType === 'movie') {
                await apiCall('rate_movie', { movie_id: id, rating: selectedRating });
            }
        } else {
            await apiCall('update_prequel_item', { prequel_id: id, rating: selectedRating });
        }
        document.getElementById('ratingModal').classList.remove('show');
        document.getElementById('ratingAlert').style.display = 'none';
        showToast('امتیاز با موفقیت ثبت شد', 'success');
        if (typeof loadPrequelData === 'function') await loadPrequelData();
        await reloadCurrentList();
        return;
    }

    const action = type === 'movie' ? 'rate_movie' : 'rate_season';

    const data = {
        [type === 'movie' ? 'movie_id' : 'season_id']: id,
        rating: selectedRating
    };

    const result = await apiCall(action, data);
    if (result.ok) {
        document.getElementById('ratingModal').classList.remove('show');
        document.getElementById('ratingAlert').style.display = 'none';
        await loadFullList(currentListId);
    } else {
        showAlert(document.getElementById('ratingAlert'), result.message, 'error');
    }
});

document.getElementById('removeRatingBtn').addEventListener('click', async () => {
    const type = document.getElementById('ratingTargetType').value;
    const id = document.getElementById('ratingTargetId').value;

    if (type === 'prequel') {
        const isMain = currentRatingExtra && currentRatingExtra.is_main;
        if (isMain) {
            if (currentPrequelMediaType === 'movie') {
                await apiCall('rate_movie', { movie_id: id, rating: null });
            }
        } else {
            await apiCall('update_prequel_item', { prequel_id: id, rating: null });
        }
        document.getElementById('ratingModal').classList.remove('show');
        document.getElementById('ratingAlert').style.display = 'none';
        showToast('امتیاز با موفقیت حذف شد', 'success');
        if (typeof loadPrequelData === 'function') await loadPrequelData();
        await reloadCurrentList();
        return;
    }

    const action = type === 'movie' ? 'rate_movie' : 'rate_season';

    const data = {
        [type === 'movie' ? 'movie_id' : 'season_id']: id,
        rating: null
    };

    const result = await apiCall(action, data);
    if (result.ok) {
        document.getElementById('ratingModal').classList.remove('show');
        document.getElementById('ratingAlert').style.display = 'none';
        await loadFullList(currentListId);
    } else {
        showAlert(document.getElementById('ratingAlert'), result.message, 'error');
    }
});

document.querySelectorAll('.close-rating-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('ratingModal').classList.remove('show');
        document.getElementById('ratingAlert').style.display = 'none';
    });
});


/* ============================================================
   SECTION 16: COMMENT MODAL
   ============================================================ */
async function openCommentModal(mediaType, mediaId, commentId, currentText = '') {
    if (!navigator.onLine) {
        showToast('در حالت آفلاین امکان ثبت یا ویرایش یادداشت وجود ندارد.', 'warning');
        return;
    }
    document.getElementById('commentMediaType').value = mediaType;
    document.getElementById('commentMediaId').value = mediaId;
    document.getElementById('commentId').value = commentId || '';

    let typeLabel = 'یادداشت';
    if (mediaType === 'movie') typeLabel = 'فیلم';
    else if (mediaType === 'season') typeLabel = 'فصل';
    else if (mediaType === 'prequel') typeLabel = 'عنوان مرتبط';

    document.getElementById('commentModalTitle').innerHTML =
        `<i data-lucide="message-square"></i><span>یادداشت ${typeLabel}</span>`;

    if (mediaType === 'prequel') {
        document.getElementById('commentText').value = currentText || '';
        document.getElementById('deleteCommentBtn').style.display = currentText ? 'inline-flex' : 'none';
    } else if (commentId) {
        const result = await apiCall('get_comment', { media_type: mediaType, media_id: mediaId });
        if (result.ok && result.data) {
            document.getElementById('commentText').value = result.data.comment || '';
        } else {
            document.getElementById('commentText').value = '';
        }
        document.getElementById('deleteCommentBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('commentText').value = '';
        document.getElementById('deleteCommentBtn').style.display = 'none';
    }

    updateCharCount();
    document.getElementById('commentAlert').style.display = 'none';
    document.getElementById('commentModal').classList.add('show');
    refreshIcons();
    document.getElementById('commentText').focus();
}

function updateCharCount() {
    const textarea = document.getElementById('commentText');
    const charCount = document.getElementById('commentCharCount');
    const charWarning = document.getElementById('commentCharWarning');
    const saveBtn = document.getElementById('saveCommentBtn');
    const length = textarea.value.length;
    const maxLength = 1000;

    charCount.textContent = `${length.toLocaleString('fa-IR')} / ${maxLength.toLocaleString('fa-IR')}`;
    charCount.classList.remove('warning', 'danger');
    if (length > 900) charCount.classList.add('warning');
    if (length > 980) charCount.classList.add('danger');

    if (length > maxLength) {
        saveBtn.disabled = true;
        saveBtn.style.opacity = '0.5';
        saveBtn.style.cursor = 'not-allowed';
        charWarning.style.display = 'block';
    } else {
        saveBtn.disabled = false;
        saveBtn.style.opacity = '1';
        saveBtn.style.cursor = 'pointer';
        charWarning.style.display = 'none';
    }
}

document.getElementById('commentText').addEventListener('input', updateCharCount);

document.getElementById('saveCommentBtn').addEventListener('click', async () => {
    const mediaType = document.getElementById('commentMediaType').value;
    const mediaId = document.getElementById('commentMediaId').value;
    const commentText = document.getElementById('commentText').value.trim();

    if (!commentText) {
        showAlert(document.getElementById('commentAlert'), 'متن کامنت نمی‌تواند خالی باشد', 'error');
        return;
    }

    if (commentText.length > 1000) {
        showAlert(document.getElementById('commentAlert'), 'کامنت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد', 'error');
        return;
    }

    if (mediaType === 'prequel') {
        const result = await apiCall('update_prequel_item', {
            prequel_id: mediaId,
            comment: commentText
        });
        if (result.ok) {
            document.getElementById('commentModal').classList.remove('show');
            document.getElementById('commentAlert').style.display = 'none';
            showToast('یادداشت با موفقیت ذخیره شد', 'success');
            await loadPrequelData();
            if (currentListId) loadFullList(currentListId);
        } else {
            showAlert(document.getElementById('commentAlert'), result.message || 'خطا در ثبت یادداشت', 'error');
        }
        return;
    }

    const result = await apiCall('save_comment', {
        media_type: mediaType,
        media_id: mediaId,
        comment: commentText
    });

    if (result.ok) {
        document.getElementById('commentModal').classList.remove('show');
        document.getElementById('commentAlert').style.display = 'none';
        await loadFullList(currentListId);
        if (document.getElementById('prequelModal')?.classList.contains('show')) {
            await loadPrequelData();
        }
    } else {
        showAlert(document.getElementById('commentAlert'), result.message, 'error');
    }
});

document.getElementById('deleteCommentBtn').addEventListener('click', async () => {
    const mediaType = document.getElementById('commentMediaType').value;
    const mediaId = document.getElementById('commentMediaId').value;
    const commentId = document.getElementById('commentId').value;

    confirmDelete('آیا از حذف این کامنت مطمئن هستید؟', async () => {
        if (mediaType === 'prequel') {
            const result = await apiCall('update_prequel_item', {
                prequel_id: mediaId,
                comment: ''
            });
            if (result.ok) {
                document.getElementById('commentModal').classList.remove('show');
                document.getElementById('commentAlert').style.display = 'none';
                showToast('یادداشت حذف شد', 'success');
                await loadPrequelData();
                if (currentListId) loadFullList(currentListId);
            } else {
                showToast(result.message || 'خطا در حذف یادداشت', 'error');
            }
            return;
        }

        if (!commentId) return;
        const result = await apiCall('delete_comment', { comment_id: commentId });
        if (result.ok) {
            document.getElementById('commentModal').classList.remove('show');
            document.getElementById('commentAlert').style.display = 'none';
            await loadFullList(currentListId);
            if (document.getElementById('prequelModal')?.classList.contains('show')) {
                await loadPrequelData();
            }
        } else {
            showToast(result.message, 'error');
        }
    });
});

document.querySelectorAll('.close-comment-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('commentModal').classList.remove('show');
        document.getElementById('commentAlert').style.display = 'none';
    });
});


/* ============================================================
   SECTION 17: PREQUEL MODAL & ADVANCED TIMELINE
   ============================================================ */
// ==================== ADVANCED PREQUEL & TIMELINE SYSTEM ====================
    let currentPrequelMediaType = 'movie';
    let currentPrequelMediaId = 0;
    let currentPrequelMainMedia = null;
    let currentPrequelItems = [];
    let currentPrequelView = 'story'; // 'story' or 'release'
    let currentPrequelType = 'prequel';
    let currentRelatedType = 'movie';
    let activeTimelineItem = null;

    async function openPrequelModal(mediaType, mediaId) {
        currentPrequelMediaType = mediaType;
        currentPrequelMediaId = mediaId;
        activeTimelineItem = null;

        document.getElementById('prequelMediaType').value = mediaType;
        document.getElementById('prequelMediaId').value = mediaId;

        // بستن پاپ‌اور و فرم افزودن
        closeNodePopover();
        const drawer = document.getElementById('compactAddPrequelDrawer');
        if (drawer) drawer.style.display = 'none';
        const toggleBtnText = document.getElementById('toggleAddPrequelText');
        if (toggleBtnText) toggleBtnText.textContent = 'افزودن عنوان';

        // بازنشانی فیلدهای افزودن
        resetNewPrequelForm();

        // نمایش لودینگ اولیه
        document.getElementById('prequelAlert').style.display = 'none';
        document.getElementById('prequelModal').classList.add('show');

        // بارگذاری اطلاعات از API
        await loadPrequelData();
    }

    async function loadPrequelData() {
        const releaseContainer = document.getElementById('releaseItemsList');
        if (releaseContainer && (!currentPrequelItems || currentPrequelItems.length === 0)) {
            releaseContainer.innerHTML = `
                <div class="skeleton-media-item"><div class="skeleton-media-icon"></div><div class="skeleton-media-info"><div class="skeleton-line skeleton-line-md"></div><div class="skeleton-line skeleton-line-sm"></div></div><div class="skeleton-media-actions"><div class="skeleton-media-btn"></div><div class="skeleton-media-btn"></div></div></div>
                <div class="skeleton-media-item"><div class="skeleton-media-icon"></div><div class="skeleton-media-info"><div class="skeleton-line skeleton-line-md"></div><div class="skeleton-line skeleton-line-sm"></div></div><div class="skeleton-media-actions"><div class="skeleton-media-btn"></div><div class="skeleton-media-btn"></div></div></div>
                <div class="skeleton-media-item"><div class="skeleton-media-icon"></div><div class="skeleton-media-info"><div class="skeleton-line skeleton-line-md"></div><div class="skeleton-line skeleton-line-sm"></div></div><div class="skeleton-media-actions"><div class="skeleton-media-btn"></div><div class="skeleton-media-btn"></div></div></div>
            `;
        }
        const reorderContainer = document.getElementById('storyReorderList');
        if (reorderContainer && (!currentPrequelItems || currentPrequelItems.length === 0)) {
            reorderContainer.innerHTML = `
                <div class="skeleton-line skeleton-line-md" style="margin-bottom: 0.5rem; height: 32px; border-radius: 8px;"></div>
                <div class="skeleton-line skeleton-line-md" style="height: 32px; border-radius: 8px;"></div>
            `;
        }

        const result = await apiCall('get_prequels', { 
            media_type: currentPrequelMediaType, 
            media_id: currentPrequelMediaId 
        });

        if (result.ok && result.data) {
            if (Array.isArray(result.data)) {
                currentPrequelItems = result.data;
                currentPrequelMainMedia = {
                    id: currentPrequelMediaId,
                    title: 'عنوان اصلی',
                    release_year: '',
                    genre: '',
                    watched: 0,
                    rating: null,
                    media_type: currentPrequelMediaType
                };
            } else {
                currentPrequelItems = result.data.items || [];
                currentPrequelMainMedia = result.data.main_media || {
                    id: currentPrequelMediaId,
                    title: 'عنوان اصلی',
                    release_year: '',
                    genre: '',
                    watched: 0,
                    rating: null,
                    media_type: currentPrequelMediaType
                };
            }
        } else {
            currentPrequelItems = [];
            currentPrequelMainMedia = {
                id: currentPrequelMediaId,
                title: 'عنوان اصلی',
                release_year: '',
                genre: '',
                watched: 0,
                rating: null,
                media_type: currentPrequelMediaType
            };
        }

        // به‌روزرسانی عنوان هدر و بج‌های اثر مرجع و تعداد عناوین
        const mainTitle = currentPrequelMainMedia.title || (currentPrequelMediaType === 'movie' ? 'فیلم اصلی' : 'سریال اصلی');
        const modalTitleEl = document.getElementById('prequelModalTitle');
        if (modalTitleEl) modalTitleEl.textContent = `خط زمانی: ${mainTitle}`;
        const refText = document.getElementById('prequelMainTitleText');
        if (refText) {
            refText.textContent = `اثر مرجع: ${mainTitle}${currentPrequelMainMedia.release_year ? ` (${currentPrequelMainMedia.release_year})` : ''}`;
        }
        const countBadge = document.getElementById('prequelCountBadge');
        if (countBadge) {
            countBadge.textContent = `${currentPrequelItems.length} عنوان مرتبط`;
        }

        // رندر هر دو حالت
        renderStoryTimeline();
        renderReleaseTimeline();
    }

    // ==================== STORY TIMELINE (S-CURVE) ====================
    function getStoryOrderedList() {
        // ایجاد لیست داستانی کامل شامل پیش‌درآمدها، فیلم اصلی و دنباله‌ها/مجموعه‌ها
        const mainNode = {
            id: 'main_' + currentPrequelMainMedia.id,
            real_id: currentPrequelMainMedia.id,
            is_main: true,
            relation_type: 'main',
            related_type: currentPrequelMediaType,
            title: currentPrequelMainMedia.title || 'عنوان اصلی',
            release_year: currentPrequelMainMedia.release_year || '',
            genre: currentPrequelMainMedia.genre || '',
            watched: currentPrequelMainMedia.watched || 0,
            watch_date: currentPrequelMainMedia.watch_date || null,
            rating: currentPrequelMainMedia.rating || null,
            comment: currentPrequelMainMedia.comment || '',
            sort_order: 0
        };

        // جدا کردن پیش‌درآمدها و دنباله‌ها
        const prequels = currentPrequelItems.filter(p => p.relation_type === 'prequel')
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
        const sequels = currentPrequelItems.filter(p => p.relation_type === 'sequel')
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
        const collections = currentPrequelItems.filter(p => p.relation_type === 'collection')
            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

        // ترتیب پیش‌فرض داستانی: Prequels -> Main Title -> Sequels -> Collections
        return [...prequels, mainNode, ...sequels, ...collections];
    }

    function renderStoryTimeline() {
        const pathEl = document.getElementById('sCurveMain');
        const layer = document.getElementById('svgNodesLayer');
        if (!pathEl || !layer) return;

        layer.innerHTML = '';
        closeNodePopover();

        const storyList = getStoryOrderedList();
        const totalLen = pathEl.getTotalLength();
        const count = storyList.length;

        storyList.forEach((item, index) => {
            // محاسبه موقعیت روی خط مارپیچی عمودی S
            const t = count === 1 ? 0.5 : (0.07 + (index / (count - 1)) * 0.86);
            const pt = pathEl.getPointAtLength(t * totalLen);
            const x = pt.x;
            const y = pt.y;

            item._x = x;
            item._y = y;

            let color = '#f59e0b'; // پیش‌درآمد
            let r = 7;
            let strokeColor = '#1e293b';

            if (item.is_main) {
                color = '#6366f1'; // اصلی
                r = 10.5;
                strokeColor = '#ffffff';
            } else if (item.relation_type === 'sequel') {
                color = '#3b82f6'; // دنباله
                r = 7;
            } else if (item.relation_type === 'collection') {
                color = '#8b5cf6'; // مجموعه
                r = 7;
            }

            // گروه نود SVG
            const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            g.setAttribute('class', `s-node ${activeTimelineItem && activeTimelineItem.id === item.id ? 'active' : ''}`);
            g.setAttribute('data-id', item.id);
            g.setAttribute('tabindex', '0');

            // دایره اصلی تمیز و کوچک (بدون دایره بزرگ پشت و بدون پرش و انیمیشن scale)
            const core = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            core.setAttribute('cx', x);
            core.setAttribute('cy', y);
            core.setAttribute('r', r);
            core.setAttribute('fill', color);
            core.setAttribute('stroke', strokeColor);
            core.setAttribute('stroke-width', item.is_main ? '2.5' : '2');
            core.setAttribute('class', 'core');
            g.appendChild(core);

            // علامت تماشا (چک‌مارک ظریف داخل دایره)
            if (item.watched == 1) {
                const check = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                check.setAttribute('x', x);
                check.setAttribute('y', y + (r > 8 ? 3.5 : 2.5));
                check.setAttribute('text-anchor', 'middle');
                check.setAttribute('font-size', r > 8 ? '8.5px' : '7px');
                check.setAttribute('font-weight', '900');
                check.setAttribute('fill', '#ffffff');
                check.setAttribute('pointer-events', 'none');
                check.textContent = '✓';
                g.appendChild(check);
            }

            // متون زیر هر دایره (منطبق، بدون STROKE)
            const textY = y + r + 13;
            const tTitle = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            tTitle.setAttribute('x', x);
            tTitle.setAttribute('y', textY);
            tTitle.setAttribute('class', 'node-text-title');
            const displayTitle = item.title.length > 20 ? item.title.substring(0, 18) + '...' : item.title;
            tTitle.textContent = displayTitle;
            g.appendChild(tTitle);

            const tMeta = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            tMeta.setAttribute('x', x);
            tMeta.setAttribute('y', textY + 11);
            tMeta.setAttribute('class', 'node-text-meta');
            const relText = item.is_main ? 'عنوان اصلی' : (item.relation_type === 'prequel' ? 'پیش‌درآمد' : item.relation_type === 'sequel' ? 'دنباله' : 'مجموعه');
            tMeta.textContent = `${item.release_year || '؟'} • ${relText}`;
            g.appendChild(tMeta);

            // رویداد کلیک برای باز شدن پاپ‌اور تعاملی
            g.addEventListener('click', (e) => {
                e.stopPropagation();
                openNodePopover(item, x, y);
            });

            layer.appendChild(g);
        });

        // رندر لیست درگ/دراپ زیر خط
        renderStoryReorderList(storyList);
    }

    // ==================== POPOVER TOOLTIP ====================
    function openNodePopover(item, x, y) {
        activeTimelineItem = item;
        const popover = document.getElementById('nodePopover');
        const container = document.getElementById('spiralCanvasWrap');
        if (!popover || !container) return;

        // پر کردن اطلاعات
        const typeBadge = document.getElementById('popoverTypeBadge');
        if (item.is_main) {
            typeBadge.textContent = 'عنوان اصلی';
            typeBadge.style.background = '#6366f125';
            typeBadge.style.color = '#6366f1';
        } else if (item.relation_type === 'prequel') {
            typeBadge.textContent = 'پیش‌درآمد';
            typeBadge.style.background = '#f59e0b25';
            typeBadge.style.color = '#f59e0b';
        } else if (item.relation_type === 'sequel') {
            typeBadge.textContent = 'دنباله';
            typeBadge.style.background = '#3b82f625';
            typeBadge.style.color = '#3b82f6';
        } else {
            typeBadge.textContent = 'مجموعه';
            typeBadge.style.background = '#8b5cf625';
            typeBadge.style.color = '#8b5cf6';
        }

        document.getElementById('popoverTitle').textContent = item.title;
        document.getElementById('popoverYear').textContent = item.release_year ? item.release_year : 'سال نامشخص';
        document.getElementById('popoverGenres').textContent = item.genre || (item.related_type === 'series' ? 'سریال' : 'فیلم');

        // وضعیت تماشا
        const isWatched = item.watched == 1;
        const watchBtn = document.getElementById('popoverWatchBtn');
        const watchText = document.getElementById('popoverWatchText');
        if (isWatched) {
            watchBtn.className = 'btn btn-primary';
            watchBtn.style.background = 'var(--primary)';
            watchBtn.style.color = '#fff';
            watchText.textContent = item.watch_date ? `دیده‌ام (${formatDate(item.watch_date)})` : 'دیده‌ام ✓';
        } else {
            watchBtn.className = 'btn btn-outline';
            watchBtn.style.background = 'transparent';
            watchBtn.style.color = 'var(--foreground)';
            watchText.textContent = 'تماشا نکرده‌ام';
        }

        // امتیاز
        const ratingText = document.getElementById('popoverRatingText');
        if (ratingText) {
            ratingText.textContent = (item.rating && item.rating > 0) ? `${item.rating} از ۱۰ ★` : 'بدون امتیاز (ثبت)';
        }

        // یادداشت
        const commentTextEl = document.getElementById('popoverCommentText');
        if (commentTextEl) {
            const hasC = item.comment && item.comment.trim();
            commentTextEl.textContent = hasC ? item.comment.trim() : 'بدون یادداشت (ثبت)';
            commentTextEl.style.color = hasC ? 'var(--foreground)' : 'var(--muted-foreground)';
        }

        // دکمه حذف
        const deleteBtn = document.getElementById('popoverDeleteBtn');
        if (item.is_main) {
            deleteBtn.style.display = 'none';
        } else {
            deleteBtn.style.display = 'inline-flex';
        }

        // موقعیت‌یابی پاپ‌اور با دقت در مرز کانتینر
        const containerRect = container.getBoundingClientRect();
        const svgW = 500;
        const svgH = 480;
        const scale = containerRect.width / svgW;

        let posX = (x * scale) - 125;
        let posY = (y * scale) + 25;

        if (posX < 10) posX = 10;
        if (posX + 250 > containerRect.width) posX = containerRect.width - 260;
        if (posY + 200 > containerRect.height && y * scale > 200) {
            posY = (y * scale) - 190;
        }

        popover.style.left = `${posX}px`;
        popover.style.top = `${posY}px`;
        popover.style.display = 'block';

        // هایلایت نود فعال در SVG
        document.querySelectorAll('.s-node').forEach(n => {
            if (n.dataset.id == item.id) n.classList.add('active');
            else n.classList.remove('active');
        });
    }

    function closeNodePopover() {
        activeTimelineItem = null;
        const popover = document.getElementById('nodePopover');
        if (popover) popover.style.display = 'none';
        document.querySelectorAll('.s-node.active').forEach(n => n.classList.remove('active'));
    }

    // رویدادهای پاپ‌اور
    document.getElementById('closePopoverBtn')?.addEventListener('click', closeNodePopover);

    document.getElementById('popoverRatingBtn')?.addEventListener('click', () => {
        if (!activeTimelineItem) return;
        openRatingModal('prequel', activeTimelineItem.is_main ? activeTimelineItem.real_id : activeTimelineItem.id, activeTimelineItem.rating || 0, { is_main: !!activeTimelineItem.is_main });
    });

    document.getElementById('popoverCommentBtn')?.addEventListener('click', () => {
        if (!activeTimelineItem) return;
        if (activeTimelineItem.is_main) {
            const commentObj = (allCommentsCache.movies && allCommentsCache.movies[activeTimelineItem.real_id]) || null;
            openCommentModal(currentPrequelMediaType || 'movie', activeTimelineItem.real_id, commentObj ? commentObj.id : '', activeTimelineItem.comment || '');
        } else {
            openCommentModal('prequel', activeTimelineItem.id, activeTimelineItem.comment ? '1' : '', activeTimelineItem.comment || '');
        }
    });

    document.getElementById('popoverWatchBtn')?.addEventListener('click', async () => {
        if (!activeTimelineItem) return;
        if (activeTimelineItem.watched == 1) {
            await markAsWatched(activeTimelineItem.is_main ? (currentPrequelMediaType || 'movie') : 'prequel', activeTimelineItem.is_main ? activeTimelineItem.real_id : activeTimelineItem.id, 0, null);
            activeTimelineItem.watched = 0;
            activeTimelineItem.watch_date = null;
            const watchBtn = document.getElementById('popoverWatchBtn');
            const watchText = document.getElementById('popoverWatchText');
            if (watchBtn && watchText) {
                watchBtn.className = 'btn btn-outline';
                watchBtn.style.background = 'transparent';
                watchBtn.style.color = 'var(--foreground)';
                watchText.textContent = 'تماشا نکرده‌ام';
            }
        } else {
            document.getElementById('watchTargetType').value = activeTimelineItem.is_main ? (currentPrequelMediaType || 'movie') : 'prequel';
            document.getElementById('watchTargetId').value = activeTimelineItem.is_main ? activeTimelineItem.real_id : activeTimelineItem.id;
            document.getElementById('watchAction').value = activeTimelineItem.is_main ? 'mark_watched_movie' : 'update_prequel_item';
            initDatePicker();
            document.getElementById('watchDateModal').classList.add('show');
            refreshIcons();
        }
    });

    document.getElementById('popoverSaveBtn')?.addEventListener('click', () => {
        closeNodePopover();
    });

    document.getElementById('popoverDeleteBtn')?.addEventListener('click', async () => {
        if (!activeTimelineItem || activeTimelineItem.is_main) return;
        if (!confirm(`آیا از حذف "${activeTimelineItem.title}" از خط زمانی اطمینان دارید؟`)) return;

        const result = await apiCall('delete_prequel', { prequel_id: activeTimelineItem.id });
        if (result.ok) {
            showToast('از خط زمانی حذف شد', 'success');
            closeNodePopover();
            await loadPrequelData();
            await reloadCurrentList();
        } else {
            showToast(result.message || 'خطا در حذف', 'error');
        }
    });

    // ==================== DRAG & DROP STORY REORDER ====================
    let draggedStoryIndex = null;

    function renderStoryReorderList(storyList) {
        const container = document.getElementById('storyReorderList');
        if (!container) return;

        container.innerHTML = '';
        if (!storyList || storyList.length <= 1) {
            container.innerHTML = '<div style="font-size: 0.75rem; color: var(--muted-foreground); text-align: center; padding: 0.5rem;">برای مرتب‌سازی حداقل دو عنوان نیاز است</div>';
            return;
        }

        storyList.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'story-drag-row';
            row.setAttribute('draggable', item.is_main ? 'false' : 'true');
            row.dataset.index = index;
            row.dataset.id = item.id;

            let badgeHtml = '';
            if (item.is_main) {
                badgeHtml = '<span class="status-badge" style="background: #6366f120; color: #6366f1; font-size: 0.68rem; padding: 0.15rem 0.4rem;">فیلم اصلی</span>';
            } else if (item.relation_type === 'prequel') {
                badgeHtml = '<span class="status-badge" style="background: #f59e0b20; color: #f59e0b; font-size: 0.68rem; padding: 0.15rem 0.4rem;"><i data-lucide="corner-down-left"></i> پیش‌درآمد</span>';
            } else if (item.relation_type === 'sequel') {
                badgeHtml = '<span class="status-badge" style="background: #3b82f620; color: #3b82f6; font-size: 0.68rem; padding: 0.15rem 0.4rem;"><i data-lucide="corner-down-right"></i> دنباله</span>';
            } else {
                badgeHtml = '<span class="status-badge" style="background: #8b5cf620; color: #8b5cf6; font-size: 0.68rem; padding: 0.15rem 0.4rem;"><i data-lucide="layers-2"></i> مجموعه</span>';
            }

            const watchDateStr = (item.watched == 1 && item.watch_date) ? ` (${formatDate(item.watch_date)})` : '';
            const watchedHtml = item.watched == 1 
                ? `<span style="color: #10b981; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 0.2rem;"><i data-lucide="eye"></i> دیدم${watchDateStr}</span>` 
                : '<span style="color: var(--muted-foreground); font-size: 0.72rem;">ندیدم</span>';

            const ratingHtml = item.rating 
                ? `<span style="color: #f59e0b; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 0.15rem;"><i data-lucide="star"></i> ${item.rating}/10</span>` 
                : '';

            row.innerHTML = `
                <div class="flex items-center gap-2" style="flex: 1; overflow: hidden;">
                    ${item.is_main ? '<span style="width: 16px;"></span>' : '<span class="drag-handle" title="جابجایی"><i data-lucide="grip-vertical"></i></span>'}
                    ${badgeHtml}
                    <span style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(item.title)}</span>
                    ${item.release_year ? `<span style="font-size: 0.72rem; color: var(--muted-foreground);">(${item.release_year})</span>` : ''}
                </div>
                <div class="flex items-center gap-3">
                    ${watchedHtml}
                    ${ratingHtml}
                    ${!item.is_main ? `
                        <button type="button" class="btn-icon delete-story-row-btn" data-id="${item.id}" style="color: var(--destructive); width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center;" title="حذف">
                            <i data-lucide="trash-2"></i>
                        </button>
                    ` : ''}
                </div>
            `;

            // رویدادهای Drag & Drop
            if (!item.is_main) {
                row.addEventListener('dragstart', (e) => {
                    draggedStoryIndex = index;
                    row.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });

                row.addEventListener('dragend', () => {
                    row.classList.remove('dragging');
                    document.querySelectorAll('.story-drag-row').forEach(r => r.classList.remove('drag-over'));
                });

                row.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    row.classList.add('drag-over');
                });

                row.addEventListener('dragleave', () => {
                    row.classList.remove('drag-over');
                });

                row.addEventListener('drop', async (e) => {
                    e.preventDefault();
                    row.classList.remove('drag-over');
                    const targetIndex = index;

                    if (draggedStoryIndex !== null && draggedStoryIndex !== targetIndex) {
                        // جابجایی آیتم‌ها در آرایه
                        const movedItem = storyList[draggedStoryIndex];
                        storyList.splice(draggedStoryIndex, 1);
                        storyList.splice(targetIndex, 0, movedItem);

                        // استخراج شناسه‌های موارد prequel جهت ذخیره در سرور
                        const newOrderIds = storyList
                            .filter(it => !it.is_main)
                            .map(it => it.id);

                        await apiCall('reorder_prequels', {
                            media_type: currentPrequelMediaType,
                            media_id: currentPrequelMediaId,
                            order_ids: JSON.stringify(newOrderIds)
                        });

                        // بازسازی فوری خط زمانی
                        await loadPrequelData();
                        showToast('ترتیب خط زمانی ذخیره شد', 'success');
                    }
                });
            }

            // رویداد دکمه حذف سطر
            const delBtn = row.querySelector('.delete-story-row-btn');
            if (delBtn) {
                delBtn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    if (!confirm(`آیا از حذف "${item.title}" اطمینان دارید؟`)) return;
                    const res = await apiCall('delete_prequel', { prequel_id: item.id });
                    if (res.ok) {
                        showToast('عنوان حذف شد', 'success');
                        await loadPrequelData();
                        await reloadCurrentList();
                    } else {
                        showToast(res.message || 'خطا در حذف', 'error');
                    }
                });
            }

            container.appendChild(row);
        });
        refreshIcons();
    }

    // ==================== RELEASE CONTEXT MENU ====================
    function showReleaseContextMenu(e, m) {
        e.preventDefault();
        e.stopPropagation();

        const menu = document.getElementById('contextMenu');
        if (!menu) return;

        const isWatched = m.watched == 1;
        const watchedIcon = isWatched ? 'eye' : 'eye-off';
        const hasRating = m.rating && m.rating > 0;
        const hasComment = m.comment && m.comment.trim() !== '';

        let html = `
            <button class="context-menu-item" data-action="release-watch">
                <i data-lucide="${watchedIcon}"></i>
                <span>${isWatched ? 'تغییر به ندیده‌ام' : 'علامت‌زدن به دیده‌ام'}</span>
                ${isWatched ? '<span class="context-menu-badge watched">✓</span>' : ''}
            </button>
            <button class="context-menu-item" data-action="release-rating">
                <i data-lucide="star"></i>
                <span>${hasRating ? 'امتیاز: ' + m.rating + '/10' : 'امتیازدهی'}</span>
                ${hasRating ? '<span class="context-menu-badge rated">★</span>' : ''}
            </button>
            <button class="context-menu-item" data-action="release-comment">
                <i data-lucide="${hasComment ? 'message-circle' : 'message-square'}"></i>
                <span>${hasComment ? 'ویرایش یادداشت' : 'افزودن یادداشت'}</span>
                ${hasComment ? '<span class="context-menu-badge commented">💬</span>' : ''}
            </button>
        `;

        if (!m.is_main) {
            html += `
                <div class="context-menu-separator"></div>
                <button class="context-menu-item destructive" data-action="release-delete">
                    <i data-lucide="trash-2"></i>
                    <span>حذف از خط زمانی</span>
                </button>
            `;
        }

        menu.innerHTML = html;

        let x = e.clientX || (e.touches && e.touches[0].clientX);
        let y = e.clientY || (e.touches && e.touches[0].clientY);
        if (!x && !y && e.target) {
            const rect = e.target.getBoundingClientRect();
            x = rect.left;
            y = rect.bottom + 4;
        }
        const menuWidth = 220;
        const menuHeight = 220;
        if (x + menuWidth > window.innerWidth) x = window.innerWidth - menuWidth - 8;
        if (y + menuHeight > window.innerHeight) y = y - menuHeight - 8;
        if (x < 8) x = 8;
        if (y < 8) y = 8;

        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
        menu.classList.add('show');
        refreshIcons();

        menu.querySelectorAll('.context-menu-item').forEach(btn => {
            btn.onclick = async function(evt) {
                evt.stopPropagation();
                hideContextMenu();
                const action = this.dataset.action;
                if (action === 'release-watch') {
                    if (isWatched) {
                        await markAsWatched(m.is_main ? (currentPrequelMediaType || 'movie') : 'prequel', m.is_main ? m.real_id : m.id, 0, null);
                    } else {
                        document.getElementById('watchTargetType').value = m.is_main ? (currentPrequelMediaType || 'movie') : 'prequel';
                        document.getElementById('watchTargetId').value = m.is_main ? m.real_id : m.id;
                        document.getElementById('watchAction').value = m.is_main ? 'mark_watched_movie' : 'update_prequel_item';
                        initDatePicker();
                        document.getElementById('watchDateModal').classList.add('show');
                        refreshIcons();
                    }
                } else if (action === 'release-rating') {
                    openRatingModal('prequel', m.is_main ? m.real_id : m.id, m.rating || 0, { is_main: !!m.is_main });
                } else if (action === 'release-comment') {
                    if (m.is_main) {
                        const commentObj = (allCommentsCache.movies && allCommentsCache.movies[m.real_id]) || null;
                        openCommentModal(currentPrequelMediaType || 'movie', m.real_id, commentObj ? commentObj.id : '', m.comment || '');
                    } else {
                        openCommentModal('prequel', m.id, m.comment ? '1' : '', m.comment || '');
                    }
                } else if (action === 'release-delete') {
                    if (!confirm(`آیا از حذف "${m.title}" اطمینان دارید؟`)) return;
                    const res = await apiCall('delete_prequel', { prequel_id: m.id });
                    if (res.ok) {
                        showToast('با موفقیت حذف شد', 'success');
                        await loadPrequelData();
                        await reloadCurrentList();
                    } else {
                        showToast(res.message || 'خطا در حذف', 'error');
                    }
                }
            };
        });
    }

    // ==================== RELEASE TIMELINE VIEW ====================
    function renderReleaseTimeline() {
        const container = document.getElementById('releaseItemsList');
        if (!container) return;

        container.innerHTML = '';

        // لیست شامل فیلم اصلی و همه عناوین مرتبط مرتب‌شده براساس سال ساخت (صعودی)
        const allItems = [
            {
                id: currentPrequelMainMedia.id,
                real_id: currentPrequelMainMedia.id,
                is_main: true,
                relation_type: 'main',
                related_type: currentPrequelMediaType,
                title: currentPrequelMainMedia.title || 'عنوان اصلی',
                release_year: currentPrequelMainMedia.release_year || '',
                genre: currentPrequelMainMedia.genre || '',
                watched: currentPrequelMainMedia.watched || 0,
                watch_date: currentPrequelMainMedia.watch_date || null,
                rating: currentPrequelMainMedia.rating || null,
                comment: ''
            },
            ...currentPrequelItems
        ];

        allItems.sort((a, b) => {
            const yearA = parseInt(a.release_year) || 9999;
            const yearB = parseInt(b.release_year) || 9999;
            return yearA - yearB;
        });

        if (allItems.length === 0) {
            container.innerHTML = '<div style="text-align: center; color: var(--muted-foreground); padding: 2rem;">عنوانی ثبت نشده است</div>';
            return;
        }

        allItems.forEach((m, idx) => {
            const rowNumber = idx + 1;
            const isWatched = m.watched == 1;
            const hasRating = m.rating && m.rating > 0;
            const hasComment = m.comment && m.comment.trim() !== '';

            let badgeHtml = '';
            if (m.is_main) {
                badgeHtml = '<span class="status-badge" style="background: #6366f120; color: #6366f1; font-size: 0.68rem; margin-right: 0.35rem;">فیلم اصلی</span>';
            } else if (m.relation_type === 'prequel') {
                badgeHtml = '<span class="status-badge" style="background: #f59e0b20; color: #f59e0b; font-size: 0.68rem; margin-right: 0.35rem;"><i data-lucide="corner-down-left"></i> پیش‌درآمد</span>';
            } else if (m.relation_type === 'sequel') {
                badgeHtml = '<span class="status-badge" style="background: #3b82f620; color: #3b82f6; font-size: 0.68rem; margin-right: 0.35rem;"><i data-lucide="corner-down-right"></i> دنباله</span>';
            } else {
                badgeHtml = '<span class="status-badge" style="background: #8b5cf620; color: #8b5cf6; font-size: 0.68rem; margin-right: 0.35rem;"><i data-lucide="layers-2"></i> مجموعه</span>';
            }

            const itemEl = document.createElement('div');
            itemEl.className = 'media-item prequel-release-item';
            itemEl.style.flexDirection = 'column';
            itemEl.style.alignItems = 'stretch';

            itemEl.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 0.75rem;">
                    <div class="media-info" style="cursor: default; flex: 1; min-width: 0;">
                        <div class="media-icon" style="position: relative; overflow: hidden; flex-shrink: 0;">
                            <span class="media-number">#${rowNumber}</span>
                        </div>
                        <div class="media-details" style="min-width: 0;">
                            <div class="media-title" style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                                <span style="font-weight: 600; font-size: 0.95rem; word-break: break-word;">${escapeHtml(m.title)}</span>
                                ${badgeHtml}
                            </div>
                            <div class="media-meta" style="margin-top: 0.25rem;">
                                <span>${m.release_year ? `${m.release_year}` : 'سال نامشخص'}</span>
                                ${m.genre ? `<span style="display: inline-flex; align-items: center; gap: 0.3rem; margin-right: 0.5rem;">• ${escapeHtml(m.genre)}</span>` : ''}
                                ${m.watched == 1 && m.watch_date ? `<span style="display: inline-flex; align-items: center; gap: 0.25rem; margin-right: 0.5rem; color: #10b981; font-weight: 500; font-size: 0.72rem;"><i data-lucide="calendar" style="width: 12px; height: 12px;"></i> ${formatDate(m.watch_date)}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 items-center" style="flex-shrink: 0;">
                        <button type="button" class="btn-icon release-watch-btn ${isWatched ? 'btn-watched' : ''}" 
                                data-id="${m.id}" data-main="${m.is_main ? '1' : '0'}" data-watched="${m.watched}" title="${isWatched ? (m.watch_date ? `دیده‌ام (${formatDate(m.watch_date)})` : 'دیده‌ام') : 'ندیده‌ام'}">
                            ${isWatched ? '<i data-lucide="eye"></i>' : '<i data-lucide="eye-off"></i>'}
                        </button>
                        <button type="button" class="btn-icon release-rating-btn ${hasRating ? 'has-rating' : ''}" 
                                data-id="${m.id}" data-main="${m.is_main ? '1' : '0'}" data-rating="${m.rating || ''}" title="امتیاز">
                            ${hasRating ? `<i data-lucide="star"></i><span class="rating-label">${m.rating}/10</span>` : '<i data-lucide="star"></i>'}
                        </button>
                        <button type="button" class="btn-icon release-comment-btn ${hasComment ? 'has-comment' : ''}" 
                                data-id="${m.id}" data-main="${m.is_main ? '1' : '0'}" data-comment="${escapeHtml(m.comment || '')}" title="یادداشت">
                            ${hasComment ? '<i data-lucide="message-circle"></i>' : '<i data-lucide="message-square"></i>'}
                        </button>
                        ${!m.is_main ? `
                            <button type="button" class="btn-icon release-delete-btn" data-id="${m.id}" style="color:var(--destructive);" title="حذف">
                                <i data-lucide="trash-2"></i>
                            </button>
                        ` : ''}
                        <button type="button" class="btn-more release-more-btn" title="گزینه‌ها">
                            <i data-lucide="more-horizontal"></i>
                        </button>
                    </div>
                </div>
                ${hasComment ? `
                    <div class="comment-section" style="margin-top: 0.5rem; background: var(--secondary); border-radius: 8px; padding: 0.5rem 0.75rem; border: 1px solid var(--border);">
                        <span style="font-size: 0.78rem; color: var(--foreground);">${escapeHtml(m.comment)}</span>
                    </div>
                ` : ''}
            `;

            // رویداد کلیک دکمه سه نقطه (btn-more) در نسخه موبایل و دسکتاپ
            itemEl.querySelector('.release-more-btn')?.addEventListener('click', (e) => {
                e.stopPropagation();
                showReleaseContextMenu(e, m);
            });

            // تعاملات دکمه‌های تماشا، امتیاز، نظر و حذف در لیست انتشار
            itemEl.querySelector('.release-watch-btn')?.addEventListener('click', async () => {
                if (isWatched) {
                    await markAsWatched(m.is_main ? (currentPrequelMediaType || 'movie') : 'prequel', m.is_main ? m.real_id : m.id, 0, null);
                } else {
                    document.getElementById('watchTargetType').value = m.is_main ? (currentPrequelMediaType || 'movie') : 'prequel';
                    document.getElementById('watchTargetId').value = m.is_main ? m.real_id : m.id;
                    document.getElementById('watchAction').value = m.is_main ? 'mark_watched_movie' : 'update_prequel_item';
                    initDatePicker();
                    document.getElementById('watchDateModal').classList.add('show');
                    refreshIcons();
                }
            });

            itemEl.querySelector('.release-rating-btn')?.addEventListener('click', async () => {
                openRatingModal('prequel', m.is_main ? m.real_id : m.id, m.rating || 0, { is_main: !!m.is_main });
            });

            itemEl.querySelector('.release-comment-btn')?.addEventListener('click', () => {
                if (m.is_main) {
                    const commentObj = (allCommentsCache.movies && allCommentsCache.movies[m.real_id]) || null;
                    openCommentModal(currentPrequelMediaType || 'movie', m.real_id, commentObj ? commentObj.id : '', m.comment || '');
                } else {
                    openCommentModal('prequel', m.id, m.comment ? '1' : '', m.comment || '');
                }
            });

            itemEl.querySelector('.release-delete-btn')?.addEventListener('click', async () => {
                if (!confirm(`آیا از حذف "${m.title}" اطمینان دارید؟`)) return;
                const res = await apiCall('delete_prequel', { prequel_id: m.id });
                if (res.ok) {
                    showToast('با موفقیت حذف شد', 'success');
                    await loadPrequelData();
                    await reloadCurrentList();
                } else {
                    showToast(res.message || 'خطا در حذف', 'error');
                }
            });

            container.appendChild(itemEl);
        });
        refreshIcons();
    }

    // ==================== TABS & CONTROLS ====================
    document.getElementById('tabStoryTimeline')?.addEventListener('click', () => {
        document.getElementById('tabStoryTimeline').classList.add('active');
        document.getElementById('tabReleaseTimeline').classList.remove('active');
        document.getElementById('storyTimelineView').style.display = 'block';
        document.getElementById('releaseTimelineView').style.display = 'none';
        currentPrequelView = 'story';
        renderStoryTimeline();
    });

    document.getElementById('tabReleaseTimeline')?.addEventListener('click', () => {
        document.getElementById('tabReleaseTimeline').classList.add('active');
        document.getElementById('tabStoryTimeline').classList.remove('active');
        document.getElementById('releaseTimelineView').style.display = 'block';
        document.getElementById('storyTimelineView').style.display = 'none';
        currentPrequelView = 'release';
        closeNodePopover();
        renderReleaseTimeline();
    });

    // ==================== COMPACT ADD PREQUEL DRAWER ====================
    function resetNewPrequelForm() {
        document.getElementById('newPrequelTitle').value = '';
        document.getElementById('newPrequelYear').value = '';
        document.getElementById('newPrequelGenre').value = '';
        document.getElementById('newPrequelWatched').checked = false;
        document.getElementById('newPrequelRating').value = '';
        document.getElementById('newPrequelComment').value = '';
    }

    document.getElementById('toggleAddPrequelBtn')?.addEventListener('click', () => {
        const drawer = document.getElementById('compactAddPrequelDrawer');
        const toggleBtnText = document.getElementById('toggleAddPrequelText');
        if (drawer.style.display === 'none' || !drawer.style.display) {
            drawer.style.display = 'block';
            toggleBtnText.textContent = 'بستن فرم';
            document.getElementById('newPrequelTitle').focus();
        } else {
            drawer.style.display = 'none';
            toggleBtnText.textContent = 'افزودن عنوان';
        }
    });

    document.getElementById('closeAddPrequelDrawerBtn')?.addEventListener('click', () => {
        document.getElementById('compactAddPrequelDrawer').style.display = 'none';
        document.getElementById('toggleAddPrequelText').textContent = 'افزودن عنوان';
    });

    // دکمه‌های نوع ارتباط در دراور
    document.querySelectorAll('#compactAddPrequelDrawer .prequel-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#compactAddPrequelDrawer .prequel-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPrequelType = this.dataset.type;
        });
    });

    // دکمه‌های فرمت مدیا (فیلم / سریال) در دراور
    document.querySelectorAll('#compactAddPrequelDrawer .prequel-related-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#compactAddPrequelDrawer .prequel-related-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentRelatedType = this.dataset.type;
        });
    });

    // ثبت عنوان در دراور
    document.getElementById('submitNewPrequelBtn')?.addEventListener('click', async () => {
        const title = document.getElementById('newPrequelTitle').value.trim();
        const year = document.getElementById('newPrequelYear').value.trim();
        const genre = document.getElementById('newPrequelGenre').value.trim();
        const watched = document.getElementById('newPrequelWatched').checked ? 1 : 0;
        const rating = document.getElementById('newPrequelRating').value.trim();
        const comment = document.getElementById('newPrequelComment').value.trim();

        if (!title) {
            showAlert(document.getElementById('prequelAlert'), 'لطفاً عنوان فیلم یا سریال مرتبط را وارد نمایید', 'error');
            return;
        }

        const result = await apiCall('add_prequel', {
            media_type: currentPrequelMediaType,
            media_id: currentPrequelMediaId,
            relation_type: currentPrequelType,
            related_type: currentRelatedType,
            title: title,
            release_year: year || null,
            genre: genre || null,
            watched: watched,
            rating: rating || null,
            comment: comment || null,
            sort_order: (currentPrequelItems.length + 1)
        });

        if (result.ok) {
            showAlert(document.getElementById('prequelAlert'), `عنوان "${title}" با موفقیت اضافه شد`, 'success');
            resetNewPrequelForm();
            document.getElementById('compactAddPrequelDrawer').style.display = 'none';
            document.getElementById('toggleAddPrequelText').textContent = 'افزودن عنوان';

            await loadPrequelData();
            await reloadCurrentList();
        } else {
            showAlert(document.getElementById('prequelAlert'), result.message || 'خطا در ثبت عنوان', 'error');
        }
    });

    // بستن پاپ‌اور با کلیک خارج از آن
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#nodePopover') && !e.target.closest('.s-node')) {
            closeNodePopover();
        }
    });

    // بستن مودال
    document.querySelectorAll('.close-prequel-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('prequelModal').classList.remove('show');
            document.getElementById('prequelAlert').style.display = 'none';
            closeNodePopover();
        });
    });

/* ============================================================
   SECTION 18: BANNER UPLOAD & GENRE TAGS
   ============================================================ */
let bannerUploadInput = null;

function createBannerUploadInput() {
    if (bannerUploadInput) bannerUploadInput.remove();
    bannerUploadInput = document.createElement('input');
    bannerUploadInput.type = 'file';
    bannerUploadInput.accept = 'image/jpeg,image/png,image/jpg';
    bannerUploadInput.style.display = 'none';
    bannerUploadInput.id = 'bannerFileInput';
    document.body.appendChild(bannerUploadInput);

    bannerUploadInput.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const listId = this.dataset.listId;
        await uploadBanner(listId, file);
        this.value = '';
    });
}

function triggerBannerUpload(listId) {
    if (!bannerUploadInput) createBannerUploadInput();
    bannerUploadInput.dataset.listId = listId;
    bannerUploadInput.click();
}

async function uploadBanner(listId, file) {
    const card = document.querySelector(`.list-card[data-list-id="${listId}"]`);
    const overlay = document.createElement('div');
    overlay.className = 'list-card-loading-overlay';
    overlay.innerHTML = `
    <div class="list-card-loading-content">
        <div class="loading"></div>
        <span class="list-card-loading-text">در حال آپلود بنر...</span>
    </div>
`;
    if (card) {
        card.appendChild(overlay);
        card.style.pointerEvents = 'none';
    }

    try {
        const formData = new FormData();
        formData.append('action', 'upload_banner');
        formData.append('list_id', listId);
        formData.append('banner', file);

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
            err.requestData = data;
            throw err;
        }
        const result = await response.json();

        if (result.ok) {
            await loadLists();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error uploading banner:', error);
        showToast('خطا در آپلود، دوباره تلاش کنید.', 'error');
    } finally {
        if (overlay) {
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.2s ease';
            setTimeout(() => {
                overlay.remove();
                if (card) card.style.pointerEvents = '';
            }, 200);
        }
    }
}

async function removeListBanner(listId) {
    confirmDelete('آیا از حذف بنر این لیست مطمئن هستید؟', async () => {
        const result = await apiCall('remove_banner', { list_id: listId });
        if (result.ok) await loadLists();
        else showToast(result.message, 'error');
    });
}

// ===== GENRE TAGS =====
const MAX_GENRE_TAGS = 3;
let allLibraryGenres = [];

async function loadGenres(forceRefresh = false) {
    if (forceRefresh) {
        try { localStorage.removeItem('pwa_cached_genres'); } catch (e) {}
    }
    const result = await apiCall('get_genres');
    const fetched = (result.ok && result.data && Array.isArray(result.data)) ? result.data : [];

    // ایجاد نقشه یکپارچه از ۳۱ ژانر استاندارد تا در هر شرایطی (حتی کش قدیمی) کامل باشند
    const genreMap = new Map();
    if (typeof STANDARD_GENRES_CATALOG !== 'undefined' && Array.isArray(STANDARD_GENRES_CATALOG)) {
        STANDARD_GENRES_CATALOG.forEach(g => genreMap.set(g.slug, { ...g, usage_count: 0, is_standard: 1 }));
    }
    fetched.forEach(g => {
        if (g.slug && genreMap.has(g.slug)) {
            genreMap.set(g.slug, { ...genreMap.get(g.slug), ...g });
        } else if (g.slug) {
            genreMap.set(g.slug, g);
        }
    });

    allLibraryGenres = Array.from(genreMap.values());
    return allLibraryGenres;
}

// ==================== 30 STANDARD GENRES & AUDIT SYSTEM ====================
    let activeGenreFilter = 'all';
    let genreAuditData = null;

    async function openGenreModal(defaultTab = 'library') {
        document.getElementById('genreModalAlert').style.display = 'none';
        document.getElementById('genreModal').classList.add('show');
        switchGenreTab(defaultTab);
        await loadGenres(true); // پاک‌سازی کش قدیمی جهت نمایش قطعی ژانر سیتکام و ۳۱ ژانر
        await renderGenreLibrary();
        // اجرای بررسی سایلنت برای نشان دادن تعداد ارورها روی برچسب تب
        runGenreAudit(true);
    }

    function switchGenreTab(tabName) {
        const libBtn = document.getElementById('genreTabLibraryBtn');
        const auditBtn = document.getElementById('genreTabAuditBtn');
        const libPane = document.getElementById('genreLibraryTabPane');
        const auditPane = document.getElementById('genreAuditTabPane');

        if (tabName === 'library') {
            libBtn.classList.add('active');
            libBtn.style.color = 'var(--primary)';
            libBtn.style.borderBottom = '2px solid var(--primary)';
            auditBtn.classList.remove('active');
            auditBtn.style.color = 'var(--muted-foreground)';
            auditBtn.style.borderBottom = '2px solid transparent';
            libPane.style.display = 'block';
            auditPane.style.display = 'none';
        } else {
            auditBtn.classList.add('active');
            auditBtn.style.color = 'var(--primary)';
            auditBtn.style.borderBottom = '2px solid var(--primary)';
            libBtn.classList.remove('active');
            libBtn.style.color = 'var(--muted-foreground)';
            libBtn.style.borderBottom = '2px solid transparent';
            libPane.style.display = 'none';
            auditPane.style.display = 'block';
            runGenreAudit(false);
        }
    }

    document.getElementById('genreTabLibraryBtn')?.addEventListener('click', () => switchGenreTab('library'));
    document.getElementById('genreTabAuditBtn')?.addEventListener('click', () => switchGenreTab('audit'));

    async function renderGenreLibrary(filterText = '') {
        const genres = await loadGenres();
        const container = document.getElementById('genreLibraryList');
        if (!container) return;

        let filtered = genres;
        const q = filterText.trim().toLowerCase();
        if (q) {
            filtered = filtered.filter(g =>
                (g.name && g.name.toLowerCase().includes(q)) ||
                (g.name_fa && g.name_fa.toLowerCase().includes(q)) ||
                (g.name_en && g.name_en.toLowerCase().includes(q)) ||
                (g.slug && g.slug.toLowerCase().includes(q))
            );
        }

        if (activeGenreFilter === 'used') {
            filtered = filtered.filter(g => (g.usage_count || 0) > 0);
        }

        if (!filtered.length) {
            container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: var(--muted-foreground);"><i data-lucide="search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--muted-foreground); width: 16px; height: 16px;"></i><p style="margin-top:0.5rem; font-size:0.85rem;">ژانری مطابق با جستجوی شما یافت نشد</p></div>';
            return;
        }

        container.innerHTML = filtered.map(g => `
            <div class="genre-card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 0.75rem 0.9rem; border: 1px solid var(--border); border-radius: 12px; background: var(--card); transition: transform 0.15s, box-shadow 0.15s;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="color-dot" style="background: ${g.color || '#6366f1'}; width: 14px; height: 14px; border-radius: 4px; flex-shrink: 0;"></span>
                        <div>
                            <div style="font-size: 0.88rem; font-weight: 700; color: var(--foreground);">${escapeHtml(g.name_fa || g.name)}</div>
                            <div style="font-size: 0.72rem; color: var(--muted-foreground); font-family: monospace;">${escapeHtml(g.name_en || g.slug)}</div>
                        </div>
                    </div>
                    <span style="font-size: 0.68rem; font-weight: 600; color: ${g.usage_count > 0 ? 'var(--primary)' : 'var(--muted-foreground)'}; background: var(--secondary); padding: 0.2rem 0.55rem; border-radius: 8px; white-space: nowrap;">
                        ${g.usage_count || 0} عنوان
                    </span>
                </div>
                <div style="margin-top: 0.65rem; padding-top: 0.5rem; border-top: 1px dashed var(--border); display: flex; align-items: center; justify-content: space-between;">
                    <button class="btn btn-secondary btn-sm view-genre-titles-btn" data-slug="${escapeHtml(g.slug)}" style="width: 100%; font-size: 0.72rem; padding: 0.25rem 0.5rem;">
                        <i data-lucide="layers-2"></i> مشاهده عناوین
                    </button>
                </div>
                <div id="genreTitlesList_${escapeHtml(g.slug)}" style="display: none; margin-top: 0.5rem; font-size: 0.75rem; border-top: 1px solid var(--border); padding-top: 0.4rem; max-height: 140px; overflow-y: auto;"></div>
            </div>
        `).join('');

        // اتصال دکمه‌های مشاهده عناوین
        container.querySelectorAll('.view-genre-titles-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const slug = btn.dataset.slug;
                const listEl = document.getElementById(`genreTitlesList_${slug}`);
                if (!listEl) return;

                if (listEl.style.display === 'block') {
                    listEl.style.display = 'none';
                    btn.innerHTML = '<i data-lucide="layers-2"></i> مشاهده عناوین';
                    return;
                }

                btn.innerHTML = '<i data-lucide="refresh-cw"></i> در حال بارگذاری...';
                const res = await apiCall('get_titles_by_genre', { slug });
                if (res.ok && res.data) {
                    listEl.style.display = 'block';
                    btn.innerHTML = '<i data-lucide="x"></i> بستن عناوین';
                    const titles = res.data.titles || [];
                    if (!titles.length) {
                        listEl.innerHTML = '<div style="color:var(--muted-foreground); padding:0.3rem;">عنوانی با این ژانر ثبت نشده است.</div>';
                    } else {
                        listEl.innerHTML = titles.map(t => `
                            <div style="padding: 0.25rem 0; border-bottom: 1px dotted var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <span>${t.type === 'movie' ? '🎬' : '📺'} ${escapeHtml(t.title)}</span>
                                <span style="font-size: 0.65rem; color: var(--muted-foreground);">${escapeHtml(t.list_name || '')}</span>
                            </div>
                        `).join('');
                    }
                } else {
                    btn.innerHTML = '<i data-lucide="layers-2"></i> مشاهده عناوین';
                    showToast('خطا در دریافت عناوین', 'error');
                }
            });
        });
    }

    // فیلتر جستجوی کتابخانه
    document.getElementById('searchGenreLibraryInput')?.addEventListener('input', (e) => {
        renderGenreLibrary(e.target.value);
    });

    document.querySelectorAll('.genre-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.genre-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeGenreFilter = btn.dataset.filter;
            const searchVal = document.getElementById('searchGenreLibraryInput')?.value || '';
            renderGenreLibrary(searchVal);
        });
    });

    // ==================== ابزار ممیزی و یکپارچه‌سازی ژانرها (AUDIT & FIXER) ====================

    async function runGenreAudit(silent = false) {
        const badge = document.getElementById('genreAuditBadge');
        const container = document.getElementById('genreAuditResultsContainer');
        const batchBtn = document.getElementById('batchFixAllGenresBtn');
        const summaryTitle = document.getElementById('genreAuditSummaryTitle');
        const summarySub = document.getElementById('genreAuditSummarySub');

        if (!silent && container) {
            container.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--muted-foreground);"><i data-lucide="refresh-cw"></i><p style="margin-top:0.5rem;">در حال بررسی هوشمند تمام عناوین و مقایسه با کتابخانه استاندارد...</p></div>';
        }

        // پاک‌سازی کش PWA جهت دریافت قطعی آخرین وضعیت زنده از سرور
        try { localStorage.removeItem('pwa_cached_genre_audit'); } catch (e) {}

        const res = await apiCall('get_genre_audit');
        if (!res.ok || !res.data) {
            if (!silent) {
                showToast(res.message || 'خطا در ارزیابی هوشمند ژانرها', 'error');
            }
            return;
        }

        genreAuditData = res.data;
        const issues = res.data.issues || [];
        const nonStandardCount = res.data.non_standard_count || 0;

        let ignoredConflicts = [];
        try {
            ignoredConflicts = JSON.parse(localStorage.getItem('ignored_genre_conflicts') || '[]');
        } catch (e) {
            ignoredConflicts = [];
        }

        const allRedundancies = res.data.redundancies || [];
        const activeRedundancies = allRedundancies.filter(r => {
            const key = `${r.type}_${r.id}_${r.pair_type}`;
            return !ignoredConflicts.includes(key);
        });

        const totalAlerts = nonStandardCount + activeRedundancies.length;

        if (badge) {
            if (totalAlerts > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = totalAlerts;
            } else {
                badge.style.display = 'none';
            }
        }

        if (silent) return;

        if (totalAlerts === 0) {
            if (batchBtn) batchBtn.style.display = 'none';
            if (summaryTitle) summaryTitle.innerHTML = '🎉 تمام ژانرهای عناوین شما کاملاً یکپارچه و استاندارد هستند!';
            if (summarySub) summarySub.textContent = `تمام ${res.data.total_titles_checked} عنوان کتابخانه شما با ۳۲ ژانر استاندارد همخوانی دارند و هیچ مغایرت یا همپوشانی یافت نشد.`;
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem 1.5rem; background: var(--card); border: 1px dashed #10b981; border-radius: 14px;">
                        <span style="font-size: 42px;">✨</span>
                        <h4 style="margin: 0.75rem 0 0.35rem 0; color: #10b981;">هیچ مغایرتی یافت نشد</h4>
                        <p style="font-size: 0.82rem; color: var(--muted-foreground); max-width: 440px; margin: 0 auto;">
                            همه ژانرهای وارد شده با کاتالوگ استاندارد هماهنگ هستند. هر زمان عنوان جدیدی با املای نامتعارف وارد شود، در این بخش قابل اصلاح خواهد بود.
                        </p>
                    </div>`;
            }
            return;
        }

        if (batchBtn) batchBtn.style.display = nonStandardCount > 0 ? 'inline-flex' : 'none';
        if (summaryTitle) {
            let msgParts = [];
            if (nonStandardCount > 0) msgParts.push(`<strong style="color:#ef4444;">${nonStandardCount} مورد مغایرت املایی</strong>`);
            if (activeRedundancies.length > 0) msgParts.push(`<strong style="color:#f59e0b;">${activeRedundancies.length} مورد شباهت/همپوشانی ژانر</strong>`);
            summaryTitle.innerHTML = `⚠️ ${msgParts.join(' و ')} در ژانر عناوین یافت شد`;
        }
        if (summarySub) summarySub.textContent = `شما می‌توانید مغایرت‌های املایی را اصلاح کرده یا همپوشانی‌های معنایی (مانند سیتکام/کمدی یا انیمه/انیمیشن) را مدیریت و تفکیک نمایید.`;

        const catalog = res.data.catalog || [];

        let html = '';

        // بخش نمایش اخطارهای همپوشانی ژانرها (مانند سیتکام/کمدی و انیمه/انیمیشن)
        if (activeRedundancies.length > 0) {
            html += `
            <div class="redundancy-section" style="margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.65rem;">
                    <span style="font-weight: 700; font-size: 0.88rem; color: #f59e0b; display: flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="alert-triangle"></i>
                        <span>شباهت و همپوشانی ژانرها (نیازمند بررسی):</span>
                    </span>
                    <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.72rem; padding: 0.15rem 0.5rem; border-radius: 6px;">${activeRedundancies.length} مورد</span>
                </div>
            `;
            activeRedundancies.forEach(r => {
                const key = `${r.type}_${r.id}_${r.pair_type}`;
                html += `
                <div class="audit-redundancy-card" id="redundancyCard_${key}" style="border: 1px solid #f59e0b40; border-radius: 12px; padding: 0.85rem 1rem; margin-bottom: 0.65rem; background: var(--card);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                                <span style="font-weight: 600; font-size: 0.88rem; color: var(--foreground);">
                                    ${r.type === 'movie' ? '🎬' : '📺'} ${escapeHtml(r.title)}
                                </span>
                                ${r.list_name ? `<span style="font-size: 0.72rem; color: var(--muted-foreground);">(${escapeHtml(r.list_name)})</span>` : ''}
                                <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.68rem; padding: 0.15rem 0.45rem; border-radius: 6px;">
                                    ${escapeHtml(r.pair_title)}
                                </span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--muted-foreground);">
                                ژانرهای عنوان: <span style="font-weight: 500; color: var(--foreground); direction: ltr; display: inline-block;">[${escapeHtml(r.full_genre)}]</span> • ${escapeHtml(r.desc)}
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                            <button class="btn btn-outline btn-sm delete-redundant-genre-btn" data-type="${r.type}" data-id="${r.id}" data-genre="${escapeHtml(r.genre1)}" style="font-size: 0.75rem; padding: 0.35rem 0.6rem; border-radius: 6px; color: var(--destructive); border-color: var(--destructive);" title="حذف ژانر «${escapeHtml(r.genre1)}» از این عنوان">
                                <i data-lucide="trash-2"></i> حذف «${escapeHtml(r.genre1)}»
                            </button>
                            <button class="btn btn-outline btn-sm delete-redundant-genre-btn" data-type="${r.type}" data-id="${r.id}" data-genre="${escapeHtml(r.genre2)}" style="font-size: 0.75rem; padding: 0.35rem 0.6rem; border-radius: 6px; color: var(--destructive); border-color: var(--destructive);" title="حذف ژانر «${escapeHtml(r.genre2)}» از این عنوان">
                                <i data-lucide="trash-2"></i> حذف «${escapeHtml(r.genre2)}»
                            </button>
                            <button class="btn btn-secondary btn-sm ignore-redundancy-btn" data-key="${key}" style="font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 6px;" title="نادیده گرفتن این اخطار">
                                <i data-lucide="eye-off"></i> نادیده بگیر
                            </button>
                        </div>
                    </div>
                </div>`;
            });
            html += `</div>`;
        }

        if (issues.length > 0) {
            if (activeRedundancies.length > 0) {
                html += `<div style="font-weight: 700; font-size: 0.88rem; color: #ef4444; margin-bottom: 0.65rem; display: flex; align-items: center; gap: 0.35rem;"><i data-lucide="spell-check"></i><span>مغایرت‌ها و نیاز به اصلاح املا (${issues.length} مورد):</span></div>`;
            }
            issues.forEach((issue, idx) => {
            const suggested = issue.suggested_genre;
            const suggestedSlug = suggested ? suggested.slug : '';
            const suggestedName = suggested ? suggested.name : '';

            html += `
            <div class="audit-issue-card" style="border: 1px solid var(--border); border-radius: 12px; padding: 1rem; margin-bottom: 0.85rem; background: var(--card);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span style="background: #fee2e2; color: #dc2626; padding: 0.25rem 0.6rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; border: 1px solid #fca5a5;">
                            "${escapeHtml(issue.raw_genre)}"
                        </span>
                        <span style="font-size: 0.75rem; color: var(--muted-foreground);">
                            استفاده شده در <strong>${issue.affected_count} عنوان</strong>
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="font-size: 0.75rem; color: var(--muted-foreground);">تبدیل به:</span>
                        <select class="form-input audit-target-select" data-raw="${escapeHtml(issue.raw_genre)}" style="padding: 0.35rem 0.6rem; font-size: 0.82rem; min-width: 170px;">
                            ${catalog.map(cat => `
                                <option value="${escapeHtml(cat.name)}" ${suggestedSlug === cat.slug ? 'selected' : ''}>
                                    ${escapeHtml(cat.name)} ${suggestedSlug === cat.slug ? '⭐ (پیشنهادی)' : ''}
                                </option>
                            `).join('')}
                            <option value="__DELETE__" style="color:#ef4444; font-weight:700;">🗑️ حذف این مورد از عناوین</option>
                        </select>

                        <button class="btn btn-primary btn-sm apply-single-fix-btn" data-raw="${escapeHtml(issue.raw_genre)}" style="font-size: 0.78rem; padding: 0.38rem 0.75rem;">
                            <i data-lucide="check"></i> جایگزینی برای همه (${issue.affected_count})
                        </button>

                        <button class="btn btn-secondary btn-sm toggle-issue-titles-btn" data-target="issueTitles_${idx}" style="font-size: 0.75rem; padding: 0.38rem 0.55rem;">
                            عناوین (${issue.items.length}) <i data-lucide="chevron-down"></i>
                        </button>
                    </div>
                </div>

                <!-- لیست بازشونده عناوین این ژانر جهت ویرایش یا بررسی -->
                <div id="issueTitles_${idx}" style="display: none; margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px dashed var(--border); font-size: 0.78rem;">
                    <div style="color: var(--muted-foreground); margin-bottom: 0.4rem; font-size: 0.72rem;">لیست عناوین دارای این ژانر:</div>
                    ${issue.items.map(item => `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.3rem 0.5rem; background: var(--secondary)/40; border-radius: 8px; margin-bottom: 0.3rem;">
                            <span style="font-weight: 500;">
                                ${item.type === 'movie' ? '🎬' : '📺'} ${escapeHtml(item.title)}
                                <span style="font-size: 0.7rem; color: var(--muted-foreground); margin-right: 0.4rem;">(${escapeHtml(item.list_name || '')})</span>
                            </span>
                            <span style="color: var(--muted-foreground); font-size: 0.72rem; direction: ltr;">
                                [${escapeHtml(item.full_genre)}]
                            </span>
                        </div>
                    `).join('')}
                </div>
            </div>`;
        });
        }

        container.innerHTML = html;
        refreshIcons();

        // نادیده گرفتن همپوشانی
        container.querySelectorAll('.ignore-redundancy-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.key;
                if (!key) return;
                let ignored = [];
                try { ignored = JSON.parse(localStorage.getItem('ignored_genre_conflicts') || '[]'); } catch (e) { ignored = []; }
                if (!ignored.includes(key)) {
                    ignored.push(key);
                    localStorage.setItem('ignored_genre_conflicts', JSON.stringify(ignored));
                }
                const card = document.getElementById(`redundancyCard_${key}`);
                if (card) card.remove();
                showToast('اخطار نادیده گرفته شد', 'info');
                const remaining = container.querySelectorAll('.audit-redundancy-card').length;
                if (badge) {
                    const currentIssues = genreAuditData ? (genreAuditData.non_standard_count || 0) : 0;
                    const newTotal = currentIssues + remaining;
                    if (newTotal > 0) {
                        badge.textContent = newTotal;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
        });

        // حذف تک ژانر از اثر دارای همپوشانی
        container.querySelectorAll('.delete-redundant-genre-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const type = btn.dataset.type;
                const id = btn.dataset.id;
                const genre = btn.dataset.genre;
                if (!confirm(`آیا از حذف ژانر "${genre}" از این عنوان اطمینان دارید؟`)) return;

                btn.disabled = true;
                const res = await apiCall('replace_genre', {
                    old_genre: genre,
                    new_genre: '__DELETE__',
                    target_type: type,
                    target_id: id
                });
                if (res.ok) {
                    showToast(res.message || 'ژانر با موفقیت حذف شد', 'success');
                    await renderGenreLibrary();
                    await runGenreAudit(false);
                    if (currentListId) loadFullList(currentListId);
                } else {
                    showToast(res.message || 'خطا در حذف ژانر', 'error');
                    btn.disabled = false;
                }
            });
        });

        // دکمه‌های باز/بسته کردن لیست عناوین هر کارت
        container.querySelectorAll('.toggle-issue-titles-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.target;
                const targetEl = document.getElementById(targetId);
                if (!targetEl) return;
                const isHidden = targetEl.style.display === 'none';
                targetEl.style.display = isHidden ? 'block' : 'none';
                btn.innerHTML = isHidden ?
                    `عناوین <i data-lucide="chevron-up"></i>` :
                    `عناوین <i data-lucide="chevron-down"></i>`;
            });
        });

        // دکمه جایگزینی تکی یک ژانر برای تمام عناوین دارای آن
        container.querySelectorAll('.apply-single-fix-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const rawGenre = btn.dataset.raw;
                const selectEl = container.querySelector(`.audit-target-select[data-raw="${CSS.escape(rawGenre)}"]`);
                if (!selectEl) return;
                const targetGenre = selectEl.value;

                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="refresh-cw"></i> در حال انجام...';

                const res = await apiCall('replace_genre', {
                    old_genre: rawGenre,
                    new_genre: targetGenre,
                    target_type: 'all'
                });

                if (res.ok) {
                    showToast(res.message || 'ژانر با موفقیت جایگزین شد', 'success');
                    await renderGenreLibrary();
                    await runGenreAudit(false);
                    // به‌روزرسانی لیست جاری اگر باز است
                    if (currentListId) loadFullList(currentListId);
                } else {
                    showToast(res.message || 'خطا در اعمال اصلاح', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="check"></i> جایگزینی برای همه';
                }
            });
        });
    }

    // بازبینی مجدد
    document.getElementById('refreshGenreAuditBtn')?.addEventListener('click', () => runGenreAudit(false));

    // اصلاح دسته‌جمعی خودکار همه موارد پیشنهادی
    document.getElementById('batchFixAllGenresBtn')?.addEventListener('click', async () => {
        if (!genreAuditData || !genreAuditData.issues || !genreAuditData.issues.length) return;

        const fixes = [];
        genreAuditData.issues.forEach(issue => {
            const raw = issue.raw_genre;
            const selectEl = document.querySelector(`.audit-target-select[data-raw="${CSS.escape(raw)}"]`);
            const target = selectEl ? selectEl.value : (issue.suggested_genre ? issue.suggested_genre.name : null);
            if (target && raw) {
                fixes.push({ old: raw, new: target });
            }
        });

        if (!fixes.length) {
            showToast('موردی برای اصلاح خودکار یافت نشد', 'warning');
            return;
        }

        const btn = document.getElementById('batchFixAllGenresBtn');
        const origHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="refresh-cw"></i> در حال اصلاح همگانی...';

        const res = await apiCall('batch_fix_genres', { fixes: JSON.stringify(fixes) });
        if (res.ok) {
            showToast(res.message || 'تمام ژانرها با موفقیت یکپارچه شدند', 'success');
            await renderGenreLibrary();
            await runGenreAudit(false);
            if (currentListId) loadFullList(currentListId);
        } else {
            showToast(res.message || 'خطا در اصلاح دسته‌جمعی', 'error');
            btn.disabled = false;
            btn.innerHTML = origHTML;
        }
    });

    document.querySelectorAll('.close-genre-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('genreModal').classList.remove('show');
            document.getElementById('genreModalAlert').style.display = 'none';
        });
    });

    document.getElementById('openGenreModalBtn')?.addEventListener('click', () => openGenreModal());

        const STANDARD_GENRES_CATALOG = [
        { slug: 'action', name_fa: 'اکشن', name_en: 'Action', name: 'اکشن / Action', color: '#ef4444', synonyms: ['اکشن', 'action', 'مبارزه‌ای', 'حادثه‌ای'] },
        { slug: 'adventure', name_fa: 'ماجراجویی', name_en: 'Adventure', name: 'ماجراجویی / Adventure', color: '#f97316', synonyms: ['ماجراجویی', 'adventure', 'ماجراجویانه', 'اکتشافی'] },
        { slug: 'animation', name_fa: 'انیمیشن', name_en: 'Animation', name: 'انیمیشن / Animation', color: '#eab308', synonyms: ['انیمیشن', 'animation', 'پویانمایی', 'کارتون'] },
        { slug: 'anime', name_fa: 'انیمه', name_en: 'Anime', name: 'انیمه / Anime', color: '#ec4899', synonyms: ['انیمه', 'anime', 'انیمه ژاپنی', 'مانگا', 'اوتاکو', 'manga'] },
        { slug: 'biography', name_fa: 'زندگی‌نامه', name_en: 'Biography', name: 'زندگی‌نامه / Biography', color: '#84cc16', synonyms: ['زندگی‌نامه', 'زندگینامه', 'بیوگرافی', 'biography'] },
        { slug: 'comedy', name_fa: 'کمدی', name_en: 'Comedy', name: 'کمدی / Comedy', color: '#10b981', synonyms: ['کمدی', 'comedy', 'طنز', 'خنده‌دار', 'طنزآمیز'] },
        { slug: 'sitcom', name_fa: 'کمدی موقعیت', name_en: 'Sitcom', name: 'کمدی موقعیت / Sitcom', color: '#0d9488', synonyms: ['کمدی موقعیت', 'سیتکام', 'سیت کام', 'sitcom', 'situation comedy', 'کمدی سیتکام', 'کمدی وضعیت', 'طنز موقعیت'] },
        { slug: 'crime', name_fa: 'جنایی', name_en: 'Crime', name: 'جنایی / Crime', color: '#06b6d4', synonyms: ['جنایی', 'crime', 'پلیسی', 'تبهکاری'] },
        { slug: 'documentary', name_fa: 'مستند', name_en: 'Documentary', name: 'مستند / Documentary', color: '#0ea5e9', synonyms: ['مستند', 'documentary', 'واقعی'] },
        { slug: 'drama', name_fa: 'درام', name_en: 'Drama', name: 'درام / Drama', color: '#3b82f6', synonyms: ['درام', 'drama', 'احساسی', 'اجتماعی'] },
        { slug: 'family', name_fa: 'خانوادگی', name_en: 'Family', name: 'خانوادگی / Family', color: '#6366f1', synonyms: ['خانوادگی', 'family', 'کودک', 'نوجوان'] },
        { slug: 'fantasy', name_fa: 'فانتزی', name_en: 'Fantasy', name: 'فانتزی / Fantasy', color: '#8b5cf6', synonyms: ['فانتزی', 'fantasy', 'جادویی', 'افسانه‌ای'] },
        { slug: 'film-noir', name_fa: 'فیلم نوآر', name_en: 'Film-Noir', name: 'فیلم نوآر / Film-Noir', color: '#475569', synonyms: ['فیلم نوآر', 'نوآر', 'noir', 'film-noir'] },
        { slug: 'history', name_fa: 'تاریخی', name_en: 'History', name: 'تاریخی / History', color: '#a855f7', synonyms: ['تاریخی', 'history', 'تاریخ', 'کهن', 'باستانی'] },
        { slug: 'horror', name_fa: 'ترسناک', name_en: 'Horror', name: 'ترسناک / Horror', color: '#dc2626', synonyms: ['ترسناک', 'horror', 'وحشت', 'دلهره‌آور', 'اسلشر'] },
        { slug: 'music', name_fa: 'موسیقی', name_en: 'Music', name: 'موسیقی / Music', color: '#ec4899', synonyms: ['موسیقی', 'music', 'موزیک', 'کنسرت'] },
        { slug: 'musical', name_fa: 'موزیکال', name_en: 'Musical', name: 'موزیکال / Musical', color: '#f43f5e', synonyms: ['موزیکال', 'musical', 'آهنگین'] },
        { slug: 'mystery', name_fa: 'معمایی', name_en: 'Mystery', name: 'معمایی / Mystery', color: '#14b8a6', synonyms: ['معمایی', 'mystery', 'رازآلود', 'معما', 'کارآگاهی'] },
        { slug: 'romance', name_fa: 'عاشقانه', name_en: 'Romance', name: 'عاشقانه / Romance', color: '#fb7185', synonyms: ['عاشقانه', 'romance', 'رمانتیک', 'عاطفی', 'عشق'] },
        { slug: 'sci-fi', name_fa: 'علمی-تخیلی', name_en: 'Sci-Fi', name: 'علمی-تخیلی / Sci-Fi', color: '#0284c7', synonyms: ['علمی-تخیلی', 'علمی تخیلی', 'sci-fi', 'scifi', 'فضایی'] },
        { slug: 'sport', name_fa: 'ورزشی', name_en: 'Sport', name: 'ورزشی / Sport', color: '#16a34a', synonyms: ['ورزشی', 'sport', 'ورزش', 'مسابقه'] },
        { slug: 'thriller', name_fa: 'هیجان‌انگیز', name_en: 'Thriller', name: 'هیجان‌انگیز / Thriller', color: '#b91c1c', synonyms: ['هیجان‌انگیز', 'thriller', 'تریلر', 'تعلیق', 'دلهره'] },
        { slug: 'war', name_fa: 'جنگی', name_en: 'War', name: 'جنگی / War', color: '#78716c', synonyms: ['جنگی', 'war', 'دفاع مقدس', 'نظامی'] },
        { slug: 'western', name_fa: 'وسترن', name_en: 'Western', name: 'وسترن / Western', color: '#d97706', synonyms: ['وسترن', 'western', 'غرب وحشی', 'کابویی'] },
        { slug: 'superhero', name_fa: 'ابرقهرمانی', name_en: 'Superhero', name: 'ابرقهرمانی / Superhero', color: '#7c3aed', synonyms: ['ابرقهرمانی', 'superhero', 'کمیک', 'comic', 'سوپرهیرو'] },
        { slug: 'psychological', name_fa: 'روانشناختی', name_en: 'Psychological', name: 'روانشناختی / Psychological', color: '#6d28d9', synonyms: ['روانشناختی', 'روان‌شناختی', 'psychological', 'روانشناسی'] },
        { slug: 'spy', name_fa: 'جاسوسی', name_en: 'Spy', name: 'جاسوسی / Spy', color: '#334155', synonyms: ['جاسوسی', 'spy', 'امنیتی', 'مامور مخفی'] },
        { slug: 'supernatural', name_fa: 'ماوراءالطبیعه', name_en: 'Supernatural', name: 'ماوراءالطبیعه / Supernatural', color: '#4338ca', synonyms: ['ماوراءالطبیعه', 'ماورایی', 'supernatural', 'ارواح'] },
        { slug: 'post-apocalyptic', name_fa: 'پساآخرالزمانی', name_en: 'Post-Apocalyptic', name: 'پساآخرالزمانی / Post-Apocalyptic', color: '#713f12', synonyms: ['پساآخرالزمانی', 'post-apocalyptic', 'dystopian', 'دیستوپیا'] },
        { slug: 'cyberpunk', name_fa: 'سایبرپانک', name_en: 'Cyberpunk', name: 'سایبرپانک / Cyberpunk', color: '#059669', synonyms: ['سایبرپانک', 'cyberpunk', 'آینده‌نگرانه'] },
        { slug: 'short', name_fa: 'کوتاه', name_en: 'Short', name: 'کوتاه / Short', color: '#64748b', synonyms: ['کوتاه', 'short', 'فیلم کوتاه'] },
        { slug: 'gangster', name_fa: 'مافیایی', name_en: 'Gangster', name: 'مافیایی / Gangster', color: '#1e293b', synonyms: ['مافیایی', 'gangster', 'mafia', 'مافیا'] }
    ];

    function normalizeGenreSearchText(text) {
        if (!text) return '';
        return text.toString().trim().toLowerCase()
            .replace(/[ي]/g, 'ی')
            .replace(/[ك]/g, 'ک')
            .replace(/[ۀة]/g, 'ه')
            .replace(/[‌]/g, ' ')
            .replace(/[آأإ]/g, 'ا')
            .replace(/[\-–—_]/g, ' ')
            .replace(/\s+/g, ' ');
    }

    function getGenreColor(genreName) {
        if (!genreName) return '#6366f1';
        const clean = normalizeGenreSearchText(genreName);
        const all = (allLibraryGenres && allLibraryGenres.length) ? allLibraryGenres : STANDARD_GENRES_CATALOG;
        for (const g of all) {
            if (normalizeGenreSearchText(g.name) === clean ||
                normalizeGenreSearchText(g.name_fa) === clean ||
                normalizeGenreSearchText(g.name_en) === clean ||
                normalizeGenreSearchText(g.slug) === clean) {
                return g.color || '#6366f1';
            }
        }
        return '#6366f1';
    }

    function initGenreTags(containerId, inputId, counterId, hiddenInputId, initialTags = []) {
        const container = document.getElementById(containerId);
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        const hiddenInput = document.getElementById(hiddenInputId);
        if (!container || !input || !hiddenInput) return null;

        let wrapper = container.closest('.genre-field-wrapper');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'genre-field-wrapper';
            wrapper.style.position = 'relative';
            wrapper.style.width = '100%';
            container.parentNode.insertBefore(wrapper, container);
            wrapper.appendChild(container);
        }

        let tags = [...initialTags];
        let suggestionBox = null;
        let currentMatches = [];
        let highlightedIndex = -1;

        renderTags();

        function renderTags() {
            container.querySelectorAll('.genre-tag').forEach(t => t.remove());
            tags.forEach(tag => addTagElement(tag));
            updateState();
        }

        function addTagElement(tagText) {
            const cleanText = tagText.trim();
            if (!cleanText) return;

            const color = getGenreColor(cleanText);
            const tag = document.createElement('span');
            tag.className = 'genre-tag';
            tag.style.borderColor = color + '40';
            tag.innerHTML = `
                <span class="color-dot" style="background: ${color};"></span>
                <span class="tag-title">${escapeHtml(cleanText)}</span>
                <span class="tag-remove" data-tag="${escapeHtml(cleanText)}" title="حذف ژانر">✕</span>
            `;
            container.insertBefore(tag, input);

            tag.querySelector('.tag-remove').addEventListener('click', function(e) {
                e.stopPropagation();
                removeTag(this.dataset.tag);
            });
        }

        function addTag(tagText) {
            const clean = tagText.trim();
            if (!clean) return;
            if (tags.length >= MAX_GENRE_TAGS) {
                showToast(`حداکثر ${MAX_GENRE_TAGS} ژانر می‌توانید انتخاب کنید.`, 'warning');
                return;
            }
            const normNew = normalizeGenreSearchText(clean);
            const exists = tags.some(t => normalizeGenreSearchText(t) === normNew);
            if (exists) {
                showToast(`ژانر «${clean}» قبلاً اضافه شده است.`, 'warning');
                return;
            }

            tags.push(clean);
            addTagElement(clean);
            updateState();
            input.value = '';
            hideSuggestions();
            input.focus();
        }

        function removeTag(tagText) {
            tags = tags.filter(t => t !== tagText);
            renderTags();
            input.focus();
        }

        function updateState() {
            hiddenInput.value = tags.join(', ');
            const count = tags.length;

            if (counter) {
                counter.textContent = `${count} از ${MAX_GENRE_TAGS}`;
                counter.classList.remove('warning', 'full');
                if (count >= MAX_GENRE_TAGS) {
                    counter.classList.add('full');
                    counter.textContent = `${count} از ${MAX_GENRE_TAGS} (تکمیل)`;
                } else if (count === MAX_GENRE_TAGS - 1) {
                    counter.classList.add('warning');
                }
            }

            if (count >= MAX_GENRE_TAGS) {
                container.classList.add('is-full');
                input.placeholder = 'سقف ۳ ژانر تکمیل است';
                input.disabled = true;
                hideSuggestions();
            } else {
                container.classList.remove('is-full');
                input.placeholder = count === 0 ? 'تایپ ۲-۳ حرف فارسی یا انگلیسی...' : 'افزودن ژانر بعدی...';
                input.disabled = false;
            }
        }

        function ensureSuggestionBox() {
            if (!suggestionBox) {
                suggestionBox = document.createElement('div');
                suggestionBox.className = 'genre-suggestions';
                wrapper.appendChild(suggestionBox);
            }
            return suggestionBox;
        }

        function hideSuggestions() {
            if (suggestionBox) {
                suggestionBox.style.display = 'none';
                highlightedIndex = -1;
                currentMatches = [];
            }
        }

        function showSuggestions(queryText) {
            if (tags.length >= MAX_GENRE_TAGS) {
                hideSuggestions();
                return;
            }

            const rawQuery = queryText.trim();
            const normQuery = normalizeGenreSearchText(rawQuery);

            if (normQuery.length < 1) {
                hideSuggestions();
                return;
            }

            const box = ensureSuggestionBox();

            const allAvailable = (allLibraryGenres && allLibraryGenres.length)
                ? allLibraryGenres
                : STANDARD_GENRES_CATALOG;

            const genreMap = new Map();
            STANDARD_GENRES_CATALOG.forEach(g => genreMap.set(g.slug, { ...g, usage_count: 0, is_standard: 1 }));
            allAvailable.forEach(g => {
                if (g.slug && genreMap.has(g.slug)) {
                    genreMap.set(g.slug, { ...genreMap.get(g.slug), ...g });
                } else if (g.slug) {
                    genreMap.set(g.slug, g);
                }
            });

            const currentTagNorms = tags.map(t => normalizeGenreSearchText(t));

            currentMatches = Array.from(genreMap.values()).filter(g => {
                if (currentTagNorms.includes(normalizeGenreSearchText(g.name)) ||
                    currentTagNorms.includes(normalizeGenreSearchText(g.name_fa)) ||
                    currentTagNorms.includes(normalizeGenreSearchText(g.name_en))) {
                    return false;
                }

                const nFa = normalizeGenreSearchText(g.name_fa);
                const nEn = normalizeGenreSearchText(g.name_en);
                const nName = normalizeGenreSearchText(g.name);
                const nSlug = normalizeGenreSearchText(g.slug);

                if (nFa.includes(normQuery) || nEn.includes(normQuery) || nName.includes(normQuery) || nSlug.includes(normQuery)) {
                    return true;
                }

                if (Array.isArray(g.synonyms)) {
                    return g.synonyms.some(s => normalizeGenreSearchText(s).includes(normQuery));
                }
                return false;
            }).slice(0, 8);

            if (!currentMatches.length) {
                box.style.display = 'block';
                box.innerHTML = `
                    <div style="padding: 0.75rem 0.9rem; text-align: center; color: var(--muted-foreground); font-size: 0.78rem;">
                        <span>ژانر استانداردی با «${escapeHtml(rawQuery)}» یافت نشد.</span>
                        <div style="margin-top: 0.35rem; font-size: 0.72rem; color: var(--primary);">
                            با فشردن <strong>Enter</strong> می‌توانید آن را به عنوان ژانر اختصاصی ثبت کنید.
                        </div>
                    </div>
                `;
                highlightedIndex = -1;
                return;
            }

            highlightedIndex = 0;
            renderSuggestionList(box, normQuery);
            box.style.display = 'block';
        }

        function renderSuggestionList(box, query) {
            box.innerHTML = currentMatches.map((g, idx) => {
                const isActive = idx === highlightedIndex;
                const faHighlighted = highlightMatch(g.name_fa || g.name, query);
                const enHighlighted = highlightMatch(g.name_en || g.slug, query);
                const usageText = (g.usage_count && g.usage_count > 0) ? `${g.usage_count} عنوان` : 'استاندارد ⭐';

                return `
                <div class="genre-suggestion-item ${isActive ? 'active' : ''}" data-index="${idx}" style="display:flex; align-items:center; justify-content:space-between; padding:0.5rem 0.8rem; cursor:pointer;">
                    <div class="genre-name-group" style="display:flex; align-items:center; gap:0.55rem;">
                        <span class="color-dot" style="background:${g.color || '#6366f1'}; width:10px; height:10px; border-radius:50%; flex-shrink:0;"></span>
                        <span class="genre-fa-name" style="font-weight:700; font-size:0.85rem;">${faHighlighted}</span>
                        <span class="genre-en-name" style="font-size:0.75rem; color:var(--muted-foreground); font-family:monospace;">(${enHighlighted})</span>
                    </div>
                    <span class="genre-badge" style="font-size:0.68rem; background:var(--secondary); color:var(--muted-foreground); padding:0.15rem 0.45rem; border-radius:6px;">${usageText}</span>
                </div>`;
            }).join('');

            box.querySelectorAll('.genre-suggestion-item').forEach(item => {
                item.addEventListener('mouseenter', () => {
                    highlightedIndex = parseInt(item.dataset.index, 10);
                    updateActiveItem(box);
                });
                item.addEventListener('mousedown', (ev) => {
                    ev.preventDefault();
                    const idx = parseInt(item.dataset.index, 10);
                    if (currentMatches[idx]) {
                        addTag(currentMatches[idx].name);
                    }
                });
            });
        }

        function updateActiveItem(box) {
            box.querySelectorAll('.genre-suggestion-item').forEach((item, idx) => {
                if (idx === highlightedIndex) {
                    item.classList.add('active');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('active');
                }
            });
        }

        function highlightMatch(text, query) {
            if (!text) return '';
            if (!query) return escapeHtml(text);
            const normText = normalizeGenreSearchText(text);
            const pos = normText.indexOf(query);
            if (pos === -1) return escapeHtml(text);
            const originalPart = text.substr(pos, query.length);
            const before = escapeHtml(text.substr(0, pos));
            const after = escapeHtml(text.substr(pos + query.length));
            return `${before}<span style="color:var(--primary); font-weight:800; text-decoration:underline;">${escapeHtml(originalPart)}</span>${after}`;
        }

        input.addEventListener('input', () => {
            showSuggestions(input.value);
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 1) {
                showSuggestions(input.value);
            }
        });

        input.addEventListener('keydown', (e) => {
            if (tags.length >= MAX_GENRE_TAGS && e.key !== 'Backspace') {
                e.preventDefault();
                return;
            }

            if (e.key === 'ArrowDown') {
                if (suggestionBox && suggestionBox.style.display === 'block' && currentMatches.length > 0) {
                    e.preventDefault();
                    highlightedIndex = (highlightedIndex + 1) % currentMatches.length;
                    updateActiveItem(suggestionBox);
                }
                return;
            }

            if (e.key === 'ArrowUp') {
                if (suggestionBox && suggestionBox.style.display === 'block' && currentMatches.length > 0) {
                    e.preventDefault();
                    highlightedIndex = (highlightedIndex - 1 + currentMatches.length) % currentMatches.length;
                    updateActiveItem(suggestionBox);
                }
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                if (suggestionBox && suggestionBox.style.display === 'block' && highlightedIndex >= 0 && currentMatches[highlightedIndex]) {
                    addTag(currentMatches[highlightedIndex].name);
                } else if (input.value.trim()) {
                    addTag(input.value.trim());
                }
                return;
            }

            if (e.key === 'Escape') {
                hideSuggestions();
                return;
            }

            if (e.key === 'Backspace' && input.value === '' && tags.length > 0) {
                removeTag(tags[tags.length - 1]);
                return;
            }
        });

        container.addEventListener('click', (e) => {
            if (e.target === container && !input.disabled) {
                input.focus();
            }
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                hideSuggestions();
            }
        });

        return {
            getTags: () => tags,
            setTags: (newTags) => {
                tags = Array.isArray(newTags) ? [...newTags] : [];
                renderTags();
            },
            clear: () => {
                tags = [];
                renderTags();
            }
        };
    }

let movieGenreManager = null;
let seriesGenreManager = null;

async function syncGenresToLibrary(genreString) {
    const genreList = (genreString || '').split(',').map(g => g.trim()).filter(g => g);
    if (!genreList.length) return;

    await loadGenres();
    const existingNames = new Set(allLibraryGenres.map(g => g.name));
    const newGenres = genreList.filter(g => !existingNames.has(g));

    if (newGenres.length) {
        for (const g of newGenres) {
            await apiCall('add_genre', { name: g, color: '#0891b2' });
        }
        showToast(`ژانرهای «${newGenres.join('، ')}» به کتابخانه اضافه شدند.`, 'warning');
    }
}


/* ============================================================
   SECTION 19: EXPORT (PNG & MULTI-PAGE CANVAS SLICING PDF)
   ============================================================ */
    let isExporting = false;

    // تبدیل امن تصویر بنر به DataURL جهت جلوگیری قطعی از خطای CORS و Tainted Canvas در html2canvas
    async function getSafeBannerDataUrl(bannerFileName) {
        if (!bannerFileName || typeof bannerFileName !== 'string') return null;
        const cleanName = bannerFileName.trim();
        if (!cleanName || cleanName === 'none' || cleanName === 'null') return null;
        try {
            const url = 'uploads/banners/' + encodeURIComponent(cleanName);
            const res = await fetch(url, { credentials: 'same-origin', cache: 'force-cache' });
            if (!res.ok) return null;
            const blob = await res.blob();
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.onerror = () => resolve(null);
                reader.readAsDataURL(blob);
            });
        } catch (e) {
            console.warn('Could not load banner as data URL:', e);
            return null;
        }
    }

    async function exportList(format) {
        if (!currentListId) {
            showToast('لطفاً ابتدا یک لیست را انتخاب کنید.', 'warning');
            return;
        }
        if (isExporting) {
            showToast('لطفا تا اتمام خروجی قبلی صبر کنید.', 'warning');
            return;
        }

        // بررسی بارگذاری کتابخانه‌ها
        if (typeof html2canvas === 'undefined') {
            showToast('کتابخانه ساخت تصویر در حال بارگذاری است، لطفاً چند لحظه بعد تلاش کنید.', 'error');
            return;
        }

        const isPng = (format === 'png' || format === 'jpg');
        const jsPDFClass = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : (typeof window.jsPDF === 'function' ? window.jsPDF : null);

        if (!isPng && format === 'pdf' && !jsPDFClass) {
            showToast('کتابخانه PDF هنوز بارگذاری نشده است. لطفاً صفحه را تازه‌سازی کنید.', 'error');
            return;
        }

        isExporting = true;
        const btnId = isPng ? 'exportPngBtn' : 'exportPdfBtn';
        const otherBtnId = isPng ? 'exportPdfBtn' : 'exportPngBtn';
        const btn = document.getElementById(btnId) || document.getElementById('exportJpgBtn');
        const otherBtn = document.getElementById(otherBtnId);
        const originalHTML = btn ? btn.innerHTML : '';
        const otherOriginalHTML = otherBtn ? otherBtn.innerHTML : '';

        if (btn) {
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.6';
            btn.innerHTML = '<span class="loading" style="display:inline-block; vertical-align:middle; width:12px; height:12px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin 0.6s linear infinite; margin-left:4px;"></span> در حال آماده‌سازی...';
        }
        if (otherBtn) {
            otherBtn.style.pointerEvents = 'none';
            otherBtn.style.opacity = '0.3';
        }

        try {
            const result = await apiCall('get_full_list', { list_id: currentListId });
            if (!result.ok || !result.data) {
                const errMsg = result?.message || 'خطا در دریافت اطلاعات لیست از سرور';
                showToast(errMsg, 'error');
                return;
            }

            const listData = result.data;
            const preqResult = await apiCall('get_all_prequels_for_list', { list_id: currentListId });
            const prequelData = (preqResult && preqResult.ok) ? preqResult.data : { movies: [], series: [] };
            const commentsResult = await apiCall('get_all_comments_for_list', { list_id: currentListId });
            const commentsData = (commentsResult && commentsResult.ok) ? commentsResult.data : { movies: [], seasons: [] };
            const displayName = '<?php echo htmlspecialchars($display_name); ?>';

            if (isPng) {
                await exportAsPNG(listData, prequelData, commentsData, displayName);
            } else if (format === 'pdf') {
                await exportAsPDF(listData, prequelData, commentsData, displayName);
            }
        } catch (error) {
            console.error('Export error:', error);
            showToast('خطا در تولید خروجی (' + (isPng ? 'PNG' : 'PDF') + '): ' + (error.message || 'خطای ناشناخته'), 'error');
        } finally {
            if (btn) {
                btn.style.pointerEvents = '';
                btn.style.opacity = '1';
                btn.innerHTML = originalHTML;
            }
            if (otherBtn) {
                otherBtn.style.pointerEvents = '';
                otherBtn.style.opacity = '1';
                otherBtn.innerHTML = otherOriginalHTML;
            }
            refreshIcons();
            isExporting = false;
        }
    }

    function getSafeWatchDateText(watchDate, isWatched) {
        if (!watchDate || watchDate === 0 || watchDate === '0') {
            return isWatched == 1 ? 'بدون تاریخ' : '';
        }
        const str = String(watchDate).trim();
        if (str === '1970-01-01' || str.startsWith('1970-01-01') || str.startsWith('1970/01/01')) {
            return isWatched == 1 ? 'بدون تاریخ' : '';
        }
        const formatted = formatDate(watchDate);
        if (!formatted || formatted.startsWith('1970/01/01')) {
            return isWatched == 1 ? 'بدون تاریخ' : '';
        }
        return formatted;
    }

    function buildMovieItemHTML(movie, index, total, prequels, comment, format) {
        const number = total - index;
        const watchDateText = getSafeWatchDateText(movie.watch_date, movie.watched);
        const rating = movie.rating ? `${movie.rating}/10` : '';
        const hasComment = comment !== null;
        const hasPrequels = prequels && prequels.length > 0;

        let html = `
        <div class="export-movie-item" style="padding:12px 14px; margin-bottom:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; display:flex; justify-content:space-between; align-items:center;">
            <div class="export-movie-left" style="display:flex; align-items:center; gap:12px;">
                <div class="export-movie-number" style="font-size:12px; font-weight:700; color:#6366f1; background:#eef2ff; padding:3px 8px; border-radius:6px;">#${number}</div>
                <div class="export-movie-info">
                    <div class="export-movie-title" style="font-size:14px; font-weight:700; color:#111827;">${escapeHtml(movie.title)}</div>
                    <div class="export-movie-meta" style="font-size:11px; color:#6b7280; margin-top:2px;">${movie.release_year || '?'} • ${escapeHtml(movie.genre || 'بدون ژانر')}</div>
                </div>
            </div>
            <div class="export-movie-right" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                ${movie.downloaded == 1 ? '<span class="export-badge" style="font-size:10px; padding:2px 8px; background:#ecfdf5; color:#059669; border-radius:6px; border:1px solid #a7f3d0;">دانلود شده</span>' : ''}
                ${movie.watched == 1 ? `<span class="export-badge" style="font-size:10px; padding:2px 8px; background:#f0fdf4; color:#16a34a; border-radius:6px; border:1px solid #bbf7d0;">👁️ ${watchDateText}</span>` : ''}
                ${rating ? `<span class="export-badge" style="font-size:10px; padding:2px 8px; background:#fffbeb; color:#d97706; border-radius:6px; border:1px solid #fde68a;">⭐ ${rating}</span>` : ''}
                ${hasComment ? '<span class="export-badge" style="font-size:10px; padding:2px 8px; background:#f5f3ff; color:#7c3aed; border-radius:6px; border:1px solid #ddd6fe;">💬 دیدگاه</span>' : ''}
                ${hasPrequels ? `<span class="export-badge" style="font-size:10px; padding:2px 8px; background:#f0f9ff; color:#0284c7; border-radius:6px; border:1px solid #bae6fd;">🔗 ${prequels.length} عنوان مرتبط</span>` : ''}
            </div>
        </div>`;

        if (hasPrequels) {
            html += `<div class="export-prequels-list" style="margin-right:24px; margin-top:-6px; margin-bottom:8px; display:flex; flex-direction:column; gap:4px;">`;
            prequels.forEach(p => {
                const typeText = p.relation_type === 'prequel' ? '◀ پیش‌درآمد:' : (p.relation_type === 'sequel' ? '▶ دنباله:' : '📚 مجموعه:');
                const pRating = p.rating ? `⭐ ${p.rating}/10` : '';
                const pYear = p.release_year ? `(${p.release_year})` : '';
                const pWatched = p.watched == 1 ? '👁️ تماشا شده' : '';
                html += `
                <div style="font-size:10px; color:#4b5563; background:#ffffff; padding:4px 8px; border-radius:6px; border:1px dashed #cbd5e1; display:flex; justify-content:space-between; align-items:center;">
                    <span><strong>${typeText}</strong> ${escapeHtml(p.title)} ${pYear}</span>
                    <span style="display:flex; gap:6px; font-size:9px;">
                        ${pWatched ? `<span style="color:#16a34a;">${pWatched}</span>` : ''}
                        ${pRating ? `<span style="color:#d97706;">${pRating}</span>` : ''}
                    </span>
                </div>`;
            });
            html += `</div>`;
        }

        if (hasComment) {
            html += `
            <div class="export-comment-box" style="margin-right:24px; margin-top:-6px; margin-bottom:8px; padding:8px 12px; background:#faf5ff; border:1px solid #e9d5ff; border-radius:8px; font-size:11px; color:#581c87;">
                <div style="font-weight:700; margin-bottom:2px;">💬 دیدگاه:</div>
                <div style="line-height:1.4;">${escapeHtml(comment.comment)}</div>
            </div>`;
        }

        return html;
    }

    function buildSeriesItemHTML(series, index, total, prequels, commentsData) {
        const number = total - index;
        const seasons = series.seasons || [];
        const hasPrequels = prequels && prequels.length > 0;

        let html = `
        <div class="export-movie-item" style="padding:12px 14px; margin-bottom:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; display:flex; justify-content:space-between; align-items:center;">
            <div class="export-movie-left" style="display:flex; align-items:center; gap:12px;">
                <div class="export-movie-number" style="font-size:12px; font-weight:700; color:#6366f1; background:#eef2ff; padding:3px 8px; border-radius:6px;">#${number}</div>
                <div class="export-movie-info">
                    <div class="export-movie-title" style="font-size:14px; font-weight:700; color:#111827;">${escapeHtml(series.title)}</div>
                    <div class="export-movie-meta" style="font-size:11px; color:#6b7280; margin-top:2px;">${escapeHtml(series.genre || 'بدون ژانر')} • ${seasons.length} فصل</div>
                </div>
            </div>
            <div class="export-movie-right">
                ${hasPrequels ? `<span class="export-badge" style="font-size:10px; padding:2px 8px; background:#f0f9ff; color:#0284c7; border-radius:6px; border:1px solid #bae6fd;">🔗 ${prequels.length} عنوان مرتبط</span>` : ''}
            </div>
        </div>`;

        if (hasPrequels) {
            html += `<div class="export-prequels-list" style="margin-right:24px; margin-top:-6px; margin-bottom:8px; display:flex; flex-direction:column; gap:4px;">`;
            prequels.forEach(p => {
                const typeText = p.relation_type === 'prequel' ? '◀ پیش‌درآمد:' : (p.relation_type === 'sequel' ? '▶ دنباله:' : '📚 مجموعه:');
                const pRating = p.rating ? `⭐ ${p.rating}/10` : '';
                const pYear = p.release_year ? `(${p.release_year})` : '';
                html += `
                <div style="font-size:10px; color:#4b5563; background:#ffffff; padding:4px 8px; border-radius:6px; border:1px dashed #cbd5e1; display:flex; justify-content:space-between; align-items:center;">
                    <span><strong>${typeText}</strong> ${escapeHtml(p.title)} ${pYear}</span>
                    <span style="display:flex; gap:6px; font-size:9px;">
                        ${pRating ? `<span style="color:#d97706;">${pRating}</span>` : ''}
                    </span>
                </div>`;
            });
            html += `</div>`;
        }

        if (seasons.length > 0) {
            html += `<div style="margin-right:20px; margin-bottom:12px; display:flex; flex-direction:column; gap:6px;">`;
            seasons.forEach(season => {
                const seasonComment = commentsData?.seasons?.[season.id] || null;
                const hasSeasonComment = seasonComment !== null;
                const seasonDateText = getSafeWatchDateText(season.watch_date, season.watched);

                html += `
                <div style="font-size:11px; padding:8px 12px; background:#ffffff; border:1px solid #f3f4f6; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-weight:700; color:#374151;">فصل ${season.season_number}</span>
                        ${season.episodes ? `<span style="color:#9ca3af; font-size:10px;">(${season.episodes} قسمت)</span>` : ''}
                    </div>
                    <div style="display:flex; gap:6px; align-items:center;">
                        ${season.downloaded == 1 ? '<span style="font-size:9px; padding:1px 6px; background:#ecfdf5; color:#059669; border-radius:4px;">دانلود</span>' : ''}
                        ${season.watched == 1 ? `<span style="font-size:9px; padding:1px 6px; background:#f0fdf4; color:#16a34a; border-radius:4px;">👁️ ${seasonDateText}</span>` : ''}
                        ${season.rating ? `<span style="font-size:9px; padding:1px 6px; background:#fffbeb; color:#d97706; border-radius:4px;">⭐ ${season.rating}/10</span>` : ''}
                        ${hasSeasonComment ? '<span style="font-size:9px; padding:1px 6px; background:#f5f3ff; color:#7c3aed; border-radius:4px;">💬</span>' : ''}
                    </div>
                </div>`;

                if (hasSeasonComment) {
                    html += `
                    <div style="margin-right:16px; margin-top:-4px; margin-bottom:4px; padding:6px 10px; background:#faf5ff; border:1px solid #e9d5ff; border-radius:6px; font-size:10px; color:#581c87;">
                        <span style="font-weight:700;">💬 کامنت:</span> ${escapeHtml(seasonComment.comment)}
                    </div>`;
                }
            });
            html += `</div>`;
        }

        return html;
    }

    const EXPORT_HEADER_HTML = (displayName) => `
        <div class="export-header" style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; border-bottom:2px solid #6366f1; margin-bottom:18px;">
            <div class="export-header-left" style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:22px;">🎬</span>
                <span class="site-name" style="font-size:18px; font-weight:700; color:#6366f1;">MovieManager</span>
            </div>
            <div class="export-header-right" style="text-align:left; font-size:11px; color:#6b7280; line-height:1.4;">
                ساخته شده توسط: <strong>${escapeHtml(displayName)}</strong><br>
                ${new Date().toLocaleDateString('fa-IR')}
            </div>
        </div>`;

    const EXPORT_FOOTER_HTML = `
        <div class="export-footer" style="margin-top:20px; padding-top:10px; border-top:1px solid #e5e7eb; text-align:center; font-size:10px; color:#9ca3af;">
            © ${new Date().getFullYear()} MovieManager | سامانه‌ی مدیریت فیلم و سریال
        </div>`;

    async function exportAsPNG(listData, prequelData, commentsData, displayName) {
        let container = document.getElementById('exportContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'exportContainer';
            document.body.appendChild(container);
        }
        container.style.position = 'fixed';
        container.style.left = '-9999px';
        container.style.top = '0';
        container.style.width = '850px';
        container.style.zIndex = '-9999';
        container.style.pointerEvents = 'none';

        // تبدیل امن ساختار داده‌های prequels و comments به Map بر اساس media_id
        const pMoviesMap = {};
        if (Array.isArray(prequelData?.movies)) {
            prequelData.movies.forEach(p => {
                if (!pMoviesMap[p.media_id]) pMoviesMap[p.media_id] = [];
                pMoviesMap[p.media_id].push(p);
            });
        } else if (prequelData?.movies && typeof prequelData.movies === 'object') {
            Object.assign(pMoviesMap, prequelData.movies);
        }

        const pSeriesMap = {};
        if (Array.isArray(prequelData?.series)) {
            prequelData.series.forEach(p => {
                if (!pSeriesMap[p.media_id]) pSeriesMap[p.media_id] = [];
                pSeriesMap[p.media_id].push(p);
            });
        } else if (prequelData?.series && typeof prequelData.series === 'object') {
            Object.assign(pSeriesMap, prequelData.series);
        }

        const cMoviesMap = {};
        if (Array.isArray(commentsData?.movies)) {
            commentsData.movies.forEach(c => { cMoviesMap[c.media_id] = c; });
        } else if (commentsData?.movies && typeof commentsData.movies === 'object') {
            Object.assign(cMoviesMap, commentsData.movies);
        }

        const cSeasonsMap = {};
        if (Array.isArray(commentsData?.seasons)) {
            commentsData.seasons.forEach(c => { cSeasonsMap[c.media_id] = c; });
        } else if (commentsData?.seasons && typeof commentsData.seasons === 'object') {
            Object.assign(cSeasonsMap, commentsData.seasons);
        }
        const safeCommentsData = { movies: cMoviesMap, seasons: cSeasonsMap };

        const bannerDataUrl = await getSafeBannerDataUrl(listData?.list?.banner);

        let html = `<div class="export-png-container" style="width:850px; background:#ffffff; direction:rtl; font-family:'IRANSansX', Tahoma, sans-serif; color:#1f2937; box-sizing:border-box;">`;
        html += `<div class="export-png-inner" style="background:#ffffff;">`;

        if (bannerDataUrl) {
            html += `
            <div class="export-banner-area" style="width:100%; height:200px; overflow:hidden; position:relative;">
                <img src="${bannerDataUrl}" style="width:100%; height:100%; object-fit:cover; display:block;">
                <div class="export-banner-overlay" style="position:absolute; bottom:0; left:0; right:0; padding:16px 24px; background:linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                    <div class="export-banner-title" style="color:#ffffff; font-size:22px; font-weight:700;">${escapeHtml(listData.list.name)}</div>
                </div>
            </div>`;
        }

        html += `<div class="export-png-content" style="padding:24px 30px;">`;
        html += EXPORT_HEADER_HTML(displayName);

        if (!bannerDataUrl) {
            html += `<div class="export-list-title" style="font-size:20px; font-weight:700; color:#1f2937; margin-bottom:8px;">${escapeHtml(listData.list.name)}</div>`;
        }

        if (listData.list.description) {
            html += `<div class="export-list-desc" style="font-size:12px; color:#6b7280; margin-bottom:16px; padding:10px 14px; background:#f3f4f6; border-radius:8px; border-right:3px solid #6366f1;">${escapeHtml(listData.list.description)}</div>`;
        }

        if (listData.movies && listData.movies.length > 0) {
            html += `<div class="export-section-title" style="font-size:15px; font-weight:700; color:#6366f1; margin:16px 0 10px 0; padding-bottom:6px; border-bottom:1px solid #e5e7eb;">فیلم‌ها (${listData.movies.length})</div>`;
            listData.movies.forEach((movie, index) => {
                const prequels = pMoviesMap[movie.id] || [];
                const comment = cMoviesMap[movie.id] || null;
                html += buildMovieItemHTML(movie, index, listData.movies.length, prequels, comment, 'png');
            });
        }

        if (listData.series && listData.series.length > 0) {
            html += `<div class="export-section-title" style="font-size:15px; font-weight:700; color:#6366f1; margin:20px 0 10px 0; padding-bottom:6px; border-bottom:1px solid #e5e7eb;">سریال‌ها (${listData.series.length})</div>`;
            listData.series.forEach((series, index) => {
                const prequels = pSeriesMap[series.id] || [];
                html += buildSeriesItemHTML(series, index, listData.series.length, prequels, safeCommentsData);
            });
        }

        html += EXPORT_FOOTER_HTML;
        html += `</div></div></div>`;

        container.innerHTML = html;

        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }
        await new Promise(resolve => setTimeout(resolve, 250));

        const element = container.querySelector('.export-png-container');

        const canvas = await html2canvas(element, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            allowTaint: false,
            logging: false,
            scrollX: 0,
            scrollY: 0,
            windowWidth: 850,
            onclone: (clonedDoc) => {
                const el = clonedDoc.getElementById('exportContainer');
                if (el) {
                    el.style.position = 'absolute';
                    el.style.left = '0';
                    el.style.top = '0';
                    el.style.zIndex = '9999';
                }
            }
        });

        const safeName = (listData.list.name || 'list').replace(/[\/\?%*:|"<>]/g, '_');
        const link = document.createElement('a');
        link.download = `MovieList_${safeName}.png`;
        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        setTimeout(() => { if (link.parentNode) link.parentNode.removeChild(link); }, 100);

        container.innerHTML = '';
        showToast('تصویر PNG با موفقیت ذخیره شد', 'success');
    }

    const exportAsJPG = exportAsPNG;

    async function exportAsPDF(listData, prequelData, commentsData, displayName) {
        const jsPDFClass = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : (typeof window.jsPDF === 'function' ? window.jsPDF : null);
        if (!jsPDFClass) {
            showToast('کتابخانه jsPDF لود نشده است.', 'error');
            return;
        }

        let container = document.getElementById('exportContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'exportContainer';
            document.body.appendChild(container);
        }
        container.style.position = 'fixed';
        container.style.left = '-9999px';
        container.style.top = '0';
        container.style.width = '850px';
        container.style.zIndex = '-9999';
        container.style.pointerEvents = 'none';

        // تبدیل امن ساختار داده‌های prequels و comments به Map بر اساس media_id
        const pMoviesMap = {};
        if (Array.isArray(prequelData?.movies)) {
            prequelData.movies.forEach(p => {
                if (!pMoviesMap[p.media_id]) pMoviesMap[p.media_id] = [];
                pMoviesMap[p.media_id].push(p);
            });
        } else if (prequelData?.movies && typeof prequelData.movies === 'object') {
            Object.assign(pMoviesMap, prequelData.movies);
        }

        const pSeriesMap = {};
        if (Array.isArray(prequelData?.series)) {
            prequelData.series.forEach(p => {
                if (!pSeriesMap[p.media_id]) pSeriesMap[p.media_id] = [];
                pSeriesMap[p.media_id].push(p);
            });
        } else if (prequelData?.series && typeof prequelData.series === 'object') {
            Object.assign(pSeriesMap, prequelData.series);
        }

        const cMoviesMap = {};
        if (Array.isArray(commentsData?.movies)) {
            commentsData.movies.forEach(c => { cMoviesMap[c.media_id] = c; });
        } else if (commentsData?.movies && typeof commentsData.movies === 'object') {
            Object.assign(cMoviesMap, commentsData.movies);
        }

        const cSeasonsMap = {};
        if (Array.isArray(commentsData?.seasons)) {
            commentsData.seasons.forEach(c => { cSeasonsMap[c.media_id] = c; });
        } else if (commentsData?.seasons && typeof commentsData.seasons === 'object') {
            Object.assign(cSeasonsMap, commentsData.seasons);
        }
        const safeCommentsData = { movies: cMoviesMap, seasons: cSeasonsMap };

        const bannerDataUrl = await getSafeBannerDataUrl(listData?.list?.banner);

        let html = `<div class="export-pdf-wrapper" style="width:850px; background:#ffffff; padding:24px 30px; direction:rtl; font-family:'IRANSansX', Tahoma, sans-serif; color:#1f2937; box-sizing:border-box;">`;
        html += EXPORT_HEADER_HTML(displayName);

        if (bannerDataUrl) {
            html += `
            <div class="export-banner-area" style="margin-bottom:16px; border-radius:10px; overflow:hidden; max-height:200px;">
                <img src="${bannerDataUrl}" style="width:100%; height:200px; object-fit:cover; display:block;">
            </div>`;
        }

        html += `<div class="export-list-title" style="font-size:20px; font-weight:700; color:#1f2937; margin-bottom:8px;">${escapeHtml(listData.list.name)}</div>`;

        if (listData.list.description) {
            html += `<div class="export-list-desc" style="font-size:12px; color:#6b7280; margin-bottom:16px; padding:10px 14px; background:#f3f4f6; border-radius:8px; border-right:3px solid #6366f1;">${escapeHtml(listData.list.description)}</div>`;
        }

        if (listData.movies && listData.movies.length > 0) {
            html += `<div class="export-section-title" style="font-size:15px; font-weight:700; color:#6366f1; margin:16px 0 10px 0; padding-bottom:6px; border-bottom:1px solid #e5e7eb;">فیلم‌ها (${listData.movies.length})</div>`;
            listData.movies.forEach((movie, index) => {
                const prequels = pMoviesMap[movie.id] || [];
                const comment = cMoviesMap[movie.id] || null;
                html += buildMovieItemHTML(movie, index, listData.movies.length, prequels, comment, 'pdf');
            });
        }

        if (listData.series && listData.series.length > 0) {
            html += `<div class="export-section-title" style="font-size:15px; font-weight:700; color:#6366f1; margin:20px 0 10px 0; padding-bottom:6px; border-bottom:1px solid #e5e7eb;">سریال‌ها (${listData.series.length})</div>`;
            listData.series.forEach((series, index) => {
                const prequels = pSeriesMap[series.id] || [];
                html += buildSeriesItemHTML(series, index, listData.series.length, prequels, safeCommentsData);
            });
        }

        html += EXPORT_FOOTER_HTML;
        html += `</div>`;

        container.innerHTML = html;

        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }
        await new Promise(resolve => setTimeout(resolve, 250));

        const element = container.querySelector('.export-pdf-wrapper');

        const canvas = await html2canvas(element, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            allowTaint: false,
            logging: false,
            scrollX: 0,
            scrollY: 0,
            windowWidth: 850,
            onclone: (clonedDoc) => {
                const el = clonedDoc.getElementById('exportContainer');
                if (el) {
                    el.style.position = 'absolute';
                    el.style.left = '0';
                    el.style.top = '0';
                    el.style.zIndex = '9999';
                }
            }
        });

        const pdf = new jsPDFClass('p', 'mm', 'a4');
        const pageWidth = pdf.internal.pageSize.getWidth(); // 210mm
        const pageHeight = pdf.internal.pageSize.getHeight(); // 297mm
        const margin = 10;
        const printWidth = pageWidth - (margin * 2); // 190mm
        const printHeight = pageHeight - (margin * 2); // 277mm

        const imgWidth = canvas.width;
        const imgHeight = canvas.height;

        const pageCanvasHeight = Math.floor((imgWidth * printHeight) / printWidth);

        let currentY = 0;
        let pageNum = 0;

        while (currentY < imgHeight) {
            if (pageNum > 0) {
                pdf.addPage();
            }

            const sliceHeight = Math.min(pageCanvasHeight, imgHeight - currentY);
            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width = imgWidth;
            sliceCanvas.height = sliceHeight;
            const sliceCtx = sliceCanvas.getContext('2d');
            sliceCtx.fillStyle = '#ffffff';
            sliceCtx.fillRect(0, 0, imgWidth, sliceHeight);
            sliceCtx.drawImage(canvas, 0, currentY, imgWidth, sliceHeight, 0, 0, imgWidth, sliceHeight);

            const sliceData = sliceCanvas.toDataURL('image/jpeg', 0.95);
            const slicePdfHeight = (sliceHeight * printWidth) / imgWidth;
            pdf.addImage(sliceData, 'JPEG', margin, margin, printWidth, slicePdfHeight);

            currentY += sliceHeight;
            pageNum++;
        }

        const safeName = (listData.list.name || 'list').replace(/[\/\?%*:|"<>]/g, '_');
        pdf.save(`MovieList_${safeName}.pdf`);
        container.innerHTML = '';
        showToast('فایل PDF با موفقیت دانلود شد', 'success');
    }


/* ============================================================
   SECTION 20: SHARE, SHARED LIST & COMMENTS + INIT
   ============================================================ */

// ===== LIST DETAIL TABS =====
function initListDetailTabs() {
    const tabs = document.querySelectorAll('#listDetailModal .tab');
    if (!tabs.length) {
        setTimeout(initListDetailTabs, 100);
        return;
    }

    tabs.forEach(tab => {
        const newTab = tab.cloneNode(true);
        tab.parentNode.replaceChild(newTab, tab);

        newTab.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const tabName = this.dataset.tab;

            document.querySelectorAll('#listDetailModal .tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const moviesTabDiv = document.getElementById('moviesTab');
            const seriesTabDiv = document.getElementById('seriesTab');
            const commentsTabDiv = document.getElementById('listCommentsTab');

            if (moviesTabDiv) moviesTabDiv.style.display = 'none';
            if (seriesTabDiv) seriesTabDiv.style.display = 'none';
            if (commentsTabDiv) commentsTabDiv.style.display = 'none';

            if (tabName === 'movies') {
                if (moviesTabDiv) moviesTabDiv.style.display = 'block';
            } else if (tabName === 'series') {
                if (seriesTabDiv) seriesTabDiv.style.display = 'block';
            } else if (tabName === 'list-comments') {
                if (commentsTabDiv) {
                    commentsTabDiv.style.display = 'block';
                    if (currentListId) {
                        const commentsListDiv = document.getElementById('listCommentsList');
                        if (commentsListDiv && (commentsListDiv.innerHTML.includes('در حال بارگذاری') || commentsListDiv.innerHTML === '')) {
                            loadListComments(true);
                        }
                    }
                }
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initListDetailTabs);
} else {
    initListDetailTabs();
}

// ===== SHARE MODAL =====
let currentShareListId = null;

async function openShareModal(listId) {
    currentShareListId = listId;
    const result = await apiCall('get_share_code', { list_id: listId });

    if (result.ok && result.data) {
        const shareCode = result.data.share_code;
        document.getElementById('shareCodeDisplay').textContent = shareCode;
        document.getElementById('shareAlert').style.display = 'none';
        document.getElementById('shareModal').classList.add('show');
        refreshIcons();
    } else {
        showToast(result.message || 'خطا در دریافت کد اشتراک', 'error');
    }
}

document.getElementById('copyShareCodeBtn')?.addEventListener('click', () => {
    const code = document.getElementById('shareCodeDisplay').textContent;
    navigator.clipboard.writeText(code).then(() => {
        showToast('کد با موفقیت کپی شد', 'success');
    }).catch(() => {
        showToast('خطا در کپی کردن', 'error');
    });
});

document.getElementById('regenerateShareCodeBtn')?.addEventListener('click', async () => {
    if (!currentShareListId) return;
    const result = await apiCall('regenerate_share_code', { list_id: currentShareListId });
    if (result.ok && result.data) {
        document.getElementById('shareCodeDisplay').textContent = result.data.share_code;
        showToast('کد اشتراک جدید ساخته شد', 'success');
    } else {
        showToast(result.message || 'خطا در بازسازی کد', 'error');
    }
});

document.getElementById('openSharedListModalBtn')?.addEventListener('click', () => {
    document.getElementById('shareCodeInput').value = '';
    document.getElementById('sharedListAlert').style.display = 'none';
    document.getElementById('sharedListModal').classList.add('show');
    refreshIcons();
});

document.getElementById('viewSharedListBtn')?.addEventListener('click', async () => {
    const shareCode = document.getElementById('shareCodeInput').value.trim();
    const viewBtn = document.getElementById('viewSharedListBtn');
    const originalText = viewBtn.innerHTML;

    if (!shareCode || shareCode.length !== 15) {
        showAlert(document.getElementById('sharedListAlert'), 'لطفاً کد ۱۵ کاراکتری معتبر وارد کنید', 'error');
        return;
    }

    viewBtn.innerHTML = '<div class="loading-small" style="margin: 0 auto;"></div>';
    viewBtn.disabled = true;

    try {
        const result = await apiCall('get_shared_list', { share_code: shareCode });
        if (result.ok && result.data) {
            document.getElementById('sharedListModal').classList.remove('show');
            displaySharedList(result.data);
        } else {
            showAlert(document.getElementById('sharedListAlert'), result.message || 'لیست مورد نظر یافت نشد', 'error');
        }
    } catch (error) {
        showAlert(document.getElementById('sharedListAlert'), 'خطا در ارتباط با سرور', 'error');
    } finally {
        viewBtn.innerHTML = originalText;
        viewBtn.disabled = false;
        refreshIcons();
    }
});

document.getElementById('shareListBtn')?.addEventListener('click', () => {
    if (currentListId) openShareModal(currentListId);
});

document.querySelectorAll('.close-share-modal, .close-shared-modal, .close-shared-detail-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('shareModal')?.classList.remove('show');
        document.getElementById('sharedListModal')?.classList.remove('show');
        document.getElementById('sharedListDetailModal')?.classList.remove('show');
    });
});

function displaySharedList(data) {
    const list = data.list;
    const isOwner = data.is_owner;
    const shareCode = document.getElementById('shareCodeInput').value.trim();

    document.getElementById('sharedDetailListTitle').innerHTML = escapeHtml(list.name);

    document.getElementById('sharedListOwnerInfo').innerHTML =
        `👤 ساخته شده توسط: ${escapeHtml(list.owner_name || 'کاربر ناشناس')} ${isOwner ? '<span style="color: #10b981;">(لیست خود شما)</span>' : ''}`;

    window.sharedMoviesData = data.movies;
    window.sharedSeriesData = data.series;
    window.sharedPrequelsData = data.prequels;
    window.sharedCommentsData = data.comments;
    window.currentSharedListId = list.id;
    window.currentSharedListCode = shareCode;

    renderSharedMovies(data.movies);
    renderSharedSeries(data.series);

    sharedCommentsOffset = 0;
    sharedCommentsLimit = 20;
    sharedCommentsTotal = 0;
    editingSharedCommentId = null;

    document.getElementById('sharedCommentsList').innerHTML = '<div class="loading-center"><div class="loading"></div><p>در حال بارگذاری نظرات...</p></div>';
    document.getElementById('sharedCommentsPagination').innerHTML = '';

    const commentTextarea = document.getElementById('sharedNewComment');
    if (commentTextarea) {
        commentTextarea.value = '';
        document.getElementById('sharedCharCount').textContent = '0';
    }

    loadSharedListComments(true);
    document.getElementById('sharedListDetailModal').classList.add('show');
    refreshIcons();

    document.getElementById('sharedListSearch').value = '';
    document.getElementById('sharedListSearch').oninput = () => filterSharedListItems();

    document.querySelectorAll('#sharedListDetailModal .tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('#sharedListDetailModal .tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('sharedMoviesTab').style.display = 'none';
            document.getElementById('sharedSeriesTab').style.display = 'none';
            document.getElementById('sharedCommentsTab').style.display = 'none';

            if (tab.dataset.tab === 'shared-movies') {
                document.getElementById('sharedMoviesTab').style.display = 'block';
            } else if (tab.dataset.tab === 'shared-series') {
                document.getElementById('sharedSeriesTab').style.display = 'block';
            } else if (tab.dataset.tab === 'shared-comments') {
                document.getElementById('sharedCommentsTab').style.display = 'block';
                if (window.currentSharedListId &&
                    document.getElementById('sharedCommentsList').innerHTML.includes('در حال بارگذاری')) {
                    loadSharedListComments(true);
                }
            }
        });
    });
}

function renderSharedMovies(movies) {
    const container = document.getElementById('sharedMoviesList');
    if (!movies.length) {
        container.innerHTML = '<div class="empty-state"><i data-lucide="film"></i><p>فیلمی یافت نشد</p></div>';
        refreshIcons();
        return;
    }

    container.innerHTML = movies.map((m, index) => {
        const moviePrequels = (window.sharedPrequelsData?.movies && window.sharedPrequelsData.movies[m.id]) || [];
        const hasPrequels = moviePrequels.length > 0;
        const movieComment = (window.sharedCommentsData?.movies && window.sharedCommentsData.movies[m.id]) || null;
        const hasComment = movieComment !== null;
        const genreTags = m.genre ? m.genre.split(',').map(g => g.trim()).filter(g => g) : [];
        const genreDisplay = genreTags.length > 0
            ? `<span class="genre-display">${genreTags.map(g => `<span class="genre-display-tag">${escapeHtml(g)}</span>`).join('')}</span>`
            : '<span style="font-size:0.65rem; color: var(--muted-foreground);">بدون ژانر</span>';

        return `
    <div class="media-item" style="flex-direction: column; align-items: stretch;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div class="media-info">
                <div class="media-icon">
                    <i data-lucide="film"></i>
                </div>
                <div class="media-details">
                    <div class="media-title">${escapeHtml(m.title)}</div>
                    <div class="media-meta">
                        <span>${m.release_year || 'سال نامشخص'}</span>
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; margin-right: 0.5rem;">• ${genreDisplay}</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                ${m.downloaded == 1 ? '<span class="status-badge badge-downloaded"><i data-lucide="download"></i> دانلود</span>' : ''}
                ${m.watched == 1 ? `<span class="status-badge badge-watched"><i data-lucide="eye"></i> ${m.watch_date ? formatDate(m.watch_date) : 'دیده شده'}</span>` : ''}
                ${m.rating ? `<span class="status-badge" style="background:#f59e0b20; color:#f59e0b;"><i data-lucide="star"></i> ${m.rating}/10</span>` : ''}
            </div>
        </div>
        ${hasComment ? `
        <div class="comment-section" style="margin-top: 0.5rem;">
            <div class="comment-header">
                <i data-lucide="message-circle"></i>
                <span class="comment-author">${escapeHtml(movieComment.display_name || 'کاربر')}</span>
                <span class="comment-date">${formatDate(movieComment.created_at)}</span>
            </div>
            <div class="comment-text">${escapeHtml(movieComment.comment)}</div>
        </div>
        ` : ''}
        ${hasPrequels ? `
        <div class="prequels-list" style="margin-top: 0.5rem;">
            ${moviePrequels.map(p => {
                const typeLabel = p.relation_type === 'prequel' ? 'پیش‌درآمد' : p.relation_type === 'sequel' ? 'دنباله' : 'مجموعه';
                return `<span class="prequel-tag ${p.relation_type}">${typeLabel}: ${escapeHtml(p.title)}${p.release_year ? ` (${p.release_year})` : ''}</span>`;
            }).join('')}
        </div>
        ` : ''}
    </div>`;
    }).join('');
    refreshIcons();
}

function renderSharedSeries(seriesList) {
    const container = document.getElementById('sharedSeriesList');
    if (!seriesList.length) {
        container.innerHTML = '<div class="empty-state"><i data-lucide="tv"></i><p>سریالی یافت نشد</p></div>';
        refreshIcons();
        return;
    }

    container.innerHTML = seriesList.map(s => {
        const seriesPrequels = (window.sharedPrequelsData?.series && window.sharedPrequelsData.series[s.id]) || [];
        const hasPrequels = seriesPrequels.length > 0;
        const genreTags = s.genre ? s.genre.split(',').map(g => g.trim()).filter(g => g) : [];
        const genreDisplay = genreTags.length > 0
            ? `<span class="genre-display">${genreTags.map(g => `<span class="genre-display-tag">${escapeHtml(g)}</span>`).join('')}</span>`
            : '<span style="font-size:0.65rem; color: var(--muted-foreground);">بدون ژانر</span>';

        return `
    <div class="media-item" style="flex-direction:column;align-items:stretch;">
        <div class="flex items-center justify-between">
            <div class="media-info">
                <div class="media-icon">
                    <i data-lucide="tv"></i>
                </div>
                <div class="media-details">
                    <div class="media-title">${escapeHtml(s.title)}</div>
                    <div class="media-meta">
                        <span>${s.start_year || '?'} - ${s.end_year || 'در حال پخش'}</span>
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; margin-right: 0.5rem;">• ${genreDisplay}</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                ${hasPrequels ? `<span class="status-badge" style="background:#3b82f620; color:#3b82f6;"><i data-lucide="link-2"></i> ${seriesPrequels.length}</span>` : ''}
            </div>
        </div>
        <div class="seasons-container">
            ${renderSharedSeasons(s.seasons)}
        </div>
    </div>`;
    }).join('');
    refreshIcons();
}

function renderSharedSeasons(seasons) {
    if (!seasons?.length) return '<div style="padding:0.5rem 0;">هنوز فصلی اضافه نشده</div>';
    return seasons.map(s => {
        const seasonComment = (window.sharedCommentsData?.seasons && window.sharedCommentsData.seasons[s.id]) || null;
        const hasSeasonComment = seasonComment !== null;

        return `
    <div class="season-item" style="flex-direction: column; align-items: stretch; gap: 0.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div><strong>فصل ${s.season_number}</strong> <span style="font-size:0.7rem;">(${s.episode_count} قسمت)</span></div>
            <div class="flex gap-2">
                ${s.downloaded == 1 ? '<span class="status-badge badge-downloaded"><i data-lucide="download"></i></span>' : ''}
                ${s.watched == 1 ? `<span class="status-badge badge-watched"><i data-lucide="eye"></i> ${s.watch_date ? formatDate(s.watch_date) : ''}</span>` : ''}
                ${s.rating ? `<span class="status-badge" style="background:#f59e0b20; color:#f59e0b;"><i data-lucide="star"></i> ${s.rating}/10</span>` : ''}
            </div>
        </div>
        ${hasSeasonComment ? `
        <div class="comment-section">
            <div class="comment-header">
                <i data-lucide="message-circle"></i>
                <span class="comment-author">${escapeHtml(seasonComment.display_name || 'کاربر')}</span>
                <span class="comment-date">${formatDate(seasonComment.created_at)}</span>
            </div>
            <div class="comment-text">${escapeHtml(seasonComment.comment)}</div>
        </div>
        ` : ''}
    </div>`;
    }).join('');
}

function filterSharedListItems() {
    const term = document.getElementById('sharedListSearch').value.toLowerCase();
    if (window.sharedMoviesData) {
        const filteredMovies = window.sharedMoviesData.filter(m => m.title.toLowerCase().includes(term));
        renderSharedMovies(filteredMovies);
    }
    if (window.sharedSeriesData) {
        const filteredSeries = window.sharedSeriesData.filter(s => s.title.toLowerCase().includes(term));
        renderSharedSeries(filteredSeries);
    }
}

// ===== SHARED LIST COMMENTS =====
let sharedCommentsOffset = 0;
let sharedCommentsLimit = 20;
let sharedCommentsTotal = 0;
let editingSharedCommentId = null;

async function loadSharedListComments(reset = true) {
    if (!window.currentSharedListId && !window.currentSharedListCode) return;

    if (reset) {
        sharedCommentsOffset = 0;
        document.getElementById('sharedCommentsList').innerHTML = '<div class="loading-center"><div class="loading"></div><p>در حال بارگذاری نظرات...</p></div>';
    }

    const result = await apiCall('get_list_comments', {
        list_id: window.currentSharedListId || '',
        share_code: window.currentSharedListCode || '',
        limit: sharedCommentsLimit,
        offset: sharedCommentsOffset
    });

    if (result.ok) {
        sharedCommentsTotal = result.data.total;
        renderSharedComments(result.data.comments, reset);
        renderSharedCommentsPagination();
    } else {
        document.getElementById('sharedCommentsList').innerHTML = `<div class="empty-state">${escapeHtml(result.message)}</div>`;
    }
}

function renderSharedComments(comments, reset) {
    const container = document.getElementById('sharedCommentsList');

    if (!comments.length && sharedCommentsOffset === 0) {
        container.innerHTML = '<div class="empty-state">هنوز نظری ثبت نشده است. اولین نفری باشید که نظر می‌دهید!</div>';
        return;
    }

    let html = '';
    if (reset) {
        html = comments.map(comment => renderCommentItem(comment)).join('');
    } else {
        html = container.innerHTML + comments.map(comment => renderCommentItem(comment)).join('');
    }

    container.innerHTML = html;
    refreshIcons();

    document.querySelectorAll('.edit-shared-comment-btn').forEach(btn => {
        btn.removeEventListener('click', handleEditComment);
        btn.addEventListener('click', handleEditComment);
    });

    document.querySelectorAll('.delete-shared-comment-btn').forEach(btn => {
        btn.removeEventListener('click', handleDeleteComment);
        btn.addEventListener('click', handleDeleteComment);
    });

    document.querySelectorAll('.cancel-edit-shared-comment').forEach(btn => {
        btn.removeEventListener('click', handleCancelEdit);
        btn.addEventListener('click', handleCancelEdit);
    });

    document.querySelectorAll('.save-edit-shared-comment').forEach(btn => {
        btn.removeEventListener('click', handleSaveEdit);
        btn.addEventListener('click', handleSaveEdit);
    });
}

function handleEditComment(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    showEditSharedCommentForm(btn.dataset.id, btn.dataset.text);
}

function handleDeleteComment(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    confirmDelete('آیا از حذف این نظر مطمئن هستید؟', async () => {
        const result = await apiCall('delete_list_comment', { comment_id: btn.dataset.id });
        if (result.ok) {
            showToast('نظر با موفقیت حذف شد', 'success');
            loadSharedListComments(true);
        } else {
            showToast(result.message, 'error');
        }
    });
}

function handleCancelEdit(e) {
    const btn = e.currentTarget;
    const form = btn.closest('.edit-comment-form');
    if (form) form.classList.remove('active');
    editingSharedCommentId = null;
}

function handleSaveEdit(e) {
    const btn = e.currentTarget;
    const form = btn.closest('.edit-comment-form');
    const commentId = form.dataset.id;
    const textarea = form.querySelector('.edit-comment-text');
    const newText = textarea.value.trim();

    if (!newText) {
        showToast('متن نظر نمی‌تواند خالی باشد', 'error');
        return;
    }

    if (newText.length > 1000) {
        showToast('نظر نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد', 'error');
        return;
    }

    (async () => {
        const result = await apiCall('edit_list_comment', { comment_id: commentId, comment: newText });
        if (result.ok) {
            showToast('نظر با موفقیت ویرایش شد', 'success');
            loadSharedListComments(true);
            editingSharedCommentId = null;
        } else {
            showToast(result.message, 'error');
        }
    })();
}

function renderCommentItem(comment) {
    const canEdit = comment.can_edit || false;
    const canDelete = comment.can_delete || false;

    return `
    <div class="comment-item" data-comment-id="${comment.id}">
        <div class="comment-item-header">
            <span class="comment-author-name">${escapeHtml(comment.display_name || comment.username)}</span>
            <span class="comment-date">${formatDate(comment.created_at)}</span>
        </div>
        <div class="comment-text">${escapeHtml(comment.comment)}</div>
        ${(canEdit || canDelete) ? `
        <div class="comment-actions">
            ${canEdit ? `<button class="btn-icon edit-shared-comment-btn" data-id="${comment.id}" data-text="${escapeHtml(comment.comment)}" style="padding: 0.25rem 0.5rem;"><i data-lucide="pencil" style="width:16px;height:16px;"></i> ویرایش</button>` : ''}
            ${canDelete ? `<button class="btn-icon delete-shared-comment-btn" data-id="${comment.id}" style="padding: 0.25rem 0.5rem; color: var(--destructive);"><i data-lucide="trash-2" style="width:16px;height:16px;"></i> حذف</button>` : ''}
        </div>
        ` : ''}
        <div class="edit-comment-form" data-id="${comment.id}">
            <textarea class="edit-comment-text form-input" maxlength="1000" rows="3" placeholder="ویرایش نظر...">${escapeHtml(comment.comment)}</textarea>
            <div class="comment-char-count">
                <span class="edit-char-count">${comment.comment.length}</span> / 1000
            </div>
            <div class="flex gap-2 mt-2">
                <button class="btn btn-secondary btn-sm cancel-edit-shared-comment">انصراف</button>
                <button class="btn btn-primary btn-sm save-edit-shared-comment">ذخیره</button>
            </div>
        </div>
    </div>
`;
}

function renderSharedCommentsPagination() {
    const container = document.getElementById('sharedCommentsPagination');
    const totalPages = Math.ceil(sharedCommentsTotal / sharedCommentsLimit);
    const currentPage = Math.floor(sharedCommentsOffset / sharedCommentsLimit) + 1;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="btn-icon shared-page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}" style="padding: 0.35rem 0.75rem;">${i}</button>`;
    }
    container.innerHTML = html;

    document.querySelectorAll('.shared-page-btn').forEach(btn => {
        btn.removeEventListener('click', handlePageClick);
        btn.addEventListener('click', handlePageClick);
    });
}

function handlePageClick(e) {
    const page = parseInt(e.currentTarget.dataset.page);
    sharedCommentsOffset = (page - 1) * sharedCommentsLimit;
    loadSharedListComments(true);
}

function showEditSharedCommentForm(commentId, commentText) {
    const commentItem = document.querySelector(`#sharedCommentsList .comment-item[data-comment-id="${commentId}"]`);
    if (!commentItem) return;

    document.querySelectorAll('#sharedCommentsList .edit-comment-form.active').forEach(form => {
        form.classList.remove('active');
    });

    const editForm = commentItem.querySelector('.edit-comment-form');
    if (editForm) {
        editForm.classList.add('active');
        const textarea = editForm.querySelector('.edit-comment-text');
        if (textarea) {
            textarea.value = commentText;
            textarea.focus();
            const charCountSpan = editForm.querySelector('.edit-char-count');
            if (charCountSpan) charCountSpan.textContent = commentText.length;
            textarea.addEventListener('input', function() {
                if (charCountSpan) charCountSpan.textContent = this.value.length;
            });
        }
        editingSharedCommentId = commentId;
    }
}

async function submitSharedComment() {
    const textarea = document.getElementById('sharedNewComment');
    const comment = textarea.value.trim();

    if (!comment) {
        showToast('لطفاً متن نظر را وارد کنید', 'error');
        return;
    }

    if (comment.length > 1000) {
        showToast('نظر نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد', 'error');
        return;
    }

    const submitBtn = document.getElementById('sharedSubmitCommentBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<div class="loading-small"></div>';
    submitBtn.disabled = true;

    const result = await apiCall('add_list_comment', {
        share_code: window.currentSharedListCode,
        comment: comment
    });

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;

    if (result.ok) {
        textarea.value = '';
        document.getElementById('sharedCharCount').textContent = '0';
        showToast('نظر شما با موفقیت ثبت شد', 'success');
        loadSharedListComments(true);
    } else {
        showToast(result.message, 'error');
    }
}

function initSharedCommentCharCounter() {
    const textarea = document.getElementById('sharedNewComment');
    if (!textarea) return;
    const charCountSpan = document.getElementById('sharedCharCount');
    textarea.removeEventListener('input', handleCharCountInput);
    textarea.addEventListener('input', handleCharCountInput);
    function handleCharCountInput() {
        charCountSpan.textContent = this.value.length;
    }
}

document.getElementById('sharedSubmitCommentBtn')?.addEventListener('click', submitSharedComment);
initSharedCommentCharCounter();

// ===== LIST COMMENTS =====
let listCommentsOffset = 0;
let listCommentsLimit = 20;
let listCommentsTotal = 0;
let editingListCommentId = null;

async function loadListComments(reset = true) {
    if (!currentListId) return;

    if (reset) {
        listCommentsOffset = 0;
        document.getElementById('listCommentsList').innerHTML = '<div class="loading-center"><div class="loading"></div><p>در حال بارگذاری نظرات...</p></div>';
    }

    try {
        const result = await apiCall('get_list_comments', {
            list_id: currentListId,
            limit: listCommentsLimit,
            offset: listCommentsOffset
        });

        if (result.ok) {
            listCommentsTotal = result.data.total;
            renderListComments(result.data.comments, reset);
            renderListCommentsPagination();
            const countSpan = document.getElementById('listCommentsCount');
            if (countSpan) countSpan.textContent = listCommentsTotal;
        } else {
            document.getElementById('listCommentsList').innerHTML = `<div class="empty-state">${escapeHtml(result.message)}</div>`;
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        document.getElementById('listCommentsList').innerHTML = '<div class="empty-state">خطا در بارگذاری نظرات</div>';
    }
}

function renderListComments(comments, reset) {
    const container = document.getElementById('listCommentsList');

    if (!comments.length && listCommentsOffset === 0) {
        container.innerHTML = '<div class="empty-state">هنوز نظری ثبت نشده است.</div>';
        return;
    }

    let html = '';
    if (reset) {
        html = comments.map(comment => renderListCommentItem(comment)).join('');
    } else {
        html = container.innerHTML + comments.map(comment => renderListCommentItem(comment)).join('');
    }

    container.innerHTML = html;
    refreshIcons();

    document.querySelectorAll('.edit-list-comment-btn').forEach(btn => {
        btn.removeEventListener('click', handleEditListComment);
        btn.addEventListener('click', handleEditListComment);
    });

    document.querySelectorAll('.delete-list-comment-btn').forEach(btn => {
        btn.removeEventListener('click', handleDeleteListComment);
        btn.addEventListener('click', handleDeleteListComment);
    });

    document.querySelectorAll('.cancel-edit-list-comment').forEach(btn => {
        btn.removeEventListener('click', handleCancelEditList);
        btn.addEventListener('click', handleCancelEditList);
    });

    document.querySelectorAll('.save-edit-list-comment').forEach(btn => {
        btn.removeEventListener('click', handleSaveEditList);
        btn.addEventListener('click', handleSaveEditList);
    });
}

function renderListCommentItem(comment) {
    const canEdit = comment.can_edit || false;
    const canDelete = comment.can_delete || false;

    return `
    <div class="comment-item" data-comment-id="${comment.id}">
        <div class="comment-item-header">
            <span class="comment-author-name">${escapeHtml(comment.display_name || comment.username)}</span>
            <span class="comment-date">${formatDate(comment.created_at)}</span>
        </div>
        <div class="comment-text">${escapeHtml(comment.comment)}</div>
        ${(canEdit || canDelete) ? `
        <div class="comment-actions">
            ${canEdit ? `<button class="btn-icon edit-list-comment-btn" data-id="${comment.id}" data-text="${escapeHtml(comment.comment)}" style="padding: 0.25rem 0.5rem;"><i data-lucide="pencil" style="width:16px;height:16px;"></i> ویرایش</button>` : ''}
            ${canDelete ? `<button class="btn-icon delete-list-comment-btn" data-id="${comment.id}" style="padding: 0.25rem 0.5rem; color: var(--destructive);"><i data-lucide="trash-2" style="width:16px;height:16px;"></i> حذف</button>` : ''}
        </div>
        ` : ''}
        <div class="edit-comment-form" data-id="${comment.id}">
            <textarea class="edit-comment-text form-input" maxlength="1000" rows="3" placeholder="ویرایش نظر...">${escapeHtml(comment.comment)}</textarea>
            <div class="comment-char-count">
                <span class="edit-char-count">${comment.comment.length}</span> / 1000
            </div>
            <div class="flex gap-2 mt-2">
                <button class="btn btn-secondary btn-sm cancel-edit-list-comment">انصراف</button>
                <button class="btn btn-primary btn-sm save-edit-list-comment">ذخیره</button>
            </div>
        </div>
    </div>
`;
}

function renderListCommentsPagination() {
    const container = document.getElementById('listCommentsPagination');
    const totalPages = Math.ceil(listCommentsTotal / listCommentsLimit);
    const currentPage = Math.floor(listCommentsOffset / listCommentsLimit) + 1;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="btn-icon list-page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}" style="padding: 0.35rem 0.75rem;">${i}</button>`;
    }
    container.innerHTML = html;

    document.querySelectorAll('.list-page-btn').forEach(btn => {
        btn.removeEventListener('click', handleListPageClick);
        btn.addEventListener('click', handleListPageClick);
    });
}

function handleListPageClick(e) {
    const page = parseInt(e.currentTarget.dataset.page);
    listCommentsOffset = (page - 1) * listCommentsLimit;
    loadListComments(true);
}

function handleEditListComment(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    showEditListCommentForm(btn.dataset.id, btn.dataset.text);
}

function handleDeleteListComment(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    confirmDelete('آیا از حذف این نظر مطمئن هستید؟', async () => {
        const result = await apiCall('delete_list_comment', { comment_id: btn.dataset.id });
        if (result.ok) {
            showToast('نظر با موفقیت حذف شد', 'success');
            loadListComments(true);
            loadAllListCommentBadges();
        } else {
            showToast(result.message, 'error');
        }
    });
}

function handleCancelEditList(e) {
    const btn = e.currentTarget;
    const form = btn.closest('.edit-comment-form');
    if (form) form.classList.remove('active');
    editingListCommentId = null;
}

function handleSaveEditList(e) {
    const btn = e.currentTarget;
    const form = btn.closest('.edit-comment-form');
    const commentId = form.dataset.id;
    const textarea = form.querySelector('.edit-comment-text');
    const newText = textarea.value.trim();

    if (!newText) {
        showToast('متن نظر نمی‌تواند خالی باشد', 'error');
        return;
    }
    if (newText.length > 1000) {
        showToast('نظر نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد', 'error');
        return;
    }

    (async () => {
        const result = await apiCall('edit_list_comment', { comment_id: commentId, comment: newText });
        if (result.ok) {
            showToast('نظر با موفقیت ویرایش شد', 'success');
            loadListComments(true);
            editingListCommentId = null;
        } else {
            showToast(result.message, 'error');
        }
    })();
}

function showEditListCommentForm(commentId, commentText) {
    const commentItem = document.querySelector(`#listCommentsList .comment-item[data-comment-id="${commentId}"]`);
    if (!commentItem) return;

    document.querySelectorAll('#listCommentsList .edit-comment-form.active').forEach(form => {
        form.classList.remove('active');
    });

    const editForm = commentItem.querySelector('.edit-comment-form');
    if (editForm) {
        editForm.classList.add('active');
        const textarea = editForm.querySelector('.edit-comment-text');
        if (textarea) {
            textarea.value = commentText;
            textarea.focus();
            const charCountSpan = editForm.querySelector('.edit-char-count');
            if (charCountSpan) charCountSpan.textContent = commentText.length;
            textarea.addEventListener('input', function() {
                if (charCountSpan) charCountSpan.textContent = this.value.length;
            });
        }
        editingListCommentId = commentId;
    }
}

async function submitListComment() {
    const textarea = document.getElementById('listNewComment');
    const comment = textarea.value.trim();

    if (!comment) {
        showToast('لطفاً متن نظر را وارد کنید', 'error');
        return;
    }
    if (comment.length > 1000) {
        showToast('نظر نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد', 'error');
        return;
    }

    const submitBtn = document.getElementById('listSubmitCommentBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<div class="loading-small"></div>';
    submitBtn.disabled = true;

    const result = await apiCall('add_list_comment', { list_id: currentListId, comment: comment });

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;

    if (result.ok) {
        textarea.value = '';
        document.getElementById('listCharCount').textContent = '0';
        showToast('نظر شما با موفقیت ثبت شد', 'success');
        loadListComments(true);
        loadAllListCommentBadges();
    } else {
        showToast(result.message, 'error');
    }
}

function initListCommentCharCounter() {
    const textarea = document.getElementById('listNewComment');
    if (!textarea) return;
    const charCountSpan = document.getElementById('listCharCount');
    textarea.removeEventListener('input', handleListCharInput);
    textarea.addEventListener('input', handleListCharInput);
    function handleListCharInput() {
        charCountSpan.textContent = this.value.length;
    }
}

document.getElementById('listSubmitCommentBtn')?.addEventListener('click', submitListComment);

// ===== NEW COMMENT BADGES =====
let lastViewedLists = JSON.parse(localStorage.getItem('last_viewed_lists') || '{}');

function updateLastViewedList(listId) {
    lastViewedLists[listId] = Date.now();
    localStorage.setItem('last_viewed_lists', JSON.stringify(lastViewedLists));
    const card = document.querySelector(`.list-card[data-list-id="${listId}"]`);
    if (card) {
        const badge = card.querySelector('.list-card-comment-badge');
        if (badge) badge.remove();
    }
}

async function checkNewCommentsForList(listId, listCardElement) {
    try {
        const result = await apiCall('get_list_comments', { list_id: listId, limit: 1, offset: 0 });
        if (result.ok && result.data.total > 0) {
            const lastView = lastViewedLists[listId] || 0;
            const latestComment = result.data.comments[0];
            if (latestComment) {
                const commentTime = new Date(latestComment.created_at).getTime();
                if (commentTime > lastView) {
                    const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
                    if (latestComment.user_id != currentUserId) {
                        addCommentBadgeToListCard(listCardElement);
                    }
                }
            }
        }
    } catch (e) {
        console.error('Error checking comments:', e);
    }
}

function addCommentBadgeToListCard(cardElement) {
    if (cardElement.querySelector('.list-card-comment-badge')) return;
    const badge = document.createElement('div');
    badge.className = 'list-card-comment-badge';
    badge.title = 'کامنت جدید برای این لیست';
    cardElement.style.position = 'relative';
    cardElement.appendChild(badge);
}

async function loadAllListCommentBadges() {
    const cards = document.querySelectorAll('.list-card');
    for (const card of cards) {
        const listId = card.dataset.listId;
        if (listId) await checkNewCommentsForList(listId, card);
    }
}

// ===== GLOBAL MODAL MANAGER (STACKING, ESC, OUTSIDE CLICK, AUTO FOCUS) =====
let modalOpenSequence = 0;

function autoFocusModalInput(modal) {
    if (!modal) return;
    setTimeout(() => {
        if (!modal.classList.contains('show')) return;
        let targetInput = null;
        switch (modal.id) {
            case 'listModal':
                targetInput = modal.querySelector('#listName');
                break;
            case 'movieModal':
                targetInput = modal.querySelector('#movieTitle');
                break;
            case 'seriesModal':
                targetInput = modal.querySelector('#seriesTitle');
                break;
            case 'groupModal':
                targetInput = modal.querySelector('#groupName');
                break;
            case 'sharedListModal':
                targetInput = modal.querySelector('#shareCodeInput');
                break;
            case 'commentModal':
                targetInput = modal.querySelector('#commentText');
                break;
            case 'prequelModal':
                targetInput = modal.querySelector('#prequelTitleInput, .prequel-title');
                break;
            case 'genreModal':
                targetInput = modal.querySelector('#searchGenreLibraryInput');
                break;
            case 'deleteModal':
                targetInput = modal.querySelector('#confirmDeleteBtn, .close-delete-modal');
                break;
            case 'watchDateModal':
                targetInput = modal.querySelector('#confirmWatchBtn');
                break;
            case 'ratingModal':
                targetInput = modal.querySelector('#confirmRatingBtn');
                break;
            default:
                targetInput = modal.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled])');
        }
        if (targetInput) {
            targetInput.focus();
            if (typeof targetInput.select === 'function' && targetInput.tagName === 'INPUT' && (targetInput.type === 'text' || !targetInput.type)) {
                targetInput.select();
            }
        }
    }, 120);
}

const modalStackObserver = new MutationObserver((mutations) => {
    for (const m of mutations) {
        if (m.type === 'attributes' && m.attributeName === 'class') {
            const el = m.target;
            if (el.classList && el.classList.contains('modal')) {
                if (el.classList.contains('show')) {
                    if (!el._modalOpenSeq) {
                        el._modalOpenSeq = ++modalOpenSequence;
                    }
                    autoFocusModalInput(el);
                } else {
                    el._modalOpenSeq = 0;
                }
            }
        }
    }
});
modalStackObserver.observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class'] });

function getTopmostOpenModal() {
    const openModals = Array.from(document.querySelectorAll('.modal.show'));
    if (!openModals.length) return null;
    if (openModals.length === 1) return openModals[0];

    return openModals.sort((a, b) => {
        const zA = parseInt(window.getComputedStyle(a).zIndex) || 1000;
        const zB = parseInt(window.getComputedStyle(b).zIndex) || 1000;
        if (zB !== zA) return zB - zA;
        return (b._modalOpenSeq || 0) - (a._modalOpenSeq || 0);
    })[0];
}

function closeModalSafely(modal) {
    if (!modal) return false;
    const closeBtn = modal.querySelector(
        '.close-genre-modal, .close-group-manage-modal, .close-group-detail-modal, .close-group-modal, ' +
        '.close-share-modal, .close-shared-modal, .close-shared-detail-modal, ' +
        '.close-prequel-modal, .close-comment-modal, .close-rating-modal, ' +
        '.close-watchdate-modal, .close-season-modal, .close-series-modal, ' +
        '.close-movie-modal, .close-list-modal, .close-detail-modal, ' +
        '.close-delete-modal, .close-single-season-modal, [data-dismiss="modal"], .modal-header .btn-icon:first-of-type'
    );
    if (closeBtn && typeof closeBtn.click === 'function') {
        closeBtn.click();
    } else {
        modal.classList.remove('show');
        if (modal.id === 'singleSeasonModal') {
            modal.remove();
        }
    }
    return true;
}

// Global ESC key handling: close only the topmost modal
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    const topModal = getTopmostOpenModal();
    if (topModal) {
        e.preventDefault();
        e.stopPropagation();
        closeModalSafely(topModal);
    }
});

// Global outside backdrop click handling: close topmost modal when backdrop is clicked
let modalBackdropMouseDownTarget = null;
document.addEventListener('mousedown', function(e) {
    modalBackdropMouseDownTarget = e.target;
});

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('modal') && e.target.classList.contains('show')) {
        if (modalBackdropMouseDownTarget === e.target) {
            const topModal = getTopmostOpenModal();
            if (topModal === e.target) {
                e.preventDefault();
                e.stopPropagation();
                closeModalSafely(topModal);
            }
        }
    }
});

// Keyboard shortcuts for submit inside modals (Enter / Ctrl+Enter)
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    const topModal = getTopmostOpenModal();
    if (!topModal) return;

    const activeEl = document.activeElement;

    // Comment modal: Ctrl+Enter or Cmd+Enter
    if (topModal.id === 'commentModal') {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            const saveBtn = document.getElementById('saveCommentBtn');
            if (saveBtn && !saveBtn.disabled) saveBtn.click();
        }
        return;
    }

    // Rating modal: Enter confirms rating
    if (topModal.id === 'ratingModal') {
        e.preventDefault();
        const confirmBtn = document.getElementById('confirmRatingBtn');
        if (confirmBtn) confirmBtn.click();
        return;
    }

    // Watch date modal: Enter confirms watch date
    if (topModal.id === 'watchDateModal') {
        e.preventDefault();
        const confirmBtn = document.getElementById('confirmWatchBtn');
        if (confirmBtn) confirmBtn.click();
        return;
    }

    // Ignore Enter in multiline textareas for other modals
    if (activeEl && activeEl.tagName === 'TEXTAREA') return;

    if (topModal.id === 'listModal') {
        e.preventDefault();
        const btn = document.getElementById('saveListBtn');
        if (btn && !btn.disabled) btn.click();
    } else if (topModal.id === 'groupModal') {
        e.preventDefault();
        const btn = document.getElementById('saveGroupBtn');
        if (btn && !btn.disabled) btn.click();
    } else if (topModal.id === 'sharedListModal') {
        e.preventDefault();
        const btn = document.getElementById('viewSharedListBtn');
        if (btn && !btn.disabled) btn.click();
    } else if (topModal.id === 'movieModal') {
        if (activeEl && (activeEl.id === 'movieTitle' || activeEl.id === 'movieYear')) {
            e.preventDefault();
            const btn = document.getElementById('saveMovieBtn');
            if (btn && !btn.disabled) btn.click();
        }
    } else if (topModal.id === 'seriesModal') {
        if (activeEl && (activeEl.id === 'seriesTitle' || activeEl.id === 'seriesYear')) {
            e.preventDefault();
            const btn = document.getElementById('saveSeriesBtn');
            if (btn && !btn.disabled) btn.click();
        }
    } else if (topModal.id === 'prequelModal') {
        if (activeEl && activeEl.classList && activeEl.classList.contains('prequel-title')) {
            e.preventDefault();
            const btn = document.getElementById('savePrequelsBtn');
            if (btn && !btn.disabled) btn.click();
        }
    }
});


// Smart flip tooltip: اگر بالای المان فضای کافی نباشد (کمتر از ۵۵ پیکسل)، تول‌تیپ به پایین فلیپ می‌شود
document.addEventListener('mouseenter', (e) => {
    const el = e.target.closest && e.target.closest('[data-tooltip]');
    if (!el) return;
    const rect = el.getBoundingClientRect();
    if (rect.top < 55) {
        el.classList.add('tooltip-bottom');
    } else {
        el.classList.remove('tooltip-bottom');
    }
}, true);

// ===== INITIAL LOAD =====
document.getElementById('addMovieBtn')?.addEventListener('click', () => openMovieModal());
document.getElementById('addSeriesBtn')?.addEventListener('click', () => openSeriesModal());

document.querySelectorAll('.close-detail-modal').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('listDetailModal').classList.remove('show'));
});
document.querySelectorAll('.close-movie-modal').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('movieModal').classList.remove('show'));
});
document.querySelectorAll('.close-series-modal').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('seriesModal').classList.remove('show'));
});

// Donate button
document.getElementById('donateBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    window.open('https://pay.mhdr-shpr.ir', '_blank');
});

// Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then((registration) => {
                console.log('Service Worker registered successfully:', registration.scope);
            })
            .catch((error) => {
                console.error('Service Worker registration failed:', error);
            });
    });
}

// ===== HANDLE URL PARAMS (باز کردن لیست از all_titles) =====
function handleUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const openList = urlParams.get('open_list');
    const highlightType = urlParams.get('highlight_type');
    const highlightId = urlParams.get('highlight_id');

    if (openList) {
        // صبر کن تا لیست‌ها لود بشن
        setTimeout(async () => {
            // پیدا کردن اسم لیست از data
            const list = allListsData.find(l => l.id == openList);
            const listName = list ? list.name : 'لیست';

            await openListAndHighlight(openList, listName, highlightType || 'movie', highlightId || '');

            // پاک کردن URL
            window.history.replaceState({}, '', 'dash.php');
        }, 1500);
    }
}

// ===== INITIAL PAGE LOAD =====
loadLists();
loadGroups();
loadGenres();

// اطمینان از بارگذاری اولیه آیکن‌ها
document.addEventListener('DOMContentLoaded', () => {
    updatePwaOnlineStatus();
    refreshIcons();
});
setTimeout(refreshIcons, 300);
setTimeout(refreshIcons, 1000);

// Handle URL params بعد از لود اولیه
setTimeout(handleUrlParams, 2000);
    </script>
  </body>
</html>