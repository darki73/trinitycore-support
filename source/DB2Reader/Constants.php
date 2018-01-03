<?php namespace FreedomCore\TrinityCore\Support\DB2Reader;

/**
 * Class Constants
 * @package FreedomCore\TrinityCore\Support\DB2Reader
 */
class Constants
{

    const FIELD_TYPE_UNKNOWN = 0;
    const FIELD_TYPE_INT = 1;
    const FIELD_TYPE_FLOAT = 2;
    const FIELD_TYPE_STRING = 3;
    const DISTINCT_STRINGS_REQUIRED = 5;
    const FIELD_COMPRESSION_NONE = 0;
    const FIELD_COMPRESSION_BITPACKED = 1;
    const FIELD_COMPRESSION_COMMON = 2;
    const FIELD_COMPRESSION_BITPACKED_INDEXED = 3;
    const FIELD_COMPRESSION_BITPACKED_INDEXED_ARRAY = 4;
}
