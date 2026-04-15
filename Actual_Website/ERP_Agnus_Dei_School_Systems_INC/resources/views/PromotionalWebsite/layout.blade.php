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
            opacity: 0.15;
            animation: float 20s infinite alternate ease-in-out;
        }
        
        .blob-1 { width: 600px; height: 600px; background: var(--primary-navy); top: -100px; left: -100px; }
        .blob-2 { width: 500px; height: 500px; background: var(--lilac-glow); bottom: -100px; right: 10%; animation-delay: -5s; }

        @keyframes float {
            0% { transform: translateY(0) scale(1.0); }
            100% { transform: translateY(50px) scale(1.1); }
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Glassmorphism Navigation */
        nav {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1200px;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-full);
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: var(--shadow-soft);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .nav-logo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-image: url("{{ asset('images/agnus_logo.png') }}");
            background-size: cover;
            background-color: var(--primary-navy);
            background-position: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .nav-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-navy);
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary-navy);
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
            border: 1px solid var(--primary-navy);
            color: var(--primary-navy);
            padding: 12px 28px;
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: var(--transition);
            display: inline-block;
        }

        .btn-outline:hover {
            background: rgba(36, 34, 92, 0.05);
            transform: translateY(-2px);
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

    <!-- Glassmorphism Nav -->
    <nav>
        <a href="/" class="nav-brand">
            <div class="nav-logo"></div>
            <span class="nav-title">Agnus Dei</span>
        </a>
        <ul class="nav-links">
            <li><a href="/vision" class="{{ request()->is('vision') ? 'active' : '' }}">School Vision</a></li>
            <li><a href="/mission" class="{{ request()->is('mission') ? 'active' : '' }}">Mission</a></li>
            <li><a href="/admissions" class="{{ request()->is('admissions') ? 'active' : '' }}">Admission Process</a></li>
            <li><a href="/academics" class="{{ request()->is('academics') ? 'active' : '' }}">Academic Offerings</a></li>
        </ul>
        <a href="/login" class="btn-primary">Access Portal</a>
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
