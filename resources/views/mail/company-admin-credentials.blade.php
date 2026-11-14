<x-mj.wrapper backgroundColor="#F3F4F6" title="Bienvenido a Rutely">
    <x-mj.header :url="$loginUrl" />

    <x-mj.section backgroundColor="#ffffff" padding="20px">
        <x-mj.column width="100%">
            <x-mj.text fontSize="28px" fontWeight="800" color="#111827" padding="0 0 16px 0">
                ¡Bienvenido a <span style="color: #4f46e5;">Rutely</span>, {{ $user->name }}!
            </x-mj.text>

            <x-mj.text fontSize="16px" color="#4B5563">
                <strong style="color: #111827;">{{ $company->name }}</strong> creó una cuenta de administrador para ti. Estas son tus credenciales de acceso:
            </x-mj.text>

            <x-mj.divider />

            <x-mj.text fontSize="14px" fontWeight="700" color="#6B7280" padding="0 0 8px 0">
                CORREO ELECTRÓNICO
            </x-mj.text>
            <x-mj.text fontSize="18px" fontWeight="600" color="#111827" padding="0 0 20px 0">
                {{ $user->email }}
            </x-mj.text>

            <x-mj.text fontSize="14px" fontWeight="700" color="#6B7280" padding="0 0 8px 0">
                CONTRASEÑA TEMPORAL
            </x-mj.text>
            <x-mj.text fontSize="20px" fontWeight="800" color="#4f46e5" padding="0 0 20px 0">
                <span style="font-family: 'Courier New', monospace; background-color: #F3F4F6; padding: 8px 14px; border-radius: 8px; letter-spacing: 1px;">{{ $password }}</span>
            </x-mj.text>

            <x-mj.button :href="$loginUrl" backgroundColor="#4f46e5" padding="20px 0 10px 0">
                Iniciar sesión
            </x-mj.button>

            <x-mj.divider />

            <x-mj.text fontSize="14px" fontWeight="700" color="#111827" padding="0 0 8px 0">
                Aviso importante de seguridad
            </x-mj.text>
            <x-mj.text fontSize="14px" color="#4B5563">
                Esta contraseña es temporal. Te recomendamos cambiarla después de iniciar sesión por primera vez. No compartas estas credenciales con nadie.
            </x-mj.text>

            <x-mj.text fontSize="14px" color="#4B5563">
                Si tienes alguna duda o necesitas ayuda, no dudes en contactarnos.
            </x-mj.text>

            <x-mj.divider />

            <x-mj.text fontSize="16px" fontWeight="600" color="#111827" padding="10px 0 0 0">
                Saludos, equipo de Rutely
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
