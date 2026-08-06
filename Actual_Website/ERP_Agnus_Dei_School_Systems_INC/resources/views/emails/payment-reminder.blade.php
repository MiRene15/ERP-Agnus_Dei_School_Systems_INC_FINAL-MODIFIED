<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a365d; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .amount { font-size: 24px; font-weight: bold; color: #dc2626; text-align: center; margin: 20px 0; }
        .footer { text-align: center; padding: 15px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Agnus Dei School — Payment Reminder</h2>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>,</p>

            <p>This is a gentle reminder that you have an outstanding balance for the school year <strong>{{ $schoolYear }}</strong>.</p>

            <div class="amount">
                ₱ {{ number_format($balance, 2) }}
            </div>

            <p>Please visit the school's Cashier's Office to settle your balance. You may also inquire about installment options if needed.</p>

            <p>If you have already made a payment, please disregard this message.</p>

            <p>Thank you,<br><strong>Agnus Dei School — Cashier's Office</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated reminder. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
