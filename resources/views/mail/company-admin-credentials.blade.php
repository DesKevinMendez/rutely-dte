<x-mj.wrapper>
    <x-mj.header />

    <x-mj.section backgroundColor="#ffffff" padding="40px">
        <x-mj.column width="100%">
            <x-mj.text fontSize="26px" fontWeight="800" color="#111827" padding="0 0 16px 0">
                ¡Bienvenido a Rutely, {{ $user->name }}!
            </x-mj.text>

            <x-mj.text>
                {{ $company->name }} creó una cuenta de administrador para ti. Estas son tus credenciales de acceso:
            </x-mj.text>

            <x-mj.text padding="12px 0 0 0">
                <strong>Correo electrónico:</strong> {{ $user->email }}
            </x-mj.text>

            <x-mj.text padding="8px 0 0 0">
                <strong>Contraseña temporal:</strong> <code>{{ $password }}</code>
            </x-mj.text>

            <x-mj.button href="{{ $loginUrl }}">
                Iniciar sesión
            </x-mj.button>

            <x-mj.text fontSize="14px" color="#6B7280">
                Esta contraseña es temporal. Te recomendamos cambiarla después de iniciar sesión por primera vez. No compartas estas credenciales con nadie.
            </x-mj.text>

            <x-mj.text fontWeight="600" color="#111827">
                Saludos,<br>
                {{ config('app.name') }}
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
