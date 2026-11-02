<?php
// all_titles.php
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
    <title>همه عناوین | مدیریت فیلم و سریال</title>
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
        font-style: normal;
      }
      @font-face {
        font-family: 'IRANSansX';
        src: url('static/fonts/IRANSansX-Regular.woff2') format('woff2');
        font-weight: 400;
        font-style: normal;
      }
      @font-face {
        font-family: 'IRANSansX';
        src: url('static/fonts/IRANSansX-Bold.woff2') format('woff2');
        font-weight: 700;
        font-style: normal;
      }

      * {
        font-family: 'IRANSansX', system-ui, -apple-system, Tahoma, sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
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
      .icon-h3 { width: 28px; height: 28px; }
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

      body {
        background-color: var(--background);
        color: var(--foreground);
        font-size: var(--font-size);
        line-height: 1.5;
        transition: background-color 0.3s, color 0.3s;
        min-height: 100vh;
      }

      /* ==================== CONTAINER ==================== */
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

      .nav-link-logout {
        color: var(--destructive);
      }

      .nav-link-logout:hover {
        border-bottom-color: var(--destructive);
        color: var(--destructive);
      }

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

      .btn-icon:hover {
        background: var(--secondary);
      }

      .btn-icon:disabled,
      .btn-icon.disabled {
        opacity: 0.5;
        cursor: not-allowed;
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

      .btn-primary {
        background: var(--primary);
        color: #fff;
      }
      .btn-primary:hover { background: var(--primary-light); }

      .btn-secondary {
        background: var(--secondary);
        color: var(--secondary-foreground);
        border-color: var(--border);
      }
      .btn-secondary:hover { background: var(--accent); }

      /* ==================== PAGE TITLE ==================== */
      .page-title-section {
        margin-top: 1.5rem;
      }
      .page-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.5rem;
        font-weight: 700;
      }

      /* ==================== SEARCH BAR ==================== */
      .search-section {
        margin-top: 1rem;
        margin-bottom: 1rem;
      }
      .search-bar {
        display: flex;
        gap: 0.5rem;
      }
      .search-input {
        flex: 1;
        padding: 0.75rem 1rem;
        background: var(--input-background);
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 0.9rem;
        color: var(--foreground);
        transition: all 0.2s;
      }
      .search-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
      }
      .search-input::placeholder {
        color: var(--muted-foreground);
      }
      .search-highlight {
        background: #fef3c7;
        padding: 0 2px;
        border-radius: 3px;
        color: #78350f;
      }
      .dark .search-highlight {
        background: #78350f;
        color: #fef3c7;
      }

      /* ==================== FILTERS ==================== */
      .filters-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.25rem;
        margin-top: 1rem;
        transition: all 0.3s ease;
        overflow: hidden;
      }
      .filters-section.collapsed {
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        margin-top: 0;
        margin-bottom: 0;
        opacity: 0;
        border-width: 0;
      }
      .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
      }
      .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--muted-foreground);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .filter-label [data-lucide] {
        width: 16px;
        height: 16px;
      }
      .filter-select {
        padding: 0.6rem 0.75rem;
        background: var(--input-background);
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 0.85rem;
        color: var(--foreground);
        transition: all 0.2s;
        cursor: pointer;
      }
      .filter-select:focus {
        outline: none;
        border-color: var(--primary);
      }
      .tabs-row {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
      }
      .filter-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border);
        background: var(--secondary);
        color: var(--muted-foreground);
        white-space: nowrap;
      }
      .filter-tag.active {
        color: var(--primary);
        border-color: var(--primary);
        background: var(--accent);
      }
      .filter-tag:hover:not(.active) {
        background: var(--accent);
      }
      .filter-tag [data-lucide] {
        width: 15px;
        height: 15px;
      }

      /* ==================== INFO BANNERS ==================== */
      .info-banner {
        background: var(--accent);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-right: 4px solid var(--primary);
      }
      .info-banner [data-lucide] {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        color: var(--primary);
      }
      .info-banner span {
        font-size: 0.8rem;
        color: var(--foreground);
      }
      .info-banner a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
      }

      /* ==================== RESULTS HEADER ==================== */
      .results-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
      }
      .results-count {
        font-size: 0.875rem;
        color: var(--muted-foreground);
      }

      /* ==================== TITLES GRID ==================== */
      .titles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1.25rem;
      }

      /* ==================== TITLE CARD ==================== */
      .title-card {
        position: relative;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: visible; /* ← تغییر: برای نمایش tooltip */
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        display: flex;
        flex-direction: column;
      }
      .title-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
      }
      /* content wrapper با overflow hidden */
      .title-card > .title-card-header,
      .title-card > .title-card-body, {
        border-radius: 0;
      }
      .title-card > .title-card-header:first-child {
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
      }

      .title-card-header {
        padding: 0.9rem 1rem 0.6rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        border-bottom: 1px solid var(--border);
      }
      .title-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: var(--accent);
        color: var(--primary);
        position: relative;
      }
      .title-card-icon.movie {
        background: linear-gradient(135deg, #0891b220 0%, #06b6d430 100%);
        color: var(--primary);
      }
      .title-card-icon.series {
        background: linear-gradient(135deg, #8b5cf620 0%, #a78bfa30 100%);
        color: #8b5cf6;
      }
      .title-card-icon [data-lucide] {
        width: 24px;
        height: 24px;
      }
      .title-card-info {
        flex: 1;
        min-width: 0;
      }
      .title-card-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--foreground);
        word-break: break-word;
        line-height: 1.3;
      }
      .title-card-subtitle {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.4rem;
        font-size: 0.7rem;
        color: var(--muted-foreground);
      }
      .list-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.5rem;
        border-radius: 8px;
        font-size: 0.65rem;
        font-weight: 500;
        background: var(--secondary);
        color: var(--muted-foreground);
        border: 1px solid var(--border);
      }
      .list-badge [data-lucide] {
        width: 12px;
        height: 12px;
      }

      /* ==================== TITLE CARD BODY ==================== */
      .title-card-body {
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .title-card-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: var(--muted-foreground);
      }
      .title-card-meta .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
      }
      .title-card-meta [data-lucide] {
        width: 14px;
        height: 14px;
        opacity: 0.7;
      }

      .genre-display {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.3rem;
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

      /* ==================== SERIES STATS ==================== */
      .series-stats-inline {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        font-size: 0.7rem;
        color: var(--muted-foreground);
      }
      .series-stat-item {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
      }
      .series-stat-item [data-lucide] {
        width: 13px;
        height: 13px;
        opacity: 0.7;
      }

      /* ==================== STATUS BADGES ==================== */
      .status-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.25rem;
      }
      .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.6rem;
        border-radius: 20px;
        font-size: 0.68rem;
        font-weight: 500;
      }
      .status-badge [data-lucide] {
        width: 13px;
        height: 13px;
      }
      .badge-downloaded { background: #10b98120; color: #10b981; }
      .badge-watched { background: #6366f120; color: #6366f1; }
      .badge-rating { background: #f59e0b20; color: #f59e0b; }
      .badge-comment { background: #8b5cf620; color: #8b5cf6; }
      .badge-prequel { background: #3b82f620; color: #3b82f6; }
      .badge-collection { background: #8b5cf620; color: #8b5cf6; }

      /* ==================== COMMENT SECTION ==================== */
      .comment-section {
        margin-top: 0.5rem;
        padding: 0.65rem 0.75rem;
        background: var(--secondary);
        border-radius: 10px;
        font-size: 0.75rem;
      }
      .comment-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.4rem;
        padding-bottom: 0.4rem;
        border-bottom: 1px solid var(--border);
        font-size: 0.7rem;
      }
      .comment-header [data-lucide] {
        width: 15px;
        height: 15px;
        opacity: 0.7;
      }
      .comment-author {
        font-weight: 600;
        color: var(--primary);
      }
      .comment-date {
        color: var(--muted-foreground);
        margin-right: auto;
      }
      .comment-text {
        color: var(--foreground);
        line-height: 1.6;
        white-space: pre-wrap;
        word-wrap: break-word;
      }

      /* ==================== PREQUELS SECTION ==================== */
      .prequels-list {
        margin-top: 0.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
      }
      .prequel-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.6rem;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 500;
        width: 100%;
      }
      .prequel-tag [data-lucide] {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
      }
      .prequel-tag.prequel {
        background: #f59e0b15;
        color: #f59e0b;
        border: 1px solid #f59e0b30;
      }
      .prequel-tag.sequel {
        background: #3b82f615;
        color: #3b82f6;
        border: 1px solid #3b82f630;
      }
      .prequel-tag.collection {
        background: #8b5cf615;
        color: #8b5cf6;
        border: 1px solid #8b5cf630;
      }
      .prequel-order {
        font-size: 0.65rem;
        opacity: 0.7;
        font-family: monospace;
      }

      /* ==================== TITLE CARD ACTIONS ==================== */
      .title-card-actions {
        padding: 0.6rem 0.75rem;
        display: flex;
        gap: 0.35rem;
        border-top: 1px solid var(--border);
        background: var(--card);
        flex-wrap: wrap;
        justify-content: flex-end;
        border-bottom-left-radius: 15px;
        border-bottom-right-radius: 15px;
      }
      .action-btn {
        position: relative;
        padding: 0.45rem;
        border-radius: 10px;
        background: transparent;
        color: var(--foreground);
        border: 1px solid var(--border);
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        min-height: 34px;
        text-decoration: none;
      }
      .action-btn:hover:not(:disabled) {
        background: var(--secondary);
        transform: translateY(-1px);
      }
      .action-btn:disabled,
      .action-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }
      .action-btn [data-lucide] {
        width: 15px;
        height: 15px;
      }
      .action-btn.downloaded {
        background: #10b98115;
        border-color: #10b981;
        color: #10b981;
      }
      .action-btn.watched {
        background: #6366f115;
        border-color: #6366f1;
        color: #6366f1;
      }
      .action-btn.not-watched {
        /* حالت پیش‌فرض */
      }
      .action-btn.has-rating {
        background: #f59e0b15;
        border-color: #f59e0b;
        color: #f59e0b;
      }
      .action-btn.has-comment {
        background: #8b5cf615;
        border-color: #8b5cf6;
        color: #8b5cf6;
      }
      .action-btn.has-prequels {
        background: #3b82f615;
        border-color: #3b82f6;
        color: #3b82f6;
      }
      .action-btn.has-collection {
        background: #8b5cf615;
        border-color: #8b5cf6;
        color: #8b5cf6;
      }
      .action-btn .rating-label,
      .action-btn .watch-date-label {
        font-size: 0.65rem;
        font-weight: 600;
        direction: ltr;
        margin-right: 0.25rem;
      }

      /* ==================== PREQUEL COUNT BADGE (گوشه بالا-راست) ==================== */
      .action-btn.prequel-action {
        position: relative;
        overflow: visible;
      }
      .prequel-count-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        border-radius: 9px;
        background: #3b82f6;
        color: #ffffff;
        font-size: 0.6rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 0 1px var(--card);
        line-height: 1;
        z-index: 2;
      }
      .action-btn.has-collection .prequel-count-badge {
        background: #8b5cf6;
      }

      /* ==================== TOOLTIP (اصلاح شده - بالاتر) ==================== */
      .action-btn[data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 18px);
        right: 50%;
        transform: translateX(50%);
        background: var(--foreground);
        color: var(--background);
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }
      /* فلش زیر tooltip */
      .action-btn[data-tooltip]::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 6px); /* ← بالاتر از کارت */
        right: 50%;
        transform: translateX(50%);
        border: 6px solid transparent;
        border-top-color: var(--foreground);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
        z-index: 1000;
      }
      /* Smart flip: نمایش در پایین در صورت نبود فضای کافی در بالا */
      [data-tooltip].tooltip-bottom::after,
      .action-btn[data-tooltip].tooltip-bottom::after {
        bottom: auto !important;
        top: calc(100% + 12px) !important;
      }
      [data-tooltip].tooltip-bottom::before,
      .action-btn[data-tooltip].tooltip-bottom::before {
        bottom: auto !important;
        top: calc(100% + 4px) !important;
        border-top-color: transparent !important;
        border-bottom-color: var(--foreground) !important;
      }

      .action-btn[data-tooltip]:hover::after,
      .action-btn[data-tooltip]:hover::before {
        opacity: 1;
      }
      .action-btn.dashboard-link {
        background: var(--accent);
        border-color: var(--primary);
        color: var(--primary);
      }

      /* ==================== EMPTY STATE ==================== */
      .empty-state {
        text-align: center;
        padding: 4rem 1rem;
        color: var(--muted-foreground);
      }
      .empty-state [data-lucide] {
        width: 60px;
        height: 60px;
        opacity: 0.4;
        margin: 0 auto 1rem;
        display: block;
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
        -webkit-backdrop-filter: blur(4px);
        z-index: 1050;
        align-items: center;
        justify-content: center;
        padding: 1rem;
      }
      .modal.show {
        display: flex;
      }
      /* ==================== MODAL Z-INDEX HIERARCHY ==================== */
      #prequelModal { z-index: 1100; }
      #watchDateModal { z-index: 1150; }
      #ratingModal { z-index: 1150; }
      #commentModal { z-index: 1150; }
      #deleteModal { z-index: 1300 !important; }
      .modal-content {
        background: var(--card);
        border-radius: 16px;
        max-width: 500px;
        width: 100%;
        max-height: 85vh;
        overflow-y: auto;
        border: 1px solid var(--border);
        animation: modalIn 0.25s ease;
      }
      @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
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
      .modal-header h3 {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
      }
      .modal-header h3 [data-lucide] {
        width: 22px;
        height: 22px;
      }
      .modal-body {
        padding: 1.25rem;
      }
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

      /* ==================== FORM ==================== */
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
      textarea.form-input {
        resize: vertical;
        min-height: 100px;
      }

      /* ==================== ALERT ==================== */
      .alert {
        padding: 0.75rem 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        display: none;
        font-size: 0.875rem;
      }
      .alert.show { display: block; }
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

      /* ==================== LOADING ==================== */
      .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid var(--border);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
      .loading-small {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
      }
      @keyframes spin {
        to { transform: rotate(360deg); }
      }
      .loading-center {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        gap: 0.75rem;
        color: var(--muted-foreground);
      }

      /* ==================== SKELETON ==================== */
      @keyframes skeletonPulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
      }
      .skeleton-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
      }
      .skeleton-card-header {
        padding: 0.9rem 1rem;
        display: flex;
        gap: 0.75rem;
        align-items: center;
      }
      .skeleton-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: var(--secondary);
        animation: skeletonPulse 1.6s ease-in-out infinite;
        flex-shrink: 0;
      }
      .skeleton-lines {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .skeleton-line {
        height: 12px;
        border-radius: 6px;
        background: var(--secondary);
        animation: skeletonPulse 1.6s ease-in-out infinite;
      }
      .skeleton-line-lg { width: 80%; }
      .skeleton-line-md { width: 60%; }
      .skeleton-line-sm { width: 40%; }
      .skeleton-card-body {
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .skeleton-card-actions {
        padding: 0.6rem 0.75rem;
        display: flex;
        gap: 0.35rem;
        justify-content: flex-end;
        border-top: 1px solid var(--border);
      }
      .skeleton-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: var(--secondary);
        animation: skeletonPulse 1.6s ease-in-out infinite;
      }

      /* ==================== RATING STARS ==================== */
      .star {
        font-size: 2rem;
        cursor: pointer;
        transition: all 0.2s ease;
        color: var(--border);
        user-select: none;
      }
      .star:hover { transform: scale(1.2); }
      .star.active { color: #f59e0b; }
      .star.hover { color: #fbbf24; }
      #starRating {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin: 1rem 0;
        direction: ltr;
      }
      #ratingValue {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
        min-height: 1.5rem;
        transition: all 0.2s;
        text-align: center;
      }

      /* ==================== DATE PICKER WHEELS ==================== */
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
      .footer-donate-btn [data-lucide] {
        width: 14px;
        height: 14px;
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
        position: relative;
      }
      .toast-warning { background: rgba(245, 158, 11, 0.95); }
      .toast-success { background: rgba(16, 185, 129, 0.95); }
      .toast-error { background: rgba(220, 38, 38, 0.95); }
      .toast-info { background: rgba(8, 145, 178, 0.95); }
      .toast .toast-close {
        cursor: pointer;
        opacity: 0.7;
        font-size: 1rem;
        margin-right: auto;
      }
      .toast .toast-close:hover { opacity: 1; }
      @keyframes toastIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
      }
      @keyframes toastOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-20px); }
      }

      /* ==================== RESPONSIVE ==================== */
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
        .nav-link [data-lucide] {
          width: 15px;
          height: 15px;
        }
        .nav-link span { display: none; }
        .page-title { font-size: 1.2rem; }
        .page-title [data-lucide] { width: 24px; height: 24px; }
        .search-input {
          padding: 0.65rem 0.9rem;
          font-size: 0.8rem;
        }
        .filters-section {
          padding: 1rem;
          margin-top: 1rem;
          border-radius: 12px;
        }
        .filters-grid {
          grid-template-columns: 1fr 1fr;
          gap: 0.75rem;
          margin-bottom: 0.75rem;
        }
        .filter-label { font-size: 0.7rem; gap: 0.35rem; }
        .filter-label [data-lucide] { width: 15px; height: 15px; }
        .filter-select {
          padding: 0.5rem 0.6rem;
          font-size: 0.75rem;
          border-radius: 8px;
        }
        .tabs-row {
          flex-wrap: nowrap;
          overflow-x: auto;
          overflow-y: hidden;
          gap: 0.35rem;
          padding-bottom: 0.25rem;
          -webkit-overflow-scrolling: touch;
          scrollbar-width: none;
        }
        .tabs-row::-webkit-scrollbar { display: none; }
        .filter-tag {
          padding: 0.35rem 0.65rem;
          font-size: 0.7rem;
          gap: 3px;
          flex-shrink: 0;
        }
        .titles-grid {
          grid-template-columns: 1fr;
          gap: 0.75rem;
        }
        .title-card-header {
          padding: 0.7rem 0.85rem 0.5rem;
          gap: 0.6rem;
        }
        .title-card-icon {
          width: 38px;
          height: 38px;
          border-radius: 10px;
        }
        .title-card-icon [data-lucide] { width: 20px; height: 20px; }
        .title-card-name { font-size: 0.875rem; }
        .title-card-subtitle { font-size: 0.65rem; gap: 0.3rem; }
        .list-badge { font-size: 0.6rem; padding: 0.1rem 0.4rem; }
        .title-card-body {
          padding: 0.6rem 0.85rem;
          gap: 0.4rem;
        }
        .title-card-meta { font-size: 0.7rem; gap: 0.4rem; }
        .genre-display-tag { font-size: 0.6rem; padding: 0.1rem 0.4rem; }
        .status-badge { font-size: 0.62rem; padding: 0.2rem 0.5rem; }
        .title-card-actions {
          padding: 0.5rem 0.6rem;
          gap: 0.3rem;
          justify-content: space-between;
        }
        .action-btn {
          padding: 0.4rem;
          min-width: 32px;
          min-height: 32px;
          border-radius: 8px;
        }
        .action-btn [data-lucide] { width: 16px; height: 16px; }
        .action-btn .rating-label,
        .action-btn .watch-date-label {
          font-size: 0.6rem;
          margin-right: 0.2rem;
        }
        .prequel-count-badge {
          top: -5px;
          right: -5px;
          min-width: 16px;
          height: 16px;
          font-size: 0.55rem;
        }
        .action-btn[data-tooltip]::after,
        .action-btn[data-tooltip]::before { display: none; }
        .info-banner {
          padding: 0.6rem 0.75rem;
          font-size: 0.7rem;
          margin-top: 0.75rem;
          margin-bottom: 0.75rem;
        }
        .info-banner [data-lucide] { width: 18px; height: 18px; }
        .info-banner span { font-size: 0.7rem; }
        .modal {
          padding: 0.5rem;
          align-items: flex-end;
        }
        .modal-content {
          border-radius: 20px 20px 0 0;
          max-height: 92vh;
        }
        .modal-header { padding: 1rem; }
        .modal-body { padding: 1rem; }
        .modal-footer {
          padding: 0.85rem 1rem;
          flex-wrap: wrap;
        }
        .modal-footer .btn {
          flex: 1;
          min-width: 100px;
          justify-content: center;
        }
        .modal-footer .btn-primary { order: -1; }
        #starRating { gap: 0.25rem !important; }
        #starRating .star { font-size: 1.5rem !important; }
        .comment-section {
          font-size: 0.7rem;
          padding: 0.55rem 0.65rem;
        }
        .prequel-tag {
          font-size: 0.65rem;
          padding: 0.3rem 0.5rem;
        }
        .footer-text { font-size: 0.7rem; }
      }

      @media (max-width: 400px) {
        .title-card-actions { gap: 0.25rem; }
        .action-btn {
          min-width: 30px;
          min-height: 30px;
          padding: 0.35rem;
        }
        .action-btn [data-lucide] { width: 15px; height: 15px; }
        .action-btn .rating-label,
        .action-btn .watch-date-label {
          display: none;
        }
      }

      /* Helper utilities */
      .flex { display: flex; }
      .items-center { align-items: center; }
      .justify-between { justify-content: space-between; }
      .gap-2 { gap: 0.5rem; }
      .mt-2 { margin-top: 0.5rem; }
      .mt-4 { margin-top: 1rem; }
      .mb-4 { margin-bottom: 1rem; }
      .w-full { width: 100%; }
      .text-center { text-align: center; }
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
            <button id="themeToggle" class="btn-icon" title="تغییر تم">
              <i data-lucide="moon"></i>
            </button>
            <a href="all_titles.php" class="nav-link active">
              <i data-lucide="list"></i>
              <span>همه عناوین</span>
            </a>
            <a href="dash.php" class="nav-link">
              <i data-lucide="layout-grid"></i>
              <span>داشبورد</span>
            </a>
            <a href="user.php" class="nav-link">
              <i data-lucide="user"></i>
              <span><?php echo htmlspecialchars($display_name); ?></span>
            </a>
            <a href="logout.php" class="nav-link nav-link-logout">
              <i data-lucide="log-out"></i>
              <span>خروج</span>
            </a>
          </div>
        </div>
      </div>
    </header>

    <main class="container">
      <div class="page-title-section">
        <h2 class="page-title">
          <i data-lucide="list"></i>
          <span>همه عناوین</span>
        </h2>
      </div>

      <div class="search-section">
        <div class="search-bar">
          <input type="text" id="globalSearch" class="search-input" placeholder="جستجو در بین عناوین، ژانرها و لیست‌ها...">
          <button id="clearSearchBtn" class="btn btn-secondary" style="display: none;">
            <i data-lucide="x"></i>
          </button>
        </div>
      </div>

      <div class="filters-section" id="filtersSection">
        <div class="filters-grid">
          <div class="filter-group">
            <label class="filter-label">
              <i data-lucide="bar-chart-2"></i>
              <span>دسته‌بندی</span>
            </label>
            <select id="filterType" class="filter-select">
              <option value="all">همه عناوین</option>
              <option value="movies">فیلم‌ها</option>
              <option value="series">سریال‌ها</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">
              <i data-lucide="circle-arrow-down"></i>
              <span>وضعیت دانلود</span>
            </label>
            <select id="filterDownload" class="filter-select">
              <option value="all">همه</option>
              <option value="downloaded">دانلود شده</option>
              <option value="not_downloaded">دانلود نشده</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">
              <i data-lucide="eye"></i>
              <span>وضعیت مشاهده</span>
            </label>
            <select id="filterWatched" class="filter-select">
              <option value="all">همه</option>
              <option value="watched">مشاهده شده</option>
              <option value="not_watched">مشاهده نشده</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">
              <i data-lucide="star"></i>
              <span>امتیاز</span>
            </label>
            <select id="filterRating" class="filter-select">
              <option value="all">همه</option>
              <option value="1">★ ۱</option>
              <option value="2">★ ۲</option>
              <option value="3">★ ۳</option>
              <option value="4">★ ۴</option>
              <option value="5">★ ۵</option>
              <option value="6">★ ۶</option>
              <option value="7">★ ۷</option>
              <option value="8">★ ۸</option>
              <option value="9">★ ۹</option>
              <option value="10">★ ۱۰</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">
              <i data-lucide="link-2"></i>
              <span>زیرمجموعه</span>
            </label>
            <select id="filterPrequels" class="filter-select">
              <option value="all">همه</option>
              <option value="has">دارای زیرمجموعه</option>
              <option value="none">بدون زیرمجموعه</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">
              <i data-lucide="tag"></i>
              <span>ژانر (۳۲ ژانر استاندارد)</span>
            </label>
            <select id="filterGenre" class="filter-select">
              <option value="all">همه ژانرها</option>
              <option value="action">اکشن / Action</option>
              <option value="adventure">ماجراجویی / Adventure</option>
              <option value="animation">انیمیشن / Animation</option>
              <option value="anime">انیمه / Anime</option>
              <option value="biography">زندگی‌نامه / Biography</option>
              <option value="comedy">کمدی / Comedy</option>
              <option value="sitcom">کمدی موقعیت / Sitcom</option>
              <option value="crime">جنایی / Crime</option>
              <option value="documentary">مستند / Documentary</option>
              <option value="drama">درام / Drama</option>
              <option value="family">خانوادگی / Family</option>
              <option value="fantasy">فانتزی / Fantasy</option>
              <option value="film-noir">فیلم نوآر / Film-Noir</option>
              <option value="history">تاریخی / History</option>
              <option value="horror">ترسناک / Horror</option>
              <option value="music">موسیقی / Music</option>
              <option value="musical">موزیکال / Musical</option>
              <option value="mystery">معمایی / Mystery</option>
              <option value="romance">عاشقانه / Romance</option>
              <option value="sci-fi">علمی-تخیلی / Sci-Fi</option>
              <option value="sport">ورزشی / Sport</option>
              <option value="thriller">هیجان‌انگیز / Thriller</option>
              <option value="war">جنگی / War</option>
              <option value="western">وسترن / Western</option>
              <option value="superhero">ابرقهرمانی / Superhero</option>
              <option value="psychological">روانشناختی / Psychological</option>
              <option value="spy">جاسوسی / Spy</option>
              <option value="supernatural">ماوراءالطبیعه / Supernatural</option>
              <option value="post-apocalyptic">پساآخرالزمانی / Post-Apocalyptic</option>
              <option value="cyberpunk">سایبرپانک / Cyberpunk</option>
              <option value="short">کوتاه / Short</option>
              <option value="gangster">مافیایی / Gangster</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">
              <i data-lucide="arrow-up-down"></i>
              <span>مرتب‌سازی</span>
            </label>
            <select id="sortBy" class="filter-select">
              <option value="newest">جدیدترین ثبت</option>
              <option value="oldest">قدیمی‌ترین ثبت</option>
              <option value="watch_newest">تاریخ مشاهده (جدید → قدیم)</option>
              <option value="watch_oldest">تاریخ مشاهده (قدیم → جدید)</option>
              <option value="rating_high">امتیاز (زیاد → کم)</option>
              <option value="rating_low">امتیاز (کم → زیاد)</option>
            </select>
          </div>
        </div>
        <div class="tabs-row">
          <span class="filter-tag active" data-type="all">
            <i data-lucide="layout-grid"></i>
            <span>همه</span>
          </span>
          <span class="filter-tag" data-type="movies">
            <i data-lucide="film"></i>
            <span>فیلم‌ها</span>
          </span>
          <span class="filter-tag" data-type="series">
            <i data-lucide="tv"></i>
            <span>سریال‌ها</span>
          </span>
          <span class="filter-tag" data-download="downloaded">
            <i data-lucide="download"></i>
            <span>دانلود شده</span>
          </span>
          <span class="filter-tag" data-download="not_downloaded">
            <i data-lucide="circle-arrow-down"></i>
            <span>دانلود نشده</span>
          </span>
          <span class="filter-tag" data-watched="watched">
            <i data-lucide="eye"></i>
            <span>دیده شده</span>
          </span>
          <span class="filter-tag" data-watched="not_watched">
            <i data-lucide="eye-off"></i>
            <span>دیده نشده</span>
          </span>
        </div>
      </div>

      <div class="info-banner">
        <i data-lucide="info"></i>
        <span>
          برای ویرایش عنوان، فصل‌ها و افزودن پریکوئل به
          <a href="dash.php">داشبورد</a>
          مراجعه کنید. امتیاز، کامنت و تاریخ تماشا را می‌توانید از همین‌جا ثبت کنید.
        </span>
      </div>

      <div class="results-header">
        <span class="results-count" id="resultsCount">در حال بارگذاری...</span>
      </div>

      <div id="titlesContainer">
        <div id="titlesSkeleton" class="titles-grid">
          <div class="skeleton-card">
            <div class="skeleton-card-header">
              <div class="skeleton-icon"></div>
              <div class="skeleton-lines">
                <div class="skeleton-line skeleton-line-lg"></div>
                <div class="skeleton-line skeleton-line-sm"></div>
              </div>
            </div>
            <div class="skeleton-card-body">
              <div class="skeleton-line skeleton-line-md"></div>
              <div class="skeleton-line skeleton-line-sm"></div>
            </div>
            <div class="skeleton-card-actions">
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
            </div>
          </div>
          <div class="skeleton-card">
            <div class="skeleton-card-header">
              <div class="skeleton-icon"></div>
              <div class="skeleton-lines">
                <div class="skeleton-line skeleton-line-lg"></div>
                <div class="skeleton-line skeleton-line-sm"></div>
              </div>
            </div>
            <div class="skeleton-card-body">
              <div class="skeleton-line skeleton-line-md"></div>
              <div class="skeleton-line skeleton-line-sm"></div>
            </div>
            <div class="skeleton-card-actions">
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
            </div>
          </div>
          <div class="skeleton-card">
            <div class="skeleton-card-header">
              <div class="skeleton-icon"></div>
              <div class="skeleton-lines">
                <div class="skeleton-line skeleton-line-lg"></div>
                <div class="skeleton-line skeleton-line-sm"></div>
              </div>
            </div>
            <div class="skeleton-card-body">
              <div class="skeleton-line skeleton-line-md"></div>
              <div class="skeleton-line skeleton-line-sm"></div>
            </div>
            <div class="skeleton-card-actions">
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
              <div class="skeleton-btn"></div>
            </div>
          </div>
        </div>

        <div id="titlesContent" class="titles-grid" style="display: none;"></div>
      </div>
    </main>

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

    <!-- ==================== MODAL: RATING ==================== -->
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
            <div id="starRating">
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
            <div id="ratingValue"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button id="removeRatingBtn" class="btn btn-secondary" style="color: var(--destructive);">حذف امتیاز</button>
          <button id="confirmRatingBtn" class="btn btn-primary">ثبت امتیاز</button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL: WATCH DATE ==================== -->
    <div id="watchDateModal" class="modal">
      <div class="modal-content" style="max-width: 420px;">
        <div class="modal-header">
          <h3>
            <i data-lucide="check-circle"></i>
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
            <div style="color: var(--foreground); margin-bottom: 1rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 10px;">
              <i data-lucide="calendar"></i>
              <span>تاریخ تماشا را انتخاب کنید</span>
            </div>
            <div class="picker-wheels" style="display: flex; gap: 0.5rem; justify-content: center;">
              <div class="wheel-container" style="width: 80px; height: 180px;">
                <div class="wheel-label">روز</div>
                <div class="selection-indicator" style="top: 50%; left: 10%; right: 10%; height: 36px; transform: translateY(-50%); border-top: 1px solid var(--primary); border-bottom: 1px solid var(--primary);"></div>
                <div class="wheel" id="dayWheel"></div>
              </div>
              <div class="wheel-container" style="width: 100px; height: 180px;">
                <div class="wheel-label">ماه</div>
                <div class="selection-indicator" style="top: 50%; left: 10%; right: 10%; height: 36px; transform: translateY(-50%); border-top: 1px solid var(--primary); border-bottom: 1px solid var(--primary);"></div>
                <div class="wheel" id="monthWheel"></div>
              </div>
              <div class="wheel-container" style="width: 90px; height: 180px;">
                <div class="wheel-label">سال</div>
                <div class="selection-indicator" style="top: 50%; left: 10%; right: 10%; height: 36px; transform: translateY(-50%); border-top: 1px solid var(--primary); border-bottom: 1px solid var(--primary);"></div>
                <div class="wheel" id="yearWheel"></div>
              </div>
            </div>
            <div id="watchDateDisplay" style="margin: 1rem 0; font-size: 1.1rem; font-weight: 700; color: var(--primary); min-height: 1.5rem;"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button id="watchWithoutDateBtn" class="btn btn-secondary">بدون تاریخ</button>
          <button id="watchTodayBtn" class="btn btn-secondary">امروز</button>
          <button id="confirmWatchBtn" class="btn btn-primary">ثبت</button>
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
            <textarea id="commentText" class="form-input" rows="6" placeholder="نظر خود را بنویسید..." maxlength="1000"></textarea>
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

    <!-- ==================== MODAL: PREQUEL (View Only) ==================== -->
    <div id="prequelModal" class="modal">
      <div class="modal-content" style="max-width: 560px;">
        <div class="modal-header">
          <h3 id="prequelModalTitle">
            <i data-lucide="link-2"></i>
            <span>عناوین مرتبط</span>
          </h3>
          <button class="btn-icon close-prequel-modal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="prequelAlert" class="alert"></div>
          <input type="hidden" id="prequelMediaType">
          <input type="hidden" id="prequelMediaId">
          <div id="prequelList"></div>
          <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: var(--accent); border-radius: 12px; border-right: 3px solid var(--primary); display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="info" style="color: var(--primary); flex-shrink: 0;"></i>
            <span style="font-size: 0.75rem;">برای افزودن یا ویرایش عناوین مرتبط به <a href="dash.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">داشبورد</a> مراجعه کنید.</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary close-prequel-modal">
            <i data-lucide="x"></i>
            <span>بستن</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== DELETE MODAL ==================== -->
    <div id="deleteModal" class="modal">
      <div class="modal-content" style="max-width: 420px;">
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

    <div class="toast-container" id="toastContainer"></div>

    <script>
