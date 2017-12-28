<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;
use FreedomCore\TrinityCore\Support\DB2Reader\Constants;
use FreedomCore\TrinityCore\Support\DB2Reader\FileManager;

/**
 * Class BaseFormat
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 * @copyright Erorus (https://github.com/erorus) and Darki73 (https://github.com/darki73)
 * @see https://github.com/erorus/db2/blob/master/src/Erorus/DB2/Reader.php
 */
abstract class BaseFormat implements IFormat {

    /**
     * File Manager Instance
     * @var FileManager|null
     */
    protected $fileManager          = null;

    /**
     * String Fields Array
     * @var null|array
     */
    protected $stringFields         = null;

    /**
     * Version of the file (build number)
     * @var int
     */
    protected $wdbVersion           = 0;

    /**
     * Is file used for processing of type WDBC
     * @var bool
     */
    protected $isWDBC               = false;

    /**
     * Field Identification Number
     * @var int
     */
    protected $idField              = 0;

    /**
     * Map of identification numbers
     * @var array
     */
    protected $idMap                = [];

    /**
     * Headers count
     * @var int
     */
    protected $headerFieldCount     = 0;

    /**
     * Records count
     * @var int
     */
    protected $recordCount          = 0;

    /**
     * Fields count
     * @var int
     */
    protected $fieldCount           = 0;

    /**
     * Size of the record
     * @var int
     */
    protected $recordSize           = 0;

    /**
     * Size of the string block
     * @var int
     */
    protected $stringBlockSize      = 0;

    /**
     * Hash of the table name
     * @var int
     */
    protected $tableHash            = 0;

    /**
     * This is a hash field that changes only when the structure of the data changes
     * @var int
     */
    protected $layoutHash           = 0;

    /**
     * Build number
     * @var int
     */
    protected $build                = 0;

    /**
     * Timestamp
     * @var int
     */
    protected $timestamp            = 0;

    /**
     * Flags
     * @var int
     */
    protected $flags                = 0;

    /**
     * Minimal Identification Number
     * @var int
     */
    protected $minId                = 0;

    /**
     * Maximal Identification Number
     * @var int
     */
    protected $maxId                = 0;

    /**
     * Locale of the file
     * @var int
     */
    protected $locale               = 0;

    /**
     * Common block size
     * @var int
     */
    protected $commonBlockSize      = 0;

    /**
     * Copy block size
     * @var int
     */
    protected $copyBlockSize        = 0;

    /**
     * Size of the header
     * @var int
     */
    protected $headerSize           = 0;

    /**
     * Format of the header
     * @var null|string
     */
    protected $headerFormat         = null;

    /**
     * Length of the preamble
     * @var int
     */
    protected $preambleLength       = 0;

    /**
     * Does file has embedded strings
     * @var bool
     */
    protected $hasEmbeddedStrings   = false;

    /**
     * Total fields count
     * @var int
     */
    protected $totalFieldCount      = 0;

    /**
     * Does file has id block
     * @var bool
     */
    protected $hasIdBlock           = false;

    /**
     * Does file has ids in the index block
     * @var bool
     */
    protected $hasIdsInIndexBlock   = false;

    /**
     * Position of the id block
     * @var int
     */
    protected $idBlockPosition      = 0;

    /**
     * Position of the index block
     * @var int
     */
    protected $indexBlockPosition   = 0;

    /**
     * Common block position
     * @var int
     */
    protected $commonBlockPosition  = 0;

    /**
     * String block position
     * @var int
     */
    protected $stringBlockPosition  = 0;

    /**
     * Copy block position
     * @var int
     */
    protected $copyBlockPosition    = 0;

    /**
     * Position of the EoF
     * @var int
     */
    protected $endOfFile            = 0;

    /**
     * Format for the record
     * @var array
     */
    protected $recordFormat         = [];

    /**
     * Offsets for the records
     * @var null
     */
    protected $recordOffsets        = null;

    protected $commonLookup         = [];

    protected $bitpackedDataPosition = 0;

    protected $lookupColumnCount = 0;

    protected $offsetMapPosition = 0;

    protected $idListSize = 0;

    protected $fieldStorageInfoPosition = 0;

    protected $fieldStorageInfoSize = 0;

    protected $palletDataPosition = 0;

    protected $palletDataSize = 0;

    protected $relationshipDataPosition = 0;

    protected $relationshipDataSize = 0;

    /**
     * Length of the header
     * @var int
     */
    protected $headerLength = 0;


