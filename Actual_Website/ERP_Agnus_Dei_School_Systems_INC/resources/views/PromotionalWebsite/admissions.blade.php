@extends('PromotionalWebsite.layout')

@section('content')

    <style>
        .fee-card {
            background: var(--primary-dark); 
            color: var(--surface-off-white);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow-soft);
        }

        .ul-clean {
            list-style: none;
            padding-left: 0;
        }

        .ul-clean li {
            margin-bottom: 15px;
            position: relative;
            padding-left: 25px;
        }
        
        .ul-clean li::before {
            content: '📌';
            position: absolute;
            left: 0;
            top: 2px;
        }

        .badge {
            background: var(--lilac-glow);
            color: var(--primary-dark);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 15px;
        }
    </style>

    <main class="container">
        
        <header class="page-header">
            <h1 class="page-title">Admissions & Payments</h1>
            <p class="page-subtitle">We mandate flexible financial policies and strict admission tracking to ensure quality education remains incredibly accessible.</p>
        </header>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 40px; margin-bottom: 100px;">
            
            <div class="card">
                <div class="badge">REQUIRED DOCUMENTS</div>
                <h3 style="font-size: 2rem;">Admission Checklist</h3>
                <p style="margin-bottom: 20px;">Upload these requirements securely to our Head Registrar via your student portal.</p>
                <ul class="ul-clean" style="color: var(--primary-navy); font-weight: 600;">
                    <li>PSA Certified Birth Certificate</li>
                    <li>Form 138 (Latest Student Report Card)</li>
                    <li>Certificate of Good Moral Character</li>
                    <li>ESC Grant Certificate (For Transferring JHS Grants)</li>
                    <li>QVR Voucher (For incoming Grade 11)</li>
                </ul>
                <a href="/login" class="btn-outline" style="margin-top: 20px;">Start Admission Process</a>
            </div>

            <div class="fee-card">
                <div class="badge" style="background: var(--gold-accent);">FINANCIAL LEDGERS</div>
                <h3 style="font-size: 2rem; color: var(--lilac-glow);">Payment Schemes</h3>
                <p style="color: #a0aec0; margin-bottom: 25px;">Our automated Cashier Module supports dynamic discounting based on honors and internal grants.</p>
                <ul class="ul-clean">
                    <li><strong>Plan A (Full Cash):</strong> Paid exactly upon enrollment. Triggers an automatic <strong>10% Tuition Discount</strong> for the whole year.</li>
                    <li><strong>Plan B (Monthly):</strong> Easily budget with a strict ₱1,500 downpayment, with your balance evenly divided across 10 accessible monthly terms.</li>
                    <li style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 20px; padding-top: 20px;"><strong>The Honors Matrix:</strong> Institutional Scholars</li>
                    <li>🥇 Rank 1 Achievers: 100% Tuition Subsidy</li>
                    <li>🥈 Rank 2 Achievers: 50% Tuition Subsidy (75% for JHS/SHS)</li>
                </ul>
            </div>

        </div>

    </main>

@endsection
