<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agnus Dei School Systems, Inc.</title>
    <link rel="icon" type="image/png" href="{{ asset('images/agnus_logo.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- Premium Vanilla CSS Architecture -->
    <style>
        :root {
            --primary-navy: #24225C;
            --primary-dark: #121034;
            --lilac-glow: #A39FE9;
            --gold-accent: #E5C06A;
            --surface-white: #FFFFFF;
            --surface-off-white: #F8F9FA;
            
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --glass-blur: blur(12px);
            
            --font-main: 'Outfit', sans-serif;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-lg: 20px;
            --radius-full: 9999px;
            --shadow-soft: 0 10px 40px -10px rgba(36, 34, 92, 0.15);
            --shadow-glow: 0 0 30px rgba(163, 159, 233, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            color: var(--text-dark);
            background-color: var(--surface-off-white);
            line-height: 1.6;
            overflow-x: hidden;
        }

        #global-skeleton {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background-color: var(--surface-off-white);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 15vh;
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .skeleton-block {
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite linear;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .skel-nav { width: 80%; max-width: 900px; height: 60px; border-radius: 50px; margin-bottom: 80px;}
        .skel-title { width: 50%; height: 50px; }
        .skel-text { width: 40%; height: 20px; }
        .skel-text-2 { width: 35%; height: 20px; }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .ambient-bg {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }
        
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.05;
            animation: float 20s infinite alternate ease-in-out;
        }
        
        .blob-1 { width: 300px; height: 300px; background: var(--primary-navy); top: -50px; left: -50px; }
        .blob-2 { width: 300px; height: 300px; background: var(--lilac-glow); bottom: -50px; right: 5%; animation-delay: -5s; }

        @keyframes float {
            0% { transform: translateY(0) scale(1.0); }
            100% { transform: translateY(50px) scale(1.1); }
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        nav {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1200px;
            background: var(--surface-white);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            display: flex;
            justify-content: center;
            transition: var(--transition);
        }

        .nav-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-image: url("{{ asset('images/agnus_logo.png') }}");
            background-size: cover;
            background-color: var(--primary-navy);
            background-position: center;
        }

        .nav-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary-navy);
            letter-spacing: -0.2px;
        }

        .nav-links {
            display: flex;
            gap: 24px;
            list-style: none;
            align-items: center;
            height: 100%;
        }

        .nav-links li {
            height: 100%;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.85rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            padding: 16px 0;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary-navy);
        }

        .nav-links a.active {
            color: var(--primary-navy);
        }
        
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-navy);
            border-top-left-radius: 3px;
            border-top-right-radius: 3px;
        }

        .nav-links .dropdown {
            position: relative;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .nav-links .caret {
            font-size: 0.75rem;
            margin-left: 6px;
            transition: transform 0.3s ease;
        }

        .nav-links .dropdown:hover .caret {
            transform: rotate(180deg);
        }

        .nav-links .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: var(--surface-white);
            min-width: 220px;
            list-style: none;
            padding: 6px 0;
            margin: 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .nav-links .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-links .dropdown-menu a {
            display: block;
            padding: 10px 20px;
            height: auto;
            color: var(--primary-navy);
            font-weight: 500;
            font-size: 0.82rem;
            transition: all 0.2s ease;
        }

        .nav-links .dropdown-menu a::after {
            display: none !important;
        }

        .nav-links .dropdown-menu a:hover,
        .nav-links .dropdown-menu a.active-dropdown {
            color: var(--surface-white);
            background-color: var(--primary-navy);
            padding-left: 24px;
        }

        .btn-primary {
            background: var(--primary-navy);
            color: var(--surface-white);
            padding: 12px 28px;
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: var(--transition);
            border: 1px solid transparent;
            box-shadow: 0 4px 15px rgba(36, 34, 92, 0.2);
            display: inline-block;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid rgba(36, 34, 92, 0.2); 
            color: var(--primary-navy);
            padding: 6px 14px;
            border-radius: 6px; 
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: var(--transition);
            display: inline-block;
        }

        .btn-outline:hover {
            background: rgba(36, 34, 92, 0.05);
            border-color: var(--primary-navy);
            transform: translateY(-1px);
        }

        .page-header {
            padding: 150px 0 60px;
            text-align: center;
        }

        .page-title {
            font-size: clamp(2.5rem, 4vw, 3.5rem);
            font-weight: 800;
            color: var(--primary-navy);
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .page-subtitle {
            font-size: 1.15rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        footer {
            background: var(--primary-dark);
            color: var(--surface-off-white);
            text-align: center;
            padding: 60px 0;
            margin-top: 100px;
        }

        .card {
            background: var(--surface-white);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--primary-navy), var(--lilac-glow));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px -10px rgba(36, 34, 92, 0.12);
        }

        .card:hover::before { transform: scaleX(1); }

        .card h3 {
            font-size: 1.8rem;
            color: var(--primary-navy);
            margin-bottom: 20px;
            font-weight: 800;
        }

        .card p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
        }
    </style>
