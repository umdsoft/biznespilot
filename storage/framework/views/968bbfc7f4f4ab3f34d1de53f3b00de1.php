<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haftalik Hisobot - <?php echo e($business->name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1f2937;
            background: #fff;
        }

        .container {
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .header .week-label {
            font-size: 12px;
            color: #3b82f6;
            margin-top: 5px;
        }

        /* Summary Cards */
        .summary-section {
            margin-bottom: 20px;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .summary-row {
            display: table-row;
        }

        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .summary-card .label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .summary-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
        }

        .summary-card .change {
            font-size: 9px;
            margin-top: 2px;
        }

        .positive { color: #059669; }
        .negative { color: #dc2626; }
        .neutral { color: #6b7280; }

        /* Section titles */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin: 15px 0 10px 0;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th, td {
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        /* AI Section */
        .ai-section {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }

        .ai-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .ai-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
        }

        .ai-score {
            display: inline-block;
            background: #3b82f6;
            color: #fff;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 10px;
        }

        .ai-assessment {
            font-size: 11px;
            color: #374151;
            margin-bottom: 10px;
            padding: 8px;
            background: #fff;
            border-radius: 3px;
        }

        .ai-list {
            margin: 8px 0;
        }

        .ai-list-title {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }

        .ai-list ul {
            margin-left: 15px;
            font-size: 10px;
        }

        .ai-list li {
            margin-bottom: 3px;
        }

        .ai-goal {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 8px;
            border-radius: 3px;
            margin-top: 10px;
        }

        .ai-goal-title {
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
        }

        .ai-goal-text {
            font-size: 11px;
            color: #78350f;
            margin-top: 3px;
        }

        /* Two column layout */
        .two-column {
            display: table;
            width: 100%;
        }

        .column {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }

        .column:first-child {
            padding-right: 10px;
        }

        .column:last-child {
            padding-left: 10px;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 9px;
        }

        /* Progress bar */
        .progress-bar {
            background: #e5e7eb;
            border-radius: 10px;
            height: 8px;
            width: 100%;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
        }

        .progress-blue { background: #3b82f6; }
        .progress-green { background: #10b981; }
        .progress-red { background: #ef4444; }
        .progress-yellow { background: #f59e0b; }

        /* Stats box */
        .stats-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 10px;
        }

        .stats-label {
            color: #6b7280;
        }

        .stats-value {
            font-weight: bold;
            color: #1f2937;
        }

        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?php echo e($business->name); ?></h1>
            <div class="subtitle">Haftalik Analitika Hisoboti</div>
            <div class="week-label"><?php echo e($analytics->week_label); ?></div>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-card">
                        <div class="label">Jami Lidlar</div>
                        <div class="value"><?php echo e($summary['total_leads']); ?></div>
                        <?php if(isset($vs_last_week['leads'])): ?>
                            <div class="change <?php echo e(App\Services\WeeklyAnalyticsPdfService::getChangeClass(floatval($vs_last_week['leads']))); ?>">
                                <?php echo e($vs_last_week['leads']); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="summary-card">
                        <div class="label">Yutilgan</div>
                        <div class="value" style="color: #059669;"><?php echo e($summary['won']); ?></div>
                        <?php if(isset($vs_last_week['won'])): ?>
                            <div class="change <?php echo e(App\Services\WeeklyAnalyticsPdfService::getChangeClass(floatval($vs_last_week['won']))); ?>">
                                <?php echo e($vs_last_week['won']); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="summary-card">
                        <div class="label">Yo'qotilgan</div>
                        <div class="value" style="color: #dc2626;"><?php echo e($summary['lost']); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Konversiya</div>
                        <div class="value"><?php echo e($summary['conversion_rate']); ?>%</div>
                        <?php if(isset($vs_last_week['conversion'])): ?>
                            <div class="change <?php echo e(App\Services\WeeklyAnalyticsPdfService::getChangeClass($vs_last_week['conversion'])); ?>">
                                <?php echo e(App\Services\WeeklyAnalyticsPdfService::formatChange($vs_last_week['conversion'])); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-card">
                        <div class="label">Daromad</div>
                        <div class="value"><?php echo e(App\Services\WeeklyAnalyticsPdfService::formatMoney($summary['total_revenue'])); ?></div>
                        <?php if(isset($vs_last_week['revenue'])): ?>
                            <div class="change <?php echo e(App\Services\WeeklyAnalyticsPdfService::getChangeClass(floatval($vs_last_week['revenue']))); ?>">
                                <?php echo e($vs_last_week['revenue']); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="summary-card">
                        <div class="label">Yo'qotilgan Daromad</div>
                        <div class="value" style="color: #dc2626;"><?php echo e(App\Services\WeeklyAnalyticsPdfService::formatMoney($summary['lost_revenue'])); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Pipeline Qiymati</div>
                        <div class="value"><?php echo e(App\Services\WeeklyAnalyticsPdfService::formatMoney($summary['pipeline_value'])); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="label">O'rtacha Bitim</div>
                        <div class="value"><?php echo e(App\Services\WeeklyAnalyticsPdfService::formatMoney($summary['avg_deal_value'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Analysis Section -->
        <?php if($ai['has_analysis']): ?>
        <div class="ai-section">
            <div class="ai-header">
                <span class="ai-title"><?php echo e($ai['emoji'] ?? ''); ?> AI Tahlil</span>
                <?php if($ai['score']): ?>
                    <span class="ai-score"><?php echo e($ai['score']); ?>/100</span>
                <?php endif; ?>
            </div>

            <?php if($ai['overall_assessment']): ?>
                <div class="ai-assessment">
                    <?php echo e($ai['overall_assessment']); ?>

                </div>
            <?php endif; ?>

            <div class="two-column">
                <div class="column">
                    <?php if(!empty($ai['good_results'])): ?>
                        <div class="ai-list">
                            <div class="ai-list-title">Yaxshi natijalar:</div>
                            <ul>
                                <?php $__currentLoopData = $ai['good_results']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($item); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="column">
                    <?php if(!empty($ai['problems'])): ?>
                        <div class="ai-list">
                            <div class="ai-list-title">Muammolar:</div>
                            <ul>
                                <?php $__currentLoopData = $ai['problems']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($item); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!empty($ai['recommendations'])): ?>
                <div class="ai-list">
                    <div class="ai-list-title">Tavsiyalar:</div>
                    <ul>
                        <?php $__currentLoopData = $ai['recommendations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($item); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if($ai['next_week_goal']): ?>
                <div class="ai-goal">
                    <div class="ai-goal-title">Keyingi hafta maqsadi:</div>
                    <div class="ai-goal-text"><?php echo e($ai['next_week_goal']); ?></div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Channel Stats -->
        <?php if(!empty($channels)): ?>
        <div class="section-title">Kanallar bo'yicha statistika</div>
        <table>
            <thead>
                <tr>
                    <th>Kanal</th>
                    <th>Lidlar</th>
                    <th>Yutilgan</th>
                    <th>Konversiya</th>
                    <th>Daromad</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $channel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($channel['name'] ?? '-'); ?></td>
                    <td><?php echo e($channel['leads'] ?? 0); ?></td>
                    <td><?php echo e($channel['won'] ?? 0); ?></td>
                    <td><?php echo e($channel['conversion'] ?? 0); ?>%</td>
                    <td><?php echo e(App\Services\WeeklyAnalyticsPdfService::formatMoney($channel['revenue'] ?? 0)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Operators Stats -->
        <?php if(!empty($operators)): ?>
        <div class="section-title">Operatorlar bo'yicha statistika</div>
        <table>
            <thead>
                <tr>
                    <th>Operator</th>
                    <th>Lidlar</th>
                    <th>Yutilgan</th>
                    <th>Yo'qotilgan</th>
                    <th>Konversiya</th>
                    <th>Daromad</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operator): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($operator['name'] ?? '-'); ?></td>
                    <td><?php echo e($operator['leads'] ?? 0); ?></td>
                    <td><?php echo e($operator['won'] ?? 0); ?></td>
                    <td><?php echo e($operator['lost'] ?? 0); ?></td>
                    <td><?php echo e($operator['conversion'] ?? 0); ?>%</td>
                    <td><?php echo e(App\Services\WeeklyAnalyticsPdfService::formatMoney($operator['revenue'] ?? 0)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Lost Reasons -->
        <?php if(!empty($lost_reasons['reasons'])): ?>
        <div class="section-title">Yo'qotish sabablari</div>
        <table>
            <thead>
                <tr>
                    <th>Sabab</th>
                    <th>Soni</th>
                    <th>Yo'qotilgan summa</th>
                    <th>Ulushi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $lost_reasons['reasons']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($reason['reason'] ?? 'Noma\'lum'); ?></td>
                    <td><?php echo e($reason['count'] ?? 0); ?></td>
                    <td><?php echo e(App\Services\WeeklyAnalyticsPdfService::formatMoney($reason['value'] ?? 0)); ?></td>
                    <td><?php echo e($reason['percentage'] ?? 0); ?>%</td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Qualification Stats -->
        <?php if(!empty($qualification)): ?>
        <div class="section-title">Kvalifikatsiya statistikasi</div>
        <div class="stats-box">
            <div class="stats-row">
                <span class="stats-label">MQL (Marketing Qualified Leads):</span>
                <span class="stats-value"><?php echo e($qualification['mql'] ?? 0); ?></span>
            </div>
            <div class="stats-row">
                <span class="stats-label">SQL (Sales Qualified Leads):</span>
                <span class="stats-value"><?php echo e($qualification['sql'] ?? 0); ?></span>
            </div>
            <div class="stats-row">
                <span class="stats-label">MQL -> SQL konversiya:</span>
                <span class="stats-value"><?php echo e($qualification['mql_to_sql'] ?? 0); ?>%</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Call Stats -->
        <?php if(!empty($calls)): ?>
        <div class="section-title">Qo'ng'iroqlar statistikasi</div>
        <div class="stats-box">
            <div class="stats-row">
                <span class="stats-label">Jami qo'ng'iroqlar:</span>
                <span class="stats-value"><?php echo e($calls['total'] ?? 0); ?></span>
            </div>
            <div class="stats-row">
                <span class="stats-label">Muvaffaqiyatli:</span>
                <span class="stats-value"><?php echo e($calls['successful'] ?? 0); ?></span>
            </div>
            <div class="stats-row">
                <span class="stats-label">O'rtacha davomiylik:</span>
                <span class="stats-value"><?php echo e($calls['avg_duration'] ?? '0:00'); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Task Stats -->
        <?php if(!empty($tasks)): ?>
        <div class="section-title">Vazifalar statistikasi</div>
        <div class="stats-box">
            <div class="stats-row">
                <span class="stats-label">Jami vazifalar:</span>
                <span class="stats-value"><?php echo e($tasks['total'] ?? 0); ?></span>
            </div>
            <div class="stats-row">
                <span class="stats-label">Bajarilgan:</span>
                <span class="stats-value"><?php echo e($tasks['completed'] ?? 0); ?></span>
            </div>
            <div class="stats-row">
                <span class="stats-label">Muddati o'tgan:</span>
                <span class="stats-value"><?php echo e($tasks['overdue'] ?? 0); ?></span>
            </div>
            <div class="stats-row">
                <span class="stats-label">Bajarilish darajasi:</span>
                <span class="stats-value"><?php echo e($tasks['completion_rate'] ?? 0); ?>%</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>Bu hisobot BiznesPilot tomonidan avtomatik yaratilgan</p>
            <p>Yaratilgan: <?php echo e($generated_at); ?></p>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\marketing startap\biznespilot\resources\views\pdf\weekly-analytics.blade.php ENDPATH**/ ?>