    /**
     * BaseFormat constructor.
     * @param FileManager $fileManager
     * @param null $stringFields
     */
    public function __construct(FileManager $fileManager, $stringFields = null) {
        $this->fileManager = $fileManager;
        $this->stringFields = $stringFields;
        $this->prepareFile();
    }

    /**
     * Get Record
     * @param $id
     * @return array|null
     * @throws \Exception
     */
    public function getRecord($id) {
        return (!isset($this->idMap[$id])) ? null : $this->getRecordByOffset($this->idMap[$id], $id);
    }

    /**
     * Generate Records
     * @return \Generator
     * @throws \Exception
     */
    public function generateRecords() {
        foreach ($this->idMap as $id => $offset) {
            yield $id => $this->getRecordByOffset($offset, $id);
        }
    }

    /**
     * Get IDs
     * @return array
     */
    public function getIDs() {
        return array_keys($this->idMap);
    }

    /**
     * Validate That File Sizes Match
     * @throws \Exception
     */
    public function validateEndOfFile() {
        $this->endOfFile = $this->copyBlockPosition + $this->copyBlockSize;
        if ($this->endOfFile != $this->fileManager->getProcessedSize()) {
            throw new \Exception("Expected size: " . $this->endOfFile . ", actual size: " . $this->fileManager->getProcessedSize());
        }
    }

    /**
     * Populate ID Map Array
     * @throws \Exception
     */
    protected function populateIDMap() {
        if (!$this->hasIdBlock) {
            $this->recordFormat[$this->idField]['signed'] = false;
            for ($x = 0; $x < $this->recordCount; $x++) {
                $rec = $this->getRecordByOffset($x, false);
                $this->idMap[$rec[$this->idField]] = $x;
            }
        } else {
            $this->fileManager->seekBytes($this->idBlockPosition);
            if ($this->fileManager->getFormat() == 'WDB2') {
                for ($x = $this->minId; $x <= $this->maxId; $x++) {
                    $record = current(unpack('V', $this->fileManager->readBytes(4)));
                    if ($record) {
                        $this->idMap[$x] = $record - 1;
                    }
                    $this->fileManager->seekBytes(2, SEEK_CUR);
                }
            } else {
                for ($x = 0; $x < $this->recordCount; $x++) {
                    $this->idMap[current(unpack('V', $this->fileManager->readBytes(4)))] = $x;
                }
            }
        }
        if ($this->copyBlockSize) {
            $this->fileManager->seekBytes($this->copyBlockPosition);
            $entryCount = floor($this->copyBlockSize / 8);
            for ($x = 0; $x < $entryCount; $x++) {
                list($newId, $existingId) = array_values(unpack('V*', $this->fileManager->readBytes(8)));
                if (!isset($this->idMap[$existingId])) {
                    throw new \Exception("Copy block referenced ID " . $existingId . " which does not exist");
                }
                $this->idMap[$newId] = $this->idMap[$existingId];
            }
            ksort($this->idMap, SORT_NUMERIC);
        }
    }

