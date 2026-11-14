@props([
    'src', 
    'href', 
    'alt' => '', 
    'iconSize' => '24px', 
    'containerSize' => '40px',
    'backgroundColor' => '#000000',
    'padding' => '4px'
])

<td style="padding:{{ $padding }}; vertical-align: middle;">
  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color:{{ $backgroundColor }}; border-radius:50%; width:{{ $containerSize }}; height:{{ $containerSize }};">
    <tbody>
      <tr>
        <td align="center" valign="middle" style="width:{{ $containerSize }}; height:{{ $containerSize }};">
          <a href="{{ $href }}" target="_blank" style="display: block; line-height: 0;">
            <img 
                alt="{{ $alt }}" 
                src="{{ $src }}" 
                style="display:block; border:0;" 
                width="{{ str_replace('px', '', $iconSize) }}" 
                height="{{ str_replace('px', '', $iconSize) }}" 
            />
          </a>
        </td>
      </tr>
    </tbody>
  </table>
</td>
