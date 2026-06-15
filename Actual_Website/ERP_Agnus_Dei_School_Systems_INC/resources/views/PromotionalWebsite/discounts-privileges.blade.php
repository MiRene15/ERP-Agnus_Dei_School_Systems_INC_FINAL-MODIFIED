@extends('PromotionalWebsite.layout')

@section('content')
    <main class="container">
        <header class="page-header">
            <h1 class="page-title">Discounts & Privileges</h1>
            <p class="page-subtitle">We make quality education accessible through our flexible financial programs.</p>
        </header>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 40px; margin-bottom: 150px;">
            <div class="card">
                <h3>💰 Payment Plans</h3>
                <ul style="list-style: none; padding-left: 0; color: var(--text-muted);">
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;"><strong>Plan A (Full Cash):</strong> 10% Tuition Discount for the whole year.</li>
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;"><strong>Plan B (Monthly):</strong> ₱1,500 downpayment, balance divided into 10 months.</li>
                    <li><strong>Plan C:</strong> Custom arrangements with the Cashier's Office.</li>
                </ul>
            </div>
            <div class="card" style="background: var(--primary-dark); color: var(--surface-off-white);">
                <h3 style="color: var(--lilac-glow);">🏆 Honors Scholarship Matrix</h3>
                <ul style="list-style: none; padding-left: 0;">
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1);">🥇 <strong>Rank 1:</strong> 100% Tuition Subsidy</li>
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1);">🥈 <strong>Rank 2:</strong> 50% Tuition Subsidy (75% for JHS/SHS)</li>
                    <li>🎓 <strong>Sibling Discount:</strong> Available for families with multiple enrollees.</li>
                </ul>
            </div>
        </div>
    </main>
@endsection
