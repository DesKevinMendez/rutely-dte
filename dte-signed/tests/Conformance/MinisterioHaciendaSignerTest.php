<?php

declare(strict_types=1);

use Rutely\DteSigned\Crypto\Base64Url;
use Rutely\DteSigned\DteSigner;
use Rutely\DteSigned\Tests\Support\OfficialMhFixture;

const OFFICIAL_BASIC_JWS = 'eyJhbGciOiJSUzUxMiJ9.ewogICJpZGVudGlmaWNhY2lvbiIgOiB7CiAgICAidmVyc2lvbiIgOiAxLAogICAgImFtYmllbnRlIiA6ICIwMCIsCiAgICAidGlwb0R0ZSIgOiAiMDEiLAogICAgIm51bWVyb0NvbnRyb2wiIDogIkRURS0wMS1NMDAxUDAwMS0wMDAwMDAwMDAwMDAwMDEiLAogICAgImNvZGlnb0dlbmVyYWNpb24iIDogIkExQjJDM0Q0LUU1RjYtNzg5MC1BQkNELUVGMTIzNDU2Nzg5MCIKICB9LAogICJlbWlzb3IiIDogewogICAgIm5pdCIgOiAiMjIyMjIyMjIyMjIyMjkiLAogICAgIm5vbWJyZSIgOiAiQ0hBTUJBIENBQkFMIgogIH0sCiAgInJlc3VtZW4iIDogewogICAgInRvdGFsUGFnYXIiIDogMTIuMzQsCiAgICAib2JzZXJ2YWNpb25lcyIgOiBudWxsCiAgfSwKICAiZXh0ZW5zaW9uIiA6IG51bGwsCiAgImFwZW5kaWNlIiA6IFsgXQp9.cAVU7cTb6BdAOuOmaCe_3fDkVbDh4mdcoOUSj2yAXW6t5w9Gdgb0QrXq8B_xRtZ4U7_5t6GozrqslyyttWnN_SyRsrlV51Q1ZukM2wegMwGW5f8Eya3SakGnyHMh6Qco-dmEJXtLXzE1fruiTucGAn1WKCjbfkMFeQdB10-S_yHX7gcuU8DiPRk1FOfjTxTB9gQNF_P_Cc7Ull5dcIdyD-lzM9o8yOkDGHTNhA29rTVBj62MaV-j0FFbxTNyKiKnlKfY4OxzNUuci1miqX0kIoIXVM4Kwd4fK9nwj5WyPQwYVK9BzRylpIGhPmEEHIurkXQYB9oWi62iII-ZgVnH7Q';

const OFFICIAL_ARRAY_JWS = 'eyJhbGciOiJSUzUxMiJ9.ewogICJpdGVtcyIgOiBbIHsKICAgICJudW1JdGVtIiA6IDEsCiAgICAiY2FudGlkYWQiIDogMiwKICAgICJkZXNjcmlwY2lvbiIgOiAiQ2Fmw6kgLyB0w6kiCiAgfSwgewogICAgIm51bUl0ZW0iIDogMiwKICAgICJjYW50aWRhZCIgOiAxLjAsCiAgICAiZGVzY3JpcGNpb24iIDogIkFcbkIiCiAgfSBdLAogICJudW1iZXJzIiA6IFsgMSwgMi4wLCAzLjUgXSwKICAiZmxhZ3MiIDogWyB0cnVlLCBmYWxzZSwgbnVsbCBdLAogICJlbXB0eSIgOiBbIF0sCiAgIm9iaiIgOiB7IH0KfQ.LlrgFC7vV3DWG157a_P-NN0aMAKCdtfrUuvkemj1V9WWQYxzyujRL7O3mZmCm7aMTvOFaKKp65i-flpGW5oli1SN1hB8sKepeoON5INVUrr-VLEkcvOohtqOqIX6fTnLmFdrwSzKUU9rr5AUyYBZP_EvwoBcscwgu3q4l_KpzmOhSp_UfwG8jZAp_R6cD7YTxdvtbKk5Qh3_4iBG9td7Au_kF6MIuHQ75NQH_0CN1TYYToQWgKXtgypAJjD5kdk7At0-pK1WPHOli7vQWe8w-BVFJG3lKVs8BhVlfPTFTkpc94KScQDPWuQdJ6kHD451jBREY56aAvUpuA3QoCt5QA';

function officialSigner(): DteSigner
{
    return DteSigner::fromCertificateXml(
        OfficialMhFixture::certificateXml(),
        OfficialMhFixture::PASSWORD,
    );
}

it('produces the exact JWS emitted by svfe-api-firmador 2.0.0', function (): void {
    $json = <<<'JSON'
{
  "identificacion": {
    "version": 1,
    "ambiente": "00",
    "tipoDte": "01",
    "numeroControl": "DTE-01-M001P001-000000000000001",
    "codigoGeneracion": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890"
  },
  "emisor": {
    "nit": "22222222222229",
    "nombre": "CHAMBA CABAL"
  },
  "resumen": {
    "totalPagar": 12.34,
    "observaciones": null
  },
  "extension": null,
  "apendice": []
}
JSON;

    expect(officialSigner()->signJson($json))->toBe(OFFICIAL_BASIC_JWS);
});

it('matches MH serialization for arrays floats unicode and escaped strings', function (): void {
    $json = <<<'JSON'
{
  "items": [
    {"numItem": 1, "cantidad": 2, "descripcion": "Café / té"},
    {"numItem": 2, "cantidad": 1.0, "descripcion": "A\nB"}
  ],
  "numbers": [1, 2.0, 3.5],
  "flags": [true, false, null],
  "empty": [],
  "obj": {}
}
JSON;
    $jws = officialSigner()->signJson($json);

    expect($jws)->toBe(OFFICIAL_ARRAY_JWS)
        ->and(Base64Url::decode(explode('.', $jws)[1]))->toContain('"cantidad" : 1.0')
        ->and(Base64Url::decode(explode('.', $jws)[1]))->toContain('"descripcion" : "Café / té"');
});
