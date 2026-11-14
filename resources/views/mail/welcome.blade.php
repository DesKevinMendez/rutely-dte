<x-mj.wrapper>
    <x-mj.header />

    <x-mj.section backgroundColor="#ffffff" padding="40px">
        <x-mj.column width="100%">
            <x-mj.text fontSize="26px" fontWeight="800" color="#111827" padding="0 0 16px 0">
                ¡Bienvenido a Rutely, {{ $user->name }}!
            </x-mj.text>

            <x-mj.text>
                Tu cuenta ha sido creada correctamente.
            </x-mj.text>

            <x-mj.text>
                Ya puedes iniciar sesión con <strong>{{ $user->email }}</strong> y comenzar a configurar tu empresa.
            </x-mj.text>

            <x-mj.text>
                Gracias por confiar en Rutely.
            </x-mj.text>

            <x-mj.text fontWeight="600" color="#111827">
                Saludos,<br>
                Equipo de Rutely
            </x-mj.text>
        </x-mj.column>
    </x-mj.section>

    <x-mj.section padding="20px">
        <x-mj.column width="100%">
            <x-mj.text align="center" fontSize="12px" color="#9CA3AF">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </x-mj.text>
        </x-mj.column>
    </x-mj.section>
</x-mj.wrapper>