/* ============================================================
   SECTION 1: STATE, THEME & TOAST
   ============================================================ */
let allTitles = [];
let allPrequelsData = { movies: {}, series: {} };
let allCommentsData = { movies: {}, seasons: {} };
let deleteCallback = null;
let isSearchMode = false;
let searchTerm = '';

// ===== LUCIDE REFRESH HELPER =====
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
   TOAST NOTIFICATIONS
   ============================================================ */
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
   GLOBAL MODAL MANAGER (STACKING, ESC, OUTSIDE CLICK, AUTO FOCUS)
   ============================================================ */
let modalOpenSequence = 0;

function autoFocusModalInput(modal) {
    if (!modal) return;
    setTimeout(() => {
        if (!modal.classList.contains('show')) return;
        let targetInput = null;
        switch (modal.id) {
            case 'commentModal':
                targetInput = modal.querySelector('#commentText');
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
            case 'prequelModal':
                targetInput = modal.querySelector('.close-prequel-modal');
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
        '.close-prequel-modal, .close-comment-modal, .close-rating-modal, ' +
        '.close-watchdate-modal, .close-delete-modal, [data-dismiss="modal"], ' +
        '.modal-header .btn-icon:first-of-type, .btn-secondary'
    );
    if (closeBtn && typeof closeBtn.click === 'function') {
        closeBtn.click();
    } else {
        modal.classList.remove('show');
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


/* ============================================================
   SECTION 2: HELPERS
   ============================================================ */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>]/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;'
    } [m]));
}

