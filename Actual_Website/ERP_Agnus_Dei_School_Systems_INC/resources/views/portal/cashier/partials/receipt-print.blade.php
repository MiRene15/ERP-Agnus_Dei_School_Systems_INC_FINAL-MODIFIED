<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt — {{ $payment->receipt_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 5mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 11px; color: #1a1a1a; background: #fff; }
        .receipt { width: 72mm; margin: 0 auto; padding: 0; }
        .header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 8px; margin-bottom: 8px; }
        .logo { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; margin: 0 auto 4px; display: block; border: 2px solid #24225C; }
        .school-name { font-size: 13px; font-weight: 700; color: #24225C; letter-spacing: 0.5px; }
        .school-sub { font-size: 8px; color: #666; margin-top: 1px; line-height: 1.2; }
        .receipt-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #24225C; margin-top: 6px; }
        .receipt-no { font-size: 9px; font-weight: 700; text-align: center; margin: 6px 0; padding: 3px; background: #f0f0f0; border: 1px dashed #999; }
        .info { margin-bottom: 6px; }
        .info-row { display: flex; justify-content: space-between; font-size: 9px; padding: 1.5px 0; line-height: 1.4; }
        .info-row .label { color: #666; }
        .info-row .value { font-weight: 600; color: #1a1a1a; text-align: right; max-width: 55%; }
        .divider { border-top: 1px dashed #ccc; margin: 6px 0; }
        .amount-box { background: #f8f8f8; padding: 6px; border: 1px solid #e0e0e0; margin: 6px 0; }
        .amount-row { display: flex; justify-content: space-between; font-size: 9px; padding: 1.5px 0; }
        .amount-row.paid { font-size: 12px; font-weight: 700; border-top: 1px solid #999; padding-top: 4px; margin-top: 4px; }
        .amount-row.balance { font-weight: 700; padding-top: 3px; margin-top: 3px; border-top: 1px dashed #ccc; }
        .footer { text-align: center; margin-top: 8px; font-size: 7.5px; color: #999; line-height: 1.4; border-top: 2px dashed #333; padding-top: 6px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 14px; font-size: 8px; }
        .sig-block { text-align: center; width: 45%; }
        .sig-line { border-top: 1px solid #333; margin-top: 28px; padding-top: 3px; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .receipt { width: 72mm; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <img src="{{ asset('images/agnus_logo.png') }}" alt="Logo" class="logo" onerror="this.style.display='none'">
            <div class="school-name">AGNUS DEI</div>
            <div class="school-sub">Agnus Dei School Systems, Inc.</div>
            <div class="receipt-title">Official Receipt</div>
        </div>

        <div class="receipt-no">{{ $payment->receipt_number }}</div>

        <div class="info">
            <div class="info-row">
                <span class="label">Date Paid</span>
                <span class="value">{{ $payment->payment_date->format('M d, Y') }}</span>
            </div>
            <div class="info-row">
                <span class="label">AR No.</span>
                <span class="value">{{ $payment->ar_number ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Student</span>
                <span class="value">{{ $student->first_name }} {{ $student->last_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Grade/Section</span>
                <span class="value">{{ $enrollment?->section?->grade_level ?? 'N/A' }} — {{ $enrollment?->section?->section_name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">SY</span>
                <span class="value">{{ $enrollment?->school_year ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Plan</span>
                <span class="value">{{ ucfirst($payment->ledger->payment_plan ?? 'N/A') }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <div class="amount-box">
            <div class="amount-row">
                <span>Total Assessed</span>
                <span>₱{{ number_format($payment->ledger->total_assessed, 2) }}</span>
            </div>
            @if($payment->ledger->discount_applied > 0)
            <div class="amount-row" style="color: #059669;">
                <span>Discount ({{ ucfirst($payment->ledger->discount_type ?? '') }})</span>
                <span>-₱{{ number_format($payment->ledger->discount_applied, 2) }}</span>
            </div>
            @endif
            @if($previousPayments - $payment->amount_paid > 0)
            <div class="amount-row">
                <span>Previous Payments</span>
                <span>₱{{ number_format($previousPayments - $payment->amount_paid, 2) }}</span>
            </div>
            @endif
            <div class="amount-row paid">
                <span>AMOUNT PAID</span>
                <span>₱{{ number_format($payment->amount_paid, 2) }}</span>
            </div>
            <div class="amount-row balance" style="color: {{ $balanceAfter > 0 ? '#dc2626' : '#059669' }};">
                <span>Balance</span>
                <span>₱{{ number_format($balanceAfter, 2) }}</span>
            </div>
        </div>

        <div class="info">
            <div class="info-row">
                <span class="label">Cashier</span>
                <span class="value">{{ $payment->cashier?->name ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer-generated receipt.</p>
            <p>Thank you for your payment.</p>
        </div>

        <div class="signatures">
            <div class="sig-block">
                <div class="sig-line">Cashier</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">Parent/Guardian</div>
            </div>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 16px;">
            <button onclick="window.print()" style="padding: 8px 24px; background: #24225C; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">Print Receipt</button>
            <button onclick="window.close()" style="padding: 8px 24px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; margin-left: 8px;">Close</button>
        </div>
    </div>
</body>
</html>
