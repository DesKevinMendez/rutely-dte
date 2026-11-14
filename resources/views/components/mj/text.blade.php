@props(['align' => 'left', 'color' => '#1F2937', 'fontSize' => '16px', 'fontWeight' => '400', 'padding' => '0', 'lineHeight' => '1.6'])

<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
  <tbody>
    <tr>
      <td align="{{ $align }}" style="font-size:0px;padding:{{ $padding }};word-break:break-word;">
        <div style="font-family:'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;font-size:{{ $fontSize }};font-weight:{{ $fontWeight }};line-height:{{ $lineHeight }};text-align:{{ $align }};color:{{ $color }};">
            {{ $slot }}
        </div>
      </td>
    </tr>
  </tbody>
</table>
