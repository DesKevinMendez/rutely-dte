@props([
    'color' => '#1F2937',
    'fontSize' => '14px',
    'lineHeight' => '1.5',
    'padding' => '10px 25px',
    'width' => '100%',
    'align' => 'left'
])

<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
  <tbody>
    <tr>
      <td style="font-size:0px;padding:{{ $padding }};word-break:break-word;">
        <table cellpadding="0" cellspacing="0" width="{{ $width }}" border="0" style="color:{{ $color }};font-family:'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;font-size:{{ $fontSize }};line-height:{{ $lineHeight }};table-layout:auto;width:{{ $width }};border:none;" align="{{ $align }}">
          {{ $slot }}
        </table>
      </td>
    </tr>
  </tbody>
</table>
