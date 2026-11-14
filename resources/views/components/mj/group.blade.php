@props(['width' => '100%', 'verticalAlign' => 'top'])

@php
    $widthInt = (int) str_replace('%', '', $width);
@endphp

<!--[if mso | IE]>
<td style="vertical-align:{{ $verticalAlign }};width:{{ $width }};">
<![endif]-->
<div class="mj-column-per-{{ $widthInt }} mj-outlook-group-fix" style="font-size:0px;line-height:0px;text-align:left;display:inline-block;width:100%;direction:ltr;vertical-align:{{ $verticalAlign }};">
  <!--[if mso | IE]>
  <table role="presentation" border="0" cellpadding="0" cellspacing="0">
    <tr>
  <![endif]-->
  {{ $slot }}
  <!--[if mso | IE]>
    </tr>
  </table>
  <![endif]-->
</div>
<!--[if mso | IE]>
</td>
<![endif]-->
