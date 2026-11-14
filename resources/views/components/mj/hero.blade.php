@props([
    'backgroundUrl' => '',
    'backgroundColor' => '#2a3448',
    'padding' => '100px 0px',
    'borderRadius' => '0px'
])

<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; border-radius: {{ $borderRadius }};">
  <tbody>
    <tr>
      <td style="direction:ltr;font-size:0px;padding:0px;text-align:center;">
        <!--[if mso | IE]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;" width="600">
          <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
              <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;">
                <v:fill type="frame" src="{{ $backgroundUrl }}" color="{{ $backgroundColor }}" />
                <v:textbox style="mso-fit-shape-to-text:true" inset="0,0,0,0">
        <![endif]-->
        <div style="background:{{ $backgroundColor }} url('{{ $backgroundUrl }}') no-repeat center center / cover; margin:0px auto; max-width:600px; border-radius: {{ $borderRadius }};">
          <div style="line-height:0;font-size:0;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; border-radius: {{ $borderRadius }};">
              <tbody>
                <tr>
                  <td style="direction:ltr;font-size:0px;padding:{{ $padding }};text-align:center;vertical-align:middle;">
                    {{ $slot }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!--[if mso | IE]>
                </v:textbox>
              </v:rect>
            </td>
          </tr>
        </table>
        <![endif]-->
      </td>
    </tr>
  </tbody>
</table>
