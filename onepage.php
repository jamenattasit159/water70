<?php
// onepage.php - Executive OnePage Flood Situation Infographic Dashboard (Ang Thong)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$isLoggedIn = is_logged_in();
$currentUser = current_user();

$todayDate = date('Y-m-d');
$selectedDate = trim($_GET['report_date'] ?? $todayDate);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $todayDate;
}

$pageTitle = 'OnePage รายงานสถานการณ์อุทกภัย (ด้านการแพทย์และสาธารณสุข) จ.อ่างทอง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800;900&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230284c7'><path d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/></svg>">
    <style>

        /* =========================================================
           ONEPAGE v2 — A4 / single-page infographic layout
           Designed to stay visually close to the supplied reference
           while remaining data-driven and print-safe.
        ========================================================== */
        :root {
            --op-navy: #103d66;
            --op-blue: #1f78b9;
            --op-cyan: #22b8d6;
            --op-sky: #eaf8ff;
            --op-teal: #0f8b8d;
            --op-green: #4b8d34;
            --op-purple: #7656b5;
            --op-orange: #ef7419;
            --op-red: #dc2f2f;
            --op-yellow: #f3c343;
            --op-ink: #17324a;
            --op-muted: #6c8293;
            --op-line: #a9cce0;
            --op-white: #ffffff;
        }

        * { box-sizing: border-box; }
        html { background: #dfeef7; }
        body {
            margin: 0;
            padding: 0;
            background:
                radial-gradient(circle at 18% 12%, rgba(255,255,255,.85), transparent 26%),
                linear-gradient(180deg, #e9f7ff 0%, #d9eef9 100%);
            color: var(--op-ink);
            font-family: 'Sarabun', sans-serif;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* ---------- top controls: screen only ---------- */
        .op-controls-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem 1rem;
            flex-wrap: wrap;
            padding: .55rem .85rem;
            background: rgba(255,255,255,.94);
            border-bottom: 1px solid #c7ddea;
            box-shadow: 0 4px 16px rgba(30,82,111,.08);
            backdrop-filter: blur(8px);
        }
        .op-controls-bar .btn {
            border-radius: 999px;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
        }

        /* ---------- poster ---------- */
        .onepage-poster {
            position: relative;
            width: min(94vw, 210mm);
            min-width: 0;
            margin: 1rem auto;
            background: #fff;
            border: 1px solid #b5d2e2;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(37,86,112,.18);
        }

        /* decorative poster edge */
        .onepage-poster::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            border: 5px solid rgba(255,255,255,.38);
            border-radius: 16px;
        }

        /* ---------- hero/header ---------- */
        .op-header {
            position: relative;
            display: grid;
            grid-template-columns: 84px minmax(0,1fr) 92px;
            align-items: center;
            gap: .6rem;
            min-height: 158px;
            padding: 12px 16px 18px;
            overflow: hidden;
            background:
                radial-gradient(circle at 12% 35%, rgba(255,255,255,.92), transparent 22%),
                radial-gradient(circle at 86% 18%, rgba(255,255,255,.55), transparent 19%),
                linear-gradient(180deg, #69c9ef 0%, #a6def3 58%, #e9f8ff 100%);
            border-bottom: 4px solid #2389bd;
        }
        .op-header::before,
        .op-header::after {
            content: '';
            position: absolute;
            left: -3%; right: -3%;
            pointer-events: none;
        }
        .op-header::before {
            bottom: -1px;
            height: 36px;
            background: rgba(255,255,255,.82);
            clip-path: polygon(0 48%, 10% 70%, 20% 33%, 30% 66%, 41% 28%, 53% 61%, 66% 36%, 76% 66%, 87% 27%, 100% 56%, 100% 100%, 0 100%);
        }
        .op-header::after {
            top: 0;
            height: 18px;
            background: linear-gradient(90deg, transparent 0 8%, rgba(255,255,255,.48) 8% 18%, transparent 18% 100%);
            opacity: .55;
        }

        .op-title-block {
            position: relative;
            z-index: 3;
            min-width: 0;
            text-align: center;
        }
        .op-title-sub1 {
            margin-top: 1px;
            font-family: 'Prompt', sans-serif;
            font-size: clamp(16px, 2.1vw, 23px);
            font-weight: 700;
            color: #1a4870;
            line-height: 1;
        }
        .op-title-main,
        .op-title-province {
            display: inline-block;
            font-family: 'Prompt', sans-serif;
            font-size: clamp(25px, 4vw, 42px);
            font-weight: 900;
            line-height: .99;
            letter-spacing: -.4px;
        }
        .op-title-main {
            color: #18456f;
            text-shadow: 0 2px 0 #fff, 1px 3px 8px rgba(28,76,110,.12);
        }
        .op-title-province {
            color: var(--op-orange);
            margin-left: .18em;
            text-shadow: 0 2px 0 #fff, 1px 3px 8px rgba(239,116,25,.12);
        }
        .op-badge-health,
        .op-badge-date {
            display: inline-block;
            border-radius: 999px;
            font-family: 'Prompt', sans-serif;
            line-height: 1;
        }
        .op-badge-health {
            margin-top: 8px;
            padding: 7px 18px;
            color: #fff;
            font-size: clamp(12px, 1.55vw, 17px);
            font-weight: 700;
            background: linear-gradient(90deg, #0a77b6, #2767a6);
            box-shadow: 0 4px 10px rgba(19,88,138,.18);
        }
        .op-badge-date {
            margin-top: 6px;
            padding: 6px 16px;
            color: #fff;
            font-size: clamp(10px, 1.25vw, 14px);
            font-weight: 700;
            background: #174d79;
            box-shadow: 0 3px 8px rgba(23,77,121,.16);
        }

        .op-logo-wrap {
            position: relative;
            z-index: 4;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            gap: .35rem;
        }
        .op-logo-img {
            width: 68px;
            height: 68px;
            flex: 0 0 68px;
            padding: 2px;
            border-radius: 50%;
            background: rgba(255,255,255,.92);
            border: 2px solid rgba(255,255,255,.9);
            box-shadow: 0 5px 12px rgba(40,91,119,.16);
        }
        .op-rescue-badge {
            max-width: 78px;
            padding: .35rem .4rem;
            border: 2px solid rgba(255,255,255,.9);
            border-radius: 12px;
            background: linear-gradient(180deg, #ffb81c, #ee711a);
            color: #fff;
            font-family: 'Prompt', sans-serif;
            font-size: 10px;
            font-weight: 800;
            line-height: 1.1;
            text-align: center;
            box-shadow: 0 5px 12px rgba(192,91,22,.16);
        }

        /* ---------- body ---------- */
        .op-body {
            padding: 10px 12px 8px;
            background:
                linear-gradient(180deg, rgba(234,248,255,.62), #fff 18%, #fff 100%);
        }
        .op-body > div { margin-bottom: 8px !important; }
        .op-body > div:last-child { margin-bottom: 0 !important; }

        /* section header */
        .sec-header {
            display: flex !important;
            align-items: center;
            width: fit-content;
            min-height: 28px;
            margin: 0 0 5px !important;
            padding: 4px 13px;
            border-radius: 999px;
            color: #fff;
            font-family: 'Prompt', sans-serif;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: .1px;
            box-shadow: 0 3px 8px rgba(26,76,108,.10);
        }
        .sec-header.navy  { background: linear-gradient(90deg, #175687, #2675ad); }
        .sec-header.purple{ background: linear-gradient(90deg, #6f55ad, #8a68c2); }
        .sec-header.blue  { background: linear-gradient(90deg, #167bb7, #249bd1); }
        .sec-header.green { background: linear-gradient(90deg, #498d35, #72a74c); }
        .sec-header.teal  { background: linear-gradient(90deg, #0e8585, #1ba7a3); }

        /* ---------- first section ---------- */
        #onepage_printable .op-body > div:nth-child(1) > div:last-child {
            display: grid !important;
            grid-template-columns: 92px 205px minmax(0,1fr) !important;
            gap: 8px !important;
            align-items: start;
        }
        .affected-pill {
            min-height: 43px;
            margin-bottom: 5px !important;
            padding: 5px 8px;
            border-radius: 13px;
            background: linear-gradient(135deg, #259ed0 0%, #1d70ab 100%);
            color: #fff;
            box-shadow: 0 4px 9px rgba(39,127,173,.16);
            gap: 4px;
        }
        .affected-pill:last-child { margin-bottom: 0 !important; }
        .affected-pill-num {
            font-family: 'Prompt', sans-serif;
            font-size: 20px;
            font-weight: 900;
            line-height: 1;
        }
        .affected-pill-label {
            font-family: 'Prompt', sans-serif;
            font-size: 10px;
            font-weight: 700;
        }

        #angthong_map_svg {
            width: 100% !important;
            height: 192px !important;
            max-height: none !important;
            display: block;
        }
        #onepage_printable .op-body > div:nth-child(1) > div:last-child > div:nth-child(2) {
            padding: 4px !important;
            border: 1px solid #b9d6e5 !important;
            border-radius: 12px !important;
            background: linear-gradient(180deg, #f6fcff, #eff9fd) !important;
            overflow: hidden;
        }
        .op-info-card {
            min-width: 0;
            min-height: 45px;
            margin-bottom: 0 !important;
            padding: 6px 7px;
            border-radius: 11px;
            background: #effcff;
            border: 1px solid #9edfe8;
            gap: 6px;
            font-size: 10px;
        }
        .op-info-card > span { font-size: 17px !important; }
        .op-info-card [style*="font-size: 0.775rem"] { font-size: 9px !important; }
        .op-info-card [style*="font-weight: 700"] { font-size: 10px !important; }
        #onepage_printable .op-body > div:nth-child(1) > div:last-child > div:last-child > div:first-child {
            grid-template-columns: 1fr 1fr !important;
            gap: 5px !important;
            margin-bottom: 5px !important;
        }

        /* ---------- compact tables ---------- */
        .op-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border: 1px solid var(--op-line);
            border-radius: 9px;
            background: #fff;
            font-size: 9.5px;
            line-height: 1.08;
        }
        .op-table th,
        .op-table td {
            padding: 4px 4px !important;
            border: 0;
            border-right: 1px solid #c6dce8;
            border-bottom: 1px solid #c6dce8;
            overflow-wrap: anywhere;
        }
        .op-table tr:last-child td { border-bottom: 0; }
        .op-table th:last-child,
        .op-table td:last-child { border-right: 0; }
        .op-table th {
            font-family: 'Prompt', sans-serif;
            font-weight: 800;
            line-height: 1.05;
        }
        .op-table tbody tr:nth-child(even) td { background: #f8fcfe; }
        .op-table tr.total-row td { font-family: 'Prompt', sans-serif; font-weight: 800; }
        .op-table thead th.cyan-head { background: #2e92c2 !important; color: #fff !important; }
        .op-table th.purple-head { background: #eee8fa !important; color: #5e4695 !important; }
        .op-table th.blue-head { background: #dff2fb !important; color: #176493 !important; }

        #op_districts_tbody td,
        #op_districts_tfoot td { padding-top: 5px !important; padding-bottom: 5px !important; }
        #op_source_text { font-size: 8px !important; margin-top: 3px !important; }

        /* ---------- vulnerable section ---------- */
        #onepage_printable .op-body > div:nth-child(2) .op-table th,
        #onepage_printable .op-body > div:nth-child(2) .op-table td {
            padding: 3px 3px !important;
        }
        #onepage_printable .op-body > div:nth-child(2) .op-table { font-size: 8.5px; }

        /* ---------- services + disease columns ---------- */
        #onepage_printable .op-body > div:nth-child(3) {
            display: grid !important;
            grid-template-columns: minmax(0, 1.9fr) minmax(220px, .9fr) !important;
            gap: 8px !important;
            align-items: start;
        }
        #onepage_printable .op-body > div:nth-child(3) .op-table th,
        #onepage_printable .op-body > div:nth-child(3) .op-table td {
            padding: 4px 3px !important;
        }
        #onepage_printable .op-body > div:nth-child(3) .op-table { font-size: 8.6px; }

        /* ---------- bottom summary grid ---------- */
        #onepage_printable .op-body > div:nth-child(4) {
            display: grid !important;
            grid-template-columns: minmax(0,1.18fr) minmax(0,1fr) minmax(0,.92fr) !important;
            gap: 8px !important;
            align-items: stretch;
        }
        .mini-card {
            border-radius: 10px;
            border: 1px solid #bfd4df;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(51,100,126,.06);
        }
        .mini-card-head {
            min-height: 26px;
            padding: 5px 7px !important;
            font-family: 'Prompt', sans-serif;
            font-size: 10px !important;
            font-weight: 800;
            line-height: 1.05;
        }
        .mini-card-body { padding: 5px !important; }
        .mini-card .op-table { font-size: 7.6px !important; border-radius: 7px; }
        .mini-card .op-table th,
        .mini-card .op-table td { padding: 3px 3px !important; }
        .badge-stat {
            padding: 4px 6px;
            border-radius: 7px;
            font-size: 8.5px;
            line-height: 1.1;
        }
        #onepage_printable .op-body > div:nth-child(4) > div:last-child {
            gap: 7px !important;
        }
        #onepage_printable .op-body > div:nth-child(4) > div:last-child .mini-card-body {
            gap: 4px !important;
        }
        #onepage_printable .op-body > div:nth-child(4) > div:first-child .mini-card-body > div {
            margin-top: 5px !important;
            padding: 4px 6px !important;
        }
        #onepage_printable .op-body > div:nth-child(4) > div:first-child .mini-card-body > div > div {
            font-size: 7.5px !important;
        }
        #onepage_printable .op-body > div:nth-child(4) > div:first-child strong {
            font-size: 9px !important;
        }

        /* ---------- footer ---------- */
        .op-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-height: 32px;
            padding: 5px 11px;
            background: linear-gradient(90deg, #e8f8ff, #f7fcff);
            border-top: 1px solid #b5d7e7;
            color: #627889;
            font-size: 8px;
            line-height: 1.15;
        }
        .op-footer > div:last-child {
            color: #1b557e !important;
            font-family: 'Prompt', sans-serif;
            font-size: 8.2px;
        }

        /* ---------- visual polish ---------- */
        #onepage_printable td[style*="color: #dc2626"] { color: #cc2e2e !important; }
        #onepage_printable .op-body table { page-break-inside: avoid; break-inside: avoid; }
        #onepage_printable > * { page-break-inside: avoid; break-inside: avoid; }

        /* ---------- responsive screen ---------- */
        @media (max-width: 760px) {
            .op-controls-bar { padding: .45rem .55rem; }
            .onepage-poster {
                width: 98vw;
                margin: .5rem auto;
                border-radius: 10px;
            }
            .op-header {
                grid-template-columns: 58px minmax(0,1fr) 62px;
                min-height: 126px;
                padding: 8px 8px 14px;
            }
            .op-logo-img { width: 50px; height: 50px; flex-basis: 50px; }
            .op-rescue-badge { max-width: 55px; font-size: 7.5px; padding: .25rem; }
            #onepage_printable .op-body > div:nth-child(1) > div:last-child {
                grid-template-columns: 72px 145px minmax(0,1fr) !important;
                gap: 5px !important;
            }
            #onepage_printable .op-body > div:nth-child(3) {
                grid-template-columns: minmax(0,1.55fr) minmax(170px,.85fr) !important;
            }
            #onepage_printable .op-body > div:nth-child(4) {
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 5px !important;
            }
        }

        /* ---------- print / PDF: force exactly one A4 page ---------- */
        @media print {
            html, body {
                width: 210mm;
                height: 297mm;
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body { overflow: hidden !important; }
            .op-controls-bar, .gov-topbar, footer { display: none !important; }

            .onepage-poster {
                width: 210mm !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                margin: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: hidden !important;
                page-break-after: avoid !important;
                break-after: avoid-page !important;
            }
            .onepage-poster::after { border: 0 !important; }
            .op-header {
                min-height: 42mm !important;
                height: 42mm !important;
                padding: 3mm 4mm 4mm !important;
                grid-template-columns: 19mm minmax(0,1fr) 23mm !important;
                gap: 2mm !important;
            }
            .op-logo-img { width: 17mm !important; height: 17mm !important; flex-basis: 17mm !important; }
            .op-rescue-badge { max-width: 20mm !important; font-size: 2.55mm !important; }
            .op-title-sub1 { font-size: 4.7mm !important; }
            .op-title-main, .op-title-province { font-size: 8.5mm !important; }
            .op-badge-health { margin-top: 1.5mm !important; padding: 1.6mm 4.5mm !important; font-size: 3.35mm !important; }
            .op-badge-date { margin-top: 1.4mm !important; padding: 1.2mm 3.7mm !important; font-size: 2.8mm !important; }

            .op-body { padding: 2.2mm 3mm 1.8mm !important; }
            .op-body > div { margin-bottom: 2.2mm !important; }
            .sec-header { min-height: 7mm; margin-bottom: 1.2mm !important; padding: 1.1mm 3.3mm; font-size: 3.25mm; }

            #onepage_printable .op-body > div:nth-child(1) > div:last-child {
                grid-template-columns: 24mm 54mm minmax(0,1fr) !important;
                gap: 2.2mm !important;
            }
            .affected-pill { min-height: 11.5mm; margin-bottom: 1.25mm !important; padding: 1.2mm 2mm; border-radius: 3.2mm; }
            .affected-pill-num { font-size: 5.4mm; }
            .affected-pill-label { font-size: 2.7mm; }
            #angthong_map_svg { height: 49mm !important; }
            .op-info-card { min-height: 12mm; padding: 1.5mm 1.7mm; border-radius: 2.5mm; gap: 1.5mm; font-size: 2.6mm; }
            .op-info-card > span { font-size: 4.4mm !important; }
            .op-info-card [style*="font-size: 0.775rem"] { font-size: 2.35mm !important; }
            .op-info-card [style*="font-weight: 700"] { font-size: 2.55mm !important; }
            #op_source_text { font-size: 2mm !important; margin-top: .8mm !important; }

            .op-table { font-size: 2.35mm !important; border-radius: 2mm; }
            .op-table th, .op-table td { padding: 1.05mm .8mm !important; }

            #onepage_printable .op-body > div:nth-child(2) .op-table { font-size: 2.12mm !important; }
            #onepage_printable .op-body > div:nth-child(2) .op-table th,
            #onepage_printable .op-body > div:nth-child(2) .op-table td { padding: .8mm .55mm !important; }

            #onepage_printable .op-body > div:nth-child(3) {
                grid-template-columns: minmax(0,1.9fr) minmax(56mm,.9fr) !important;
                gap: 2.2mm !important;
            }
            #onepage_printable .op-body > div:nth-child(3) .op-table { font-size: 2.12mm !important; }
            #onepage_printable .op-body > div:nth-child(3) .op-table th,
            #onepage_printable .op-body > div:nth-child(3) .op-table td { padding: .85mm .55mm !important; }

            #onepage_printable .op-body > div:nth-child(4) { gap: 2.2mm !important; }
            .mini-card { border-radius: 2.5mm; }
            .mini-card-head { min-height: 6.8mm; padding: 1.3mm 1.5mm !important; font-size: 2.55mm !important; }
            .mini-card-body { padding: 1.3mm !important; }
            .mini-card .op-table { font-size: 1.85mm !important; }
            .mini-card .op-table th, .mini-card .op-table td { padding: .65mm .55mm !important; }
            .badge-stat { padding: 1mm 1.3mm; border-radius: 1.6mm; font-size: 2.05mm; }
            #onepage_printable .op-body > div:nth-child(4) > div:first-child .mini-card-body > div { margin-top: 1.3mm !important; padding: 1.1mm 1.3mm !important; }
            #onepage_printable .op-body > div:nth-child(4) > div:first-child .mini-card-body > div > div { font-size: 1.8mm !important; }
            #onepage_printable .op-body > div:nth-child(4) > div:first-child strong { font-size: 2.3mm !important; }

            .op-footer { min-height: 8mm; padding: 1.2mm 3mm; font-size: 1.95mm; }
            .op-footer > div:last-child { font-size: 2.05mm !important; }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

<!-- Interactive Top Control Bar -->
<div class="op-controls-bar">
    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
        <a href="dashboard.php" class="btn btn-sm btn-outline">◀ กลับหน้าหลัก</a>
        <a href="medical_services.php" class="btn btn-sm btn-outline">🩺 แบบบันทึกบริการแพทย์ (ส่วน 3)</a>
        <a href="provincial_overview.php" class="btn btn-sm btn-outline">📊 สรุปภาพรวมจังหวัด</a>
    </div>

    <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 0.35rem;">
            <label for="op_date_picker" style="font-size: 0.85rem; font-weight: 600; color: #0f2c4c;">📅 เลือกวันที่:</label>
            <input type="date" id="op_date_picker" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedDate) ?>" style="font-weight: 700;">
        </div>

        <button type="button" class="btn btn-sm btn-primary" onclick="loadOnePageData()" id="btn_refresh_op">
            🔄 รีเฟรชข้อมูล
        </button>

        <?php if ($isLoggedIn && in_array($currentUser['role'], ['admin', 'superadmin'])): ?>
            <button type="button" class="btn btn-sm" style="background: #fef08a; color: #854d0e; border: 1px solid #fde047;" onclick="loadDemoPreset()" title="โหลดข้อมูลตัวอย่างให้ตรงกับรูปถ่ายรายงาน">
                ⚡ โหลดข้อมูลตัวอย่างตามภาพ
            </button>
        <?php endif; ?>

        <button type="button" class="btn btn-sm btn-success" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 0.35rem;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>พิมพ์ OnePage / PDF</span>
        </button>
    </div>
</div>

<!-- ========================================================
     ONEPAGE POSTER CONTAINER (Matching b8dd3bda-1648-4b1d-96c2-4e5a20cf25e3.jpg)
     ======================================================== -->
<div class="onepage-poster" id="onepage_printable">
    
    <!-- 1. Header Banner -->
    <div class="op-header">
        <!-- Left Logo: Ministry of Public Health (MOPH) -->
        <div class="op-logo-wrap">
            <svg class="op-logo-img" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="48" fill="#ffffff" stroke="#15803d" stroke-width="3"/>
                <circle cx="50" cy="50" r="42" fill="#15803d"/>
                <circle cx="50" cy="50" r="38" fill="#ffffff"/>
                <!-- Rod of Asclepius & Snake -->
                <line x1="50" y1="18" x2="50" y2="82" stroke="#d97706" stroke-width="4.5" stroke-linecap="round"/>
                <path d="M43,30 C58,32 58,40 43,44 C28,48 58,56 43,62 C34,66 54,72 50,78" fill="none" stroke="#15803d" stroke-width="3" stroke-linecap="round"/>
                <circle cx="50" cy="18" r="3.5" fill="#d97706"/>
                <!-- Inscription circles -->
                <text x="50" y="14" font-size="6" font-family="'Prompt', sans-serif" font-weight="700" fill="#15803d" text-anchor="middle">กระทรวงสาธารณสุข</text>
                <text x="50" y="94" font-size="5" font-family="'Prompt', sans-serif" font-weight="600" fill="#15803d" text-anchor="middle">MINISTRY OF PUBLIC HEALTH</text>
            </svg>
        </div>

        <!-- Center Title Block -->
        <div class="op-title-block">
            <div class="op-title-sub1">รายงาน</div>
            <div>
                <span class="op-title-main">สถานการณ์อุทกภัย</span>
                <span class="op-title-province">จ.อ่างทอง</span>
            </div>
            <div>
                <div class="op-badge-health">(ด้านการแพทย์และสาธารณสุข)</div>
            </div>
            <div>
                <div class="op-badge-date" id="display_date_banner">ประจำวันที่ <?= htmlspecialchars($selectedDate) ?></div>
            </div>
        </div>

        <!-- Right Logo & Rescue Emblem -->
        <div class="op-logo-wrap">
            <div class="op-rescue-badge">
                ร่วมใจ<br>ช่วยเหลือ<br>ประชาชน
            </div>
            <!-- Ang Thong Provincial Crest -->
            <svg class="op-logo-img" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="48" fill="#ffffff" stroke="#d97706" stroke-width="3"/>
                <circle cx="50" cy="50" r="42" fill="#fef3c7"/>
                <!-- Golden Bowl with Rice Sheaves (อ่างทอง รวงข้าว) -->
                <path d="M25,58 Q50,78 75,58 L70,72 Q50,86 30,72 Z" fill="#d97706"/>
                <path d="M50,22 Q52,38 50,55" stroke="#15803d" stroke-width="3" fill="none"/>
                <path d="M42,28 Q50,33 46,45" stroke="#d97706" stroke-width="2.5" fill="none"/>
                <path d="M58,28 Q50,33 54,45" stroke="#d97706" stroke-width="2.5" fill="none"/>
                <path d="M35,36 Q48,40 43,52" stroke="#d97706" stroke-width="2.5" fill="none"/>
                <path d="M65,36 Q52,40 57,52" stroke="#d97706" stroke-width="2.5" fill="none"/>
                <text x="50" y="93" font-size="6.5" font-family="'Prompt', sans-serif" font-weight="700" fill="#92400e" text-anchor="middle">จังหวัดอ่างทอง</text>
            </svg>
        </div>
    </div>

    <!-- 2. Poster Body -->
    <div class="op-body">
        
        <!-- SECTION 1: พื้นที่ได้รับผลกระทบ -->
        <div style="margin-bottom: 1rem;">
            <div class="sec-header navy">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                <span>พื้นที่ได้รับผลกระทบ</span>
            </div>

            <div style="display: grid; grid-template-columns: 140px 240px 1fr; gap: 0.85rem; align-items: start;">
                <!-- Left: 4 Cyan Pills -->
                <div>
                    <div class="affected-pill">
                        <span class="affected-pill-num" id="op_tot_districts">5</span>
                        <span class="affected-pill-label">อำเภอ</span>
                    </div>
                    <div class="affected-pill">
                        <span class="affected-pill-num" id="op_tot_subdistricts">33</span>
                        <span class="affected-pill-label">ตำบล</span>
                    </div>
                    <div class="affected-pill">
                        <span class="affected-pill-num" id="op_tot_villages">146</span>
                        <span class="affected-pill-label">หมู่บ้าน</span>
                    </div>
                    <div class="affected-pill">
                        <span class="affected-pill-num" id="op_tot_households">2,009</span>
                        <span class="affected-pill-label">ครัวเรือน</span>
                    </div>
                </div>

                <!-- Center: Stylized Map of Ang Thong Province with Rivers -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.5rem; text-align: center;">
                    <svg viewBox="0 0 280 280" style="width: 100%; max-height: 215px;" id="angthong_map_svg">
                        <!-- Background Frame -->
                        <rect x="0" y="0" width="280" height="280" fill="#f8fafc"/>
                        
                        <!-- Chao Phraya & Noi Rivers -->
                        <path d="M 210,0 Q 190,70 175,130 T 180,210 T 215,280" fill="none" stroke="#60a5fa" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
                        <path d="M 80,40 Q 95,110 110,160 T 120,280" fill="none" stroke="#38bdf8" stroke-width="3" stroke-dasharray="4,2" opacity="0.8"/>
                        <text x="195" y="195" font-size="7" fill="#1d4ed8" font-family="'Prompt', sans-serif">แม่น้ำเจ้าพระยา</text>
                        <text x="95" y="145" font-size="7" fill="#0284c7" font-family="'Prompt', sans-serif">แม่น้ำน้อย</text>

                        <!-- District Polygons (Districts: 1 Mueang, 2 Chaiyo, 3 Pa Mok, 4 Pho Thong, 5 Sawaeng Ha, 6 Wiset, 7 Samko) -->
                        <!-- Sawaeng Ha (North) -->
                        <polygon id="map_dist_5" points="70,15 150,15 140,65 60,60" fill="#ffffff" stroke="#94a3b8" stroke-width="1.5"/>
                        <text x="105" y="42" font-size="8.5" font-family="'Prompt', sans-serif" font-weight="600" text-anchor="middle" fill="#334155">อ.แสวงหา</text>

                        <!-- Pho Thong (North-Central) -->
                        <polygon id="map_dist_4" points="60,60 140,65 170,110 90,115" fill="#ffffff" stroke="#94a3b8" stroke-width="1.5"/>
                        <text x="115" y="92" font-size="8.5" font-family="'Prompt', sans-serif" font-weight="600" text-anchor="middle" fill="#334155">อ.โพธิ์ทอง</text>

                        <!-- Chaiyo (North-East) -->
                        <polygon id="map_dist_2" points="140,65 240,55 245,120 170,110" fill="#ef4444" stroke="#b91c1c" stroke-width="1.5"/>
                        <text x="205" y="92" font-size="8.5" font-family="'Prompt', sans-serif" font-weight="700" text-anchor="middle" fill="#ffffff">อ.ไชโย</text>

                        <!-- Samko (West) -->
                        <polygon id="map_dist_7" points="25,115 90,115 80,185 20,180" fill="#ef4444" stroke="#b91c1c" stroke-width="1.5"/>
                        <text x="55" y="152" font-size="8.5" font-family="'Prompt', sans-serif" font-weight="700" text-anchor="middle" fill="#ffffff">อ.สามโก้</text>

                        <!-- Wiset Chai Chan (South-Central) -->
                        <polygon id="map_dist_6" points="90,115 170,110 160,205 80,185" fill="#ef4444" stroke="#b91c1c" stroke-width="1.5"/>
                        <text x="125" y="162" font-size="8.5" font-family="'Prompt', sans-serif" font-weight="700" text-anchor="middle" fill="#ffffff">อ.วิเศษชัยชาญ</text>

                        <!-- Mueang Ang Thong (East) -->
                        <polygon id="map_dist_1" points="170,110 245,120 235,195 160,185" fill="#ef4444" stroke="#b91c1c" stroke-width="1.5"/>
                        <text x="202" y="155" font-size="8.5" font-family="'Prompt', sans-serif" font-weight="700" text-anchor="middle" fill="#ffffff">อ.เมือง</text>

                        <!-- Pa Mok (South) -->
                        <polygon id="map_dist_3" points="160,185 235,195 220,265 145,255" fill="#ef4444" stroke="#b91c1c" stroke-width="1.5"/>
                        <text x="190" y="232" font-size="8.5" font-family="'Prompt', sans-serif" font-weight="700" text-anchor="middle" fill="#ffffff">อ.ป่าโมก</text>

                        <!-- Legend -->
                        <rect x="5" y="245" width="12" height="8" fill="#ef4444" stroke="#b91c1c"/>
                        <text x="20" y="252" font-size="6.5" font-family="'Prompt', sans-serif" fill="#1e293b">พื้นที่ได้รับผลกระทบ</text>
                        <rect x="5" y="260" width="12" height="8" fill="#ffffff" stroke="#94a3b8"/>
                        <text x="20" y="267" font-size="6.5" font-family="'Prompt', sans-serif" fill="#1e293b">พื้นที่ยังไม่ได้รับผลกระทบ</text>
                    </svg>
                </div>

                <!-- Right: Weather + Dam + Affected Districts Table -->
                <div>
                    <!-- Weather & Dam status -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <div class="op-info-card">
                            <span style="font-size: 1.2rem;">⛅</span>
                            <div>
                                <div style="font-weight: 700; color: #0f766e;">สถานการณ์ฝนตกในพื้นที่</div>
                                <div style="color: #334155; font-size: 0.775rem;" id="op_rain_text">ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง</div>
                            </div>
                        </div>

                        <div class="op-info-card" style="background: #fff7ed; border-color: #fed7aa;">
                            <span style="font-size: 1.2rem;">🌊</span>
                            <div>
                                <div style="font-weight: 700; color: #9a3412;">การระบาย น้ำท้ายเขื่อนเจ้าพระยา</div>
                                <div style="color: #b91c1c; font-weight: 700; font-size: 0.775rem; display: flex; align-items: center; gap: 0.25rem;">
                                    <span id="op_water_text">8.62 (+0.47)</span>
                                    <span style="background: #dc2626; color: #fff; padding: 0 3px; border-radius: 2px; font-size: 0.7rem;">⬆</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Affected Table -->
                    <table class="op-table">
                        <thead>
                            <tr>
                                <th class="cyan-head" style="text-align: left;">อำเภอ</th>
                                <th class="cyan-head">ตำบล</th>
                                <th class="cyan-head">หมู่บ้าน</th>
                                <th class="cyan-head">ชุมชน</th>
                                <th class="cyan-head">หลังคาเรือน</th>
                            </tr>
                        </thead>
                        <tbody id="op_districts_tbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                        <tfoot id="op_districts_tfoot">
                            <!-- Sum row -->
                        </tfoot>
                    </table>

                    <div style="font-size: 0.7rem; color: #64748b; text-align: right; margin-top: 0.25rem;" id="op_source_text">
                        ที่มา : ข้อมูลสถานการณ์อุทกภัยในพื้นที่จังหวัดอ่างทอง สนง.ปภ.จังหวัดอ่างทอง
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: กลุ่มเปราะบางพื้นที่น้ำท่วมที่ได้รับผลกระทบ -->
        <div style="margin-bottom: 1rem;">
            <div class="sec-header purple">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>กลุ่มเปราะบางพื้นที่น้ำท่วมที่ได้รับผลกระทบ</span>
            </div>

            <table class="op-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="purple-head" style="width: 12%;">รายการ</th>
                        <th colspan="8" class="purple-head">กลุ่มประชาชนได้รับการดูแล (สะสม)</th>
                        <th rowspan="2" class="purple-head" style="background: #6d28d9; color: #ffffff; width: 10%;">รวม</th>
                    </tr>
                    <tr>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">ผู้ป่วยติดเตียง</th>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">หญิงตั้งครรภ์</th>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">ผู้พิการ</th>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">เด็ก (0-5 ปี)</th>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">ผู้สูงอายุ</th>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">ผู้ป่วยฟอกไต</th>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">จิตเวชเรื้อรัง</th>
                        <th style="background: #f5f3ff; color: #5b21b6; font-size: 0.775rem;">NCD</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 700; background: #f8fafc;">ทั้งหมด (ราย)</td>
                        <td id="vul_bedridden" style="font-weight: 600;">0</td>
                        <td id="vul_pregnant" style="font-weight: 600;">0</td>
                        <td id="vul_disabled" style="font-weight: 600;">0</td>
                        <td id="vul_children" style="font-weight: 600;">0</td>
                        <td id="vul_elderly" style="font-weight: 600;">0</td>
                        <td id="vul_dialysis" style="font-weight: 600;">0</td>
                        <td id="vul_psychiatric" style="font-weight: 600;">0</td>
                        <td id="vul_ncd" style="font-weight: 600;">0</td>
                        <td id="vul_total" style="font-weight: 800; font-size: 1rem; color: #6d28d9; background: #ede9fe;">0</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- SECTION 3: การให้บริการทางการแพทย์ & โรคที่พบจากการให้บริการ (2 Columns) -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.85rem; margin-bottom: 1rem;">
            
            <!-- Left: การให้บริการทางการแพทย์ (จำนวนครั้งสะสม) -->
            <div>
                <div class="sec-header blue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    <span>การให้บริการทางการแพทย์</span>
                </div>

                <table class="op-table">
                    <thead>
                        <tr>
                            <th colspan="8" class="blue-head">การให้บริการทางการแพทย์ (จำนวนครั้งสะสม)</th>
                        </tr>
                        <tr>
                            <th style="background: #f0f9ff; font-size: 0.725rem;">เยี่ยมบ้าน ติดเตียง/ชรา/เด็ก</th>
                            <th style="background: #f0f9ff; font-size: 0.725rem;">สุขศึกษา/คำปรึกษา</th>
                            <th style="background: #f0f9ff; font-size: 0.725rem;">ตรวจรักษา</th>
                            <th style="background: #f0f9ff; font-size: 0.725rem;">จัดส่งยาโรคประจำตัว</th>
                            <th style="background: #f0f9ff; font-size: 0.725rem;">รับยา / แจกยา</th>
                            <th style="background: #f0f9ff; font-size: 0.725rem;">ส่งต่อผู้ป่วย</th>
                            <th style="background: #f0f9ff; font-size: 0.725rem;">ประเมินสุขภาพจิต</th>
                            <th style="background: #0284c7; color: #ffffff; font-size: 0.8rem; width: 12%;">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="med_home_visit">0</td>
                            <td id="med_health_edu">0</td>
                            <td id="med_treatment">0</td>
                            <td id="med_chronic_meds">0</td>
                            <td id="med_dispense">0</td>
                            <td id="med_referral">0</td>
                            <td id="med_mental_eval">0</td>
                            <td id="med_total" style="font-weight: 800; font-size: 1rem; color: #0284c7; background: #e0f2fe;">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Right: โรคที่พบจากการให้บริการ (จำนวน คน) -->
            <div>
                <table class="op-table">
                    <thead>
                        <tr>
                            <th style="background: #0f2c4c; color: #ffffff; text-align: left;">โรคที่พบจากการให้บริการ</th>
                            <th style="background: #0f2c4c; color: #ffffff; width: 35%;">จำนวน (คน)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: left; font-weight: 600; color: #dc2626;">น้ำกัดเท้า</td>
                            <td id="dis_athletes_foot" style="font-weight: 700; color: #dc2626;">0</td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">โรคผิวหนัง เช่น แพ้ ผื่นคัน</td>
                            <td id="dis_skin">0</td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">ปวดเมื่อยกล้ามเนื้อ</td>
                            <td id="dis_muscle_pain">0</td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">ปวดศีรษะ/เวียนศีรษะ</td>
                            <td id="dis_headache">0</td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">โรคตาอักเสบ/ตาแดง</td>
                            <td id="dis_eye_infection">0</td>
                        </tr>
                        <tr>
                            <td style="text-align: left;">ไข้เลือดออก</td>
                            <td id="dis_dengue">0</td>
                        </tr>
                        <tr class="total-row" style="background: #e0f2fe;">
                            <td style="text-align: left; color: #0f2c4c;">รวม</td>
                            <td id="dis_total" style="color: #0284c7; font-size: 0.95rem;">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECTION 4: 4 Bottom Boxes (Mental Health, Resources, Shelter, Facility & Casualties) -->
        <div style="display: grid; grid-template-columns: 1.15fr 1fr 1fr; gap: 0.85rem;">
            
            <!-- Box 1: การคัดกรองสุขภาพจิต -->
            <div class="mini-card" style="border-color: #86efac;">
                <div class="mini-card-head" style="background: #15803d; display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    <span>การคัดกรองสุขภาพจิต</span>
                </div>
                <div class="mini-card-body" style="padding: 0.4rem;">
                    <table class="op-table" style="font-size: 0.75rem;">
                        <thead>
                            <tr>
                                <th rowspan="2" style="background: #dcfce7; color: #166534; width: 22%;">จำนวนคัดกรอง<br>สะสม (คน)</th>
                                <th colspan="3" style="background: #fef08a; color: #854d0e;">ผลการประเมินสะสม (คน)</th>
                                <th rowspan="2" style="background: #e0f2fe; color: #0369a1; width: 25%;">การให้บริการ PFA, พบแพทย์ (คน)</th>
                            </tr>
                            <tr>
                                <th style="background: #fef9c3; color: #854d0e;">เครียดสูง</th>
                                <th style="background: #fef9c3; color: #854d0e;">เสี่ยงต่อ<br>ซึมเศร้า</th>
                                <th style="background: #fef9c3; color: #854d0e;">เสี่ยง<br>ฆ่าตัวตาย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="mh_screened" style="font-weight: 700; font-size: 0.95rem; color: #166534;">0</td>
                                <td id="mh_high_stress">0</td>
                                <td id="mh_depression">0</td>
                                <td id="mh_suicide_risk">0</td>
                                <td id="mh_pfa_doctor" style="font-weight: 700; color: #0284c7;">0</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Facility Impacts Mini-table (below mental health) -->
                    <div style="margin-top: 0.45rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 0.35rem 0.5rem;">
                        <div style="font-weight: 700; color: #0f2c4c; font-size: 0.775rem; text-align: center; margin-bottom: 2px;">
                            สถานบริการสาธารณสุขที่ได้รับผลกระทบ
                        </div>
                        <div style="display: flex; justify-content: space-around; text-align: center; font-size: 0.725rem;">
                            <div>
                                <span style="display: block; color: #64748b;">ทั้งหมด</span>
                                <strong id="fac_total" style="font-size: 0.85rem; color: #0f2c4c;">0</strong>
                            </div>
                            <div>
                                <span style="display: block; color: #64748b;">รพ.สต.</span>
                                <strong id="fac_rpst">0</strong>
                            </div>
                            <div>
                                <span style="display: block; color: #64748b;">รพ.</span>
                                <strong id="fac_hosp">0</strong>
                            </div>
                            <div>
                                <span style="display: block; color: #64748b;">สสอ.</span>
                                <strong id="fac_sso">0</strong>
                            </div>
                            <div>
                                <span style="display: block; color: #15803d;">เปิดบริการ</span>
                                <strong id="fac_normal" style="color: #15803d;">0</strong>
                            </div>
                            <div>
                                <span style="display: block; color: #ea580c;">เปิดบางส่วน</span>
                                <strong id="fac_partial" style="color: #ea580c;">0</strong>
                            </div>
                            <div>
                                <span style="display: block; color: #dc2626;">ปิดบริการ</span>
                                <strong id="fac_closed" style="color: #dc2626;">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Box 2: การสนับสนุนทรัพยากรให้แก่ ปชช. -->
            <div class="mini-card" style="border-color: #5eead4;">
                <div class="mini-card-head" style="background: #0f766e;">
                    การสนับสนุนทรัพยากรให้แก่ ปชช.
                </div>
                <div class="mini-card-body" style="padding: 0;">
                    <table class="op-table" style="font-size: 0.75rem;">
                        <thead>
                            <tr style="background: #ccfbf1; color: #115e59;">
                                <th style="text-align: left; padding-left: 0.5rem;">รายการ</th>
                                <th style="width: 35%;">สะสม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align: left; padding-left: 0.5rem;">ยารักษาน้ำกัดเท้า (หลอด)</td>
                                <td id="res_foot_cream" style="font-weight: 700; color: #0f766e;">0</td>
                            </tr>
                            <tr>
                                <td style="text-align: left; padding-left: 0.5rem;">ยาชุดช่วยเหลือผู้ประสบภัย (ชุด)</td>
                                <td id="res_relief_kit" style="font-weight: 600;">0</td>
                            </tr>
                            <tr>
                                <td style="text-align: left; padding-left: 0.5rem;">ถุงดำ (ใบ)</td>
                                <td id="res_garbage_bag">0</td>
                            </tr>
                            <tr>
                                <td style="text-align: left; padding-left: 0.5rem;">ยากันยุง (ซอง/กล่อง)</td>
                                <td id="res_mosquito_repellent">0</td>
                            </tr>
                            <tr>
                                <td style="text-align: left; padding-left: 0.5rem;">ยาสามัญประจำบ้าน (ชุด)</td>
                                <td id="res_home_medicine">0</td>
                            </tr>
                            <tr>
                                <td style="text-align: left; padding-left: 0.5rem;">ยาทากลากเกลื้อน (ชุด)</td>
                                <td id="res_antifungal">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Box 3: ศูนย์พักพิง & ผู้เสียชีวิต/บาดเจ็บ -->
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <!-- Shelters -->
                <div class="mini-card" style="border-color: #93c5fd;">
                    <div class="mini-card-head" style="background: #1d4ed8; font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        การให้บริการศูนย์พักพิง
                    </div>
                    <div class="mini-card-body" style="padding: 0.45rem; text-align: center; display: flex; flex-direction: column; gap: 0.25rem;">
                        <div class="badge-stat" style="background: #eff6ff; color: #1e40af;">
                            สถานที่พัก: ศูนย์พักพิง <strong id="sh_count">0</strong> แห่ง
                        </div>
                        <div class="badge-stat" style="background: #e0f2fe; color: #0369a1;">
                            รองรับได้ <strong id="sh_cap">0</strong> คน
                        </div>
                        <div class="badge-stat" style="background: #f1f5f9; color: #334155;">
                            ผู้เข้าพัก <strong id="sh_occ">0</strong> คน
                        </div>
                    </div>
                </div>

                <!-- Casualties -->
                <div class="mini-card" style="border-color: #fca5a5;">
                    <div class="mini-card-head" style="background: #991b1b; font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        สถานการณ์ผู้เสียชีวิตและบาดเจ็บ
                    </div>
                    <div class="mini-card-body" style="padding: 0.45rem; font-size: 0.75rem; display: flex; flex-direction: column; gap: 0.2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>❌ เสียชีวิต:</span>
                            <strong id="cas_deaths" style="color: #991b1b; font-size: 0.85rem;">0 ราย</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>⚠️ บาดเจ็บ:</span>
                            <strong id="cas_injuries" style="color: #ea580c;">0 ราย</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>❓ สูญหาย:</span>
                            <strong id="cas_missing" style="color: #475569;">0 ราย</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- 3. Footer -->
    <div class="op-footer">
        <div>
            ที่มา : <span id="op_footer_date">รายงานสถานการณ์อุทกภัย 2569 สสจ.อ่างทอง ณ วันที่ <?= htmlspecialchars($selectedDate) ?></span>
        </div>
        <div style="font-weight: 700; color: #0f2c4c;">
            กลุ่มภารกิจตระหนักรู้สถานการณ์ สำนักงานสาธารณสุขจังหวัดอ่างทอง
        </div>
    </div>
</div>

<script>
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function formatThaiDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const day = parseInt(parts[2], 10);
    const months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const month = months[parseInt(parts[1], 10)] || parts[1];
    const year = parseInt(parts[0], 10) + 543;
    return `${day} ${month} ${year}`;
}

function loadOnePageData() {
    const dateInput = document.getElementById('op_date_picker');
    const dateStr = dateInput.value;

    document.getElementById('display_date_banner').textContent = `ประจำวันที่ ${formatThaiDate(dateStr)}`;
    document.getElementById('op_footer_date').textContent = `รายงานสถานการณ์อุทกภัย 2569 สสจ.อ่างทอง ณ วันที่ ${formatThaiDate(dateStr)}`;

    fetch(`api/onepage_data.php?action=get_data&report_date=${dateStr}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const d = res;

                // 1. Affected Overview
                document.getElementById('op_tot_districts').textContent = d.affected_overview.districts_count || 0;
                document.getElementById('op_tot_subdistricts').textContent = d.affected_overview.subdistricts_count || 0;
                document.getElementById('op_tot_villages').textContent = d.affected_overview.villages_count || 0;
                document.getElementById('op_tot_households').textContent = Number(d.affected_overview.households_count || 0).toLocaleString();

                // 2. Weather & Situation
                document.getElementById('op_rain_text').textContent = d.situation.rain_situation || 'ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง';
                document.getElementById('op_water_text').textContent = d.situation.water_discharge || '8.62 (+0.47)';
                document.getElementById('op_source_text').textContent = `ที่มา : ${d.situation.data_source || 'สนง.ปภ.จังหวัดอ่างทอง'} ณ วันที่ ${formatThaiDate(dateStr)}`;

                // 3. District Table & Map Highlighting
                let distRowsHtml = '';
                let sumSub = 0, sumVil = 0, sumCom = 0, sumHh = 0;

                (d.district_stats || []).forEach(ds => {
                    const isF = parseInt(ds.is_flooded || 0, 10) === 1 || parseInt(ds.subdistricts_affected || 0, 10) > 0;
                    
                    // Update Map Polygon fill color
                    const poly = document.getElementById(`map_dist_${ds.district_id}`);
                    if (poly) {
                        poly.setAttribute('fill', isF ? '#ef4444' : '#ffffff');
                        poly.setAttribute('stroke', isF ? '#b91c1c' : '#94a3b8');
                    }

                    if (isF) {
                        sumSub += parseInt(ds.subdistricts_affected || 0, 10);
                        sumVil += parseInt(ds.villages_affected || 0, 10);
                        sumCom += parseInt(ds.communities_affected || 0, 10);
                        sumHh += parseInt(ds.households_affected || 0, 10);

                        distRowsHtml += `
                            <tr>
                                <td style="text-align: left; font-weight: 600; color: #0f2c4c;">${escapeHtml(ds.district_name.replace('อำเภอ', ''))}</td>
                                <td>${ds.subdistricts_affected}</td>
                                <td>${ds.villages_affected}</td>
                                <td>${ds.communities_affected}</td>
                                <td style="font-weight: 600;">${Number(ds.households_affected).toLocaleString()}</td>
                            </tr>
                        `;
                    }
                });

                document.getElementById('op_districts_tbody').innerHTML = distRowsHtml || '<tr><td colspan="5" style="color: #94a3b8;">ไม่มีพื้นที่น้ำท่วม</td></tr>';
                document.getElementById('op_districts_tfoot').innerHTML = `
                    <tr class="total-row" style="background: #e0f2fe;">
                        <td style="text-align: left; color: #0284c7;">รวม</td>
                        <td>${sumSub}</td>
                        <td>${sumVil}</td>
                        <td>${sumCom}</td>
                        <td style="color: #0284c7; font-size: 0.9rem;">${Number(sumHh).toLocaleString()}</td>
                    </tr>
                `;

                // 4. Vulnerable Groups
                const vg = d.vulnerable_groups || {};
                document.getElementById('vul_bedridden').textContent = Number(vg.bedridden || 0).toLocaleString();
                document.getElementById('vul_pregnant').textContent = Number(vg.pregnant || 0).toLocaleString();
                document.getElementById('vul_disabled').textContent = Number(vg.disabled || 0).toLocaleString();
                document.getElementById('vul_children').textContent = Number(vg.children || 0).toLocaleString();
                document.getElementById('vul_elderly').textContent = Number(vg.elderly || 0).toLocaleString();
                document.getElementById('vul_dialysis').textContent = Number(vg.dialysis || 0).toLocaleString();
                document.getElementById('vul_psychiatric').textContent = Number(vg.psychiatric || 0).toLocaleString();
                document.getElementById('vul_ncd').textContent = Number(vg.ncd || 0).toLocaleString();
                document.getElementById('vul_total').textContent = Number(vg.total || 0).toLocaleString();

                // 5. Medical Services
                const ms = d.medical_services || {};
                document.getElementById('med_home_visit').textContent = Number(ms.home_visit || 0).toLocaleString();
                document.getElementById('med_health_edu').textContent = Number(ms.health_edu || 0).toLocaleString();
                document.getElementById('med_treatment').textContent = Number(ms.treatment || 0).toLocaleString();
                document.getElementById('med_chronic_meds').textContent = Number(ms.chronic_meds || 0).toLocaleString();
                document.getElementById('med_dispense').textContent = Number(ms.med_dispense || 0).toLocaleString();
                document.getElementById('med_referral').textContent = Number(ms.referral || 0).toLocaleString();
                document.getElementById('med_mental_eval').textContent = Number(ms.mental_evaluation || 0).toLocaleString();
                document.getElementById('med_total').textContent = Number(ms.total || 0).toLocaleString();

                // 6. Diseases
                const td = d.top_diseases || {};
                document.getElementById('dis_athletes_foot').textContent = Number(td.athletes_foot || 0).toLocaleString();
                document.getElementById('dis_skin').textContent = Number(td.skin || 0).toLocaleString();
                document.getElementById('dis_muscle_pain').textContent = Number(td.muscle_pain || 0).toLocaleString();
                document.getElementById('dis_headache').textContent = Number(td.headache || 0).toLocaleString();
                document.getElementById('dis_eye_infection').textContent = Number(td.eye_infection || 0).toLocaleString();
                document.getElementById('dis_dengue').textContent = Number(td.dengue || 0).toLocaleString();
                document.getElementById('dis_total').textContent = Number(td.total || 0).toLocaleString();

                // 7. Mental Health
                const mh = d.mental_health || {};
                document.getElementById('mh_screened').textContent = Number(mh.screened || 0).toLocaleString();
                document.getElementById('mh_high_stress').textContent = Number(mh.high_stress || 0).toLocaleString();
                document.getElementById('mh_depression').textContent = Number(mh.depression || 0).toLocaleString();
                document.getElementById('mh_suicide_risk').textContent = Number(mh.suicide_risk || 0).toLocaleString();
                document.getElementById('mh_pfa_doctor').textContent = Number(mh.pfa_doctor || 0).toLocaleString();

                // 8. Resources
                const resList = d.resources || {};
                document.getElementById('res_foot_cream').textContent = Number(resList.foot_cream || 0).toLocaleString();
                document.getElementById('res_relief_kit').textContent = Number(resList.relief_kit || 0).toLocaleString();
                document.getElementById('res_garbage_bag').textContent = Number(resList.garbage_bag || 0).toLocaleString();
                document.getElementById('res_mosquito_repellent').textContent = Number(resList.mosquito_repellent || 0).toLocaleString();
                document.getElementById('res_home_medicine').textContent = Number(resList.home_medicine || 0).toLocaleString();
                document.getElementById('res_antifungal').textContent = (resList.antifungal && resList.antifungal > 0) ? Number(resList.antifungal).toLocaleString() : '-';

                // 9. Facilities Impact
                const fac = d.facility_impacts || {};
                document.getElementById('fac_total').textContent = fac.total_affected || 0;
                document.getElementById('fac_rpst').textContent = fac.rpst_affected || 0;
                document.getElementById('fac_hosp').textContent = fac.hospital_affected || 0;
                document.getElementById('fac_sso').textContent = fac.sso_affected || 0;
                document.getElementById('fac_normal').textContent = fac.normal_count || 0;
                document.getElementById('fac_partial').textContent = fac.partial_count || 0;
                document.getElementById('fac_closed').textContent = fac.closed_count || 0;

                // 10. Shelters & Casualties
                const sh = d.shelter_info || {};
                document.getElementById('sh_count').textContent = sh.total_sites || 0;
                document.getElementById('sh_cap').textContent = Number(sh.capacity || 0).toLocaleString();
                document.getElementById('sh_occ').textContent = Number(sh.occupants || 0).toLocaleString();

                const cas = d.casualty_info || {};
                document.getElementById('cas_deaths').textContent = `${cas.deaths || 0} ราย`;
                document.getElementById('cas_injuries').textContent = `${cas.injuries || 0} ราย`;
                document.getElementById('cas_missing').textContent = `${cas.missing || 0} ราย`;
            }
        });
}

function loadDemoPreset() {
    if (!confirm('ต้องการโหลดข้อมูลตัวอย่างอุทกภัยให้ตรงกับแบบฟอร์มในภาพตัวอย่างหรือไม่?')) return;
    const dateInput = document.getElementById('op_date_picker');
    const formData = new FormData();
    formData.append('report_date', dateInput.value);

    fetch('api/onepage_data.php?action=load_demo_preset', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message);
        loadOnePageData();
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('op_date_picker').addEventListener('change', loadOnePageData);
    loadOnePageData();
});
</script>

</body>
</html>
