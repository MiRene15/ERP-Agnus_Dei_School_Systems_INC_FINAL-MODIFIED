<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agnus Dei School Systems, Inc.</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- Premium Vanilla CSS Architecture -->
    <style>
        :root {
            /* Extracted from the Agnus Dei Logo */
            --primary-navy: #24225C;
            --primary-dark: #121034;
            --lilac-glow: #A39FE9;
            --gold-accent: #E5C06A;
            --surface-white: #FFFFFF;
            --surface-off-white: #F8F9FA;
            
            /* Glassmorphism Variables */
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --glass-blur: blur(12px);
            
            /* Typography */
            --font-main: 'Outfit', sans-serif;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            
            /* Spacing & Transitions */
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

        /* -------------------------------------------
           SKELETON PRELOADER LOGIC (Requested UX Feature)
           ------------------------------------------- */
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

        /* Ambient Background Blobs */
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
            opacity: 0.05; /* Made much lighter for readability */
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

        /* Minimalist Floating Rectangle Navigation (Reference Match) */
        nav {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1200px;
            background: var(--surface-white);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); /* Clean minimalist shadow */
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
            padding: 0 20px; /* Reduced for smaller footprint */
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px; /* Tighter gap */
            text-decoration: none;
        }

        .nav-logo {
            width: 28px; /* Scaled down logo */
            height: 28px;
            border-radius: 50%;
            background-image: url("{{ asset('images/agnus_logo.png') }}");
            background-size: cover;
            background-color: var(--primary-navy);
            background-position: center;
        }

        .nav-title {
            font-size: 0.95rem; /* Smaller minimalist title */
            font-weight: 700;
            color: var(--primary-navy);
            letter-spacing: -0.2px;
        }

        .nav-links {
            display: flex;
            gap: 24px; /* Tighter spacing */
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
            font-size: 0.85rem; /* Sleeker typography */
            transition: var(--transition);
            display: flex;
            align-items: center;
            padding: 16px 0; /* Creates less height for the floating bar */
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
            background-color: var(--surface-white); /* Clean white minimalist box */
            min-width: 220px; /* Slimmer dropdown width */
            list-style: none;
            padding: 6px 0; /* Minimalist padding */
            margin: 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Clean minimalist shadow */
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.04);
            overflow: hidden; /* Constrain hover background */
        }

        .nav-links .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-links .dropdown-menu a {
            display: block;
            padding: 10px 20px; /* Scaled down padding */
            height: auto;
            color: var(--primary-navy); /* Match the blue text from reference */
            font-weight: 500;
            font-size: 0.82rem; /* Clean, small typography */
            transition: all 0.2s ease;
        }

        .nav-links .dropdown-menu a::after {
            display: none !important; /* Block inner link underline */
        }

        .nav-links .dropdown-menu a:hover,
        .nav-links .dropdown-menu a.active-dropdown {
            color: var(--surface-white); /* White text on hover */
            background-color: var(--primary-navy); /* Blue highlight hover state */
            padding-left: 24px; /* Subtle minimalist hover animation */
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
            padding: 6px 14px; /* Scaled down perfectly */
            border-radius: 6px; 
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem; /* Match other typography */
            transition: var(--transition);
            display: inline-block;
        }

        .btn-outline:hover {
            background: rgba(36, 34, 92, 0.05);
            border-color: var(--primary-navy);
            transform: translateY(-1px);
        }

        /* Generic Section Headers */
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

        /* Footer */
        footer {
            background: var(--primary-dark);
            color: var(--surface-off-white);
            text-align: center;
            padding: 60px 0;
            margin-top: 100px;
        }

        /* Components (Shared Across Pages) */
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

    <!-- 🌟 THE ANIMATED SKELETON LOADER 🌟 -->
    <div id="global-skeleton">
        <div class="skeleton-block skel-nav"></div>
        <div class="skeleton-block skel-title"></div>
        <div class="skeleton-block skel-text"></div>
        <div class="skeleton-block skel-text-2"></div>
    </div>

    <!-- Script to kill the skeleton preloader exactly when DOM completes mounting -->
    <script>
        window.addEventListener('load', () => {
            const skeleton = document.getElementById('global-skeleton');
            skeleton.style.opacity = '0';
            setTimeout(() => {
                skeleton.style.display = 'none';
            }, 600); // Wait for CSS fade out to complete before display none
        });
    </script>

    <!-- Dynamic Canvas Background -->
    <div class="ambient-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <!-- Minimalist Nav -->
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
            <a href="/login" class="btn-outline">Account Portal</a>
        </div>
    </nav>

    <!-- PAGE INJECTION PORTAL -->
    @yield('content')

    <!-- Footer -->
    <footer>
        <p><strong>Agnus Dei School Systems, Inc.</strong> &copy; 1987 - {{ date('Y') }}. All Rights Reserved.</p>
        <p style="font-size: 0.85rem; color: #7c77c6; margin-top: 10px;">Powered by Laravel x Agile Tech</p>
    </footer>

</body>
</html>