    /**
     * Guess Types Of Fields
     * @throws \Exception
     */
    protected function guessFieldTypes() {
        foreach ($this->recordFormat as $fieldId => &$format) {
            if ($format['type'] != Constants::FIELD_TYPE_UNKNOWN || $format['size'] != 4) {
                continue;
            }

            $couldBeFloat = true;
            $couldBeString = !$this->hasEmbeddedStrings;
            $recordOffset = 0;
            $distinctValues = [];
            while (($couldBeString || $couldBeFloat) && $recordOffset < $this->recordCount) {
                $data = $this->getRawRecord($recordOffset);
                if (!$this->hasEmbeddedStrings) {
                    $byteOffset = $format['offset'];
                } else {
                    $byteOffset = 0;
                    for ($offsetFieldId = 0; $offsetFieldId < $fieldId; $offsetFieldId++) {
                        if ($this->recordFormat[$offsetFieldId]['type'] == Constants::FIELD_TYPE_STRING) {
                            for ($offsetFieldValueId = 0; $offsetFieldValueId < $this->recordFormat[$offsetFieldId]['valueCount']; $offsetFieldValueId++) {
                                $byteOffset = strpos($data, "\x00", $byteOffset);
                                if ($byteOffset === false) {
                                    throw new \Exception("Could not find end of embedded string $offsetFieldId x $offsetFieldValueId in record $recordOffset");
                                }
                                $byteOffset++; // skip null byte
                            }
                        } else {
                            $byteOffset += $this->recordFormat[$offsetFieldId]['valueLength'] * $this->recordFormat[$offsetFieldId]['valueCount'];
                        }
                    }
                }
                $data = substr($data, $byteOffset, $format['valueLength'] * $format['valueCount']);
                $values = unpack('V*', $data);
                foreach ($values as $value) {
                    if ($value == 0) {
                        continue;
                    }
                    if (count($distinctValues) < Constants::DISTINCT_STRINGS_REQUIRED) {
                        $distinctValues[$value] = true;
                    }
                    if ($couldBeString) {
                        if ($value > $this->stringBlockSize) {
                            $couldBeString = false;
                        } else {
                            $this->fileManager->seekBytes($this->stringBlockPosition + $value - 1);
                            if ($this->fileManager->readBytes(1) !== "\x00") {
                                $couldBeString = false;
                            }
                        }
                    }
                    if ($couldBeFloat) {
                        $exponent = ($value >> 23) & 0xFF;
                        if ($exponent == 0 || $exponent == 0xFF) {
                            $couldBeFloat = false;
                        } else {
                            $asFloat = current(unpack('f', pack('V', $value)));
                            if (round($asFloat, 6) == 0) {
                                $couldBeFloat = false;
                            }
                        }
                    }
                }
                $recordOffset++;
            }

            if ($couldBeString && ($this->recordCount < Constants::DISTINCT_STRINGS_REQUIRED * 2 || count($distinctValues) >= Constants::DISTINCT_STRINGS_REQUIRED)) {
                $format['type'] = Constants::FIELD_TYPE_STRING;
                $format['signed'] = false;
            } elseif ($couldBeFloat) {
                $format['type'] = Constants::FIELD_TYPE_FLOAT;
                $format['signed'] = true;
            } else {
                $format['type'] = Constants::FIELD_TYPE_INT;
            }
        }
        unset($format);
    }

    /**
     * Get Raw Record Data
     * @param integer|string $recordOffset
     * @param bool $id
     * @return bool|string
     * @throws \Exception
     */
    protected function getRawRecord($recordOffset, $id = false) {
        if (!is_null($this->recordOffsets)) {
            $pointer = $this->recordOffsets[$recordOffset];
            if ($pointer['size'] == 0) {
                throw new \Exception("Requested record offset $recordOffset which is empty");
            }
            $this->fileManager->seekBytes($pointer['pos']);
            $data = $this->fileManager->readBytes($pointer['size']);
        } else {
            $this->fileManager->seekBytes($this->headerSize + $recordOffset * $this->recordSize);
            $data = $this->fileManager->readBytes($this->recordSize);
        }
        if ($this->fileManager->getFormat() == 'WDB6' && $id !== false && $this->commonBlockSize) {
            $lastFieldFormat = $this->recordFormat[$this->fieldCount - 1];
            $data = substr($data, 0, $lastFieldFormat['offset'] + $lastFieldFormat['valueLength']);
            foreach ($this->commonLookup as $field => $lookup) {
                if (isset($lookup[$id])) {
                    $data .= $lookup[$id];
                } else {
                    $data .= $this->recordFormat[$field]['zero'];
                }
            }
        }
        return $data;
    }

