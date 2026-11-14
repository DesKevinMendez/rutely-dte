@props([
    'title', 
    'borderColor' => '#E5E7EB', 
    'borderWidth' => '1px',
    'titleColor' => '#111827',
    'contentColor' => '#4B5563',
    'backgroundColor' => '#ffffff'
])

<tr style="background-color: {{ $backgroundColor }};">
  <td style="padding: 0;">
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%; border-bottom: {{ $borderWidth }} solid {{ $borderColor }};">
      <tr>
        <td style="padding: 16px 20px;">
          <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tr>
              <td style="font-family:'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 700; color: {{ $titleColor }};">
                {{ $title }}
              </td>
              <td align="right" style="width: 20px; font-family:'Plus Jakarta Sans', sans-serif; font-size: 18px; font-weight: 700; color: #4f46e5;">
                &plus;
              </td>
            </tr>
            <tr>
              <td colspan="2" style="padding-top: 8px; font-family:'Plus Jakarta Sans', sans-serif; font-size: 14px; line-height: 1.6; color: {{ $contentColor }};">
                {{ $slot }}
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </td>
</tr>
