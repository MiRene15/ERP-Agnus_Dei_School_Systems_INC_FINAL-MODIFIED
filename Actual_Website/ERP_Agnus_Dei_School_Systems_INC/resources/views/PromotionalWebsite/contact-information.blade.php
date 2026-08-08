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
                <p>Agnus Dei School Systems, Inc.<br>#280, Quezon St., Cuyab,<br>San Pedro, Laguna</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>📞 Phone</h3>
                <p>
                    <strong>Landline:</strong> 02-8-478-9906<br>
                    <strong>Mobile:</strong> 0939 443 3684<br>
                    <strong>Mobile:</strong> 0991 583 0428
                </p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>🌐 Social Media</h3>
                <p>
                    Follow us on Facebook for the latest news and updates.<br>
                    <a href="https://www.facebook.com/p/Agnus-Dei-School-Systems-Inc-100095457494756/" target="_blank" rel="noopener" style="color: var(--primary-navy); font-weight: 600; text-decoration: none;">Agnus Dei School Systems, Inc.</a>
                </p>
            </div>
            <div class="card" style="text-align: center;">
                <h3>✉️ Inquiries</h3>
                <p>For inquiries, please use our <a href="/inquiry" style="color: var(--primary-navy); font-weight: 600;">Inquiry Form</a> or call us directly during business hours.</p>
            </div>
        </div>
    </main>
@endsection
