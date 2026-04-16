<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Starmax Ltd — Innovative Digital Solutions</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #fafafa;
            --surface: #ffffff;
            --surface-alt: #f4f4f5;
            --text: #18181b;
            --text-muted: #71717a;
            --accent: #4f46e5;
            --accent-light: #e0e7ff;
            --accent-2: #0ea5e9;
            --border: #27272a;
            --border-hover: #18181b;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 12px 40px rgba(0,0,0,0.1);
            --radius: 16px;
            --radius-sm: 12px;
            --radius-full: 9999px;
            --max-w: 1140px;
            --transition: 0.2s ease;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text);
            background: var(--bg);
            min-height: 100vh;
            line-height: 1.6;
            font-size: 15px;
            -webkit-font-smoothing: antialiased;
        }

        body.menu-open { overflow: hidden; }

        /* ===== HEADER ===== */
        .site-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: #ffffff;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            border-bottom: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .header-inner {
            max-width: var(--max-w);
            margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 24px;
        }
        .brand { display: inline-flex; align-items: center; text-decoration: none; color: var(--text); }
        .brand-image {
            display: block;
            height: 42px;
            width: auto;
            object-fit: contain;
        }

        .site-nav { display: flex; align-items: center; gap: 4px; }
        .site-nav a {
            color: var(--text); text-decoration: none; font-size: 14px; font-weight: 500;
            padding: 8px 14px; border-radius: var(--radius-full); transition: all var(--transition);
        }
        .site-nav a:hover { color: #09090b; background: var(--surface-alt); }
        .site-nav a.active { color: #fff; background: #18181b; font-weight: 600; }
        .site-nav a.cta-link { background: var(--text); color: #fff; font-weight: 600; margin-left: 8px; }
        .site-nav a.cta-link:hover { background: #27272a; }

        .menu-toggle {
            display: none; width: 40px; height: 40px; border-radius: 10px;
            border: 2px solid #27272a; background: var(--surface); color: var(--text);
            cursor: pointer; font-size: 18px; align-items: center; justify-content: center;
            transition: all var(--transition);
        }
        .menu-toggle:hover { border-color: var(--border-hover); background: var(--surface-alt); }
        .header-spacer { height: 72px; }

        /* ===== MAIN ===== */
        main { max-width: var(--max-w); margin: 0 auto; padding: 40px 24px 80px; }

        /* ===== TYPOGRAPHY ===== */
        .eyebrow {
            display: inline-block; font-size: 12px; font-weight: 700;
            letter-spacing: 0.12em; text-transform: uppercase; color: #18181b; margin-bottom: 8px;
        }
        h2 {
            font-size: clamp(28px, 4vw, 42px); font-weight: 800;
            letter-spacing: -0.03em; line-height: 1.15; color: var(--text); margin-bottom: 12px;
        }
        h3 { font-size: 18px; font-weight: 700; letter-spacing: -0.01em; color: var(--text); margin-bottom: 6px; }
        p { color: #52525b; line-height: 1.7; }
        .text-muted { color: var(--text-muted); font-size: 14px; }

        /* ===== GRID ===== */
        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }

        /* ===== CARDS ===== */
        .card {
            background: var(--surface); border: 1px solid #e4e4e7; border-radius: var(--radius);
            padding: 28px; transition: all 0.3s ease; position: relative; overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
        }
        .card:hover { border-color: #a1a1aa; box-shadow: 0 8px 30px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04); transform: translateY(-3px); }
        .card-accent::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: #18181b;
        }
        .card-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .card-icon svg { width: 24px; height: 24px; stroke-width: 1.75; }
        .card-icon.purple { background: #ede9fe; color: #7c3aed; }
        .card-icon.blue { background: #dbeafe; color: #2563eb; }
        .card-icon.sky { background: #e0f2fe; color: #0284c7; }
        .card-icon.teal { background: #ccfbf1; color: #0d9488; }
        .card-icon.orange { background: #ffedd5; color: #ea580c; }
        .card-icon.rose { background: #ffe4e6; color: #e11d48; }
        .card-icon.emerald { background: #d1fae5; color: #059669; }

        /* ===== KPI ===== */
        .kpi { font-size: 32px; font-weight: 900; color: #18181b; letter-spacing: -0.02em; }
        .kpi-label { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 11px 22px; border-radius: var(--radius-full);
            text-decoration: none; font-weight: 600; font-size: 14px;
            transition: all var(--transition); cursor: pointer; border: none;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: #18181b; color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .btn-primary:hover { background: #09090b; box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
        .btn-secondary { background: transparent; color: var(--text); border: 2px solid #18181b; }
        .btn-secondary:hover { background: #18181b; color: #fff; }
        .btn-dark { background: var(--text); color: #fff; }
        .btn-dark:hover { background: #27272a; }
        .stack { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }

        /* ===== HERO ===== */
        .hero-section {
            background: linear-gradient(135deg, #09090b 0%, #18181b 50%, #1c1c1e 100%);
            border-radius: 24px; padding: clamp(40px, 6vw, 72px) clamp(24px, 4vw, 56px);
            color: #fff; position: relative; overflow: hidden; margin-bottom: 48px;
        }
        .hero-section::before {
            content: ''; position: absolute; top: -40%; right: -20%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.04), transparent 70%);
            pointer-events: none;
        }
        .hero-section::after {
            content: ''; position: absolute; bottom: -30%; left: -10%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.03), transparent 70%);
            pointer-events: none;
        }
        .hero-section .eyebrow { color: #a1a1aa; }
        .hero-section h2 { color: #fff; font-size: clamp(32px, 5vw, 52px); margin-bottom: 16px; }
        .hero-section p { color: #a1a1aa; max-width: 560px; }
        .hero-content { position: relative; z-index: 1; max-width: 640px; }
        .hero-slider { overflow: hidden; position: relative; }
        .hero-slider-track {
            display: flex;
            transition: transform 0.6s cubic-bezier(0.22,1,0.36,1);
            will-change: transform;
        }
        .hero-slide {
            min-width: 100%;
            flex: 0 0 100%;
        }
        .hero-slider-controls {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 640px;
        }
        .hero-slider-nav { display: flex; align-items: center; gap: 8px; }
        .hero-slider-btn {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.24);
            background: rgba(255,255,255,0.08);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .hero-slider-btn:hover { background: rgba(255,255,255,0.16); border-color: rgba(255,255,255,0.4); }
        .hero-slider-btn svg { width: 16px; height: 16px; }
        .hero-slider-dots { display: flex; align-items: center; gap: 8px; }
        .hero-slider-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            border: none;
            background: rgba(255,255,255,0.35);
            cursor: pointer;
            padding: 0;
            transition: all 0.2s ease;
        }
        .hero-slider-dot.active { width: 22px; background: #fff; }
        .hero-stats {
            position: relative; z-index: 1;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px;
            margin-top: 40px;
        }
        .hero-stat {
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-sm); padding: 20px; backdrop-filter: blur(8px);
        }
        .hero-stat .kpi { color: #fff; }
        .hero-stat .kpi-label { color: #71717a; }

        /* ===== SECTION ===== */
        .section { margin-bottom: 56px; }
        .section-header { margin-bottom: 28px; max-width: 560px; }

        /* ===== LISTS ===== */
        .list { list-style: none; padding: 0; margin: 10px 0 0; }
        .list li { position: relative; padding-left: 20px; margin-bottom: 8px; color: #52525b; font-size: 14px; }
        .list li::before {
            content: ''; position: absolute; left: 0; top: 8px;
            width: 6px; height: 6px; border-radius: 50%; background: #18181b;
        }

        /* ===== TAG ===== */
        .tag {
            display: inline-block; font-size: 11px; font-weight: 600; letter-spacing: 0.04em;
            text-transform: uppercase; padding: 4px 10px; border-radius: var(--radius-full);
            background: #f4f4f5; color: #18181b;
        }
        .tag.teal { background: #f0fdfa; color: #0d9488; }
        .tag.orange { background: #fff7ed; color: #ea580c; }
        .tag.rose { background: #fff1f2; color: #e11d48; }

        /* ===== FORMS ===== */
        .form-wrap { max-width: 600px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text); }
        input, textarea, select {
            width: 100%; border: 1px solid var(--border); border-radius: var(--radius-sm);
            background: var(--surface); color: var(--text); font-family: inherit; font-size: 14px;
            padding: 11px 14px; transition: border-color var(--transition), box-shadow var(--transition);
        }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #18181b; box-shadow: 0 0 0 3px rgba(24,24,27,0.1); }
        textarea { resize: vertical; min-height: 120px; }
        .success-box {
            background: #dcfce7; border: 1px solid #86efac; color: #166534;
            padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 14px; font-weight: 500;
        }
        .error-text { color: #dc2626; font-size: 12px; margin: 4px 0 0; }

        .divider { height: 1px; background: var(--border); margin: 48px 0; }

        /* ===== SERVICE ACCORDION ===== */
        .service-accordion { display: flex; flex-direction: column; gap: 12px; }
        .service-item {
            background: var(--surface); border: 1px solid #e4e4e7; border-radius: var(--radius);
            overflow: hidden; transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .service-item:hover { border-color: #a1a1aa; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .service-item.open { border-color: #18181b; box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
        .service-trigger {
            width: 100%; display: flex; align-items: center; gap: 16px;
            padding: 22px 28px; cursor: pointer; border: none; background: none;
            text-align: left; font-family: inherit; transition: background 0.2s ease;
        }
        .service-trigger:hover { background: #fafafa; }
        .service-trigger .card-icon { margin-bottom: 0; flex-shrink: 0; }
        .service-trigger-text { flex: 1; min-width: 0; }
        .service-trigger-text h3 { margin-bottom: 2px; font-size: 17px; }
        .service-trigger-text p { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.4; }
        .service-chevron {
            width: 32px; height: 32px; border-radius: 8px; background: #f4f4f5;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .service-chevron svg { width: 18px; height: 18px; color: #71717a; transition: transform 0.3s ease; }
        .service-item.open .service-chevron { background: #18181b; }
        .service-item.open .service-chevron svg { color: #fff; transform: rotate(180deg); }
        .service-body {
            max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1);
        }
        .service-item.open .service-body { max-height: 600px; }
        .service-body-inner {
            padding: 0 28px 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
            border-top: 1px solid #f4f4f5;
            padding-top: 20px;
        }
        .service-body-inner .sb-col p { font-size: 14px; color: #52525b; line-height: 1.7; }
        .service-body-inner .sb-col .list { margin-top: 12px; }
        .service-body-inner .sb-col .list li { font-size: 13px; }
        .service-body-inner .sb-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 14px; }
        .sb-tag {
            display: inline-block; padding: 4px 10px; border-radius: var(--radius-full);
            font-size: 11px; font-weight: 600; background: #f4f4f5; color: #52525b;
            border: 1px solid #e4e4e7;
        }
        @media (max-width: 640px) {
            .service-body-inner { grid-template-columns: 1fr; }
        }

        /* ===== SERVICE GRID CARDS (home preview) ===== */
        .service-preview-card {
            background: var(--surface); border: 1px solid #e4e4e7; border-radius: var(--radius);
            padding: 0; overflow: hidden; transition: all 0.3s ease; text-decoration: none; color: inherit; display: block;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
        }
        .service-preview-card:hover { border-color: #18181b; box-shadow: 0 12px 40px rgba(0,0,0,0.1); transform: translateY(-4px); }
        .service-preview-top {
            padding: 28px 24px 20px; border-bottom: 1px solid #f4f4f5;
        }
        .service-preview-top .card-icon { margin-bottom: 14px; }
        .service-preview-top h3 { font-size: 16px; margin-bottom: 8px; }
        .service-preview-top p { font-size: 13px; color: #71717a; line-height: 1.6; margin: 0; }
        .service-preview-bottom {
            padding: 14px 24px; display: flex; align-items: center; justify-content: space-between;
            background: #fafafa;
        }
        .service-preview-bottom span { font-size: 13px; font-weight: 600; color: #18181b; }
        .service-preview-bottom svg { width: 16px; height: 16px; color: #18181b; }

        /* ===== MODERN SLIDER ===== */
        .modern-slider {
            position: relative;
            background: linear-gradient(160deg, #ffffff 0%, #fafafa 100%);
            border: 1px solid #e4e4e7;
            border-radius: 24px;
            padding: 24px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
        }
        .modern-slider-track {
            display: flex;
            transition: transform 0.55s cubic-bezier(0.22,1,0.36,1);
            will-change: transform;
        }
        .modern-slide {
            flex: 0 0 100%;
            min-width: 100%;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 24px;
            align-items: stretch;
        }
        .modern-slide-content {
            padding: 24px;
            border: 1px solid #e4e4e7;
            border-radius: 18px;
            background: #ffffff;
        }
        .modern-slide-content h3 {
            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.15;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }
        .modern-slide-content p { margin-bottom: 14px; }
        .modern-slide-media {
            position: relative;
            border-radius: 18px;
            padding: 0;
            background: #0f172a;
            color: #fff;
            border: 1px solid #1f2937;
            overflow: hidden;
            min-height: 340px;
        }
        .modern-slide-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.92;
        }
        .modern-slide-media::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15,23,42,0.08) 0%, rgba(15,23,42,0.7) 100%);
        }
        .modern-slide-overlay {
            position: absolute;
            left: 16px;
            right: 16px;
            bottom: 16px;
            z-index: 2;
            background: rgba(2,6,23,0.72);
            border: 1px solid rgba(226,232,240,0.14);
            border-radius: 14px;
            padding: 16px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .modern-slide-media .media-kpi {
            font-size: 38px;
            font-weight: 900;
            letter-spacing: -0.03em;
            color: #fff;
            margin-bottom: 4px;
        }
        .modern-slide-media .media-label {
            color: #d4d4d8;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .modern-slide-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .modern-slide-badges span {
            font-size: 11px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.08);
            color: #e4e4e7;
        }
        .modern-slider-controls {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .modern-slider-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modern-slider-btn {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid #d4d4d8;
            background: #fff;
            color: #18181b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .modern-slider-btn:hover { border-color: #18181b; background: #18181b; color: #fff; }
        .modern-slider-btn svg { width: 18px; height: 18px; }
        .modern-slider-dots {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modern-slider-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #d4d4d8;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
        }
        .modern-slider-dot.active {
            width: 24px;
            background: #18181b;
        }
        @media (max-width: 900px) {
            .modern-slide {
                grid-template-columns: 1fr;
            }
        }

        /* ===== CTA BANNER ===== */
        .cta-banner {
            background: #18181b;
            border-radius: var(--radius); padding: 48px; color: #fff; text-align: center;
            margin-top: 56px;
        }
        .cta-banner h2 { color: #fff; }
        .cta-banner p { color: #a1a1aa; max-width: 480px; margin: 0 auto 20px; }

        /* ===== FOOTER ===== */
        .site-footer { background: #18181b; color: #a1a1aa; }
        .footer-inner {
            max-width: var(--max-w); margin: 0 auto; padding: 48px 24px;
            display: grid; grid-template-columns: 1.3fr repeat(3, 1fr); gap: 40px;
        }
        .footer-brand {
            font-weight: 800;
            font-size: 22px;
            letter-spacing: -0.02em;
            color: #fff;
            margin-bottom: 12px;
        }
        .footer-desc { font-size: 14px; line-height: 1.7; color: #71717a; }
        .footer-heading {
            font-size: 12px; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #71717a; margin-bottom: 16px;
        }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a { color: #a1a1aa; text-decoration: none; font-size: 14px; transition: color var(--transition); }
        .footer-links a:hover { color: #fff; }
        .footer-bottom {
            border-top: 1px solid #27272a; max-width: var(--max-w); margin: 0 auto;
            padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;
            font-size: 13px; color: #52525b;
        }

        /* ===== ANIMATIONS ===== */
        .reveal { opacity: 0; transform: translateY(20px); animation: riseIn 0.6s ease forwards; }
        .reveal:nth-child(2) { animation-delay: 0.06s; }
        .reveal:nth-child(3) { animation-delay: 0.12s; }
        .reveal:nth-child(4) { animation-delay: 0.18s; }
        .reveal:nth-child(5) { animation-delay: 0.24s; }
        .reveal:nth-child(6) { animation-delay: 0.30s; }
        @keyframes riseIn { to { opacity: 1; transform: translateY(0); } }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .menu-toggle { display: inline-flex; }
            .brand-image { height: 36px; }
            .site-nav {
                position: fixed; top: 71px; left: 0; right: 0; bottom: 0;
                flex-direction: column; background: rgba(255,255,255,0.98);
                backdrop-filter: blur(16px); padding: 24px; gap: 4px;
                opacity: 0; visibility: hidden; transition: opacity 0.25s ease, visibility 0.25s ease;
            }
            .site-nav.open { opacity: 1; visibility: visible; }
            .site-nav a { font-size: 16px; padding: 12px 16px; border-radius: 12px; width: 100%; }
            .site-nav a.cta-link { text-align: center; margin-left: 0; margin-top: 8px; }
            .hero-section { border-radius: 16px; }
            .footer-inner { grid-template-columns: 1fr 1fr; gap: 32px; }
            .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
            .cta-banner { padding: 32px 24px; }
        }
        @media (max-width: 480px) { .footer-inner { grid-template-columns: 1fr; } }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f4f4f5; }
        ::-webkit-scrollbar-thumb { background: #a1a1aa; border-radius: 8px; }
        ::-webkit-scrollbar-thumb:hover { background: #71717a; }
    </style>
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
</head>
<body>
@php($tenantDemoUrl = config('app.tenant_demo_url'))

<header class="site-header">
    <div class="header-inner">
        <a href="/" class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="Starmax" class="brand-image">
        </a>
        <button class="menu-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="site-nav">&#9776;</button>
        <nav id="site-nav" class="site-nav">
            <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">Home</a>
            <a href="/about" class="{{ request()->is('about') ? 'active' : '' }}">About</a>
            <a href="/services" class="{{ request()->is('services') ? 'active' : '' }}">Services</a>
            <a href="/products" class="{{ request()->is('products') ? 'active' : '' }}">Products</a>
            <a href="/portfolio" class="{{ request()->is('portfolio') ? 'active' : '' }}">Portfolio</a>
            <a href="/contact" class="{{ request()->is('contact') ? 'active' : '' }}">Contact</a>
            <a href="{{ $tenantDemoUrl }}" class="cta-link" target="_blank" rel="noopener">Live Demo</a>
        </nav>
    </div>
</header>

<div class="header-spacer"></div>

<main>
    @yield('content')
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div>
            <p class="footer-brand">Starmax</p>
            <p class="footer-desc">Building modern digital experiences for property teams, enterprises, and mobile-first audiences across East Africa.</p>
        </div>
        <div>
            <p class="footer-heading">Company</p>
            <ul class="footer-links">
                <li><a href="/about">About Us</a></li>
                <li><a href="/services">Services</a></li>
                <li><a href="/portfolio">Portfolio</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </div>
        <div>
            <p class="footer-heading">Solutions</p>
            <ul class="footer-links">
                <li><a href="/products">Tenant Management</a></li>
                <li><a href="/services">Web Development</a></li>
                <li><a href="/services">Android Apps</a></li>
                <li><a href="/services">AI Agents</a></li>
            </ul>
        </div>
        <div>
            <p class="footer-heading">Contact</p>
            <ul class="footer-links">
                <li><a href="mailto:info@starmaxltd.com">info@starmaxltd.com</a></li>
                <li>+254 700 123 456</li>
                <li>Nairobi, Kenya</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; {{ date('Y') }} Starmax Ltd. All rights reserved.</p>
        <p>Innovative Digital Solutions</p>
    </div>
</footer>

<script>
(function () {
    const toggle = document.querySelector('.menu-toggle');
    const nav = document.getElementById('site-nav');
    if (!toggle || !nav) return;
    const close = () => { nav.classList.remove('open'); toggle.setAttribute('aria-expanded','false'); toggle.textContent='\u2630'; document.body.classList.remove('menu-open'); };
    const open = () => { nav.classList.add('open'); toggle.setAttribute('aria-expanded','true'); toggle.textContent='\u2715'; document.body.classList.add('menu-open'); };
    toggle.addEventListener('click', () => nav.classList.contains('open') ? close() : open());
    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
    window.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 768) close(); });
})();
</script>
<script>
// Service accordion
(function() {
    document.querySelectorAll('.service-trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            var item = this.closest('.service-item');
            var isOpen = item.classList.contains('open');
            // Close all others
            document.querySelectorAll('.service-item.open').forEach(function(el) {
                el.classList.remove('open');
            });
            // Toggle clicked
            if (!isOpen) item.classList.add('open');
        });
    });
})();
</script>
<script>
// Hero slider
(function() {
    document.querySelectorAll('.js-hero-slider').forEach(function(slider) {
        var track = slider.querySelector('.hero-slider-track');
        var slides = slider.querySelectorAll('.hero-slide');
        var prevBtn = slider.querySelector('.js-hero-prev');
        var nextBtn = slider.querySelector('.js-hero-next');
        var dots = slider.querySelectorAll('.hero-slider-dot');
        if (!track || !slides.length) return;

        var current = 0;
        var timer = null;
        var autoplay = slider.dataset.autoplay === 'true';
        var interval = parseInt(slider.dataset.interval || '5200', 10);

        function render(index) {
            current = (index + slides.length) % slides.length;
            track.style.transform = 'translateX(' + (current * -100) + '%)';
            dots.forEach(function(dot, i) {
                dot.classList.toggle('active', i === current);
            });
        }

        function next() { render(current + 1); }
        function prev() { render(current - 1); }

        function startAutoplay() {
            if (!autoplay || slides.length < 2) return;
            stopAutoplay();
            timer = setInterval(next, interval);
        }

        function stopAutoplay() {
            if (timer) clearInterval(timer);
            timer = null;
        }

        if (prevBtn) prevBtn.addEventListener('click', function() { prev(); startAutoplay(); });
        if (nextBtn) nextBtn.addEventListener('click', function() { next(); startAutoplay(); });
        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() {
                render(i);
                startAutoplay();
            });
        });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);

        render(0);
        startAutoplay();
    });
})();
</script>
<script>
// Modern slider
(function() {
    document.querySelectorAll('.js-modern-slider').forEach(function(slider) {
        var track = slider.querySelector('.modern-slider-track');
        var slides = slider.querySelectorAll('.modern-slide');
        var prevBtn = slider.querySelector('.js-slider-prev');
        var nextBtn = slider.querySelector('.js-slider-next');
        var dots = slider.querySelectorAll('.modern-slider-dot');
        if (!track || !slides.length) return;

        var current = 0;
        var timer = null;
        var autoplay = slider.dataset.autoplay === 'true';
        var interval = parseInt(slider.dataset.interval || '5000', 10);

        function render(index) {
            current = (index + slides.length) % slides.length;
            track.style.transform = 'translateX(' + (current * -100) + '%)';
            dots.forEach(function(dot, i) {
                dot.classList.toggle('active', i === current);
            });
        }

        function next() { render(current + 1); }
        function prev() { render(current - 1); }

        function startAutoplay() {
            if (!autoplay || slides.length < 2) return;
            stopAutoplay();
            timer = setInterval(next, interval);
        }

        function stopAutoplay() {
            if (timer) clearInterval(timer);
            timer = null;
        }

        if (prevBtn) prevBtn.addEventListener('click', function() { prev(); startAutoplay(); });
        if (nextBtn) nextBtn.addEventListener('click', function() { next(); startAutoplay(); });
        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() {
                render(i);
                startAutoplay();
            });
        });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);

        render(0);
        startAutoplay();
    });
})();
</script>
<script>lucide.createIcons();</script>
</body>
</html>
