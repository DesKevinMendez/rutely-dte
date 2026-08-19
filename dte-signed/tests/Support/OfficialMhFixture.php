<?php

declare(strict_types=1);

namespace Rutely\DteSigned\Tests\Support;

final class OfficialMhFixture
{
    public const NIT = '22222222222229';

    public const PASSWORD = 'RutelyDteTest2026!';

    private const PRIVATE_KEY_PKCS8_BASE64 = 'MIIEwAIBADANBgkqhkiG9w0BAQEFAASCBKowggSmAgEAAoIBAQCoJdrM7gRYxzicb4v38AtAfq7E4Rv90rtuXk96fDGIO4d9YHUMam5ZmrFFkK4PBAErm5dmMj1IWzX1u7/L4Gqe63MXbg0lcfbWJhIw3YHXPhqQo8vHgsXs6znRviwAEVm5TS6emm1dcnVJ3MO26npob4RdILvjzT63AdPVoeLVc9Y0f/agqaiOQCgEbMGa53ueVT/9+m2fn2xxefYN5A8isFaBuegSiaNUYMwA8SrgjWweU8M6g2t8fcMtSOqydNKvzRrZ6ZLGfqrnVj6IEA+0tT+K8I4NiJtBjKrbRHqYZtkpWuwt8eGOzygyBUmV+uSbeycqp30phQ6kC7hGIqe7AgMBAAECggEBAIH7CUFjON772cor/FIEMF6Bz04ICeBTV2pA40V23b9G7TzBJJodaAJCL4jsB3E6EkGIfCeW7IKTZ4n2wZOzfhgtQAG7o9PvXfU65tL5WBZwPo7S34Lxl1jGmSKG1HKU9vvkKwaVr7cN9JbNXkl2xnsWwYZP+I5nKXTEp+E7zCJdsTxI4SCcmULdvTQhyXzmceAw2O2mepNZ+Z64D+g4VwEcV82Lh6nXptXl4FniTrMD0vC/vYP1qkt7obhSBAPQZRFVrJjI9SmtcACM+rzCzfr9MgKkLtypCwfhbwrmnD/vsVFZy9kRg0g+/vfzLiNNLGLw5DlhGj5OkoDzeL+v9TkCgYEA1GnPucwwuPzQTUJic3nmEI2y8LrnuW5vgRFIkJ02C4kZM9qT/Wa/9sXBtmqstiOfLAD1OL4nMwRJyRBwq2MSE1KkJ1pkAd4EK+OM6NzPJh2RQmeEZYuNe2IkOIugdqUBZ76oVZnWfRPyc9dfPGJn99iyhH8hfMOzwqxGlaiJcu8CgYEAyqbBKzZIkTev5fygh06zsjX1VUH0SZxofsOOObF14cv+h31yjB+03yWeET9b9Jo7lwBlWKXKXSPMw7HuNjxEdZGB4K7JWt2zuag+KwvrIXWm89iraV8Ro1eTYkkk3wY4Pvcn21KqkWi8IrTBqWspnihm8O67/zKzSrT7acI+5/UCgYEAzUNvFCnIz4qnNHG5N8QNWePEjrLfKKcao4vzJqR1TJJww1Yu+oonaS3TMxdEzUIBGAHY9rtyn+896kmzxzsWhYuvy8OirtdACrV7Pq/akgeyjowAOiywTRIa1HXBW8W6ZOmuPAJMblQvUFhI1M53j99dK4K69pkbhjz6fLcAFAsCgYEAnudFHxowqtYMsn12bsLyuvH+jrzpzfK8KXI0Ct8xPT3VNu7SLDgMftGjcYjKFTH/OfeQgIN3+7K/tE/IJ3T4hWv0eHb14q9nZ1Qac2ykEheMMzcZqcVnMjrQkcgjBlJ9NjpdYWgf4WdL5rbwCGXEO4UYuyGn/oMF/bWOUq6C3yUCgYEAp00QIFovedelbNL388B7pgHfc6DdrRhx6wDEtlnack/dy5G5v3QqVlKkpRijxqrRQdAsFEL8eerR6GXKu5KNfwGLqV+EEn+cVL3x52ruscpD3No5IV3yZPbvEIeBh0eHs35K1fnWCJQcQ27nYqsGN5Rdjjr/qmnzY57+i7qJxOQ=';

    public static function certificateXml(): string
    {
        $passwordHash = hash('sha512', self::PASSWORD);

        return '<CertificadoMH>'
            .'<nit>'.self::NIT.'</nit>'
            .'<privateKey>'
            .'<keyType>PRIVATE</keyType>'
            .'<algorithm>RSA</algorithm>'
            .'<encodied>'.self::PRIVATE_KEY_PKCS8_BASE64.'</encodied>'
            .'<format>PKCS#8</format>'
            .'<clave>'.$passwordHash.'</clave>'
            .'</privateKey>'
            .'<activo>true</activo>'
            .'</CertificadoMH>';
    }
}