function formatDate(dateNum) {
    if (!dateNum || dateNum === 0) return '';

    if (typeof dateNum === 'string' && dateNum.includes('-')) {
        const date = new Date(dateNum);
        if (isNaN(date.getTime())) return '';
        return `${date.getFullYear()}/${(date.getMonth()+1).toString().padStart(2,'0')}/${date.getDate().toString().padStart(2,'0')}`;
    }

    const num = parseInt(dateNum);
    if (!isNaN(num) && num > 10000000) {
        const year = Math.floor(num / 10000);
        const month = Math.floor((num % 10000) / 100);
        const day = num % 100;
        return `${year}/${month.toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;
    }

    return '';
}

function highlightText(text, term) {
    if (!term || term.length < 2) return escapeHtml(text);
    const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return escapeHtml(text).replace(regex, '<span class="search-highlight">$1</span>');
}

async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (let key in data) formData.append(key, data[key]);
    try {
        const response = await fetch('api/api.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    } catch (error) {
        return {
            ok: false,
            message: error.message
        };
    }
}


/* ============================================================
   SECTION 3: SKELETON & DELETE MODAL
   ============================================================ */
function showSkeleton() {
    const skeleton = document.getElementById('titlesSkeleton');
    const content = document.getElementById('titlesContent');
    if (skeleton) skeleton.style.display = 'grid';
    if (content) content.style.display = 'none';
}

function hideSkeleton() {
    const skeleton = document.getElementById('titlesSkeleton');
    const content = document.getElementById('titlesContent');
    if (skeleton) skeleton.style.display = 'none';
    if (content) {
        content.style.display = 'grid';
        content.style.opacity = '0';
        content.style.transform = 'translateY(10px)';
        content.style.transition = 'opacity 0.35s ease-out, transform 0.35s ease-out';

        requestAnimationFrame(() => {
            content.style.opacity = '1';
            content.style.transform = 'translateY(0)';
        });
    }
}

function confirmDelete(message, callback) {
    document.getElementById('deleteMessage').textContent = message;
    deleteCallback = callback;
    document.getElementById('deleteModal').classList.add('show');
    refreshIcons();
}

document.querySelectorAll('.close-delete-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deleteModal').classList.remove('show');
        deleteCallback = null;
    });
});

document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
    if (deleteCallback) deleteCallback();
    document.getElementById('deleteModal').classList.remove('show');
    deleteCallback = null;
});


/* ============================================================
   SECTION 4: LOAD ALL TITLES
   ============================================================ */
async function loadAllTitles() {
    showSkeleton();
    const startTime = Date.now();
    const minDisplayTime = 1200;

    const listsResult = await apiCall('get_lists');
    if (!listsResult.ok || !listsResult.data) {
        hideSkeleton();
        document.getElementById('titlesContent').innerHTML =
            '<div class="empty-state"><i data-lucide="alert-triangle"></i><p>خطا در بارگذاری</p></div>';
        refreshIcons();
        return;
    }

    const titles = [];
    const prequelCache = { movies: {}, series: {} };
    const commentCache = { movies: {}, seasons: {} };

    for (const list of listsResult.data) {
        const detailResult = await apiCall('get_full_list', { list_id: list.id });
        if (detailResult.ok && detailResult.data) {
            // === فیلم‌ها ===
            detailResult.data.movies.forEach(m => {
                titles.push({
                    ...m,
                    type: 'movie',
                    list_id: list.id,
                    list_name: list.name,
                    list_color: list.color
                });
            });
            // === سریال‌ها (با محاسبه تعداد فصل/قسمت) ===
            detailResult.data.series.forEach(s => {
                const seasons = s.seasons || [];
                const totalSeasons = seasons.length;
                const totalEpisodes = seasons.reduce((sum, sea) => sum + (parseInt(sea.episode_count) || 0), 0);

                titles.push({
                    ...s,
                    type: 'series',
                    list_id: list.id,
                    list_name: list.name,
                    list_color: list.color,
                    total_seasons: totalSeasons,
                    total_episodes: totalEpisodes
                });
            });
        }

        const preqResult = await apiCall('get_all_prequels_for_list', { list_id: list.id });
        if (preqResult.ok && preqResult.data) {
            preqResult.data.movies.forEach(p => {
                if (!prequelCache.movies[p.media_id]) prequelCache.movies[p.media_id] = [];
                prequelCache.movies[p.media_id].push(p);
            });
            preqResult.data.series.forEach(p => {
                if (!prequelCache.series[p.media_id]) prequelCache.series[p.media_id] = [];
                prequelCache.series[p.media_id].push(p);
            });
        }

        const commentsResult = await apiCall('get_all_comments_for_list', { list_id: list.id });
        if (commentsResult.ok && commentsResult.data) {
            commentsResult.data.movies.forEach(c => {
                commentCache.movies[c.media_id] = c;
            });
            commentsResult.data.seasons.forEach(c => {
                commentCache.seasons[c.media_id] = c;
            });
        }
    }

    allTitles = titles;
    allPrequelsData = prequelCache;
    allCommentsData = commentCache;

    const elapsed = Date.now() - startTime;
    const remaining = Math.max(minDisplayTime - elapsed, 0);
    if (remaining > 0) {
        await new Promise(resolve => setTimeout(resolve, remaining));
    }

    hideSkeleton();
    applyFilters();
}


/* ============================================================
   SECTION 5: RENDER TITLE CARDS
   ============================================================ */
function renderTitleCard(t, searchMode = false) {
    const isMovie = t.type === 'movie';
    const typeIconHtml = isMovie
        ? '<i data-lucide="film" class="icon"></i>'
        : '<i data-lucide="tv" class="icon"></i>';

    const isDownloaded = t.downloaded == 1;
    const isWatched = t.watched == 1;
    const hasWatchDate = t.watch_date && t.watch_date != 0 && t.watch_date !== '1970-01-01';
    const watchDateFormatted = isWatched && hasWatchDate ? formatDate(t.watch_date) : '';
    const hasRating = t.rating !== null && t.rating !== undefined;
    const comment = isMovie
        ? (allCommentsData.movies[t.id] || null)
        : (allCommentsData.seasons[t.id] || null);
    const hasComment = comment !== null;
    const prequels = isMovie
        ? (allPrequelsData.movies[t.id] || [])
        : (allPrequelsData.series[t.id] || []);
    const hasPrequels = prequels.length > 0;
    const hasCollection = hasPrequels && prequels.some(p => p.relation_type === 'collection');

    const sortOrder = { 'prequel': 1, 'collection': 2, 'sequel': 3 };
    const sortedPrequels = [...prequels].sort((a, b) => {
        if (sortOrder[a.relation_type] !== sortOrder[b.relation_type]) {
            return sortOrder[a.relation_type] - sortOrder[b.relation_type];
        }
        return (a.sort_order || 0) - (b.sort_order || 0);
    });

    const titleHtml = searchMode ? highlightText(t.title, searchTerm) : escapeHtml(t.title);
    const listNameHtml = searchMode ? highlightText(t.list_name, searchTerm) : escapeHtml(t.list_name);

    const genreTags = t.genre ? t.genre.split(',').map(g => g.trim()).filter(g => g) : [];
    const genreDisplay = genreTags.length > 0
        ? genreTags.map(g => `<span class="genre-display-tag">${searchMode ? highlightText(g, searchTerm) : escapeHtml(g)}</span>`).join('')
        : '<span style="font-size:0.65rem; color: var(--muted-foreground);">بدون ژانر</span>';

    const yearInfo = isMovie
        ? (t.release_year || 'سال نامشخص')
        : `${t.start_year || '?'} - ${t.end_year || 'در حال پخش'}`;

    // === نمایش تعداد فصل و قسمت برای سریال ===
    let seriesStatsHtml = '';
    if (!isMovie && t.total_seasons !== undefined) {
        seriesStatsHtml = `
            <div class="series-stats-inline" style="margin-top: 0.3rem;">
                <span class="series-stat-item">
                    <i data-lucide="layers-2"></i>
                    <span>${t.total_seasons} فصل</span>
                </span>
                <span class="series-stat-item">
                    <i data-lucide="film"></i>
                    <span>${t.total_episodes} قسمت</span>
                </span>
            </div>
        `;
    }

    const commentHtml = comment
        ? `
        <div class="comment-section">
            <div class="comment-header">
                <i data-lucide="message-circle"></i>
                <span class="comment-author">${escapeHtml(comment.display_name || 'کاربر')}</span>
                <span class="comment-date">${formatDate(comment.created_at)}</span>
            </div>
            <div class="comment-text">${searchMode ? highlightText(comment.comment, searchTerm) : escapeHtml(comment.comment)}</div>
        </div>`
        : '';

    const prequelsHtml = hasPrequels
        ? `
        <div class="prequels-list">
            ${sortedPrequels.map(p => {
                const typeIcon = p.relation_type === 'prequel' ? 'corner-down-left'
                    : p.relation_type === 'sequel' ? 'corner-down-right'
                    : 'layers-2';
                const typeLabel = p.relation_type === 'prequel' ? 'پیش‌درآمد'
                    : p.relation_type === 'sequel' ? 'دنباله' : 'مجموعه';
                const orderText = p.sort_order ? `<span class="prequel-order">#${p.sort_order}</span>` : '';
                const yearText = p.release_year ? ` (${p.release_year})` : '';
                const relatedIcon = p.related_type === 'series' ? '<i data-lucide="tv"></i>' : '';
                const titleContent = searchMode ? highlightText(p.title, searchTerm) : escapeHtml(p.title);

                return `<span class="prequel-tag ${p.relation_type}"><i data-lucide="${typeIcon}"></i>${typeLabel}${orderText}: <span>${titleContent}</span>${yearText}${relatedIcon}</span>`;
            }).join('')}
        </div>`
        : '';

    const downloadBtn = `
        <button class="action-btn ${isDownloaded ? 'downloaded' : ''} disable-action" 
                data-tooltip="برای تغییر وضعیت دانلود به داشبورد بروید">
            <i data-lucide="${isDownloaded ? 'download' : 'circle-arrow-down'}"></i>
        </button>
    `;

    // === آیکن eye / eye-off ===
    const watchIcon = isWatched ? 'eye' : 'eye-off';
    const watchBtn = `
        <button class="action-btn ${isWatched ? 'watched' : 'not-watched'} watch-action" 
                data-tooltip="${isWatched ? 'برای لغو تماشا به داشبورد بروید' : 'ثبت تاریخ تماشا'}"
                data-media-type="${t.type}" 
                data-media-id="${t.id}"
                data-watched="${t.watched}">
            <i data-lucide="${watchIcon}"></i>
            ${isWatched && hasWatchDate ? `<span class="watch-date-label">${watchDateFormatted}</span>` : ''}
        </button>
    `;

    const ratingBtn = `
        <button class="action-btn ${hasRating ? 'has-rating' : ''} rating-action" 
                data-tooltip="${hasRating ? 'ویرایش امتیاز' : 'ثبت امتیاز'}"
                data-media-type="${t.type}" 
                data-media-id="${t.id}"
                data-rating="${t.rating || 0}">
            <i data-lucide="star"></i>
            ${hasRating ? `<span class="rating-label">${t.rating}</span>` : ''}
        </button>
    `;

    const commentBtn = `
        <button class="action-btn ${hasComment ? 'has-comment' : ''} comment-action" 
                data-tooltip="${hasComment ? 'ویرایش کامنت' : 'افزودن کامنت'}"
                data-media-type="${t.type}" 
                data-media-id="${t.id}"
                data-comment-id="${hasComment ? comment.id : ''}">
            <i data-lucide="${hasComment ? 'message-circle' : 'message-square'}"></i>
        </button>
    `;

    // === prequel با بج گوشه بالا-راست ===
    const prequelBtn = `
        <button class="action-btn ${hasCollection ? 'has-collection' : (hasPrequels ? 'has-prequels' : '')} prequel-action" 
                data-tooltip="${hasPrequels ? `${prequels.length} عنوان مرتبط` : 'بدون عنوان مرتبط'}"
                data-media-type="${t.type}" 
                data-media-id="${t.id}">
            <i data-lucide="link-2"></i>
            ${hasPrequels ? `<span class="prequel-count-badge">${prequels.length}</span>` : ''}
        </button>
    `;

    // === دکمه ویرایش: هدایت به dash.php با پارامتر باز کردن لیست و هایلایت ===
    const editBtn = `
        <a href="dash.php?open_list=${t.list_id}&highlight_type=${t.type}&highlight_id=${t.id}" 
           class="action-btn dashboard-link" 
           data-tooltip="باز کردن در داشبورد و ویرایش">
            <i data-lucide="pencil"></i>
        </a>
    `;

    return `
    <div class="title-card" data-title-id="${t.id}" data-title-type="${t.type}">
        <div class="title-card-header">
            <div class="title-card-icon ${isMovie ? 'movie' : 'series'}">
                ${typeIconHtml}
            </div>
            <div class="title-card-info">
                <div class="title-card-name">${titleHtml}</div>
                <div class="title-card-subtitle">
                    <span class="list-badge" style="border-bottom: 2px solid ${t.list_color};">
                        <i data-lucide="layers"></i>
                        ${listNameHtml}
                    </span>
                    <span>•</span>
                    <span>${yearInfo}</span>
                </div>
                ${seriesStatsHtml}
            </div>
        </div>
        <div class="title-card-body">
            <div class="title-card-meta">
                <span class="meta-item">
                    <i data-lucide="tag"></i>
                    <span>ژانر:</span>
                </span>
                <span class="genre-display">${genreDisplay}</span>
            </div>
            ${commentHtml}
            ${prequelsHtml}
        </div>
        <div class="title-card-actions">
            ${downloadBtn}
            ${watchBtn}
            ${ratingBtn}
            ${commentBtn}
            ${prequelBtn}
            ${editBtn}
        </div>
    </div>
    `;
}

function renderTitles(titles, searchMode = false) {
    const content = document.getElementById('titlesContent');
    if (!content) return;

    if (!titles.length) {
        const term = searchMode ? `"${escapeHtml(searchTerm)}"` : '';
        content.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i data-lucide="search-x"></i>
                <p>${searchMode ? `هیچ نتیجه‌ای برای ${term} یافت نشد` : 'هیچ عنوانی یافت نشد'}</p>
            </div>
        `;
        refreshIcons();
        return;
    }

    content.innerHTML = titles.map(t => renderTitleCard(t, searchMode)).join('');
    refreshIcons();
    attachCardEvents();
}

