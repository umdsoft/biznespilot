<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To'lov qabul qilindi</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #4F46E5;
        }
        .success-icon {
            width: 64px;
            height: 64px;
            margin: 16px auto;
            background: #10B981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        h1 {
            color: #10B981;
            font-size: 24px;
            margin-bottom: 16px;
            text-align: center;
        }
        .details {
            background: #F9FAFB;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .details-row:last-child {
            border-bottom: none;
        }
        .details-label {
            color: #6B7280;
        }
        .details-value {
            font-weight: 600;
            color: #111827;
        }
        .amount {
            font-size: 28px;
            font-weight: bold;
            color: #4F46E5;
            text-align: center;
            margin: 20px 0;
        }
        .cta-button {
            display: block;
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            margin-top: 24px;
        }
        .footer {
            text-align: center;
            margin-top: 32px;
            color: #9CA3AF;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">BiznesPilot</div>
            <div class="success-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: white;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <h1>To'lov muvaffaqiyatli qabul qilindi!</h1>

        <p style="text-align: center; color: #6B7280;">
            Hurmatli {{ $user->name ?? 'Foydalanuvchi' }}, sizning to'lovingiz qabul qilindi.
        </p>

        <div class="amount">
            {{ number_format($transaction->amount, 0, ',', ' ') }} so'm
        </div>

        <div class="details">
            <div class="details-row">
                <span class="details-label">Biznes:</span>
                <span class="details-value">{{ $business->name }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Tarif:</span>
                <span class="details-value">{{ $plan->name }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">To'lov usuli:</span>
                <span class="details-value">{{ ucfirst($transaction->provider) }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Buyurtma raqami:</span>
                <span class="details-value">{{ $transaction->order_id }}</span>
            </div>
            <div class="details-row">
                <span class="details-label">Sana:</span>
                <span class="details-value">{{ $transaction->performed_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i') }}</span>
            </div>
        </div>

        <p style="text-align: center; color: #6B7280;">
            Sizning obunangiz aktivlashtirildi. Endi barcha premium imkoniyatlardan foydalanishingiz mumkin!
        </p>

        <a href="{{ config('app.url') }}/dashboard" class="cta-button">
            Dashboard'ga o'tish
        </a>

        <div class="footer">
            <p>Savollar bo'lsa, biz bilan bog'laning: support@biznespilot.uz</p>
            <p>&copy; {{ date('Y') }} BiznesPilot. Barcha huquqlar himoyalangan.</p>
        </div>
    </div>
</body>
</html>
