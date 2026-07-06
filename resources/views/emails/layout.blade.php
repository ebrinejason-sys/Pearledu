<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#F5FBFD;font-family:'Segoe UI',system-ui,sans-serif;color:#053F5C;line-height:1.6">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F5FBFD;padding:32px 16px">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border:1px solid #D4E8EE;border-radius:16px;overflow:hidden">
          <tr>
            <td style="padding:22px 28px;background:#053F5C;color:#fff">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding-right:12px;vertical-align:middle">
                    @include('emails.partials.logo-sphere', ['height' => 32, 'color' => '#9FE7F5'])
                  </td>
                  <td style="vertical-align:middle">
                    <div style="font-size:18px;font-weight:700;line-height:1.2">{{ config('app.name') }}</div>
                    @isset($eyebrow)
                      <div style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#9FE7F5;margin-top:6px">{{ $eyebrow }}</div>
                    @endisset
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:28px">
              @yield('body')
            </td>
          </tr>
          <tr>
            <td style="padding:16px 28px;background:#F5FBFD;border-top:1px solid #D4E8EE;font-size:12px;color:#4A6270">
              @yield('footer', 'This message was sent by '.config('app.name').'. If you did not request it, you can ignore this email.')
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