function attachCardEvents() {
    document.querySelectorAll('.watch-action').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const mediaType = btn.dataset.mediaType;
            const mediaId = btn.dataset.mediaId;
            const watched = parseInt(btn.dataset.watched);

            if (watched == 1) {
                showToast('برای لغو تماشا به داشبورد بروید', 'warning');
                return;
            }

            document.getElementById('watchTargetType').value = mediaType;
            document.getElementById('watchTargetId').value = mediaId;
            document.getElementById('watchAction').value = 'mark_watched';
            initDatePicker();
            document.getElementById('watchDateAlert').style.display = 'none';
            document.getElementById('watchDateModal').classList.add('show');
            refreshIcons();
        });
    });

    document.querySelectorAll('.rating-action').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const mediaType = btn.dataset.mediaType;
            const mediaId = btn.dataset.mediaId;
            const currentRating = parseInt(btn.dataset.rating) || 0;
            openRatingModal(mediaType, mediaId, currentRating);
        });
    });

    document.querySelectorAll('.comment-action').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const mediaType = btn.dataset.mediaType;
            const mediaId = btn.dataset.mediaId;
            const commentId = btn.dataset.commentId || '';
            openCommentModal(mediaType, mediaId, commentId);
        });
    });

    document.querySelectorAll('.prequel-action').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const mediaType = btn.dataset.mediaType;
            const mediaId = btn.dataset.mediaId;
            openPrequelModal(mediaType, mediaId);
        });
    });

    document.querySelectorAll('.disable-action').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            showToast('برای تغییر وضعیت دانلود به داشبورد بروید', 'info');
        });
    });
}


