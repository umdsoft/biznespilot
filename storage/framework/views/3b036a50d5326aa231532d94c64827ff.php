<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yangi to'lov - Admin Xabar</title>
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
            background: linear-gradient(135deg, #10B981, #059669);
            margin: -32px -32px 24px;
            padding: 24px 32px;
            border-radius: 12px 12px 0 0;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 12px;
            margin-top: 8px;
        }
        .amount-highlight {
            text-align: center;
            padding: 24px;
            background: #F0FDF4;
            border-radius: 8px;
            margin: 24px 0;
        }
        .amount {
            font-size: 36px;
            font-weight: bold;
            color: #10B981;
        }
        .currency {
            font-size: 16px;
            color: #6B7280;
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
            padding: 10px 0;
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
        .provider-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .provider-payme {
            background: #E0F2FE;
            color: #0369A1;
        }
        .provider-click {
            background: #FEF3C7;
            color: #B45309;
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #E5E7EB;
            color: #9CA3AF;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Yangi to'lov qabul qilindi!</h1>
            <div class="badge">BiznesPilot SaaS</div>
        </div>

        <div class="amount-highlight">
            <div class="amount"><?php echo e(number_format($transaction->amount, 0, ',', ' ')); ?></div>
            <div class="currency">UZS</div>
        </div>

        <div class="details">
            <div class="details-row">
                <span class="details-label">Biznes:</span>
                <span class="details-value"><?php echo e($business->name); ?></span>
            </div>
            <div class="details-row">
                <span class="details-label">Tarif:</span>
                <span class="details-value"><?php echo e($plan->name); ?></span>
            </div>
            <div class="details-row">
                <span class="details-label">To'lov usuli:</span>
                <span class="details-value">
                    <span class="provider-badge <?php echo e($transaction->provider === 'payme' ? 'provider-payme' : 'provider-click'); ?>">
                        <?php echo e(strtoupper($transaction->provider)); ?>

                    </span>
                </span>
            </div>
            <div class="details-row">
                <span class="details-label">Buyurtma ID:</span>
                <span class="details-value"><?php echo e($transaction->order_id); ?></span>
            </div>
            <div class="details-row">
                <span class="details-label">Tranzaksiya ID:</span>
                <span class="details-value"><?php echo e($transaction->provider_transaction_id ?? 'N/A'); ?></span>
            </div>
            <div class="details-row">
                <span class="details-label">Vaqt:</span>
                <span class="details-value"><?php echo e(now()->format('d.m.Y H:i:s')); ?></span>
            </div>
        </div>

        <?php if($business->owner): ?>
        <div class="details">
            <div class="details-row">
                <span class="details-label">Egasi:</span>
                <span class="details-value"><?php echo e($business->owner->name ?? 'N/A'); ?></span>
            </div>
            <div class="details-row">
                <span class="details-label">Email:</span>
                <span class="details-value"><?php echo e($business->owner->email ?? 'N/A'); ?></span>
            </div>
            <div class="details-row">
                <span class="details-label">Telefon:</span>
                <span class="details-value"><?php echo e($business->owner->phone ?? 'N/A'); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Bu xabar avtomatik yuborildi.</p>
            <p>BiznesPilot Admin Panel</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\marketing startap\biznespilot\resources\views\emails\billing\admin-payment-notification.blade.php ENDPATH**/ ?>