@props([
    'padding' => '10px 25px', 
    'borderColor' => '#E5E7EB', 
    'borderWidth' => '1px',
    'borderRadius' => '12px'
])

<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; border-collapse: collapse;">
  <tbody>
    <tr>
      <td style="padding:{{ $padding }};">
        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; border-collapse: separate; border-radius: {{ $borderRadius }}; border: {{ $borderWidth }} solid {{ $borderColor }}; border-bottom: none; overflow: hidden;">
          <tbody>
            {{ $slot }}
          </tbody>
        </table>
      </td>
    </tr>
  </tbody>
</table>
