@extends('PromotionalWebsite.layout')

@section('content')

    <style>
        .hero {
            padding: 220px 0 100px;
            text-align: center;
            min-height: 85vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hero h1 {
            font-size: clamp(3rem, 5vw, 4.5rem);
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -1.5px;
            animation: slideUp 0.8s ease forwards;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--primary-navy), var(--lilac-glow));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto 40px;
            animation: slideUp 1s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <main class="container">
        <!-- Hero Phase -->
        <section class="hero">
            <h1>Empowering the Future, <br><span>One Student at a Time.</span></h1>
            <p>Agnus Dei School Systems, Inc. fuses intellectual integrity with deep character formation, preparing youth across Kinder, JHS, and SHS to lead and excel in the 21st century.</p>
            <div style="display: flex; gap: 20px; justify-content: center; margin-top: 20px; animation: slideUp 1.2s ease forwards; opacity: 0;">
                <a href="/inquiry" class="btn-primary" style="padding: 16px 36px; font-size: 1.1rem;">Enroll for 2026</a>
                <a href="/identity" class="btn-outline">Our Core Values</a>
            </div>
        </section>
    </main>

@endsection

