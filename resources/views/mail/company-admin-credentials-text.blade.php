¡Bienvenido a Rutely, {{ $user->name }}!

{{ $company->name }} creó una cuenta de administrador para ti. Estas son tus credenciales de acceso:

Correo electrónico: {{ $user->email }}
Contraseña temporal: {{ $password }}

Iniciar sesión: {{ $loginUrl }}

Esta contraseña es temporal. Te recomendamos cambiarla después de iniciar sesión por primera vez. No compartas estas credenciales con nadie.

Saludos,
{{ config('app.name') }}