/* ============================================================
   SECTION 6: FILTERS & SEARCH
   ============================================================ */

const ALL_31_GENRES_KEYWORDS = {
    'action': ['اکشن', 'action'],
    'adventure': ['ماجراجویی', 'adventure'],
    'animation': ['انیمیشن', 'animation', 'پویانمایی', 'کارتون'],
    'anime': ['انیمه', 'anime', 'انیمه ژاپنی', 'مانگا', 'اوتاکو', 'manga'],
    'biography': ['زندگی‌نامه', 'زندگینامه', 'بیوگرافی', 'biography'],
    'comedy': ['کمدی', 'comedy', 'طنز'],
    'sitcom': ['کمدی موقعیت', 'سیتکام', 'sitcom', 'سیت کام', 'situation comedy'],
    'crime': ['جنایی', 'crime', 'پلیسی'],
    'documentary': ['مستند', 'documentary'],
    'drama': ['درام', 'drama'],
    'family': ['خانوادگی', 'family'],
    'fantasy': ['فانتزی', 'fantasy'],
    'film-noir': ['فیلم نوآر', 'نوآر', 'noir', 'film-noir'],
    'history': ['تاریخی', 'history'],
    'horror': ['ترسناک', 'horror', 'وحشت'],
    'music': ['موسیقی', 'music', 'موزیک'],
    'musical': ['موزیکال', 'musical'],
    'mystery': ['معمایی', 'mystery', 'رازآلود'],
    'romance': ['عاشقانه', 'romance', 'رمانتیک'],
    'sci-fi': ['علمی-تخیلی', 'علمی تخیلی', 'sci-fi', 'scifi', 'فضایی'],
    'sport': ['ورزشی', 'sport'],
    'thriller': ['هیجان‌انگیز', 'thriller', 'تریلر'],
    'war': ['جنگی', 'war'],
    'western': ['وسترن', 'western'],
    'superhero': ['ابرقهرمانی', 'superhero', 'کمیک'],
    'psychological': ['روانشناختی', 'psychological'],
    'spy': ['جاسوسی', 'spy'],
    'supernatural': ['ماوراءالطبیعه', 'supernatural'],
    'post-apocalyptic': ['پساآخرالزمانی', 'post-apocalyptic', 'دیستوپیا'],
    'cyberpunk': ['سایبرپانک', 'cyberpunk'],
    'short': ['کوتاه', 'short'],
    'gangster': ['مافیایی', 'gangster', 'مافیا']
};

