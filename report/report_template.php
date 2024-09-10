<!DOCTYPE html>
<html>

<head>
</head>

<body>
    <table id="sample_1" class="table_without">
        <thead>
            <tr>
                <th>STAFF NO</th>
                <th>NAME</th>
                <th>PAY PERIOD</th>
                <th>DEPT</th>
                <?php foreach (array_keys($reportData[0]['earnings']) as $earning) : ?>
                    <th><?= $earning ?></th>
                <?php endforeach; ?>
                <th>TOTAL ALLOW</th>
                <?php foreach (array_keys($reportData[0]['deductions']) as $deduction) : ?>
                    <th><?= $deduction ?></th>
                <?php endforeach; ?>
                <th>TOTAL DEDUC</th>
                <th>NET PAY</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportData as $staff) : ?>
                <tr>
                    <td><?= $staff['staff_id'] ?></td>
                    <td><?= $staff['name'] ?></td>
                    <td><?= $staff['period'] ?></td>
                    <td><?= $staff['dept'] ?></td>
                    <?php foreach ($staff['earnings'] as $earning) : ?>
                        <td><?= number_format($earning) ?></td>
                    <?php endforeach; ?>
                    <td><?= number_format($staff['total_allow']) ?></td>
                    <?php foreach ($staff['deductions'] as $deduction) : ?>
                        <td><?= number_format($deduction) ?></td>
                    <?php endforeach; ?>
                    <td><?= number_format($staff['total_deduc']) ?></td>
                    <td><?= number_format($staff['net_pay']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>