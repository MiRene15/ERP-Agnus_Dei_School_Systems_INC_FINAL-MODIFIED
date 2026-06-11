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

        .tab-btn {
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 5px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            font-family: var(--font-main);
            color: var(--text-muted);
            transition: color 0.2s, border-color 0.2s;
        }

        .tab-btn.active {
            color: var(--primary-navy);
            font-weight: 700;
            border-bottom-color: var(--primary-navy);
        }

        .tab-btn:not(.active):hover {
            color: var(--primary-navy);
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .announcement-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        .announcement-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .date-badge {
            padding: 12px;
            border-radius: 12px;
            min-width: 70px;
            text-align: center;
            flex-shrink: 0;
        }

        .date-badge span:first-child {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .date-badge span:last-child {
            display: block;
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .badge-announcement {
            background: rgba(163, 159, 233, 0.15);
            color: var(--primary-navy);
        }

        .badge-event {
            background: rgba(229, 192, 106, 0.15);
            color: #B38F3B;
        }

        .empty-state {
            text-align: center;
            padding: 30px 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
    </style>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero" style="min-height: 65vh; padding-bottom: 60px;">
            <h1>Empowering the Future, <br><span>One Student at a Time.</span></h1>
            <p>Agnus Dei School Systems, Inc. fuses intellectual integrity with deep character formation, preparing youth across Kinder, JHS, and SHS to lead and excel in the 21st century.</p>
            <div style="display: flex; gap: 20px; justify-content: center; margin-top: 20px; animation: slideUp 1.2s ease forwards; opacity: 0;">
                <a href="/inquiry" class="btn-primary" style="padding: 16px 36px; font-size: 1.1rem;">Enroll Now</a>
            </div>
        </section>

        <!-- Announcements Tab Section -->
        <section class="announcements-tab-section" style="max-width: 800px; margin: 0 auto 100px; animation: slideUp 1.5s ease forwards; opacity: 0;">
            <div class="card" style="padding: 30px;">

                <!-- Tab Buttons -->
                <div style="display: flex; gap: 20px; border-bottom: 2px solid #f1f5f9; margin-bottom: 25px;">
                    <button id="tab-btn-announcements" class="tab-btn active" onclick="switchTab('announcements')">
                        Latest Announcements
                    </button>
                    <button id="tab-btn-events" class="tab-btn" onclick="switchTab('events')">
                        Upcoming Events
                    </button>
                </div>

                <!-- Announcements Panel -->
                <div id="tab-panel-announcements" class="tab-panel active">
                    @forelse($announcements as $item)
                        <div class="announcement-item">
                            <div class="date-badge badge-announcement">
                                <span>{{ $item->date->format('M') }}</span>
                                <span>{{ $item->date->format('d') }}</span>
                            </div>
                            <div>
                                <h4 style="color: var(--primary-dark); font-size: 1.15rem; margin-bottom: 6px;">
                                    {{ $item->title }}
                                </h4>
                                <p style="font-size: 0.95rem; color: var(--text-muted); margin: 0;">
                                    {{ $item->content }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">No announcements at this time. Check back soon!</div>
                    @endforelse
                </div>

                <!-- Events Panel -->
                <div id="tab-panel-events" class="tab-panel">
                    @forelse($events as $item)
                        <div class="announcement-item">
                            <div class="date-badge badge-event">
                                <span>{{ $item->date->format('M') }}</span>
                                <span>{{ $item->date->format('d') }}</span>
                            </div>
                            <div>
                                <h4 style="color: var(--primary-dark); font-size: 1.15rem; margin-bottom: 6px;">
                                    {{ $item->title }}
                                </h4>
                                <p style="font-size: 0.95rem; color: var(--text-muted); margin: 0;">
                                    {{ $item->content }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">No upcoming events scheduled. Check back soon!</div>
                    @endforelse
                </div>

            </div>
        </section>
    </main>

    <script>
        function switchTab(tab) {
            // Hide all panels and deactivate all buttons
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

            // Activate the selected panel and button
            document.getElementById('tab-panel-' + tab).classList.add('active');
            document.getElementById('tab-btn-' + tab).classList.add('active');
        }
    </script>

@endsection
