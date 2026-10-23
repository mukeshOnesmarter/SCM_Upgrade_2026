<?php

namespace App\Support\Spreadsheet;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * OpenSpout 3's writer factory, on top of OpenSpout 5. The old code calls
 * these names about 270 times; keeping them means the exports didn't have to
 * be rewritten line by line.
 */
final class WriterEntityFactory
{
    public static function createXLSXWriter(): Writer
    {
        return new Writer;
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    public static function createRowFromArray(array $values = [], ?Style $style = null): Row
    {
        $values = array_map(self::cellValue(...), array_values($values));

        return $style ? Row::fromValuesWithStyle($values, $style) : Row::fromValues($values);
    }

    public static function createRow(array $cells = [], ?Style $style = null): Row
    {
        return self::createRowFromArray($cells, $style);
    }

    /**
     * OpenSpout 3 wrote any value it was given. OpenSpout 5 takes scalars,
     * dates and null only, so anything else is written as text.
     */
    private static function cellValue(mixed $value): mixed
    {
        return match (true) {
            $value === null, is_scalar($value), $value instanceof \DateTimeInterface, $value instanceof \DateInterval => $value,
            $value instanceof \Stringable => (string) $value,
            default => json_encode($value),
        };
    }
}
