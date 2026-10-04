<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
    <style>
        body { margin: 0; padding: 0; background-color: {{bg_color}}; font-family: 'Inter', -apple-system, system-ui, sans-serif; color: {{text_color}}; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: {{bg_color}}; padding: 30px 0; }
        .container { max-width: 780px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header { background: linear-gradient(135deg, {{primary_color}}, {{secondary_color}}); padding: 30px 25px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; margin: 0; letter-spacing: -0.5px; }
        .header p { color: rgba(255, 255, 255, 0.85); font-size: 14px; margin: 8px 0 0 0; }
        .body { padding: 25px 20px; font-size: 15px; line-height: 1.6; color: #374151; text-align: left; }
        .footer { background-color: #F9FAFB; padding: 30px; text-align: center; border-top: 1px solid #F3F4F6; font-size: 12px; color: #6B7280; }
        .footer a { color: {{primary_color}}; text-decoration: none; font-weight: 600; }
        .badge { margin-top: 20px; font-size: 11px; color: #9CA3AF; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                {{logo_html}}
                <h1>{{site_name}}</h1>
                <p>{{site_slogan}}</p>
            </div>
            <div class="body">
                {{email_content}}
            </div>
            <div class="footer">
                <p>&copy; {{year}} <strong>{{site_name}}</strong>. Tüm hakları saklıdır.</p>
                <div class="badge">
                    <a href="{{app_url}}" target="_blank">{{signature}}</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
