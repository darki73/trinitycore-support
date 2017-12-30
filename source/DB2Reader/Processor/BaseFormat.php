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
    protected $fileManager = null;

    /**
     * File Handle Resource
     * @var null|resource
     */
    protected $fileHandle = null;

    /**
     * String Fields Array
     * @var array|null
     */
    protected $stringFields = null;

    /**
     * File Format String
     * @var null|string
     */
    protected $fileFormat = null;

    /**
     * File Size
     * @var null|int
     */
    protected $fileSize = 0;

    /**
     * WDB Version Number
     * @var int
     */
    protected $wdbVersion = 0;

    /**
     * Is this a WDB file
     * @var bool
     */
    protected $isWDB = false;

    /**
     * WDC Version Number
     * @var int
     */
    protected $wdcVersion = 0;

    /**
     * Is this a WDC file
     * @var bool
     */
    protected $isWDC = false;

    /**
     * How many fields does header have
     * @var int
     */
    protected $headerFieldCount = 0;

    /**
     * Format of the header
     * @var string|null
     */
    protected $headerFormat = null;

    /**
     * Header Length
     * @var int
     */
    protected $headerLength = 0;

    /**
     * Header Size
     * @var int
     */
    protected $headerSize = 0;

    /**
     * Whether file has Embedded Strings
     * @var bool|int
     */
    protected $hasEmbeddedStrings = false;

    /**
     * Whether file has ID Block
     * @var int
     */
    protected $hasIdBlock = 0;

    /**
     * Whether file has IDs in the Index Block
     * @var bool
     */
    protected $hasIdsInIndexBlock = false;

    /**
     * Position of the ID Block
     * @var int
     */
    protected $idBlockPosition = 0;

    /**
     * Length of the preamble
     * @var int
     */
    protected $preambleLength = 0;

    /**
     * Number of records in file
     * @var int
     */
    protected $recordCount = 0;

    /**
     * Number of fields in file
     * @var int
     */
    protected $fieldCount = 0;

    /**
     * Size of the record
     * @var int
     */
    protected $recordSize = 0;

    /**
     * Size of the string block
     * @var int
     */
    protected $stringBlockSize = 0;

    /**
     * Position of the string block
     * @var int
     */
    protected $stringBlockPosition = 0;

    /**
     * Table Hash
     * @var int|string
     */
    protected $tableHash = 0;

    /**
     * Layout Hash
     * @var int|string
     */
    protected $layoutHash = 0;

    /**
     * Build number
     * @var int
     */
    protected $build = 0;

    /**
     * Timestamp
     * @var int
     */
    protected $timestamp = 0;

    /**
     * Smallest ID
     * @var int
     */
    protected $minId = 0;

    /**
     * Biggest ID
     * @var int
     */
    protected $maxId = 0;

    /**
     * ID Map Array
     * @var array
     */
    protected $idMap = [];

    /**
     * Locale ID
     * @var int
     */
    protected $locale = 0;

    /**
     * Size of the copy block
     * @var int
     */
    protected $copyBlockSize = 0;

    /**
     * Position of the copy block
     * @var int
     */
    protected $copyBlockPosition = 0;

    /**
     * Flags
     * @var int
     */
    protected $flags = 0;

    /**
     * ID Field position
     * @var int
     */
    protected $idField = 0;

    /**
     * Total fields count
     * @var int
     */
    protected $totalFieldCount = 0;

    /**
     * Common block size
     * @var int
     */
    protected $commonBlockSize = 0;

    /**
     * Common block position
     * @var int
     */
    protected $commonBlockPosition = 0;

    /**
     * Bitpacked Data Position
     * @var int
     */
    protected $bitpackedDataPosition = 0;

    /**
     * Number of lookup columns
     * @var int
     */
    protected $lookupColumnCount = 0;

    /**
     * Position of the index block
     * @var int
     */
    protected $indexBlockPosition = 0;

    /**
     * Size of the ID list
     * @var int
     */
    protected $idListSize = 0;

    /**
     * Size of the storage info field
     * @var int
     */
    protected $fieldStorageInfoSize = 0;

    /**
     * Position of the storage info block
     * @var int
     */
    protected $fieldStorageInfoPosition = 0;

    /**
     * Pallet data size
     * @var int
     */
    protected $palletDataSize = 0;

    /**
     * Palled data position
     * @var int
     */
    protected $palletDataPosition = 0;

    /**
     * Relationship data size
     * @var int
     */
    protected $relationshipDataSize = 0;

    /**
     * Relationship data position
     * @var int
     */
    protected $relationshipDataPosition = 0;

    /**
     * Offsets for record
     * @var array|null
     */
    protected $recordOffsets = null;

    /**
     * Common Lookup Array
     * @var array
     */
    protected $commonLookup = [];

    /**
     * Record Format Array
     * @var array
     */
    protected $recordFormat = [];

    /**
     * BaseFormat constructor.
     * @param FileManager $fileManager
     * @param array $stringFields
     * @throws \Exception
     */
    public function __construct(FileManager $fileManager, array $stringFields = []) {
        $this->fileManager = $fileManager;
        $this->stringFields = $stringFields;
        $this->setFileHandle()->setFileFormat()->setFileSize()->getFileVersion()->initializeHeaderStructure()->finalPreparations();
    }

    /**
     * Get record by ID
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getRecord(int $id) {
        if (!isset($this->idMap[$id])) {
            return null;
        }
        return $this->getRecordByOffset($this->idMap[$id], $id);
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
     * Get field types
     * @param bool $byName
     * @return array
     */
    public function getFieldTypes(bool $byName = true) : array {
        $fieldTypes = [];
        foreach ($this->recordFormat as $fieldID => $format) {
            if ($byName && isset($format['name'])) {
                $fieldID = $format['name'];
            }
            $fieldTypes[$fieldID] = $format['type'];
        }
        return $fieldTypes;
    }

    /**
     * Set signed flag to specified fields
     * @param array $fields
     * @return array
     * @throws \Exception
     */
    public function setFieldsSigned(array $fields) {
        foreach ($fields as $fieldID => $isSigned) {
            if ($fieldID < 0 || $fieldID >= $this->totalFieldCount) {
                throw new \Exception("Field ID $fieldID out of bounds: 0-" . ($this->totalFieldCount - 1));
            }
            if (!$this->hasIdBlock && $this->idField == $fieldID) {
                continue;
            }
            if ($this->recordFormat[$fieldID]['type'] != Constants::FIELD_TYPE_INT) {
                continue;
            }
            $this->recordFormat[$fieldID]['signed'] = !!$isSigned;
        }
        $signedFields = [];
        foreach ($this->recordFormat as $fieldID => $format) {
            $signedFields[$fieldID] = $format['signed'];
        }
        return $signedFields;
    }

    /**
     * Set names for the fields
     * @param array $names
     * @return array
     * @throws \Exception
     */
    public function setFieldNames(array $names) {
        if ($this->totalFieldCount !== count($names))
            if (in_array('id', $names)) {
                unset($names[array_search('id', $names)]);
                $names = array_values($names);
            }
        foreach ($names as $fieldID => $name) {
            if (!is_numeric($fieldID)) {
                throw new \Exception("Field ID $fieldID must be numeric");
            }
            if (is_numeric($name)) {
                throw new \Exception("Field $fieldID Name ($name) must NOT be numeric");
            }
            if ($fieldID < 0 || $fieldID >= $this->totalFieldCount) {
                throw new \Exception("Field ID $fieldID out of bounds: 0-" . ($this->totalFieldCount - 1));
            }
            if (!$name) {
                unset($this->recordFormat[$fieldID]['name']);
            } else {
                $this->recordFormat[$fieldID]['name'] = $name;
            }
        }
        $namedFields = [];
        foreach ($this->recordFormat as $fieldID => $format) {
            if (isset($format['name'])) {
                $namedFields[$fieldID] = $format['name'];
            }
        }
        return $namedFields;
    }

    /**
     * Get total number of fields in the file
     * @return int
     */
    public function getFieldCount() {
        return $this->totalFieldCount;
    }

    /**
     * Get layout hash
     * @return int|string
     */
    public function getLayoutHash() {
        return $this->layoutHash;
    }

    /**
     * Get list of the IDs
     * @return array
     */
    public function getIDs() : array {
        return array_keys($this->idMap);
    }

    /**
     * Check if we've reached end of file
     * @param int $startPosition
     * @param int $endPosition
     * @throws \Exception
     */
    protected function isEndOfFile(int $startPosition, int $endPosition) {
        $endOfFile = $startPosition + $endPosition;
        if ($endOfFile != $this->fileSize) {
            throw new \Exception('Expected size: ' . $endOfFile . ', actual size: ' . $this->fileSize);
        }
    }

    /**
     * Populate ID Map
     * @throws \Exception
     */
    protected function populateIdMap() {
        $this->idMap = [];
        if (!$this->hasIdBlock) {
            $this->recordFormat[$this->idField]['signed'] = false;
            for ($x = 0; $x < $this->recordCount; $x++) {
                $rec = $this->getRecordByOffset($x, false);
                $this->idMap[$rec[$this->idField]] = $x;
            }
        } else {
            fseek($this->fileHandle, $this->idBlockPosition);
            if ($this->fileFormat == 'WDB2') {
                for ($x = $this->minId; $x <= $this->maxId; $x++) {
                    $record = current(unpack('V', fread($this->fileHandle, 4)));
                    if ($record) {
                        $this->idMap[$x] = $record - 1;
                    }
                    fseek($this->fileHandle, 2, SEEK_CUR);
                }
            } else {
                for ($x = 0; $x < $this->recordCount; $x++) {
                    $this->idMap[current(unpack('V', fread($this->fileHandle, 4)))] = $x;
                }
            }
        }
        if ($this->copyBlockSize) {
            fseek($this->fileHandle, $this->copyBlockPosition);
            $entryCount = floor($this->copyBlockSize / 8);
            for ($x = 0; $x < $entryCount; $x++) {
                list($newId, $existingId) = array_values(unpack('V*', fread($this->fileHandle, 8)));
                if (!isset($this->idMap[$existingId])) {
                    throw new \Exception('Copy block referenced ID ' . $existingId . ' which does not exist!');
                }
                $this->idMap[$newId] = $this->idMap[$existingId];
            }
            ksort($this->idMap, SORT_NUMERIC);
        }
    }

    /**
     * Guess Type of the fields in the file
     * @throws \Exception
     */
    protected function guessFieldTypes() {
        foreach ($this->recordFormat as $fieldID => &$format) {
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
                    for ($offsetFieldId = 0; $offsetFieldId < $fieldID; $offsetFieldId++) {
                        if ($this->recordFormat[$offsetFieldId]['type'] == Constants::FIELD_TYPE_STRING) {
                            for ($offsetFieldValueId = 0; $offsetFieldValueId < $this->recordFormat[$offsetFieldId]['valueCount']; $offsetFieldValueId++) {
                                $byteOffset = strpos($data, "\x00", $byteOffset);
                                if ($byteOffset === false) {
                                    throw new \Exception("Could not find end of embedded string $offsetFieldId x $offsetFieldValueId in record $recordOffset");
                                }
                                $byteOffset++; // skip nul byte
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
                            fseek($this->fileHandle, $this->stringBlockPosition + $value - 1);
                            if (fread($this->fileHandle, 1) !== "\x00") {
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
     * Find Common Fields
     * @throws \Exception
     */
    protected function findCommonFields() {
        $this->commonLookup = [];
        if ($this->commonBlockSize == 0) {
            return;
        }
        $commonBlockEnd = $this->commonBlockPosition + $this->commonBlockSize;
        fseek($this->fileHandle, $this->commonBlockPosition);
        $fieldCount = current(unpack('V', fread($this->fileHandle, 4)));
        if ($fieldCount != $this->totalFieldCount) {
            throw new \Exception(sprintf("Expected %d fields in common block, found %d", $this->totalFieldCount, $fieldCount));
        }
        $fourBytesEveryType = true;
        for ($field = 0; $field < $this->totalFieldCount; $field++) {
            list($entryCount, $enumType) = array_values(unpack('V1x/C1y', fread($this->fileHandle, 5)));
            $mapSize = 8 * $entryCount;
            if (($enumType > 4) ||
                ($entryCount > $this->recordCount) ||
                (ftell($this->fileHandle) + $mapSize + ($field + 1 < $this->totalFieldCount ? 5 : 0) > $commonBlockEnd)) {
                $fourBytesEveryType = false;
                break;
            }
            fseek($this->fileHandle, $mapSize, SEEK_CUR);
        }
        $fourBytesEveryType &= $commonBlockEnd - ftell($this->fileHandle) <= 8;
        fseek($this->fileHandle, $this->commonBlockPosition + 4);
        for ($field = 0; $field < $this->totalFieldCount; $field++) {
            list($entryCount, $enumType) = array_values(unpack('V1x/C1y', fread($this->fileHandle, 5)));
            if ($field < $this->fieldCount) {
                if ($entryCount > 0) {
                    throw new \Exception(sprintf("Expected 0 entries in common block field %d, instead found %d", $field, $entryCount));
                }
                continue;
            }
            $size = 4;
            $type = Constants::FIELD_TYPE_INT;
            switch ($enumType) {
                case 0: // string
                    $type = Constants::FIELD_TYPE_STRING;
                    break;
                case 1: // short
                    $size = 2;
                    break;
                case 2: // byte
                    $size = 1;
                    break;
                case 3: // float
                    $type = Constants::FIELD_TYPE_FLOAT;
                    break;
                case 4: // 4-byte int
                    break;
                default:
                    throw new \Exception("Unknown common field type: $enumType");
            }
            $this->recordFormat[$field] = [
                'valueCount'  => 1,
                'valueLength' => $size,
                'size'        => $size,
                'type'        => $type,
                'signed'      => false,
                'zero'        => str_repeat("\x00", $size),
            ];
            $this->commonLookup[$field] = [];
            $embeddedStrings = false;
            if ($this->hasEmbeddedStrings && $type == Constants::FIELD_TYPE_STRING) {
                // @codeCoverageIgnoreStart
                // file with both embedded strings and common block not found in wild, this is just a guess
                $embeddedStrings = true;
                $this->recordFormat[$field]['zero'] = "\x00";
                // @codeCoverageIgnoreEnd
            }
            for ($entry = 0; $entry < $entryCount; $entry++) {
                $id = current(unpack('V', fread($this->fileHandle, 4)));
                if ($embeddedStrings) {
                    // @codeCoverageIgnoreStart
                    // file with both embedded strings and common block not found in wild, this is just a guess
                    $maxLength = $this->commonBlockSize - (ftell($this->fileHandle) - $this->commonBlockPosition);
                    $this->commonLookup[$field][$id] = stream_get_line($this->fileHandle, $maxLength, "\x00") . "\x00";
                    // @codeCoverageIgnoreEnd
                } else {
                    $this->commonLookup[$field][$id] = ($fourBytesEveryType && $size != 4) ?
                        substr(fread($this->fileHandle, 4), 0, $size) :
                        fread($this->fileHandle, $size);
                }
            }
        }
    }

    /**
     * Populate record offsets array
     */
    protected function populateRecordOffsets() {
        fseek($this->fileHandle, $this->indexBlockPosition);
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
                $pointer = unpack('Vid/Vpos/vsize', fread($this->fileHandle, 10));
                $this->idMap[$pointer['id']] = $x;
            } else {
                $pointer = unpack('Vpos/vsize', fread($this->fileHandle, 6));
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
     * Detect embedded string fields
     * @throws \Exception
     */
    protected function detectEmbeddedStringFields() {
        $stringFields = [];
        foreach ($this->recordFormat as $fieldID => &$format) {
            if ($format['type'] != Constants::FIELD_TYPE_UNKNOWN || $format['size'] != 4) {
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
                                // should never happen, we just assigned this field as a string in a prior loop!
                                // @codeCoverageIgnoreStart
                                throw new \Exception("Could not find end of embedded string $offsetFieldId x $offsetFieldValueId in record $recordOffset");
                                // @codeCoverageIgnoreEnd
                            }
                            $byteOffset++; // skip nul byte
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
     * @param int $recordOffset
     * @param bool $id
     * @return array
     * @throws \Exception
     */
    private function getRecordByOffset(int $recordOffset, bool $id) {
        if ($recordOffset < 0 || $recordOffset >= $this->recordCount) {
            // @codeCoverageIgnoreStart
            throw new \Exception("Requested record offset $recordOffset out of bounds: 0-" . $this->recordCount);
            // @codeCoverageIgnoreEnd
        }
        $record = $this->getRawRecord($recordOffset, $id);
        $fieldMax = $id === false ? $this->fieldCount : $this->totalFieldCount;
        $runningOffset = 0;
        $row = [];
        for ($fieldID = 0; $fieldID < $fieldMax; $fieldID++) {
            $field = [];
            $format = $this->recordFormat[$fieldID];
            for ($valueId = 0; $valueId < $format['valueCount']; $valueId++) {
                if (isset($format['storage']) && !$this->hasEmbeddedStrings) {
                    switch ($format['storage']['storageType']) {
                        case Constants::FIELD_COMPRESSION_BITPACKED:
                        case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED:
                        case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED_ARRAY:
                        $rawValue = BaseFormat::extractValueFromBitstring(substr($record, $format['offset'], $format['valueLength']),
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
                        case Constants::FIELD_COMPRESSION_NONE:
                            $rawValue = substr($record, $format['offset'] + $valueId * $format['valueLength'], $format['valueLength']);
                            break;
                        default:
                            throw new \Exception(sprintf("Field %d has an unknown storage type: %d", $fieldID, $format['storage']['storageType']));
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
     * Get Pallet Data
     * @param $storage
     * @param $palletId
     * @param $valueId
     * @return mixed
     * @throws \Exception
     */
    private function getPalletData($storage, $palletId, $valueId) {
        $recordSize = 4;
        $isArray = $storage['storageType'] == Constants::FIELD_COMPRESSION_BITPACKED_INDEXED_ARRAY;
        if ($isArray) {
            $recordSize *= $storage['arrayCount'];
        }
        $offset = $storage['blockOffset'] + $palletId * $recordSize + $valueId * 4;
        if ($offset > $this->palletDataSize) {
            throw new \Exception(sprintf("Requested pallet data offset %d which is beyond pallet data size %d", $offset, $this->palletDataSize));
        }
        fseek($this->fileHandle, $this->palletDataPosition + $offset);
        return current(unpack('V', fread($this->fileHandle, 4)));
    }

    /**
     * Get Common Data
     * @param $storage
     * @param $id
     * @return bool|string
     */
    private function getCommonData($storage, $id) {
        $lo = 0;
        $hi = floor($storage['additionalDataSize'] / 8) - 1;
        while ($lo <= $hi) {
            $mid = (int)(($hi - $lo) / 2) + $lo;
            fseek($this->fileHandle, $this->commonBlockPosition + $storage['blockOffset'] + ($mid * 8));
            $thisId = current(unpack('V', fread($this->fileHandle, 4)));
            if ($thisId < $id) {
                $lo = $mid + 1;
            } elseif ($thisId > $id) {
                $hi = $mid - 1;
            } else {
                return fread($this->fileHandle, 4);
            }
        }
        return $storage['defaultValue'];
    }

    /**
     * Get String
     * @param $stringBlockOffset
     * @return string
     * @throws \Exception
     */
    private function getString($stringBlockOffset) {
        if ($stringBlockOffset >= $this->stringBlockSize) {
            // @codeCoverageIgnoreStart
            throw new \Exception("Asked to get string from $stringBlockOffset, string block size is only ".$this->stringBlockSize);
            // @codeCoverageIgnoreEnd
        }
        $maxLength = $this->stringBlockSize - $stringBlockOffset;
        fseek($this->fileHandle, $this->stringBlockPosition + $stringBlockOffset);
        return stream_get_line($this->fileHandle, $maxLength, "\x00");
    }

    /**
     * Get Raw Record
     * @param int $recordOffset
     * @param bool $id
     * @return bool|string
     * @throws \Exception
     */
    private function getRawRecord(int $recordOffset, bool $id = false) {
        if (!is_null($this->recordOffsets)) {
            $pointer = $this->recordOffsets[$recordOffset];
            if ($pointer['size'] == 0) {
                // @codeCoverageIgnoreStart
                throw new \Exception("Requested record offset $recordOffset which is empty");
                // @codeCoverageIgnoreEnd
            }
            fseek($this->fileHandle, $pointer['pos']);
            $data = fread($this->fileHandle, $pointer['size']);
        } else {
            fseek($this->fileHandle, $this->headerSize + $recordOffset * $this->recordSize);
            $data = fread($this->fileHandle, $this->recordSize);
        }
        if ($this->fileFormat == 'WDB6' && $id !== false && $this->commonBlockSize) {
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
        if ($this->relationshipDataSize) {
            $relationshipPosition = $recordOffset * 8 + 12;
            if ($relationshipPosition >= $this->relationshipDataSize) {
                throw new \Exception(sprintf("Attempted to read from offset %d in relationship map, size is only %d", $relationshipPosition, $this->relationshipDataSize));
            }
            fseek($this->fileHandle, $this->relationshipDataPosition + $relationshipPosition);
            $data .= fread($this->fileHandle, 4);
            $relationshipOffset = current(unpack('V', fread($this->fileHandle, 4)));
            if ($relationshipOffset != $recordOffset) {
                throw new \Exception(sprintf("Record offset %d attempted read of relationship offset %d", $recordOffset, $relationshipOffset));
            }
        }
        return $data;
    }

    /**
     * Populate file handle variable
     * @return BaseFormat
     */
    private function setFileHandle() : BaseFormat {
        $this->fileHandle = $this->fileManager->getFileHandle();
        return $this;
    }
    
    /**
     * Populate file format variable
     * @return BaseFormat
     */
    private function setFileFormat() : BaseFormat {
        $this->fileFormat = $this->fileManager->getFormat();
        return $this;
    }

    /**
     * Populate file size variable
     * @return BaseFormat
     */
    private function setFileSize() : BaseFormat {
        $this->fileSize = $this->fileManager->getProcessedSize();
        return $this;
    }

    /**
     * Get File Version Number and Type
     * @return BaseFormat
     */
    private function getFileVersion() : BaseFormat {
        $isWDB = strstr($this->fileFormat, 'WDB') ? true : false;
        if ($isWDB) {
            $this->isWDB = true;
            $this->getWDBVersion();
        } else {
            $this->isWDC = true;
            $this->getWDCVersion();
        }
        return $this;
    }

    /**
     * Get WDB Version Number
     * @return BaseFormat
     */
    private function getWDBVersion() : BaseFormat {
        $this->wdbVersion = intval(substr($this->fileFormat, 3));
        $this->isWDC = false;
        return $this;
    }

    /**
     * Get WDC Version Number
     * @return BaseFormat
     */
    private function getWDCVersion() : BaseFormat {
        $this->wdcVersion = intval(substr($this->fileFormat, 3));
        $this->isWDB = false;
        return $this;
    }

    /**
     * Initialize File Header Structure
     * @return BaseFormat
     */
    private function initializeHeaderStructure() : BaseFormat {
        if ($this->isWDB) {
            if ($this->wdbVersion >= 6) {
                $this->preambleLength = 56;
                $this->headerFormat = 'V10x/v2y/V2z';
            } else if ($this->wdbVersion < 6 && $this->wdbVersion > 2) {
                $this->preambleLength = 48;
                $this->headerFormat = 'V10x/v2y';
            } else if ($this->wdbVersion === 2) {
                $this->headerFieldCount = 11;
            } else {
                $this->headerFieldCount = 4;
            }
            if ($this->wdbVersion > 2) {
                fseek($this->fileHandle, 4);
                $parts = array_values(unpack($this->headerFormat, fread($this->fileHandle, $this->preambleLength - 4)));
            } else {
                $parts = array_values(unpack('V' . $this->headerFieldCount . 'x', fread($this->fileHandle, 4 * $this->headerFieldCount)));
            }
        } else if ($this->isWDC) {
            $this->headerLength = 84;
            $this->headerFormat = 'V10x/v2y/V9z';
            fseek($this->fileHandle, 4);
            $parts = array_values(unpack($this->headerFormat, fread($this->fileHandle, $this->headerLength - 4)));
        } else {
            $parts = [];
            BaseFormat::throwRuntimeException('Unknown file format ' . $this->fileFormat . '!');
        }
        $this->updateFileStructureData($parts);
        return $this;
    }

    /**
     * Update file structure details
     * @param array $parts
     * @return BaseFormat
     */
    private function updateFileStructureData(array $parts) : BaseFormat {
        if ($this->headerFormat === null) {
            $this->recordCount      = $parts[0];
            $this->fieldCount       = $parts[1];
            $this->recordSize       = $parts[2];
            $this->stringBlockSize  = $parts[3];
            $this->tableHash        = ($this->wdbVersion === 0) ? 0 : $parts[4];
            $this->build            = ($this->wdbVersion === 0) ? 0 : $parts[5];
            $this->timestamp        = ($this->wdbVersion === 0) ? 0 : $parts[6];
            $this->minId            = ($this->wdbVersion === 0) ? 0 : $parts[7];
            $this->maxId            = ($this->wdbVersion === 0) ? 0 : $parts[8];
            $this->locale           = ($this->wdbVersion === 0) ? 0 : $parts[9];
            $this->copyBlockSize    = ($this->wdbVersion === 0) ? 0 : $parts[10];
        } else {
            switch ($this->headerFormat) {
                case 'V10x/v2y/V2z':
                case 'V10x/v2y':
                        $this->recordCount      = $parts[0];
                        $this->fieldCount       = $parts[1];
                        $this->recordSize       = $parts[2];
                        $this->stringBlockSize  = $parts[3];
                        $this->tableHash        = $parts[4];
                        $this->layoutHash       = $parts[5];
                        $this->minId            = $parts[6];
                        $this->maxId            = $parts[7];
                        $this->locale           = $parts[8];
                        $this->copyBlockSize    = $parts[9];
                        $this->flags            = $parts[10];
                        $this->idField          = $parts[11];
                        $this->totalFieldCount  = ($this->wdbVersion >= 6) ? $parts[12] : $this->fieldCount;
                        $this->commonBlockSize  = ($this->wdbVersion >= 6) ? $parts[13] : 0;
                    break;
                case 'V10x/v2y/V9z':
                    $this->recordCount              = $parts[0];
                    $this->fieldCount               = $parts[1];
                    $this->recordSize               = $parts[2];
                    $this->stringBlockSize          = $parts[3];
                    $this->tableHash                = $parts[4];
                    $this->layoutHash               = $parts[5];
                    $this->minId                    = $parts[6];
                    $this->maxId                    = $parts[7];
                    $this->locale                   = $parts[8];
                    $this->copyBlockSize            = $parts[9];
                    $this->flags                    = $parts[10];
                    $this->idField                  = $parts[11];
                    $this->totalFieldCount          = $parts[12];
                    $this->bitpackedDataPosition    = $parts[13];
                    $this->lookupColumnCount        = $parts[14];
                    $this->indexBlockPosition       = $parts[15];
                    $this->idListSize               = $parts[16];
                    $this->fieldStorageInfoSize     = $parts[17];
                    $this->commonBlockSize          = $parts[18];
                    $this->palletDataSize           = $parts[19];
                    $this->relationshipDataSize     = $parts[20];
                    break;
                default:
                    BaseFormat::throwRuntimeException('Unknown file format ' . $this->fileFormat . '!');
            }
        }
        return $this;
    }

    /**
     * Perform final preparations
     */
    private function finalPreparations() {
        if ($this->isWDB) {
            if ($this->wdbVersion >= 5) {
                $this->headerSize = $this->preambleLength + $this->fieldCount * 4;
                $this->hasEmbeddedStrings = ($this->flags & 1) > 0;
                $this->hasIdBlock = ($this->flags & 4) > 0;
            } else {
                $this->headerSize = 4 * ($this->headerFieldCount + 1);
                $this->hasEmbeddedStrings = false;
                $this->totalFieldCount = $this->fieldCount;
                $this->hasIdBlock = $this->maxId > 0;
            }
        } else {
            $this->headerSize = $this->headerLength + $this->fieldCount * 4;
            $this->hasEmbeddedStrings = ($this->flags & 1) > 0;
            $this->hasIdBlock = ($this->flags & 4) > 0;
        }
    }

    /**
     * Throw Runtime Exception with message
     * @param string $message
     */
    private static function throwRuntimeException(string $message) {
        throw new \RuntimeException(sprintf('%s::%s(): %s',substr(strrchr(__CLASS__, "\\"), 1), debug_backtrace()[1]['function'], $message));
    }

    /**
     * Extract Value From Bit String
     * @param $bitString
     * @param $bitOffset
     * @param $bitLength
     * @return int
     */
    private function extractValueFromBitstring($bitString, $bitOffset, $bitLength) {
        if ($bitOffset >= 8) {
            $bitString = substr($bitString, floor($bitOffset / 8));
            $bitOffset &= 7;
        }
        $gmp = gmp_import($bitString, 1, GMP_LSW_FIRST | GMP_LITTLE_ENDIAN);
        $mask = ((gmp_init(1) << $bitLength) - 1);
        $gmp = gmp_and($gmp >> $bitOffset, $mask);
        return gmp_intval($gmp);
    }
}