@extends('PromotionalWebsite.layout')

@section('content')

    <style>
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
            margin-bottom: 80px;
        }

        .rise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 100px;
        }

        .rise-item {
            background: var(--surface-white);
            padding: 40px 30px;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(0,0,0,0.02);
            transition: var(--transition);
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            text-align: center;
        }

        .rise-item:hover {
            background: var(--primary-navy);
            transform: translateY(-8px) scale(1.02);
            z-index: 10; 
            box-shadow: var(--shadow-glow);
        }

        .rise-item h2 {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--lilac-glow);
            margin-bottom: 5px;
            transition: var(--transition);
        }

        .rise-item h4 {
            font-size: 1.3rem;
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-weight: 700;
            transition: var(--transition);
        }

        .rise-item p {
            color: var(--text-muted);
            font-size: 1rem;
            transition: var(--transition);
        }

        .rise-item:hover h2,
        .rise-item:hover h4,
        .rise-item:hover p {
            color: var(--surface-white);
        }
    </style>

    <main class="container">
        
        <header class="page-header">
            <h1 class="page-title">Identity & Institutional Pillars</h1>
            <p class="page-subtitle">The foundation of Agnus Dei School Systems rests deeply in Christian faith and unwavering academic rigor.</p>
        </header>

        <section class="grid-2">
            <div class="card">
                <h3>Our Vision</h3>
                <p>Capacitating the youth to develop the 21st Century skills with the fusion of character formation and intellectual integrity to meet the challenges ahead.</p>
            </div>
            <div class="card">
                <h3>Our Mission</h3>
                <p>The School commits its resources, time, and best efforts of the Administration, faculty, and staff to provide affordable good quality education, to develop strong Christian faith, and to make the curriculum instructions timelier, and more relevant in order to deepen the civic and spiritual consciousness of every learner.</p>
            </div>
        </section>

        <h2 style="text-align: center; margin-bottom: 40px; font-size: 2.2rem; color: var(--primary-navy);">We <span style="color: var(--lilac-glow)">RISE UP</span> Together</h2>
        <section class="rise-grid">
            <div class="rise-item">
                <h2>R</h2>
                <h4>Responsibility</h4>
                <p>Accountability in actions, thoughts, and duties towards self, school, and society.</p>
            </div>
            <div class="rise-item">
                <h2>I</h2>
                <h4>Integrity</h4>
                <p>Upholding honesty and strong moral principles in all pursuits.</p>
            </div>
            <div class="rise-item">
                <h2>S</h2>
                <h4>Service</h4>
                <p>Dedicated commitment to helping the community through Christian compassion.</p>
            </div>
            <div class="rise-item">
                <h2>E</h2>
                <h4>Excellence</h4>
                <p>Striving for the highest quality in academic performance and character building.</p>
            </div>
            <div class="rise-item">
                <h2>U</h2>
                <h4>Unity</h4>
                <p>Fostering camaraderie, mutual respect, and familial bonds across students.</p>
            </div>
            <div class="rise-item">
                <h2>P</h2>
                <h4>Peace</h4>
                <p>Cultivating a harmonious environment rooted deeply in strong Faith.</p>
            </div>
        </section>

    </main>

@endsection