    /**
     * Find Common Fields
     * @throws \Exception
     */
    protected function findCommonFields() {
        $this->commonLookup = [];
        if ($this->commonBlockSize == 0) {
            return;
        }
        $commonBlockEnd = $this->commonBlockPosition + $this->commonBlockSize;
        $this->fileManager->seekBytes($this->commonBlockPosition);
        $fieldCount = current(unpack('V', $this->fileManager->readBytes(4)));
        if ($fieldCount != $this->totalFieldCount) {
            throw new \Exception(sprintf("Expected %d fields in common block, found %d", $this->totalFieldCount, $fieldCount));
        }
        $fourBytesEveryType = true;
        for ($field = 0; $field < $this->totalFieldCount; $field++) {
            list($entryCount, $enumType) = array_values(unpack('V1x/C1y', $this->fileManager->readBytes(5)));
            $mapSize = 8 * $entryCount;
            if (($enumType > 4) || ($entryCount > $this->recordCount) || (ftell($this->fileManager->getFileHandle()) + $mapSize + ($field + 1 < $this->totalFieldCount ? 5 : 0) > $commonBlockEnd)) {
                $fourBytesEveryType = false;
                break;
             }
             fseek($this->fileManager->getFileHandle(), $mapSize, SEEK_CUR);
         }
         $fourBytesEveryType &= $commonBlockEnd - ftell($this->fileManager->getFileHandle()) <= 8;
        fseek($this->fileManager->getFileHandle(), $this->commonBlockPos + 4);
        for ($field = 0; $field < $this->totalFieldCount; $field++) {
            list($entryCount, $enumType) = array_values(unpack('V1x/C1y', $this->fileManager->readBytes(5)));
            if ($field < $this->fieldCount) {
                if ($entryCount > 0) {
                    throw new \Exception(sprintf("Expected 0 entries in common block field %d, instead found %d", $field, $entryCount));
                }
                continue;
            }
            $size = 4;
            $type = Constants::FIELD_TYPE_INT;
            switch ($enumType) {
                case 0:
                    $type = Constants::FIELD_TYPE_STRING;
                    break;
                case 1:
                    $size = 2;
                    break;
                case 2:
                    $size = 1;
                    break;
                case 3:
                    $type = Constants::FIELD_TYPE_FLOAT;
                    break;
                case 4:
                    break;
                default:
                    throw new \Exception("Unknown common field type: $enumType");
            }
            $this->recordFormat[$field] = [
                'valueCount'    => 1,
                'valueLength'   => $size,
                'type'          => $type,
                'size'          =>  4,
                'signed'        => false,
                'zero'          => str_repeat("\x00", $size),
            ];
            $this->commonLookup[$field] = [];
            $embeddedStrings = false;
            if ($this->hasEmbeddedStrings && $type == Constants::FIELD_TYPE_STRING) {
                $embeddedStrings = true;
                $this->recordFormat[$field]['zero'] = "\x00";
            }
            for ($entry = 0; $entry < $entryCount; $entry++) {
                $id = current(unpack('V', $this->fileManager->readBytes(4)));
                if ($embeddedStrings) {
                    $maxLength = $this->commonBlockSize - (ftell($this->fileManager->getFileHandle()) - $this->commonBlockPosition);
                    $this->commonLookup[$field][$id] = stream_get_line($this->fileManager->getFileHandle(), $maxLength, "\x00") . "\x00";
                } else {
                    $this->commonLookup[$field][$id] = ($fourBytesEveryType && $size != 4) ? substr($this->fileManager->readBytes(4), 0, $size) : $this->fileManager->readBytes($size);
                }
            }
        }
    }

    /**
     * Populate Offsets For Record
     */
    protected function populateRecordOffsets() {
        $this->fileManager->seekBytes($this->indexBlockPosition);
        $this->recordOffsets = [];
        if ($this->hasIdsInIndexBlock) {
            $this->idMap = [];
            $lowerBound = 0;
            $upperBound = $this->recordCount - 1;
        } else {
            $lowerBound = $this->minId;
            $upperBound = $this->maxId;
        }
        $seenBefore = [];
        for ($x = $lowerBound; $x <= $upperBound; $x++) {
            if ($this->hasIdsInIndexBlock) {
                $pointer = unpack('Vid/Vpos/vsize', $this->fileManager->readBytes(10));
                $this->idMap[$pointer['id']] = $x;
            } else {
                $pointer = unpack('Vpos/vsize', $this->fileManager->readBytes(6));
                $pointer['id'] = $x;
            }
            if ($pointer['size'] > 0) {
                if (!isset($seenBefore[$pointer['pos']])) {
                    $seenBefore[$pointer['pos']] = [];
                }
                if (!isset($this->idMap[$pointer['id']])) {
                    foreach ($seenBefore[$pointer['pos']] as $anotherId) {
                        if (isset($this->idMap[$anotherId])) {
                            $this->idMap[$pointer['id']] = $this->idMap[$anotherId];
                        }
                    }
                }
                if (isset($this->idMap[$pointer['id']])) {
                    $this->recordOffsets[$this->idMap[$pointer['id']]] = $pointer;
                    foreach ($seenBefore[$pointer['pos']] as $anotherId) {
                        if (!isset($this->idMap[$anotherId])) {
                            $this->idMap[$anotherId] = $this->idMap[$pointer['id']];
                        }
                    }
                }
                $seenBefore[$pointer['pos']][] = $pointer['id'];
            }
        }
        ksort($this->idMap);
    }

