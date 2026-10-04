<!DOCTYPE html>
<html>
<head>
    <title>Monthly Attendance Report</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <p>Dear {{ $employee->full_name }},</p>

    <p>Please find attached your official attendance and performance report for <strong>{{ str_replace('_', ' ', $monthName) }}</strong>.</p>

    <p>If you have any questions or notice any discrepancies, please contact the HR department as soon as possible.</p>

    <br>
    <p>Best regards,<br>
    <strong>Human Resources Department</strong><br>
    {{ \App\Models\Setting::getValue('company_name', 'Profitix HRM System') }}</p>
</body>
</html>
