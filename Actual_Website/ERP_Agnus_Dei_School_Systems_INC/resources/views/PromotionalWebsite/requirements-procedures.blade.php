@extends('PromotionalWebsite.layout')

@section('content')
    <main class="container">
        <header class="page-header">
            <h1 class="page-title">Requirements & Procedures</h1>
            <p class="page-subtitle">A straightforward guide to enrolling at Agnus Dei School Systems.</p>
        </header>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 40px; margin-bottom: 150px;">
            <div class="card">
                <h3>📋 Enrollment Requirements</h3>
                <ul style="list-style: none; padding-left: 0; color: var(--text-muted);">
                    <li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">✅ PSA Certified Birth Certificate</li>
                    <li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">✅ Form 138 (Latest Report Card)</li>
                    <li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">✅ Certificate of Good Moral Character</li>
                    <li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">✅ ESC Grant Certificate (if applicable)</li>
                    <li>✅ QVR Voucher (for incoming Grade 11)</li>
                </ul>
            </div>
            <div class="card">
                <h3>📝 Enrollment Procedure</h3>
                <ol style="color: var(--text-muted); padding-left: 20px;">
                    <li style="margin-bottom: 12px;">Submit an inquiry or visit the school office.</li>
                    <li style="margin-bottom: 12px;">Receive your institutional email and credentials.</li>
                    <li style="margin-bottom: 12px;">Log in to the student portal and complete the requirements upload.</li>
                    <li style="margin-bottom: 12px;">Wait for the Registrar's approval and assessment.</li>
                    <li style="margin-bottom: 12px;">Proceed to the Cashier for payment and secure your slot.</li>
                </ol>
            </div>
        </div>
    </main>
@endsection
