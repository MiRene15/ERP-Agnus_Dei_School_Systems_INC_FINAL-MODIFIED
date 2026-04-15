@extends('PromotionalWebsite.layout')

@section('content')

    <style>
        .track-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }

        .strand-card {
            background: var(--surface-white);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            border-top: 5px solid var(--primary-navy);
            transition: var(--transition);
        }

        .strand-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-soft);
        }

        .strand-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary-navy);
            margin-bottom: 10px;
        }

        .strand-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 15px;
        }
    </style>

    <main class="container">
        
        <header class="page-header">
            <h1 class="page-title">Academic Architecture</h1>
            <p class="page-subtitle">From foundation to specialization, our curriculum is engineered for 21st-century leadership.</p>
        </header>

        <section class="track-grid">
            <div class="card" style="border: 2px solid var(--lilac-glow); grid-column: 1 / -1; max-width: 800px; margin: 0 auto;">
                <h3>Primary & Junior High School</h3>
                <p>We build exceptional foundational intelligence starting from Kinder through Grade 10.</p>
                <ul style="list-style: none; padding-left: 0; color: var(--text-muted); margin-top: 15px;">
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">✅ <strong>Primary:</strong> Kinder & Grades 1-6 (Focus on Cognitive & Psychomotor Skills)</li>
                    <li>✅ <strong>Junior High (JHS):</strong> Grades 7-10 (Focus on Core Integrity & Applied Sciences. ESC Grant enabled!)</li>
                </ul>
            </div>
        </section>

        <h2 style="text-align: center; margin-bottom: 40px; font-size: 2.2rem; margin-top: 50px; color: var(--primary-navy);">Senior High Strands (Grades 11-12)</h2>
        
        <section class="track-grid">
            <div class="strand-card">
                <div class="strand-title">🔬 STEM</div>
                <p class="strand-desc">Science, Technology, Engineering, and Mathematics</p>
                <p style="font-size: 0.9rem; color: #888;">For students heavily focused on medical field aspirations, engineering architecture, and pure empirical sciences.</p>
            </div>

            <div class="strand-card">
                <div class="strand-title">💼 ABM</div>
                <p class="strand-desc">Accountancy, Business, and Management</p>
                <p style="font-size: 0.9rem; color: #888;">Geared toward future entrepreneurs, corporate executives, and certified public accountants holding massive leadership.</p>
            </div>

            <div class="strand-card">
                <div class="strand-title">⚖️ HUMSS</div>
                <p class="strand-desc">Humanities and Social Sciences</p>
                <p style="font-size: 0.9rem; color: #888;">For those passionate about political science, law, journalism, and deep societal analysis.</p>
            </div>

            <div class="strand-card">
                <div class="strand-title">🌍 GAS</div>
                <p class="strand-desc">General Academic Strand</p>
                <p style="font-size: 0.9rem; color: #888;">A flexible, highly-adaptable track giving a comprehensive overview for students still forging their distinct career path.</p>
            </div>
        </section>

    </main>

@endsection
