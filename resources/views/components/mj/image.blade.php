@props([
    'src',
    'alt' => '',
    'width' => 'auto',
    'align' => 'center',
    'padding' => '0px',
    'href' => null,
    'borderRadius' => '0px'
])

<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;display:inline-block;vertical-align:top;" align="{{ $align }}">
  <tbody>
    <tr>
      <td style="width:{{ $width }}; padding: {{ $padding }};">
        @if($href) <a href="{{ $href }}" target="_blank"> @endif
          <img
            alt="{{ $alt }}"
            height="auto"
            src="{{ $src }}"
            style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;border-radius:{{ $borderRadius }};"
            width="{{ str_replace('px', '', $width) }}"
          />
        @if($href) </a> @endif
      </td>
    </tr>
  </tbody>
</table>
