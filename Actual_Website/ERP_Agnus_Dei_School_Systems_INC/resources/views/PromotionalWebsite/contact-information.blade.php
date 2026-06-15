@extends('PromotionalWebsite.layout')

@section('content')
    <main class="container">
        <header class="page-header">
            <h1 class="page-title">Contact Information</h1>
            <p class="page-subtitle">We'd love to hear from you. Reach out to us through any of the channels below.</p>
        </header>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 150px;">
            <div class="card" style="text-align: center;">
                <h3>📍 Address</h3>
                <p>Agnus Dei School Systems, Inc.<br>Contact the school office for the exact location and campus map.</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>📞 Phone</h3>
                <p>School Office: (Contact the administration for the updated contact number)</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>✉️ Email</h3>
                <p>For inquiries, please use our <a href="/inquiry" style="color: var(--primary-navy); font-weight: 600;">Inquiry Form</a> or visit the school office during business hours.</p>
            </div>
        </div>
    </main>
@endsection
