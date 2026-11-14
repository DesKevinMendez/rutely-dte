@props(['width' => '100%', 'verticalAlign' => 'top', 'padding' => '0px', 'align' => 'left'])

@php
    $widthInt = (int) str_replace('%', '', $width);
@endphp

<!--[if mso | IE]>
<td class="" style="vertical-align:{{ $verticalAlign }};width:{{ $width }};" align="{{ $align }}">
<![endif]-->
<div class="mj-column-per-{{ $widthInt }} mj-outlook-group-fix" style="font-size:0px;text-align:{{ $align }};direction:ltr;display:inline-block;vertical-align:{{ $verticalAlign }};width:{{ $width }};">
  <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:{{ $verticalAlign }};" width="100%">
    <tbody>
      <tr>
        <td style="padding:{{ $padding }};" align="{{ $align }}">
          {{ $slot }}
        </td>
      </tr>
    </tbody>
  </table>
</div>
<!--[if mso | IE]>
</td>
<![endif]-->
