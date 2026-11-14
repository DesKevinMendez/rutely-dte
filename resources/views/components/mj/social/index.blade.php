@props(['align' => 'center', 'padding' => '10px 25px'])

<table align="{{ $align }}" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
  <tbody>
    <tr>
      <td align="{{ $align }}" style="padding:{{ $padding }};">
        <table align="{{ $align }}" border="0" cellpadding="0" cellspacing="0" role="presentation" style="display: inline-table; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
          <tbody>
            <tr>
              {{ $slot }}
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
  </tbody>
</table>
