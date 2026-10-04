<!DOCTYPE html>
<html>
<head>
    <title>Your License Key</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #0056b3;">Hello {{ $license->client_name }},</h2>
        <p>Thank you for choosing Profitix HRM.</p>
        <p>Your license key has been generated or renewed. Please use the key below to activate your system:</p>
        
        <div style="background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 5px; font-size: 20px; font-weight: bold; letter-spacing: 2px; margin: 20px 0;">
            {{ $license->license_key }}
        </div>
        
        <p><strong>Expiry Date:</strong> {{ $license->expires_at->format('M d, Y') }}</p>
        <p><strong>Max Devices/Employees allowed:</strong> {{ $license->max_employees }}</p>

        <p>To activate, log in to your Profitix System as an administrator, go to System Settings or the Activation page, and paste the key.</p>
        
        <p>Best Regards,<br><strong>Profitix Support Team</strong></p>
    </div>
</body>
</html>
