<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            padding: 24px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        .header h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }
        .header .business-name {
            opacity: 0.9;
            font-size: 14px;
        }
        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .type-alert { background: #fee2e2; color: #dc2626; }
        .type-kpi { background: #dbeafe; color: #2563eb; }
        .type-task { background: #d1fae5; color: #059669; }
        .type-lead { background: #fef3c7; color: #d97706; }
        .type-report { background: #ede9fe; color: #7c3aed; }
        .type-celebration { background: #fef08a; color: #ca8a04; }
        .type-system { background: #f3f4f6; color: #4b5563; }
        .type-insight { background: #e0e7ff; color: #4338ca; }
        .content {
            padding: 24px;
        }
        .content h2 {
            margin: 0 0 16px;
            color: #1f2937;
            font-size: 20px;
        }
        .content p {
            margin: 0 0 16px;
            color: #4b5563;
        }
        .action-button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 8px;
        }
        .action-button:hover {
            opacity: 0.9;
        }
        .extra-data {
            background: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .extra-data table {
            width: 100%;
            border-collapse: collapse;
        }
        .extra-data td {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .extra-data td:first-child {
            color: #6b7280;
            font-size: 14px;
        }
        .extra-data td:last-child {
            text-align: right;
            font-weight: 600;
            color: #1f2937;
        }
        .extra-data tr:last-child td {
            border-bottom: none;
        }
        .footer {
            padding: 20px 24px;
            text-align: center;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 0;
            font-size: 12px;
            color: #9ca3af;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>BiznesPilot</h1>
                <div class="business-name"><?php echo e($business->name); ?></div>
            </div>

            <div class="content">
                <span class="type-badge type-<?php echo e($type); ?>">
                    <?php switch($type):
                        case ('alert'): ?> Ogohlantirish <?php break; ?>
                        <?php case ('kpi'): ?> KPI <?php break; ?>
                        <?php case ('task'): ?> Vazifa <?php break; ?>
                        <?php case ('lead'): ?> Lid <?php break; ?>
                        <?php case ('report'): ?> Hisobot <?php break; ?>
                        <?php case ('celebration'): ?> Tabriklash <?php break; ?>
                        <?php case ('insight'): ?> Insight <?php break; ?>
                        <?php default: ?> Tizim
                    <?php endswitch; ?>
                </span>

                <h2><?php echo e($title); ?></h2>
                <p><?php echo nl2br(e($message)); ?></p>

                <?php if(!empty($extraData)): ?>
                    <div class="extra-data">
                        <table>
                            <?php $__currentLoopData = $extraData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($key); ?></td>
                                    <td><?php echo e($value); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if($actionUrl): ?>
                    <a href="<?php echo e($actionUrl); ?>" class="action-button">
                        <?php echo e($actionText ?? "Ko'rish"); ?>

                    </a>
                <?php endif; ?>
            </div>

            <div class="footer">
                <p>
                    Bu xabar <?php echo e($business->name); ?> biznesidan yuborildi.<br>
                    <a href="<?php echo e(config('app.url')); ?>/settings/notifications">Bildirishnoma sozlamalari</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\marketing startap\biznespilot\resources\views\emails\business-notification.blade.php ENDPATH**/ ?>