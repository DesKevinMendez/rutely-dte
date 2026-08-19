<?php

declare(strict_types=1);

namespace Rutely\DteSigned\Serialization;

use JsonException;

final class JacksonPrettyPrinter
{
    private const JSON_FLAGS = JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_PRESERVE_ZERO_FRACTION
        | JSON_UNESCAPED_LINE_TERMINATORS;

    /**
     * Reproduce el formato de ObjectMapper.writer().withDefaultPrettyPrinter()
     * utilizado por svfe-api-firmador 2.0.0 antes de generar el JWS.
     *
     * @throws JsonException
     */
    public function encode(mixed $value): string
    {
        return $this->encodeValue($value, 0);
    }

    /** @throws JsonException */
    private function encodeValue(mixed $value, int $depth): string
    {
        if (is_object($value)) {
            return $this->encodeObject(get_object_vars($value), $depth);
        }

        if (is_array($value)) {
            return array_is_list($value)
                ? $this->encodeArray($value, $depth)
                : $this->encodeObject($value, $depth);
        }

        return json_encode($value, self::JSON_FLAGS);
    }

    /**
     * @param array<string, mixed> $value
     *
     * @throws JsonException
     */
    private function encodeObject(array $value, int $depth): string
    {
        if ($value === []) {
            return '{ }';
        }

        $lines = [];

        foreach ($value as $key => $item) {
            $encodedKey = json_encode((string) $key, self::JSON_FLAGS);
            $encodedValue = $this->encodeValue($item, $depth + 1);
            $lines[] = $this->indent($depth + 1).$encodedKey.' : '.$encodedValue;
        }

        return "{\n"
            .implode(",\n", $lines)
            ."\n"
            .$this->indent($depth)
            .'}';
    }

    /**
     * @param list<mixed> $value
     *
     * @throws JsonException
     */
    private function encodeArray(array $value, int $depth): string
    {
        if ($value === []) {
            return '[ ]';
        }

        $items = array_map(
            fn (mixed $item): string => $this->encodeValue($item, $depth),
            $value,
        );

        return '[ '.implode(', ', $items).' ]';
    }

    private function indent(int $depth): string
    {
        return str_repeat('  ', $depth);
    }
}
