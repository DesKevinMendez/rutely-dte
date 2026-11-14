@props(['url' => config('app.url')])

<x-mj.section backgroundColor="transparent" padding="40px 0 20px 0">
    <x-mj.column width="100%">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto;">
            <tr>
                <td style="vertical-align: middle;">
                    <a href="{{ $url }}" style="text-decoration: none; display: block;">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" width="40" height="40" style="display: block; width: 40px; height: 40px; border: 0;">
                    </a>
                </td>
                <td style="vertical-align: middle; padding-left: 10px;">
                    <a href="{{ $url }}" style="text-decoration: none; display: block;">
                        <span style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 20px; font-weight: 800; color: #111827; line-height: 40px;">
                            Rutely POS
                        </span>
                    </a>
                </td>
            </tr>
        </table>
    </x-mj.column>
</x-mj.section>
