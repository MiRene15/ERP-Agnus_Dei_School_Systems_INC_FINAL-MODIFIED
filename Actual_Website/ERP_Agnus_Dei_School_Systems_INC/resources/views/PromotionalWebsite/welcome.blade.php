@extends('PromotionalWebsite.layout')

@section('content')

    <style>
        .hero-wrapper {
            position: relative;
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            min-height: 92vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* ── Slideshow ── */
        .slideshow {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .slideshow .slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            animation: slideFade 20s infinite;
        }
        .slideshow .slide:nth-child(1) { animation-delay: 0s; }
        .slideshow .slide:nth-child(2) { animation-delay: 5s; }
        .slideshow .slide:nth-child(3) { animation-delay: 10s; }
        .slideshow .slide:nth-child(4) { animation-delay: 15s; }

        .slide-1 {
            background: url('{{ asset("images/slideshow1.jpg") }}') center/cover no-repeat;
        }
        .slide-2 {
            background: url('{{ asset("images/slideshow2.jpg") }}') center/cover no-repeat;
        }
        .slide-3 {
            background: url('{{ asset("images/slideshow3.jpg") }}') center/cover no-repeat;
        }
        .slide-4 {
            background: url('{{ asset("images/slideshow4.jpg") }}') center/cover no-repeat;
        }

        @keyframes slideFade {
            0%        { opacity: 0; transform: scale(1.05); }
            4%        { opacity: 1; }
            21%       { opacity: 1; }
            25%       { opacity: 0; transform: scale(1.0); }
            100%      { opacity: 0; }
        }

        /* Floating shapes for depth */
        .slideshow .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.12;
            animation: floatShape 20s infinite alternate ease-in-out;
        }
        .shape-1 { width: 400px; height: 400px; background: var(--lilac-glow); top: -10%; right: -5%; animation-delay: 0s; }
        .shape-2 { width: 300px; height: 300px; background: var(--gold-accent); bottom: -10%; left: -5%; animation-delay: -7s; }
        .shape-3 { width: 250px; height: 250px; background: #a39fe9; top: 40%; left: 30%; animation-delay: -3s; }

        @keyframes floatShape {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -40px) scale(1.15); }
        }

        /* Overlay for text readability */
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.65) 0%, rgba(255,255,255,0.50) 50%, rgba(255,255,255,0.70) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 220px 20px 100px;
            max-width: 900px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 4.5vw, 3.8rem);
            font-weight: 800;
            color: #0e0c2a;
            line-height: 1.1;
            margin-bottom: 20px;
            letter-spacing: -1.5px;
            animation: slideUp 0.8s ease forwards;
            text-shadow: 0 2px 16px rgba(255,255,255,0.9), 0 1px 3px rgba(255,255,255,1);
        }

        .hero-content h1 span {
            color: var(--primary-navy);
        }

        .hero-content p {
            font-size: 1.15rem;
            color: #2d2a5e;
            max-width: 600px;
            margin: 0 auto 32px;
            animation: slideUp 1s ease forwards;
            opacity: 0;
            transform: translateY(20px);
            line-height: 1.7;
            font-weight: 500;
            text-shadow: 0 1px 8px rgba(255,255,255,0.95);
        }

        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Slide indicators */
        .slide-indicators {
            position: absolute;
            bottom: 36px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            display: flex;
            gap: 10px;
        }
        .slide-indicators .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(36, 34, 92, 0.2);
            animation: dotPulse 20s infinite;
        }
        .slide-indicators .dot:nth-child(1) { animation-delay: 0s; }
        .slide-indicators .dot:nth-child(2) { animation-delay: 5s; }
        .slide-indicators .dot:nth-child(3) { animation-delay: 10s; }
        .slide-indicators .dot:nth-child(4) { animation-delay: 15s; }

        @keyframes dotPulse {
            0%        { background: rgba(36, 34, 92, 0.2); transform: scale(1); }
            3%        { background: var(--primary-navy); transform: scale(1.3); }
            22%       { background: var(--primary-navy); transform: scale(1.3); }
            25%       { background: rgba(36, 34, 92, 0.2); transform: scale(1); }
            100%      { background: rgba(36, 34, 92, 0.2); transform: scale(1); }
        }
    </style>

    <section class="hero-wrapper">
        <div class="slideshow">
            <div class="slide slide-1"></div>
            <div class="slide slide-2"></div>
            <div class="slide slide-3"></div>
            <div class="slide slide-4"></div>
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Empowering the Future, <br><span>One Student at a Time.</span></h1>
            <p>Agnus Dei School Systems, Inc. fuses intellectual integrity with deep character formation, preparing youth across Kinder, JHS, and SHS to lead and excel in the 21st century.</p>
            <div style="display: flex; gap: 20px; justify-content: center; margin-top: 20px; animation: slideUp 1.2s ease forwards; opacity: 0;">
                <a href="/inquiry" class="btn-primary" style="padding: 16px 36px; font-size: 1.1rem;">Enroll for 2026</a>
            </div>
        </div>
        <div class="slide-indicators">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </section>

    <main class="container">

        @if($announcements->count() > 0 || $events->count() > 0)
        <style>
            .announcements-section {
                padding: 80px 0 60px;
            }
            .announcements-section .section-header {
                text-align: center;
                margin-bottom: 40px;
            }
            .announcements-section .section-header h2 {
                font-size: clamp(1.8rem, 3vw, 2.5rem);
                font-weight: 800;
                color: var(--primary-navy);
                margin-bottom: 12px;
                letter-spacing: -0.5px;
            }
            .announcements-section .section-header p {
                font-size: 1.05rem;
                color: var(--text-muted);
                max-width: 500px;
                margin: 0 auto;
            }
            .announcement-row-label {
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--primary-navy);
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .announcement-row-label .badge {
                font-size: 0.7rem;
                font-weight: 600;
                padding: 3px 10px;
                border-radius: var(--radius-full);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .badge-announce { background: rgba(36, 34, 92, 0.08); color: var(--primary-navy); }
            .badge-event { background: rgba(229, 192, 106, 0.2); color: #9A7B2D; }
            .announcement-row {
                display: flex;
                gap: 18px;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 12px;
                margin-bottom: 36px;
            }
            .announcement-row::-webkit-scrollbar { height: 4px; }
            .announcement-row::-webkit-scrollbar-track { background: transparent; }
            .announcement-row::-webkit-scrollbar-thumb { background: rgba(36,34,92,0.12); border-radius: 4px; }
            .announcement-row:last-child { margin-bottom: 0; }
            .announcement-card {
                flex: 0 0 280px;
                min-height: 180px;
                background: var(--surface-white);
                border-radius: 14px;
                padding: 22px 24px;
                border: 1px solid rgba(0,0,0,0.04);
                box-shadow: 0 2px 12px rgba(0,0,0,0.04);
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
                cursor: pointer;
                scroll-snap-align: start;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .announcement-card::before {
                content: '';
                position: absolute;
                top: 0; left: 0;
                width: 4px; height: 100%;
                border-radius: 4px 0 0 4px;
            }
            .announcement-card.announce-type::before { background: var(--primary-navy); }
            .announcement-card.event-type::before { background: var(--gold-accent); }
            .announcement-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            }
            .announcement-card .card-title {
                font-weight: 700;
                font-size: 0.95rem;
                color: var(--text-dark);
                margin-bottom: 6px;
                line-height: 1.3;
            }
            .announcement-card .card-date {
                font-size: 0.78rem;
                color: var(--text-muted);
                margin-bottom: 10px;
            }
            .announcement-card .card-content {
                font-size: 0.85rem;
                color: var(--text-muted);
                line-height: 1.5;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .announcement-card .card-hint {
                font-size: 0.75rem;
                color: var(--lilac-glow);
                font-weight: 600;
                margin-top: 10px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            .announcement-card:hover .card-hint { opacity: 1; }
            .empty-state {
                text-align: center;
                padding: 30px 20px;
                color: var(--text-muted);
                font-size: 0.9rem;
            }
            .empty-row {
                flex: 0 0 280px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Detail modal */
            .detail-modal {
                position: fixed; inset: 0; z-index: 9000;
                display: flex; align-items: center; justify-content: center;
                opacity: 0; pointer-events: none;
                transition: opacity 0.3s ease;
            }
            .detail-modal.open { opacity: 1; pointer-events: auto; }
            .detail-backdrop {
                position: absolute; inset: 0;
                background: rgba(0,0,0,0.25); backdrop-filter: blur(6px);
            }
            .detail-panel {
                position: relative; z-index: 10;
                background: var(--surface-white);
                border-radius: 20px;
                width: 92%; max-width: 520px;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 25px 60px rgba(0,0,0,0.15);
                transform: translateY(20px) scale(0.97);
                transition: transform 0.35s cubic-bezier(0.16,1,0.3,1);
            }
            .detail-modal.open .detail-panel {
                transform: translateY(0) scale(1);
            }
            .detail-accent {
                height: 5px;
                border-radius: 20px 20px 0 0;
            }
            .detail-accent.announce-type { background: linear-gradient(90deg, var(--primary-navy), var(--lilac-glow)); }
            .detail-accent.event-type { background: linear-gradient(90deg, var(--gold-accent), #f0d78c); }
            .detail-body { padding: 28px 32px 32px; }
            .detail-type-badge {
                display: inline-block;
                font-size: 0.7rem;
                font-weight: 600;
                padding: 3px 10px;
                border-radius: var(--radius-full);
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 12px;
            }
            .detail-type-badge.announce-type { background: rgba(36,34,92,0.08); color: var(--primary-navy); }
            .detail-type-badge.event-type { background: rgba(229,192,106,0.2); color: #9A7B2D; }
            .detail-title {
                font-size: 1.4rem;
                font-weight: 800;
                color: var(--primary-navy);
                margin-bottom: 8px;
                line-height: 1.3;
            }
            .detail-date {
                font-size: 0.85rem;
                color: var(--text-muted);
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .detail-content {
                font-size: 0.95rem;
                color: var(--text-dark);
                line-height: 1.7;
                white-space: pre-wrap;
            }
            .detail-close {
                position: absolute; top: 16px; right: 16px;
                background: rgba(0,0,0,0.05); border: none;
                width: 32px; height: 32px; border-radius: 50%;
                font-size: 1.1rem; color: var(--text-muted); cursor: pointer;
                display: flex; align-items: center; justify-content: center;
                transition: all 0.2s ease; z-index: 11;
            }
            .detail-close:hover { background: rgba(0,0,0,0.1); color: var(--text-dark); }
        </style>

        <section class="announcements-section">
            <div class="container">
                <div class="section-header">
                    <h2>Latest Updates</h2>
                    <p>Stay informed with the latest announcements and upcoming events from Agnus Dei.</p>
                </div>

                @if($announcements->count() > 0)
                <div class="announcement-row-label">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-navy);"><path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Announcements
                    <span class="badge badge-announce">{{ $announcements->count() }}</span>
                </div>
                <div class="announcement-row">
                    @foreach($announcements as $item)
                    <div class="announcement-card announce-type"
                         onclick="openDetail(this)"
                         data-type="announcement"
                         data-title="{{ addslashes($item->title) }}"
                         data-date="{{ $item->date?->format('F d, Y') ?? $item->created_at->format('F d, Y') }}"
                         data-content="{{ addslashes($item->content) }}">
                        <div>
                            <div class="card-title">{{ $item->title }}</div>
                            <div class="card-date">{{ $item->date?->format('M d, Y') ?? $item->created_at->format('M d, Y') }}</div>
                            <div class="card-content">{{ $item->content }}</div>
                        </div>
                        <div class="card-hint">Click to read more &rarr;</div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($events->count() > 0)
                <div class="announcement-row-label">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--gold-accent);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Upcoming Events
                    <span class="badge badge-event">{{ $events->count() }}</span>
                </div>
                <div class="announcement-row">
                    @foreach($events as $item)
                    <div class="announcement-card event-type"
                         onclick="openDetail(this)"
                         data-type="event"
                         data-title="{{ addslashes($item->title) }}"
                         data-date="{{ $item->date?->format('F d, Y \a\t g:i A') ?? $item->created_at->format('F d, Y') }}"
                         data-content="{{ addslashes($item->content) }}">
                        <div>
                            <div class="card-title">{{ $item->title }}</div>
                            <div class="card-date">{{ $item->date?->format('M d, Y \a\t g:i A') ?? $item->created_at->format('M d, Y') }}</div>
                            <div class="card-content">{{ $item->content }}</div>
                        </div>
                        <div class="card-hint">Click to read more &rarr;</div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </section>

        <!-- Detail Modal -->
        <div class="detail-modal" id="detail-modal">
            <div class="detail-backdrop" onclick="closeDetail()"></div>
            <div class="detail-panel">
                <button class="detail-close" onclick="closeDetail()">&times;</button>
                <div class="detail-accent" id="detail-accent"></div>
                <div class="detail-body">
                    <span class="detail-type-badge" id="detail-badge"></span>
                    <h3 class="detail-title" id="detail-title"></h3>
                    <div class="detail-date" id="detail-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span id="detail-date-text"></span>
                    </div>
                    <div class="detail-content" id="detail-content"></div>
                </div>
            </div>
        </div>

        <script>
        function openDetail(el) {
            var modal = document.getElementById('detail-modal');
            var type = el.getAttribute('data-type');
            document.getElementById('detail-accent').className = 'detail-accent ' + type + '-type';
            document.getElementById('detail-badge').className = 'detail-type-badge ' + type + '-type';
            document.getElementById('detail-badge').textContent = type === 'event' ? 'Event' : 'Announcement';
            document.getElementById('detail-title').textContent = el.getAttribute('data-title');
            document.getElementById('detail-date-text').textContent = el.getAttribute('data-date');
            document.getElementById('detail-content').textContent = el.getAttribute('data-content');
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeDetail() {
            document.getElementById('detail-modal').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDetail();
        });
        </script>
        @endif
    </main>

@endsection
