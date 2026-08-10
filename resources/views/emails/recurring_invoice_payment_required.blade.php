<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Required for Invoice #{{ $invoiceNumber }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f6f8;
            padding: 40px 0;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .email-header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .email-body {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .message-text {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .invoice-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 28px;
        }
        .invoice-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #cbd5e1;
            font-size: 14px;
        }
        .invoice-row:last-child {
            border-bottom: none;
        }
        .invoice-label {
            color: #64748b;
            font-weight: 500;
        }
        .invoice-value {
            font-weight: 600;
            color: #0f172a;
        }
        .shortfall-value {
            color: #dc2626;
            font-weight: 700;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn-primary {
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <h1>Payment Required</h1>
            </div>
            <div class="email-body">
                <div class="greeting">Hello {{ $userName }},</div>
                <div class="message-text">
                    Your recurring invoice <strong>{{ $invoiceTitle }}</strong> (#{{ $invoiceNumber }}) was generated, but your wallet balance is insufficient for automatic payment.
                </div>
                
                <div class="invoice-card">
                    <div class="invoice-row">
                        <span class="invoice-label">Invoice Number:</span>
                        <span class="invoice-value">#{{ $invoiceNumber }}</span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Amount Due:</span>
                        <span class="invoice-value">{{ $invoiceAmount }}</span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Wallet Balance:</span>
                        <span class="invoice-value">{{ $userBalance }}</span>
                    </div>
                    <div class="invoice-row">
                        <span class="invoice-label">Amount Needed:</span>
                        <span class="shortfall-value">{{ $shortfall }}</span>
                    </div>
                </div>

                <div class="cta-container">
                    <a href="{{ $invoiceUrl }}" class="btn-primary">Pay Invoice Now</a>
                </div>

                <div class="message-text" style="font-size: 13px; color: #64748b; margin-bottom: 0;">
                    Please top up your wallet balance or pay the invoice online to prevent any service interruption.
                </div>
            </div>
            <div class="email-footer">
                Thank you for your business! &bull; Musoftware
            </div>
        </div>
    </div>
</body>
</html>