function matchesGenreSlug(genreStr, slug) {
    if (!genreStr || !slug || slug === 'all') return true;
    const cleanStr = genreStr.toString().toLowerCase().replace(/[\-\_\/\\]/g, ' ');
    const keywords = ALL_31_GENRES_KEYWORDS[slug] || [slug];
    return keywords.some(kw => cleanStr.includes(kw.toLowerCase().replace(/[\-\_\/\\]/g, ' ')));
}

function applyFilters() {
    let filtered = [...allTitles];

    const typeFilter = document.getElementById('filterType').value;
    const downloadFilter = document.getElementById('filterDownload').value;
    const watchedFilter = document.getElementById('filterWatched').value;
    const ratingFilter = document.getElementById('filterRating').value;
    const prequelFilter = document.getElementById('filterPrequels').value;
    const genreFilter = document.getElementById('filterGenre') ? document.getElementById('filterGenre').value : 'all';
    const sortBy = document.getElementById('sortBy').value;

    if (typeFilter === 'movies') filtered = filtered.filter(t => t.type === 'movie');
    if (typeFilter === 'series') filtered = filtered.filter(t => t.type === 'series');
    if (downloadFilter === 'downloaded') filtered = filtered.filter(t => t.downloaded == 1);
    if (downloadFilter === 'not_downloaded') filtered = filtered.filter(t => t.downloaded != 1);
    if (watchedFilter === 'watched') filtered = filtered.filter(t => t.watched == 1);
    if (watchedFilter === 'not_watched') filtered = filtered.filter(t => t.watched != 1);
    if (ratingFilter !== 'all') filtered = filtered.filter(t => t.rating == parseInt(ratingFilter));

    if (prequelFilter === 'has') {
        filtered = filtered.filter(t => {
            const preqs = t.type === 'movie'
                ? (allPrequelsData.movies[t.id] || [])
                : (allPrequelsData.series[t.id] || []);
            return preqs.length > 0;
        });
    }
    if (prequelFilter === 'none') {
        filtered = filtered.filter(t => {
            const preqs = t.type === 'movie'
                ? (allPrequelsData.movies[t.id] || [])
                : (allPrequelsData.series[t.id] || []);
            return preqs.length === 0;
        });
    }

    if (genreFilter !== 'all') {
        filtered = filtered.filter(t => matchesGenreSlug(t.genre, genreFilter));
    }

    switch (sortBy) {
        case 'oldest':
            filtered.sort((a, b) => a.id - b.id);
            break;
        case 'watch_newest':
            filtered.sort((a, b) => {
                if (!a.watch_date) return 1;
                if (!b.watch_date) return -1;
                return new Date(b.watch_date) - new Date(a.watch_date);
            });
            break;
        case 'watch_oldest':
            filtered.sort((a, b) => {
                if (!a.watch_date) return 1;
                if (!b.watch_date) return -1;
                return new Date(a.watch_date) - new Date(b.watch_date);
            });
            break;
        case 'rating_high':
            filtered.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            break;
        case 'rating_low':
            filtered.sort((a, b) => (a.rating || 0) - (b.rating || 0));
            break;
        default:
            filtered.sort((a, b) => b.id - a.id);
    }

    document.getElementById('resultsCount').textContent = `${filtered.length} عنوان`;
    renderTitles(filtered, false);
}

