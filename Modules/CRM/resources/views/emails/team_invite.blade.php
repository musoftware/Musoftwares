<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #111827; padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; color: #374151; line-height: 1.6; }
        .content p { margin: 0 0 20px; font-size: 16px; }
        .button-wrapper { text-align: center; margin: 30px 0; }
        .button { display: inline-block; padding: 14px 28px; background-color: #3b82f6; color: #ffffff; text-decoration: none; font-weight: 600; border-radius: 6px; font-size: 16px; }
        .footer { background-color: #f3f4f6; padding: 20px 30px; text-align: center; font-size: 14px; color: #6b7280; }
        .footer p { margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('crm.you_are_invited') }}</h1>
        </div>
        <div class="content">
            <p>{{ __('crm.hello') }} <strong>{{ $member->name }}</strong>,</p>
            <p>{{ __('crm.invite_email_body', ['workspace' => $member->workspace->name]) }}</p>
            <p>{{ __('crm.invite_email_instruction') }}</p>
            
            <div class="button-wrapper">
                <a href="{{ $inviteUrl }}" class="button">{{ __('crm.accept_invitation') }}</a>
            </div>

            <p>{{ __('crm.invite_email_expires_warning') }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('crm.all_rights_reserved') }}</p>
        </div>
    </div>
</body>
</html>