    /**
     * Detect if field is and embedded string field
     * @throws \Exception
     */
    protected function detectEmbeddedStringFields() {
        $stringFields = [];
        foreach ($this->recordFormat as $fieldID => &$format) {
            if ($format['type'] != Constants::FIELD_TYPE_UNKNOWN || $format['valueLength'] != 4) {
                continue;
            }
            $couldBeString = true;
            $maxLength = 0;
            $recordOffset = 0;
            while ($couldBeString && $recordOffset < $this->recordCount) {
                $data = $this->getRawRecord($recordOffset);
                $byteOffset = 0;
                for ($offsetFieldId = 0; $offsetFieldId < $fieldID; $offsetFieldId++) {
                    if ($this->recordFormat[$offsetFieldId]['type'] == Constants::FIELD_TYPE_STRING) {
                        for ($offsetFieldValueId = 0; $offsetFieldValueId < $this->recordFormat[$offsetFieldId]['valueCount']; $offsetFieldValueId++) {
                            $byteOffset = strpos($data, "\x00", $byteOffset);
                            if ($byteOffset === false) {
                                throw new \Exception("Could not find end of embedded string $offsetFieldId x $offsetFieldValueId in record $recordOffset");
                            }
                            $byteOffset++;
                        }
                    } else {
                        $byteOffset += $this->recordFormat[$offsetFieldId]['valueLength'] * $this->recordFormat[$offsetFieldId]['valueCount'];
                    }
                }
                for ($valuePosition = 0; $valuePosition < $format['valueCount']; $valuePosition++) {
                    $nextEnd = strpos($data, "\x00", $byteOffset);
                    if ($nextEnd === false) {
                        $couldBeString = false;
                        break;
                    }
                    $testLength = $nextEnd - $byteOffset;
                    $stringToTest = substr($data, $byteOffset, $testLength);
                    $maxLength = max($maxLength, $testLength);
                    $byteOffset = $nextEnd + 1;
                    if ($testLength > 0 && mb_detect_encoding($stringToTest, 'UTF-8', true) === false) {
                        $couldBeString = false;
                    }
                }
                $recordOffset++;
            }
            if ($couldBeString && ($maxLength > 2 || in_array($fieldID - 1, $stringFields))) {
                $stringFields[] = $fieldID;
                $format['type'] = Constants::FIELD_TYPE_STRING;
            }
        }
        unset($format);
    }

    /**
     * Get Record By Offset
     * @param integer|string $recordOffset
     * @param integer $id
     * @return array
     * @throws \Exception
     */
    private function getRecordByOffset($recordOffset, $id) {
        if ($recordOffset < 0 || $recordOffset >= $this->recordCount) {
            throw new \Exception("Requested record offset $recordOffset out of bounds: 0-".$this->recordCount);
        }
        $record = $this->getRawRecord($recordOffset, $id);
        $runningOffset = 0;
        $row = [];
        for ($fieldID = 0; $fieldID < $this->totalFieldCount; $fieldID++) {
            $field = [];
            $format = $this->recordFormat[$fieldID];
            for ($valueId = 0; $valueId < $format['valueCount']; $valueId++) {
                if (isset($format['storage']) && !$this->hasEmbeddedStrings) {
                    $rawValue = substr($record, $format['offset'], $format['valueLength']);
                    switch ($format['storage']['storageType']) {
                        case Constants::FIELD_COMPRESSION_BITPACKED:
                        case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED:
                        case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED_ARRAY:
                            $rawValue = self::extractValueFromBitstring($rawValue,
                                $format['storage']['offsetBits'] % 8, $format['storage']['sizeBits']);
                            if ($format['storage']['storageType'] == Constants::FIELD_COMPRESSION_BITPACKED) {
                                $field[] = $rawValue;
                                continue 2;
                            }
                            $field[] = $this->getPalletData($format['storage'], $rawValue, $valueId);
                            continue 2;
                        case Constants::FIELD_COMPRESSION_COMMON:
                            $rawValue = $this->getCommonData($format['storage'], $id);
                            break;
                    }
                } else {
                    if ($this->hasEmbeddedStrings && $format['type'] == Constants::FIELD_TYPE_STRING) {
                        $rawValue = substr($record, $runningOffset,
                            strpos($record, "\x00", $runningOffset) - $runningOffset);
                        $runningOffset += strlen($rawValue) + 1;
                        $field[] = $rawValue;
                        continue;
                    } else {
                        $rawValue = substr($record, $runningOffset, $format['valueLength']);
                        $runningOffset += $format['valueLength'];
                    }
                }
                switch ($format['type']) {
                    case Constants::FIELD_TYPE_UNKNOWN:
                    case Constants::FIELD_TYPE_INT:
                        if ($format['signed']) {
                            switch ($format['size']) {
                                case 8:
                                    $field[] = current(unpack('q', $rawValue));
                                    break;
                                case 4:
                                    $field[] = current(unpack('l', $rawValue));
                                    break;
                                case 3:
                                    $field[] = current(unpack('l', $rawValue . (ord(substr($rawValue, -1)) & 0x80 ? "\xFF" : "\x00")));
                                    break;
                                case 2:
                                    $field[] = current(unpack('s', $rawValue));
                                    break;
                                case 1:
                                    $field[] = current(unpack('c', $rawValue));
                                    break;
                            }
                        } else {
                            if ($format['size'] == 8) {
                                $field[] = current(unpack('P', $rawValue));
                            } else {
                                $field[] = current(unpack('V', str_pad($rawValue, 4, "\x00", STR_PAD_RIGHT)));
                            }
                        }
                        break;
                    case Constants::FIELD_TYPE_FLOAT:
                        $field[] = round(current(unpack('f', $rawValue)), 6);
                        break;
                    case Constants::FIELD_TYPE_STRING:
                        $field[] = $this->getString(current(unpack('V', $rawValue)));
                        break;
                }
            }
            if (count($field) == 1) {
                $field = $field[0];
            }
            $row[isset($format['name']) ? $format['name'] : $fieldID] = $field;
        }
        return $row;
    }