function performSearch(term) {
    searchTerm = term.trim();

    if (!searchTerm || searchTerm.length < 2) {
        isSearchMode = false;
        document.getElementById('clearSearchBtn').style.display = 'none';
        document.getElementById('resultsCount').style.display = 'block';

        const filtersSection = document.getElementById('filtersSection');
        if (filtersSection) filtersSection.classList.remove('collapsed');

        applyFilters();
        return;
    }

    isSearchMode = true;
    document.getElementById('clearSearchBtn').style.display = 'inline-flex';
    document.getElementById('resultsCount').style.display = 'block';

    const filtersSection = document.getElementById('filtersSection');
    if (filtersSection && window.innerWidth <= 768) {
        filtersSection.classList.add('collapsed');
    }

    const termLower = searchTerm.toLowerCase();

    const filtered = allTitles.filter(t =>
        t.title.toLowerCase().includes(termLower) ||
        (t.genre && t.genre.toLowerCase().includes(termLower)) ||
        (t.list_name && t.list_name.toLowerCase().includes(termLower))
    );

    document.getElementById('resultsCount').innerHTML =
        `${filtered.length} نتیجه برای "${escapeHtml(searchTerm)}"`;

    renderTitles(filtered, true);
}

document.getElementById('filterType').addEventListener('change', applyFilters);
document.getElementById('filterGenre')?.addEventListener('change', applyFilters);
document.getElementById('filterDownload').addEventListener('change', applyFilters);
document.getElementById('filterWatched').addEventListener('change', applyFilters);
document.getElementById('filterRating').addEventListener('change', applyFilters);
document.getElementById('filterPrequels').addEventListener('change', applyFilters);
document.getElementById('sortBy').addEventListener('change', applyFilters);

document.querySelectorAll('.filter-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        document.getElementById('filterType').value = 'all';
        document.getElementById('filterDownload').value = 'all';
        document.getElementById('filterWatched').value = 'all';
        if (document.getElementById('filterGenre')) document.getElementById('filterGenre').value = 'all';

        const dataType = this.dataset.type;
        const dataDownload = this.dataset.download;
        const dataWatched = this.dataset.watched;

        if (dataType) document.getElementById('filterType').value = dataType;
        if (dataDownload) document.getElementById('filterDownload').value = dataDownload;
        if (dataWatched) document.getElementById('filterWatched').value = dataWatched;

        applyFilters();
    });
});

document.getElementById('globalSearch').addEventListener('input', function(e) {
    performSearch(e.target.value);
});

document.getElementById('clearSearchBtn').addEventListener('click', function() {
    document.getElementById('globalSearch').value = '';
    performSearch('');
    document.getElementById('globalSearch').focus();
});

window.addEventListener('resize', function() {
    const filtersSection = document.getElementById('filtersSection');
    if (!filtersSection) return;

    if (window.innerWidth > 768) {
        filtersSection.classList.remove('collapsed');
    } else if (isSearchMode && searchTerm && searchTerm.length >= 2) {
        filtersSection.classList.add('collapsed');
    }
});


/* ============================================================
   SECTION 7: RATING MODAL
   ============================================================ */
let selectedRating = 0;

function openRatingModal(mediaType, mediaId, currentRating) {
    if (mediaType === 'series') {
        showToast('برای امتیازدهی به سریال، به داشبورد بروید (امتیاز به فصل‌ها اعمال می‌شود)', 'warning');
        return;
    }

    document.getElementById('ratingTargetType').value = mediaType;
    document.getElementById('ratingTargetId').value = mediaId;
    document.getElementById('ratingAction').value = 'rate_movie';

    selectedRating = currentRating || 0;
    updateStars(selectedRating);

    document.getElementById('ratingAlert').style.display = 'none';
    document.getElementById('ratingAlert').classList.remove('show');
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

function showModalAlert(elementId, message, type) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.textContent = message;
    el.className = `alert alert-${type} show`;
    setTimeout(() => {
        el.classList.remove('show');
    }, 4000);
}

document.getElementById('confirmRatingBtn').addEventListener('click', async () => {
    if (selectedRating === 0) {
        showModalAlert('ratingAlert', 'لطفاً امتیاز را انتخاب کنید', 'error');
        return;
    }

    const mediaType = document.getElementById('ratingTargetType').value;
    const id = document.getElementById('ratingTargetId').value;

    const result = await apiCall('rate_movie', {
        movie_id: id,
        rating: selectedRating
    });

    if (result.ok) {
        document.getElementById('ratingModal').classList.remove('show');
        showToast('امتیاز با موفقیت ثبت شد', 'success');
        await refreshTitlesData();
    } else {
        showModalAlert('ratingAlert', result.message || 'خطا در ثبت امتیاز', 'error');
    }
});

document.getElementById('removeRatingBtn').addEventListener('click', async () => {
    const mediaType = document.getElementById('ratingTargetType').value;
    const id = document.getElementById('ratingTargetId').value;

    const result = await apiCall('rate_movie', {
        movie_id: id,
        rating: null
    });

    if (result.ok) {
        document.getElementById('ratingModal').classList.remove('show');
        showToast('امتیاز حذف شد', 'success');
        await refreshTitlesData();
    } else {
        showModalAlert('ratingAlert', result.message || 'خطا در حذف امتیاز', 'error');
    }
});

document.querySelectorAll('.close-rating-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('ratingModal').classList.remove('show');
    });
});


/* ============================================================
   SECTION 8: WATCH DATE MODAL + DATE PICKER
   ============================================================ */
async function markAsWatched(mediaType, id, watched, watchDate) {
    if (mediaType === 'series') {
        showToast('برای ثبت تاریخ تماشا سریال، به داشبورد بروید', 'warning');
        return;
    }

    const result = await apiCall('mark_watched_movie', {
        movie_id: id,
        watched: watched,
        watch_date: watched ? (watchDate || 0) : 0
    });

    if (result.ok) {
        await refreshTitlesData();
    } else {
        showToast(result.message || 'خطا در ثبت', 'error');
    }
}

document.getElementById('confirmWatchBtn').addEventListener('click', async () => {
    const type = document.getElementById('watchTargetType').value;
    const id = document.getElementById('watchTargetId').value;
    const date = getWatchDateNumber();

    if (!date) {
        showModalAlert('watchDateAlert', 'لطفاً تاریخ را انتخاب کنید', 'error');
        return;
    }

    await markAsWatched(type, id, 1, date);
    document.getElementById('watchDateModal').classList.remove('show');
});

