<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a365d; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .amount { font-size: 24px; font-weight: bold; color: #16a34a; text-align: center; margin: 20px 0; }
        .details { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 16px 0; }
        .details table { width: 100%; }
        .details td { padding: 4px 0; }
        .details td:first-child { color: #6b7280; width: 40%; }
        .details td:last-child { font-weight: 600; text-align: right; }
        .footer { text-align: center; padding: 15px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Agnus Dei School — Payment Confirmation</h2>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $payment->ledger->student->first_name }} {{ $payment->ledger->student->last_name }}</strong>,</p>

            <p>We have received your payment. Here are the details:</p>

            <div class="amount">
                ₱ {{ number_format($payment->amount_paid, 2) }}
            </div>

            <div class="details">
                <table>
                    <tr>
                        <td>Receipt Number</td>
                        <td>{{ $payment->receipt_number }}</td>
                    </tr>
                    @if($payment->ar_number)
                    <tr>
                        <td>AR Number</td>
                        <td>{{ $payment->ar_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Payment Date</td>
                        <td>{{ $payment->payment_date->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td>Remaining Balance</td>
                        <td>₱ {{ number_format($payment->ledger->balance, 2) }}</td>
                    </tr>
                </table>
            </div>

            <p>Thank you for your payment. Please keep this confirmation for your records.</p>

            <p>Thank you,<br><strong>Agnus Dei School — Cashier's Office</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
