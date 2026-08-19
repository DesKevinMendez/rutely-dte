<?php

declare(strict_types=1);

use Rutely\DteSigned\Serialization\JacksonPrettyPrinter;

it('matches the Jackson default pretty printer used by MH', function (): void {
    $payload = json_decode(<<<'JSON'
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
JSON, flags: JSON_THROW_ON_ERROR);

    $expected = <<<'JSON'
{
  "items" : [ {
    "numItem" : 1,
    "cantidad" : 2,
    "descripcion" : "Café / té"
  }, {
    "numItem" : 2,
    "cantidad" : 1.0,
    "descripcion" : "A\nB"
  } ],
  "numbers" : [ 1, 2.0, 3.5 ],
  "flags" : [ true, false, null ],
  "empty" : [ ],
  "obj" : { }
}
JSON;

    expect((new JacksonPrettyPrinter())->encode($payload))->toBe($expected);
});