document.getElementById('watchTodayBtn').addEventListener('click', async () => {
    const type = document.getElementById('watchTargetType').value;
    const id = document.getElementById('watchTargetId').value;

    const btn = document.getElementById('watchTodayBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="loading-small"></div>';
    btn.disabled = true;

    try {
        const result = await apiCall('get_persian_date');
        if (result.ok && result.data && result.data.date) {
            await markAsWatched(type, id, 1, result.data.date);
            document.getElementById('watchDateModal').classList.remove('show');
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

document.getElementById('watchWithoutDateBtn').addEventListener('click', async () => {
    const type = document.getElementById('watchTargetType').value;
    const id = document.getElementById('watchTargetId').value;
    await markAsWatched(type, id, 1, null);
    document.getElementById('watchDateModal').classList.remove('show');
});

document.querySelectorAll('.close-watchdate-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('watchDateModal').classList.remove('show');
    });
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
   SECTION 9: COMMENT MODAL
   ============================================================ */
async function openCommentModal(mediaType, mediaId, commentId) {
    if (mediaType === 'series') {
        showToast('برای ثبت کامنت سریال، به داشبورد بروید (کامنت به فصل‌ها اعمال می‌شود)', 'warning');
        return;
    }

    document.getElementById('commentMediaType').value = mediaType;
    document.getElementById('commentMediaId').value = mediaId;
    document.getElementById('commentId').value = commentId || '';

    document.getElementById('commentModalTitle').innerHTML =
        `<i data-lucide="message-square"></i><span>کامنت فیلم</span>`;

    if (commentId) {
        const cachedComment = allCommentsData.movies[mediaId];
        if (cachedComment && cachedComment.comment) {
            document.getElementById('commentText').value = cachedComment.comment;
        } else {
            const result = await apiCall('get_comment', { media_type: mediaType, media_id: mediaId });
            if (result.ok && result.data) {
                document.getElementById('commentText').value = result.data.comment || '';
            } else {
                document.getElementById('commentText').value = '';
            }
        }
        document.getElementById('deleteCommentBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('commentText').value = '';
        document.getElementById('deleteCommentBtn').style.display = 'none';
    }

    updateCommentCharCount();
    document.getElementById('commentAlert').classList.remove('show');
    document.getElementById('commentModal').classList.add('show');
    refreshIcons();

    setTimeout(() => document.getElementById('commentText').focus(), 100);
}

function updateCommentCharCount() {
    const textarea = document.getElementById('commentText');
    const charCount = document.getElementById('commentCharCount');
    const charWarning = document.getElementById('commentCharWarning');
    const saveBtn = document.getElementById('saveCommentBtn');
    const length = textarea.value.length;
    const maxLength = 1000;

    charCount.textContent = `${length.toLocaleString('fa-IR')} / ${maxLength.toLocaleString('fa-IR')}`;

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

document.getElementById('commentText').addEventListener('input', updateCommentCharCount);

document.getElementById('saveCommentBtn').addEventListener('click', async () => {
    const mediaType = document.getElementById('commentMediaType').value;
    const mediaId = document.getElementById('commentMediaId').value;
    const commentText = document.getElementById('commentText').value.trim();

    if (!commentText) {
        showModalAlert('commentAlert', 'متن کامنت نمی‌تواند خالی باشد', 'error');
        return;
    }

    if (commentText.length > 1000) {
        showModalAlert('commentAlert', 'کامنت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد', 'error');
        return;
    }

    const result = await apiCall('save_comment', {
        media_type: mediaType,
        media_id: mediaId,
        comment: commentText
    });

    if (result.ok) {
        document.getElementById('commentModal').classList.remove('show');
        showToast('کامنت با موفقیت ذخیره شد', 'success');
        await refreshTitlesData();
    } else {
        showModalAlert('commentAlert', result.message || 'خطا در ذخیره کامنت', 'error');
    }
});

document.getElementById('deleteCommentBtn').addEventListener('click', async () => {
    const commentId = document.getElementById('commentId').value;
    if (!commentId) return;

    confirmDelete('آیا از حذف این کامنت مطمئن هستید؟', async () => {
        const result = await apiCall('delete_comment', { comment_id: commentId });
        if (result.ok) {
            document.getElementById('commentModal').classList.remove('show');
            showToast('کامنت با موفقیت حذف شد', 'success');
            await refreshTitlesData();
        } else {
            showToast(result.message || 'خطا در حذف کامنت', 'error');
        }
    });
});

document.querySelectorAll('.close-comment-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('commentModal').classList.remove('show');
    });
});


/* ============================================================
   SECTION 10: PREQUEL MODAL (VIEW ONLY)
   ============================================================ */
async function openPrequelModal(mediaType, mediaId) {
    document.getElementById('prequelMediaType').value = mediaType;
    document.getElementById('prequelMediaId').value = mediaId;

    const typeLabel = mediaType === 'movie' ? 'فیلم' : 'سریال';
    document.getElementById('prequelModalTitle').innerHTML =
        `<i data-lucide="link-2"></i><span>عناوین مرتبط ${typeLabel}</span>`;

    document.getElementById('prequelAlert').classList.remove('show');
    document.getElementById('prequelList').innerHTML =
        '<div class="loading-center"><div class="loading"></div><p>در حال بارگذاری...</p></div>';
    document.getElementById('prequelModal').classList.add('show');
    refreshIcons();

    await loadPrequelsViewOnly(mediaType, mediaId);
}

async function loadPrequelsViewOnly(mediaType, mediaId) {
    const result = await apiCall('get_prequels', { media_type: mediaType, media_id: mediaId });
    const container = document.getElementById('prequelList');

    if (result.ok && result.data && result.data.length > 0) {
        const sortOrder = { 'prequel': 1, 'collection': 2, 'sequel': 3 };
        const sortedData = result.data.sort((a, b) => {
            if (sortOrder[a.relation_type] !== sortOrder[b.relation_type]) {
                return sortOrder[a.relation_type] - sortOrder[b.relation_type];
            }
            return (a.sort_order || 0) - (b.sort_order || 0);
        });

        container.innerHTML = sortedData.map(p => {
            const typeIcon = p.relation_type === 'prequel'
                ? 'corner-down-left'
                : p.relation_type === 'sequel'
                    ? 'corner-down-right'
                    : 'layers-2';

            const typeLabel = p.relation_type === 'prequel'
                ? 'پیش‌درآمد'
                : p.relation_type === 'sequel'
                    ? 'دنباله'
                    : 'مجموعه';

            const typeColors = {
                prequel: { bg: '#f59e0b20', color: '#f59e0b' },
                sequel: { bg: '#3b82f620', color: '#3b82f6' },
                collection: { bg: '#8b5cf620', color: '#8b5cf6' }
            };
            const colors = typeColors[p.relation_type] || typeColors.prequel;

            const orderText = p.sort_order ? `<span style="font-size:0.65rem; opacity:0.7; font-family:monospace;">#${p.sort_order}</span>` : '';
            const yearText = p.release_year ? `<span style="font-size:0.7rem; color: var(--muted-foreground);">(${p.release_year})</span>` : '';
            const relatedIcon = p.related_type === 'series'
                ? '<i data-lucide="tv" style="width:16px;height:16px; color: var(--muted-foreground);"></i>'
                : '';

            return `
            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--secondary); border-radius: 12px; margin-bottom: 0.5rem; border-right: 3px solid ${colors.color};">
                <div style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: ${colors.bg}; color: ${colors.color}; flex-shrink: 0;">
                    <i data-lucide="${typeIcon}" style="width:18px;height:18px;"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                        <span style="font-size: 0.85rem; font-weight: 600; word-break: break-word;">${escapeHtml(p.title)}</span>
                        ${yearText}
                        ${orderText}
                        ${relatedIcon}
                    </div>
                    <div style="font-size: 0.7rem; color: ${colors.color}; font-weight: 500; margin-top: 0.15rem;">
                        ${typeLabel}
                    </div>
                </div>
            </div>
            `;
        }).join('');
        refreshIcons();
    } else {
        container.innerHTML = `
            <div class="empty-state" style="padding: 2rem 1rem;">
                <i data-lucide="link-2"></i>
                <p>هنوز عنوان مرتبطی ثبت نشده است</p>
            </div>
        `;
        refreshIcons();
    }
}

document.querySelectorAll('.close-prequel-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('prequelModal').classList.remove('show');
    });
});


/* ============================================================
   SECTION 11: INIT & REFRESH
   ============================================================ */
async function refreshTitlesData() {
    const listsResult = await apiCall('get_lists');
    if (!listsResult.ok || !listsResult.data) return;

    const titles = [];
    const prequelCache = { movies: {}, series: {} };
    const commentCache = { movies: {}, seasons: {} };

    for (const list of listsResult.data) {
        const detailResult = await apiCall('get_full_list', { list_id: list.id });
        if (detailResult.ok && detailResult.data) {
            detailResult.data.movies.forEach(m => {
                titles.push({
                    ...m,
                    type: 'movie',
                    list_id: list.id,
                    list_name: list.name,
                    list_color: list.color
                });
            });
            detailResult.data.series.forEach(s => {
                const seasons = s.seasons || [];
                const totalSeasons = seasons.length;
                const totalEpisodes = seasons.reduce((sum, sea) => sum + (parseInt(sea.episode_count) || 0), 0);

                titles.push({
                    ...s,
                    type: 'series',
                    list_id: list.id,
                    list_name: list.name,
                    list_color: list.color,
                    total_seasons: totalSeasons,
                    total_episodes: totalEpisodes
                });
            });
        }

        const preqResult = await apiCall('get_all_prequels_for_list', { list_id: list.id });
        if (preqResult.ok && preqResult.data) {
            preqResult.data.movies.forEach(p => {
                if (!prequelCache.movies[p.media_id]) prequelCache.movies[p.media_id] = [];
                prequelCache.movies[p.media_id].push(p);
            });
            preqResult.data.series.forEach(p => {
                if (!prequelCache.series[p.media_id]) prequelCache.series[p.media_id] = [];
                prequelCache.series[p.media_id].push(p);
            });
        }

        const commentsResult = await apiCall('get_all_comments_for_list', { list_id: list.id });
        if (commentsResult.ok && commentsResult.data) {
            commentsResult.data.movies.forEach(c => {
                commentCache.movies[c.media_id] = c;
            });
            commentsResult.data.seasons.forEach(c => {
                commentCache.seasons[c.media_id] = c;
            });
        }
    }

    allTitles = titles;
    allPrequelsData = prequelCache;
    allCommentsData = commentCache;

    if (isSearchMode && searchTerm.length >= 2) {
        performSearch(searchTerm);
    } else {
        applyFilters();
    }
}

// ===== AUTO-FOCUS FOR RATING MODAL =====
const ratingModalEl = document.getElementById('ratingModal');
if (ratingModalEl) {
    ratingModalEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && ratingModalEl.classList.contains('show')) {
            e.preventDefault();
            const confirmBtn = document.getElementById('confirmRatingBtn');
            if (confirmBtn) confirmBtn.click();
        }
    });
}

// ===== ENTER FOR WATCH DATE MODAL =====
const watchDateModalEl = document.getElementById('watchDateModal');
if (watchDateModalEl) {
    watchDateModalEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && watchDateModalEl.classList.contains('show')) {
            e.preventDefault();
            const confirmBtn = document.getElementById('confirmWatchBtn');
            if (confirmBtn) confirmBtn.click();
        }
    });
}

// ===== CTRL+ENTER FOR COMMENT MODAL =====
const commentModalEl = document.getElementById('commentModal');
if (commentModalEl) {
    const commentTextEl = document.getElementById('commentText');
    if (commentTextEl) {
        commentTextEl.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                const saveBtn = document.getElementById('saveCommentBtn');
                if (saveBtn && !saveBtn.disabled) {
                    saveBtn.click();
                }
            }
        });
    }
}

// ===== DONATE BUTTON =====
document.getElementById('donateBtn').addEventListener('click', function(e) {
    e.preventDefault();
    window.open('https://pay.mhdr-shpr.ir', '_blank');
});

// ===== SERVICE WORKER =====
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

// ===== INITIAL LOAD =====
loadAllTitles();

// اطمینان از لود آیکن‌ها
document.addEventListener('DOMContentLoaded', refreshIcons);
setTimeout(refreshIcons, 300);
setTimeout(refreshIcons, 1000);
    </script>
  </body>
</html>