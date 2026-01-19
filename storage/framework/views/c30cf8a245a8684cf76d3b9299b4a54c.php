<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jamoaga taklif</title>
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
            background-color: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #3b82f6;
        }
        h1 {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .highlight {
            color: #3b82f6;
            font-weight: 600;
        }
        .info-box {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
        }
        .info-label {
            color: #6b7280;
        }
        .info-value {
            font-weight: 600;
            color: #1f2937;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }
        .button:hover {
            opacity: 0.9;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
        }
        .expire-note {
            background-color: #fef3c7;
            color: #92400e;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">BiznesPilot</div>
        </div>

        <h1>Siz jamoaga taklif qilindingiz!</h1>

        <p>
            <span class="highlight"><?php echo e($inviter->name); ?></span> sizni
            <span class="highlight"><?php echo e($business->name); ?></span> biznes jamoasiga taklif qildi.
        </p>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Biznes:</span>
                <span class="info-value"><?php echo e($business->name); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Bo'lim:</span>
                <span class="info-value"><?php echo e($member->department_label); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Rol:</span>
                <span class="info-value"><?php echo e($member->role_label); ?></span>
            </div>
        </div>

        <p style="text-align: center;">
            <a href="<?php echo e($acceptUrl); ?>" class="button">Taklifni qabul qilish</a>
        </p>

        <div class="expire-note">
            Bu taklif 7 kun ichida amal qiladi. Iltimos, shu muddatda qabul qiling.
        </div>

        <div class="footer">
            <p>Agar siz bu taklifni kutmagan bo'lsangiz, ushbu xatni e'tiborsiz qoldiring.</p>
            <p>&copy; <?php echo e(date('Y')); ?> BiznesPilot. Barcha huquqlar himoyalangan.</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\biznespilot\resources\views\emails\team-invitation.blade.php ENDPATH**/ ?>