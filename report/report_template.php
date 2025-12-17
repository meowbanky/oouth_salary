<!DOCTYPE html>
<html>
<head>
    <style>
        .table_without {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .table_without th, .table_without td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: right;
        }
        .table_without th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }
        .table_without td:nth-child(1),
        .table_without td:nth-child(2),
        .table_without td:nth-child(3),
        .table_without td:nth-child(4) {
            text-align: left;
        }
        .total-column {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .numeric {
            text-align: right;
            padding-right: 10px;
        }
        .text {
            text-align: left;
            padding-left: 10px;
        }
    </style>
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="../js/theme-manager.js"></script>
</head>
<body>
<table id="sample_1" class="table_without">
    <thead>
    <tr>
        <th>STAFF NO</th>
        <th>NAME</th>
        <th>PAY PERIOD</th>
        <th>DEPT</th>

        <?php foreach ($exporter->getEarnings() as $earning) : ?>
            <th><?= htmlspecialchars($earning) ?></th>
        <?php endforeach; ?>

        <th class="total-column">TOTAL ALLOW</th>

        <?php foreach ($exporter->getDeductions() as $deduction) : ?>
            <th><?= htmlspecialchars($deduction) ?></th>
        <?php endforeach; ?>

        <th class="total-column">TOTAL DEDUC</th>
        <th class="total-column">NET PAY</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($reportData as $staff) : ?>
        <tr>
            <td class="text"><?= htmlspecialchars($staff['staff_id']) ?></td>
            <td class="text"><?= htmlspecialchars($staff['name']) ?></td>
            <td class="text"><?= htmlspecialchars($period_text) ?></td>
            <td class="text"><?= htmlspecialchars($staff['dept']) ?></td>

            <?php foreach ($exporter->getEarnings() as $earning) : ?>
                <td class="numeric"><?= number_format($staff['earnings'][$earning] ?? 0, 2) ?></td>
            <?php endforeach; ?>

            <td class="numeric total-column"><?= number_format($staff['total_allow'], 2) ?></td>

            <?php foreach ($exporter->getDeductions() as $deduction) : ?>
                <td class="numeric"><?= number_format($staff['deductions'][$deduction] ?? 0, 2) ?></td>
            <?php endforeach; ?>

            <td class="numeric total-column"><?= number_format($staff['total_deduc'], 2) ?></td>
            <td class="numeric total-column"><?= number_format($staff['net_pay'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>