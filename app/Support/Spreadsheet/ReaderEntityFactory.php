<?php

namespace App\Support\Spreadsheet;

use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * OpenSpout 3's reader factory, on top of OpenSpout 5.
 */
final class ReaderEntityFactory
{
    public static function createXLSXReader(): XlsxReader
    {
        return new XlsxReader;
    }

    public static function createCSVReader(): CsvReader
    {
        return new CsvReader;
    }
}
