@props([
    'backgroundColor' => '#E5E7EB',
    'color' => '#374151',
    'fontSize' => '12px',
    'fontWeight' => '800',
    'padding' => '6px 14px',
    'borderRadius' => '9999px',
    'align' => 'left'
])

<div style="text-align:{{ $align }};">
  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="display:inline-table; margin: 2px;">
    <tbody>
      <tr>
        <td align="center" valign="middle" style="background-color:{{ $backgroundColor }}; border-radius:{{ $borderRadius }}; padding:{{ $padding }};">
          <div style="font-family:'Plus Jakarta Sans', sans-serif; font-size:{{ $fontSize }}; font-weight:{{ $fontWeight }}; color:{{ $color }}; text-transform:uppercase; letter-spacing:0.8px; line-height:1;">
            {{ $slot }}
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
