<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Agnus Dei ERP') }} - Portal</title>

    <link rel="icon" type="image/png" href="{{ asset('images/agnus_logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --navy: #24225C;
            --navy-dark: #121034;
            --lilac: #A39FE9;
            --gold: #E5C06A;
            --white: #FFFFFF;
            --off-white: #F8F9FA;
            --glass: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(255, 255, 255, 0.3);
            --font: 'Outfit', sans-serif;
            --text: #1E293B;
            --muted: #94A3B8;
            --ease: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius: 14px;
            --shadow: 0 8px 30px -8px rgba(36, 34, 92, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font);
            color: var(--text);
            background: var(--off-white);
            overflow-x: hidden;
        }

        .ambient {
            position: fixed; inset: 0; z-index: -1; pointer-events: none; overflow: hidden;
        }
        .ambient .blob {
            position: absolute; border-radius: 50%;
            filter: blur(100px); opacity: 0.04;
            animation: drift 25s infinite alternate ease-in-out;
        }
        .ambient .b1 { width: 500px; height: 500px; background: var(--navy); top: -120px; left: -120px; }
        .ambient .b2 { width: 400px; height: 400px; background: var(--lilac); bottom: -100px; right: 3%; animation-delay: -8s; }

        @keyframes drift {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(60px) scale(1.08); }
        }

        .fade-in { animation: rise 0.4s ease forwards; }
        @keyframes rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        .skelly {
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite linear;
            border-radius: 10px;
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .sk-card { height: 140px; }
        .sk-line-sm { height: 14px; width: 60%; margin-bottom: 10px; }
        .sk-line-md { height: 14px; width: 80%; margin-bottom: 10px; }
        .sk-line-lg { height: 14px; width: 90%; margin-bottom: 10px; }
        .sk-title { height: 24px; width: 240px; margin-bottom: 16px; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        .sidebar-link {
            display: flex; align-items: center; padding: 9px 11px;
            border-radius: 10px; color: var(--muted);
            transition: var(--ease); text-decoration: none;
            white-space: nowrap; overflow: hidden; gap: 0;
        }
        .sidebar-link:hover { background: rgba(163, 159, 233, 0.08); color: var(--navy); }
        .sidebar-link.active { background: rgba(163, 159, 233, 0.1); color: var(--navy); font-weight: 600; }

        .sidebar-icon { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-label {
            margin-left: 13px; font-size: 0.88rem;
            transition: opacity 0.25s, width 0.25s, margin-left 0.25s;
            white-space: nowrap;
        }
        .sidebar-collapsed .sidebar-label { opacity: 0; width: 0; margin-left: 0; overflow: hidden; }

        .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--navy), var(--lilac));
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.85rem; flex-shrink: 0;
        }

        .toggle-btn {
            display: flex; align-items: center; justify-content: center;
            width: 30px; height: 30px; border-radius: 8px;
            border: none; cursor: pointer;
            color: var(--muted); background: transparent;
            transition: var(--ease);
        }
        .toggle-btn:hover { background: rgba(163, 159, 233, 0.1); color: var(--navy); }

        .glass {
            background: var(--glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased"
      x-data="{ collapsed: false, mobileOpen: false, loading: true, time: '', date: '', greeting: '' }"
      x-init="
          setTimeout(() => loading = false, 700);
          function tick() {
              const d = new Date(), h = d.getHours();
              greeting = h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening');
              time = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
              date = d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
          }
          tick(); setInterval(tick, 1000);
      "
      :class="mobileOpen ? 'overflow-hidden' : ''">

    <div class="ambient">
        <div class="blob b1"></div>
        <div class="blob b2"></div>
    </div>

    <div class="flex h-screen overflow-hidden">

        {{-- Mobile backdrop --}}
        <template x-teleport="body">
            <div x-show="mobileOpen" x-cloak
                 @click="mobileOpen = false"
                 class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm md:hidden transition-opacity"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
            </div>
        </template>

        {{-- Desktop sidebar --}}
        <aside x-bind:class="collapsed ? 'w-[68px] sidebar-collapsed' : 'w-60'"
               class="bg-white flex-col border-r border-gray-100 z-50 flex-shrink-0 hidden md:flex transition-all duration-300">

            <div class="h-16 flex items-center px-3 border-b border-gray-50 flex-shrink-0"
                 :class="collapsed ? 'justify-center' : 'px-4'">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <img src="{{ asset('images/agnus_logo.png') }}" alt=""
                         class="flex-shrink-0 rounded-full"
                         style="width: 32px; height: 32px; object-fit: cover;">
                    <span x-show="!collapsed"
                          class="font-semibold text-sm tracking-tight whitespace-nowrap"
                          style="color: var(--navy);">Agnus Dei</span>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-2.5 py-3 space-y-0.5">
                <a href="{{ route('dashboard') }}" class="sidebar-link" :title="collapsed ? 'Dashboard' : ''">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="sidebar-label">Dashboard</span>
                </a>
                @include('portal.partials.sidebar-' . match(Auth::user()->role_id) {
                    1 => 'admin', 2 => 'registrar', 3 => 'cashier',
                    4 => 'teacher', 5 => 'librarian', 6 => 'nurse',
                    7 => 'student', 8 => 'directress', 9 => 'principal',
                    default => 'admin',
                })
            </nav>

            <div class="px-2.5 py-2.5 border-t border-gray-50 flex-shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link w-full text-red-400 hover:text-red-600 hover:bg-red-50" :title="collapsed ? 'Log Out' : ''">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="sidebar-label">Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile sidebar --}}
        <aside x-show="mobileOpen" x-cloak
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl border-r border-gray-100 flex flex-col md:hidden"
               x-transition:enter="ease-out duration-300"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="ease-in duration-200"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full">

            <div class="h-16 flex items-center justify-between px-4 border-b border-gray-50 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/agnus_logo.png') }}" alt=""
                         class="rounded-full"
                         style="width: 28px; height: 28px; object-fit: cover;">
                    <span class="font-bold text-base" style="color: var(--navy);">Agnus Dei</span>
                </div>
                <button @click="mobileOpen = false"
                        class="toggle-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">
                <a href="{{ route('dashboard') }}" @click="mobileOpen = false" class="sidebar-link">
                    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="sidebar-label">Dashboard</span>
                </a>
                @include('portal.partials.sidebar-' . match(Auth::user()->role_id) {
                    1 => 'admin', 2 => 'registrar', 3 => 'cashier',
                    4 => 'teacher', 5 => 'librarian', 6 => 'nurse',
                    7 => 'student', 8 => 'directress', 9 => 'principal',
                    default => 'admin',
                })
            </nav>

            <div class="px-3 py-2.5 border-t border-gray-50 flex-shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link w-full text-red-400 hover:text-red-600 hover:bg-red-50">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="sidebar-label">Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex-1 flex flex-col h-screen overflow-hidden">

            {{-- Top bar --}}
            <header class="h-14 flex items-center justify-between px-5 z-20 flex-shrink-0 border-b border-gray-100/80"
                    style="background: rgba(255,255,255,0.5); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                <div class="flex items-center gap-2 min-w-0">
                    <button @click="window.innerWidth < 768 ? mobileOpen = true : collapsed = !collapsed"
                            class="toggle-btn" title="Toggle sidebar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <nav class="flex items-center gap-1.5 text-xs" style="color: var(--muted);">
                        <a href="{{ route('dashboard') }}" class="no-underline transition-colors hover:opacity-70" style="color: var(--muted);">Home</a>
                        <span class="opacity-40">/</span>
                        @yield('breadcrumbs', '<span style="color: var(--navy); font-weight: 600;">Dashboard</span>')
                    </nav>
                </div>

                @php
                    $roleName = match(Auth::user()->role_id) {
                        1 => 'Administrator',
                        2 => 'Registrar',
                        3 => 'Cashier',
                        4 => 'Teacher',
                        5 => 'Librarian',
                        6 => 'Nurse',
                        7 => 'Student',
                        8 => 'Directress',
                        9 => 'Principal',
                        default => 'User'
                    };
                @endphp
                <div class="flex items-center gap-2.5 flex-shrink-0">
                    <div class="text-right leading-tight">
                        <div class="text-sm font-medium" style="color: var(--text);">{{ Auth::user()->name }}</div>
                        <div class="text-[10px] font-semibold tracking-widest uppercase" style="color: var(--lilac);">{{ $roleName }}</div>
                    </div>
                    <div class="avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                </div>
            </header>

            {{-- Welcome bar --}}
            <div class="px-5 pt-5 pb-2 flex items-end justify-between flex-shrink-0">
                <div>
                    <h1 class="text-lg font-semibold" style="color: var(--navy);" x-text="greeting + ', {{ Auth::user()->name }}.'"></h1>
                    <p class="text-xs" style="color: var(--muted); margin-top: 2px;">{{ $roleName }} Dashboard</p>
                </div>
                <div class="text-right leading-tight">
                    <div class="text-sm font-semibold" style="color: var(--navy);" x-text="time"></div>
                    <div class="text-[10px]" style="color: var(--muted); margin-top: 1px;" x-text="date"></div>
                </div>
            </div>

            {{-- Content --}}
            <main class="flex-1 overflow-x-hidden overflow-y-auto px-5 pb-5">
                <div x-show="loading" class="space-y-4 mt-4">
                    <div class="skelly sk-title"></div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="skelly sk-card"></div>
                        <div class="skelly sk-card"></div>
                        <div class="skelly sk-card"></div>
                    </div>
                    <div class="skelly" style="height: 180px; padding: 20px;">
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-lg"></div>
                        <div class="skelly sk-line-md"></div>
                        <div class="skelly sk-line-sm"></div>
                    </div>
                </div>
                <div x-show="!loading" x-cloak class="fade-in space-y-5 mt-4">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script>
        window.addEventListener('pageshow', function (e) { if (e.persisted) window.location.reload(); });
    </script>
</body>
</html>
