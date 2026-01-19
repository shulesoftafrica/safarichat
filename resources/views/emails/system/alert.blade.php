<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Alert: {{ $alert_type }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background-color: #dc3545; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .alert-icon { font-size: 24px; margin-right: 10px; }
        .details { background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .timestamp { color: #666; font-size: 14px; margin-top: 15px; }
        .footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="alert-icon">🚨</span>
            <strong>System Alert: {{ $alert_type }}</strong>
        </div>
        
        <p>Hello {{ $admin->name ?? 'Administrator' }},</p>
        
        <p>A system alert has been triggered and requires your attention.</p>
        
        <div class="details">
            <strong>Alert Details:</strong>
            <ul>
                @if(isset($alert_data) && is_array($alert_data))
                    @foreach($alert_data as $key => $value)
                        @if(is_string($value) || is_numeric($value))
                            <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                        @endif
                    @endforeach
                @else
                    {{-- Fallback: show all available data except known system variables --}}
                    @php
                        $systemVars = ['admin', 'alert_type', 'timestamp'];
                        $displayData = collect(get_defined_vars())->except($systemVars);
                    @endphp
                    @foreach($displayData as $key => $value)
                        @if((is_string($value) || is_numeric($value)) && !in_array($key, $systemVars))
                            <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
        
        <p>Please review the system and take appropriate action if necessary.</p>
        
        <p>
            <a href="{{ config('app.url') }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                Access System Dashboard
            </a>
        </p>
        
        <div class="timestamp">
            Alert generated at: {{ $timestamp->format('Y-m-d H:i:s T') }}
        </div>
        
        <div class="footer">
            This is an automated system alert from SafariChat.<br>
            If you believe this alert was sent in error, please contact system support.
        </div>
    </div>
</body>
</html>