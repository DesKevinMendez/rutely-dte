@props(['href', 'align' => 'center', 'backgroundColor' => '#4f46e5', 'color' => '#ffffff', 'borderRadius' => '9999px', 'padding' => '10px 25px', 'innerPadding' => '14px 28px', 'fontWeight' => '700'])

<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
  <tbody>
    <tr>
      <td align="{{ $align }}" style="font-size:0px;padding:{{ $padding }};word-break:break-word;">
        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;">
          <tbody>
            <tr>
              <td align="center" bgcolor="{{ $backgroundColor }}" role="presentation" style="border:none;border-radius:{{ $borderRadius }};cursor:auto;mso-padding-alt:{{ $innerPadding }};background:{{ $backgroundColor }};" valign="middle">
                <a href="{{ $href }}" style="display:inline-block;background:{{ $backgroundColor }};color:{{ $color }};font-family:'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;font-size:16px;font-weight:{{ $fontWeight }};line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:{{ $innerPadding }};mso-padding-alt:0px;border-radius:{{ $borderRadius }};" target="_blank">
                  {{ $slot }}
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
  </tbody>
</table>
