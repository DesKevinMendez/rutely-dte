@props(['backgroundColor' => 'transparent', 'padding' => '20px 0'])

<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
  <tbody>
    <tr>
      <td>
        <!--[if mso | IE]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;background:{{ $backgroundColor }};background-color:{{ $backgroundColor }};" width="600" bgcolor="{{ $backgroundColor }}" >
          <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
        <![endif]-->
        <div style="background:{{ $backgroundColor }};background-color:{{ $backgroundColor }};margin:0px auto;max-width:600px;">
          <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
              <tr>
                <td style="direction:ltr;font-size:0px;padding:{{ $padding }};text-align:center;">
                  <!--[if mso | IE]>
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                  <![endif]-->
                  {{ $slot }}
                  <!--[if mso | IE]>
                    </tr>
                  </table>
                  <![endif]-->
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!--[if mso | IE]>
            </td>
          </tr>
        </table>
        <![endif]-->
      </td>
    </tr>
  </tbody>
</table>
