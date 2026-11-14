@props(['borderColor' => '#E5E7EB', 'borderWidth' => '1px', 'borderStyle' => 'solid', 'padding' => '20px 0'])

<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
  <tbody>
    <tr>
      <td style="font-size:0px;padding:{{ $padding }};word-break:break-word;">
        <p style="border-top:{{ $borderStyle }} {{ $borderWidth }} {{ $borderColor }};font-size:1px;margin:0px auto;width:100%;">
        </p>
        <!--[if mso | IE]>
        <table align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:{{ $borderStyle }} {{ $borderWidth }} {{ $borderColor }};font-size:1px;margin:0px auto;width:100%;" role="presentation" width="100%">
          <tr><td style="height:0;line-height:0;"> &nbsp; </td></tr>
        </table>
        <![endif]-->
      </td>
    </tr>
  </tbody>
</table>