</head>
<body>

    <div id="global-skeleton">
        <div class="skeleton-block skel-nav"></div>
        <div class="skeleton-block skel-title"></div>
        <div class="skeleton-block skel-text"></div>
        <div class="skeleton-block skel-text-2"></div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const skeleton = document.getElementById('global-skeleton');
            skeleton.style.opacity = '0';
            setTimeout(() => {
                skeleton.style.display = 'none';
            }, 600);
        });
    </script>

    <div class="ambient-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <nav>
        <div class="nav-container">
            <a href="/" class="nav-brand">
                <div class="nav-logo"></div>
                <span class="nav-title">Agnus Dei</span>
            </a>
            <ul class="nav-links">
                <li><a href="/" class="{{ request()->is('/') ? 'active' : '' }}">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle {{ (request()->is('educational-philosophy') || request()->is('institutional-background') || request()->is('contact-information')) ? 'active' : '' }}">About Us <span class="caret">&#9662;</span></a>
                    <ul class="dropdown-menu">
                        <li><a href="/educational-philosophy" class="{{ request()->is('educational-philosophy') ? 'active-dropdown' : '' }}">Educational Philosophy</a></li>
                        <li><a href="/institutional-background" class="{{ request()->is('institutional-background') ? 'active-dropdown' : '' }}">Institutional Background</a></li>
                        <li><a href="/contact-information" class="{{ request()->is('contact-information') ? 'active-dropdown' : '' }}">Contact Information</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle {{ (request()->is('program-offerings') || request()->is('requirements-procedures') || request()->is('discounts-privileges')) ? 'active' : '' }}">Admissions <span class="caret">&#9662;</span></a>
                    <ul class="dropdown-menu">
                        <li><a href="/program-offerings" class="{{ request()->is('program-offerings') ? 'active-dropdown' : '' }}">Program Offerings</a></li>
                        <li><a href="/requirements-procedures" class="{{ request()->is('requirements-procedures') ? 'active-dropdown' : '' }}">Requirements and Procedures</a></li>
                        <li><a href="/discounts-privileges" class="{{ request()->is('discounts-privileges') ? 'active-dropdown' : '' }}">Discounts and Privileges</a></li>
                    </ul>
                </li>
                <li><a href="/inquiry" class="{{ request()->is('inquiry') ? 'active' : '' }}">Inquiry</a></li>
            </ul>
            <button onclick="document.getElementById('portal-modal').classList.remove('hidden')" class="btn-outline" id="portal-btn" style="border: none; cursor: pointer; font-family: var(--font-main);">Account Portal</button>
        </div>
    </nav>

    <!-- Account Portal Modal -->
    <div id="portal-modal" class="portal-modal hidden">
        <div class="portal-backdrop" onclick="document.getElementById('portal-modal').classList.add('hidden')"></div>
        <div class="portal-card">
            <button onclick="document.getElementById('portal-modal').classList.add('hidden')" class="portal-close">&times;</button>
            <div class="portal-header">
                <span class="portal-title">Agnus Dei ERP</span>
                <span class="portal-sub">Secure Account Portal</span>
            </div>
            <div class="portal-body">
                @auth
                <a href="/dashboard" onclick="document.getElementById('portal-modal').classList.add('hidden')" class="portal-option">
                    <div class="portal-option-icon" style="background: rgba(36,34,92,0.08);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--primary-navy);"><path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    </div>
                    <div class="portal-option-text">
                        <span class="portal-option-title">Go to My Dashboard</span>
                        <span class="portal-option-desc">You're already logged in. Access your portal here.</span>
                    </div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                </a>
                @else
                <a href="/login" class="portal-option">
                    <div class="portal-option-icon" style="background: rgba(36,34,92,0.08);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--primary-navy);"><path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    </div>
                    <div class="portal-option-text">
                        <span class="portal-option-title">Log In to My Account</span>
                        <span class="portal-option-desc">Already have your school credentials? Access your portal here.</span>
                    </div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                </a>
                @endauth
                <div class="portal-divider">
                    <span>Don't have an account yet?</span>
                </div>
                <a href="/inquiry" onclick="document.getElementById('portal-modal').classList.add('hidden')" class="portal-option">
                    <div class="portal-option-icon" style="background: rgba(229,192,106,0.15);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#B38F3B;"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <div class="portal-option-text">
                        <span class="portal-option-title">Request an Account</span>
                        <span class="portal-option-desc">Submit an inquiry to receive your institutional credentials.</span>
                    </div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                </a>
                <p class="portal-footer-text">Access is controlled by School Administration.</p>
            </div>
        </div>
    </div>

    <style>
        .portal-modal {
            position: fixed; inset: 0; z-index: 9000;
            display: flex; align-items: center; justify-content: center;
        }
        .portal-modal.hidden { display: none; }
        .portal-backdrop {
            position: absolute; inset: 0;
            background: rgba(0,0,0,0.3); backdrop-filter: blur(4px);
        }
        .portal-card {
            position: relative; z-index: 10;
            background: var(--surface-white);
            border-radius: 20px;
            width: 90%; max-width: 440px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
            animation: portalIn 0.35s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes portalIn {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .portal-close {
            position: absolute; top: 16px; right: 16px;
            background: none; border: none;
            font-size: 1.6rem; color: rgba(255,255,255,0.5); cursor: pointer;
            transition: color 0.2s; line-height: 1;
        }
        .portal-close:hover { color: #fff; }
        .portal-header {
            background: var(--primary-navy);
            padding: 32px 32px 28px;
            color: #fff;
        }
        .portal-title {
            display: block; font-size: 1.35rem; font-weight: 800; letter-spacing: -0.3px;
        }
        .portal-sub {
            display: block; font-size: 0.85rem; color: rgba(255,255,255,0.6); margin-top: 4px;
        }
        .portal-body { padding: 24px 28px 28px; }
        .portal-option {
            display: flex; align-items: center; gap: 14px;
            padding: 16px; border-radius: 12px;
            border: 1.5px solid #f1f5f9;
            text-decoration: none; color: inherit;
            transition: all 0.2s ease;
        }
        .portal-option:hover {
            border-color: var(--lilac-glow);
            background: rgba(163,159,233,0.04);
        }
        .portal-option-icon {
            width: 44px; height: 44px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .portal-option-text { flex: 1; min-width: 0; }
        .portal-option-title {
            display: block; font-weight: 700; font-size: 0.95rem;
            color: var(--text-dark); margin-bottom: 2px;
        }
        .portal-option-desc {
            display: block; font-size: 0.8rem; color: var(--text-muted);
        }
        .portal-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 16px 0;
        }
        .portal-divider::before,
        .portal-divider::after {
            content: ''; flex: 1; height: 1px; background: #f1f5f9;
        }
        .portal-divider span {
            font-size: 0.75rem; color: #94a3b8; white-space: nowrap;
        }
        .portal-footer-text {
            text-align: center; font-size: 0.75rem; color: #94a3b8;
            margin-top: 18px; margin-bottom: 0;
        }
    </style>

    @yield('content')

    <footer>
        <p><strong>Agnus Dei School Systems, Inc.</strong> &copy; 1987 - {{ date('Y') }}. All Rights Reserved.</p>
        <p style="font-size: 0.85rem; color: #7c77c6; margin-top: 10px;">Powered by Laravel x Agile Tech</p>
    </footer>

</body>
</html>
