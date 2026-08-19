# DTE Signed

SDK PHP para firmar Documentos Tributarios Electrónicos (DTE) de El Salvador de forma compatible con el firmador oficial del Ministerio de Hacienda.

El SDK reproduce el proceso utilizado por `svfe-api-firmador` 2.0.0, incluyendo la serialización del JSON previa a la firma y la generación del JWS con `RS512`.

## Requisitos

- PHP 8.3 o superior
- Extensión OpenSSL
- Composer

## Instalación para desarrollo

Desde la carpeta del SDK:

```bash
cd dte-signed
composer install
```

## Uso

```php
<?php

use Rutely\DteSigned\DteSigner;

require __DIR__.'/vendor/autoload.php';

$certificateXml = file_get_contents('/path/to/certificate.crt');
$password = 'certificate-password';

$signer = DteSigner::fromCertificateXml($certificateXml, $password);

$dteJson = file_get_contents('/path/to/dte.json');

$jws = $signer->signJson($dteJson);
```

`signJson()` recibe un objeto JSON en formato string y devuelve el JWS compacto firmado.

También es posible firmar directamente un array u objeto PHP:

```php
$jws = $signer->sign([
    'identificacion' => [
        'version' => 1,
        'ambiente' => '00',
        'tipoDte' => '01',
    ],
]);
```

## Certificado

El certificado esperado es el archivo XML utilizado por el firmador del Ministerio de Hacienda. El SDK:

- valida la contraseña contra el hash SHA-512 almacenado en el certificado;
- extrae la llave privada RSA PKCS#8;
- convierte la llave al formato requerido por OpenSSL;
- firma utilizando `RS512`;
- genera un JWS en Compact Serialization.

Una contraseña incorrecta o un certificado inválido producen una excepción y no se genera ninguna firma.

## Compatibilidad con Ministerio de Hacienda

El contenido firmado no corresponde al JSON compacto recibido originalmente. El firmador oficial primero reserializa el DTE utilizando el formato producido por Jackson con `DefaultPrettyPrinter` y posteriormente firma esos bytes.

`DTE Signed` replica esa serialización para que, dada la misma llave privada y el mismo DTE, produzca exactamente el mismo JWS que `svfe-api-firmador` 2.0.0.

Los tests de conformidad incluyen vectores dorados obtenidos del firmador oficial de Hacienda y validan igualdad exacta del JWS generado.

## Tests

Ejecutar toda la suite:

```bash
composer test
```

Solo unit tests:

```bash
composer test:unit
```

Solo tests de conformidad con el firmador de Hacienda:

```bash
composer test:conformance
```

También pueden ejecutarse directamente con Pest:

```bash
./vendor/bin/pest
./vendor/bin/pest tests/Unit
./vendor/bin/pest tests/Conformance
```

## Estructura

```text
src/
├── Certificate/
├── Crypto/
├── Exceptions/
├── Serialization/
└── DteSigner.php

tests/
├── Conformance/
├── Support/
└── Unit/
```

El SDK no depende de Laravel y puede utilizarse como una librería PHP independiente.