    /**
     * Extract value from string
     * @param $bitString
     * @param $bitOffset
     * @param $bitLength
     * @return int
     */
    protected function extractValueFromBitstring($bitString, $bitOffset, $bitLength) {
        if ($bitOffset >= 8) {
            $bitString = substr($bitString, floor($bitOffset / 8));
            $bitOffset &= 7;
        }
        $gmp = gmp_import($bitString, 1, GMP_LSW_FIRST | GMP_LITTLE_ENDIAN);
        $mask = ((gmp_init(1) << $bitLength) - 1);
        $gmp = gmp_and($gmp >> $bitOffset, $mask);
        return gmp_intval($gmp);
    }

    /**
     * Get pallet data
     * @param $storage
     * @param $palletId
     * @param $valueId
     * @return mixed
     * @throws \Exception
     */
    protected function getPalletData($storage, $palletId, $valueId) {
        $recordSize = 4;
        $isArray = $storage['storageType'] == Constants::FIELD_COMPRESSION_BITPACKED_INDEXED_ARRAY;
        if ($isArray) {
            $recordSize *= $storage['arrayCount'];
        }
        $offset = $storage['blockOffset'] + $palletId * $recordSize + $valueId * 4;
        if ($offset > $this->palletDataSize) {
            throw new \Exception(sprintf("Requested pallet data offset %d which is beyond pallet data size %d", $offset, $this->palletDataSize));
        }
        $this->fileManager->seekBytes($this->palletDataPosition + $offset);
        return current(unpack('V', $this->fileManager->readBytes(4)));
    }

    /**
     * Get common data
     * @param $storage
     * @param $id
     * @return bool|string
     */
    protected function getCommonData($storage, $id) {
        $lo = 0;
        $hi = floor($storage['additionalDataSize'] / 8) - 1;
        while ($lo <= $hi) {
            $mid = (int)(($hi - $lo) / 2) + $lo;
            $this->fileManager->seekBytes($this->commonBlockPosition + $storage['blockOffset'] + ($mid * 8));
            $thisId = current(unpack('V', $this->fileManager->readBytes(4)));
            if ($thisId < $id) {
                $lo = $mid + 1;
            } elseif ($thisId > $id) {
                $hi = $mid - 1;
            } else {
                return $this->fileManager->readBytes(4);
            }
        }
        return $storage['defaultValue'];
    }
    
    /**
     * Get String
     * @param string|integer $stringBlockOffset
     * @return string
     * @throws \Exception
     */
    private function getString($stringBlockOffset) {
        if ($stringBlockOffset >= $this->stringBlockSize) {
            throw new \Exception("Asked to get string from $stringBlockOffset, string block size is only " . $this->stringBlockSize);
        }
        $maxLength = $this->stringBlockSize - $stringBlockOffset;
        $this->fileManager->seekBytes($this->stringBlockPosition + $stringBlockOffset);
        return stream_get_line($this->fileManager->getFileHandle(), $maxLength, "\x00");
    }

}