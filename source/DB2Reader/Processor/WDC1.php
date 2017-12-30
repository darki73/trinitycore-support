<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

use FreedomCore\TrinityCore\Support\DB2Reader\Constants;
use FreedomCore\TrinityCore\Support\DB2Reader\FileManager;

/**
 * Class WDC1
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
class WDC1 extends BaseFormat {

    /**
     * WDC1 constructor.
     * @param FileManager $fileManager
     * @param array $stringFields
     * @throws \Exception
     */
    public function __construct(FileManager $fileManager, array $stringFields = []) {
        parent::__construct($fileManager, $this->stringFields);
        $this->processBlocks()->processRecordFormat()->finalizeProcessing();
    }

    /**
     * @inheritdoc
     * @return WDC1
     */
    public function processBlocks() : WDC1 {
        if ($this->fieldStorageInfoSize != $this->totalFieldCount * 24) {
            throw new \Exception(sprintf('Expected %d bytes for storage info, instead found %d', $this->totalFieldCount * 24, $this->fieldStorageInfoSize));
        }
        if ($this->hasEmbeddedStrings) {
            if (!$this->hasIdBlock) {
                throw new \Exception("File has embedded strings and no ID block, which was not expected, aborting");
            }
            $this->stringBlockSize = 0;
            $this->stringBlockPosition = $this->indexBlockPosition + 6 * ($this->maxId - $this->minId + 1);
        } else {
            $this->stringBlockPosition = $this->headerSize + ($this->recordCount * $this->recordSize);
        }
        $this->idBlockPosition = $this->stringBlockPosition + $this->stringBlockSize;
        $this->copyBlockPosition = $this->idBlockPosition + ($this->hasIdBlock ? $this->recordCount * 4 : 0);
        $this->fieldStorageInfoPositionition = $this->copyBlockPosition + $this->copyBlockSize;
        $this->palletDataPosition = $this->fieldStorageInfoPositionition + $this->fieldStorageInfoSize;
        $this->commonBlockPosition = $this->palletDataPosition + $this->palletDataSize;
        $this->relationshipDataPosition = $this->commonBlockPosition + $this->commonBlockSize;
        $this->isEndOfFile($this->relationshipDataPosition, $this->relationshipDataSize);
        return $this;
    }

    /**
     * @inheritdoc
     * @return WDC1
     */
    public function processRecordFormat() : WDC1 {
        fseek($this->fileHandle, $this->headerLength);
        $this->recordFormat = [];
        for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
            $this->recordFormat[$fieldId] = unpack('sbitShift/voffset', fread($this->fileHandle, 4));
            $this->recordFormat[$fieldId]['valueLength'] = max(1, ceil((32 - $this->recordFormat[$fieldId]['bitShift']) / 8));
            $this->recordFormat[$fieldId]['size'] = $this->recordFormat[$fieldId]['valueLength'];
            $this->recordFormat[$fieldId]['type'] = ($this->recordFormat[$fieldId]['size'] != 4) ? Constants::FIELD_TYPE_INT : Constants::FIELD_TYPE_UNKNOWN;
            if ($this->hasEmbeddedStrings && $this->recordFormat[$fieldId]['type'] == Constants::FIELD_TYPE_UNKNOWN
                && !is_null($this->stringFields) && in_array($fieldId, $this->stringFields)) {
                $this->recordFormat[$fieldId]['type'] = Constants::FIELD_TYPE_STRING;
            }
            $this->recordFormat[$fieldId]['signed'] = false;
            if ($fieldId > 0) {
                $this->recordFormat[$fieldId - 1]['valueCount'] =
                    floor(($this->recordFormat[$fieldId]['offset'] - $this->recordFormat[$fieldId - 1]['offset']) / $this->recordFormat[$fieldId - 1]['valueLength']);
            }
        }
        $fieldId = $this->fieldCount - 1;
        $remainingBytes = $this->recordSize - $this->recordFormat[$fieldId]['offset'];
        $this->recordFormat[$fieldId]['valueCount'] = max(1, floor($remainingBytes / $this->recordFormat[$fieldId]['valueLength']));
        if ($this->recordFormat[$fieldId]['valueCount'] > 1 &&
            (($this->recordSize % 4 == 0 && $remainingBytes <= 4)
                || (!$this->hasIdBlock && $this->idField == $fieldId))) {
            $this->recordFormat[$fieldId]['valueCount'] = 1;
        }
        $commonBlockPointer = 0;
        $palletBlockPointer = 0;
        fseek($this->fileHandle, $this->fieldStorageInfoPosition);
        $storageInfoFormat = 'voffsetBits/vsizeBits/VadditionalDataSize/VstorageType/VbitpackOffsetBits/VbitpackSizeBits/VarrayCount';
        for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
            $parts = unpack($storageInfoFormat, fread($this->fileHandle, 24));
            switch ($parts['storageType']) {
                case Constants::FIELD_COMPRESSION_COMMON:
                    $this->recordFormat[$fieldId]['size'] = 4;
                    $this->recordFormat[$fieldId]['type'] = Constants::FIELD_TYPE_INT;
                    $this->recordFormat[$fieldId]['valueCount'] = 1;
                    $parts['defaultValue'] = pack('V', $parts['bitpackOffsetBits']);
                    $parts['bitpackOffsetBits'] = 0;
                    $parts['blockOffset'] = $commonBlockPointer;
                    $commonBlockPointer += $parts['additionalDataSize'];
                    break;
                case Constants::FIELD_COMPRESSION_BITPACKED:
                    $this->recordFormat[$fieldId]['size'] = 4;
                    $this->recordFormat[$fieldId]['type'] = Constants::FIELD_TYPE_INT;
                    $this->recordFormat[$fieldId]['offset'] = floor($parts['offsetBits'] / 8);
                    $this->recordFormat[$fieldId]['valueLength'] = ceil(($parts['offsetBits'] + $parts['sizeBits']) / 8) - $this->recordFormat[$fieldId]['offset'] + 1;
                    $this->recordFormat[$fieldId]['valueCount'] = 1;
                    break;
                case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED:
                case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED_ARRAY:
                    $this->recordFormat[$fieldId]['size'] = 4;
                    $this->recordFormat[$fieldId]['type'] = Constants::FIELD_TYPE_INT;
                    $this->recordFormat[$fieldId]['offset'] = floor($parts['offsetBits'] / 8);
                    $this->recordFormat[$fieldId]['valueLength'] = ceil(($parts['offsetBits'] + $parts['sizeBits']) / 8) - $this->recordFormat[$fieldId]['offset'] + 1;
                    $this->recordFormat[$fieldId]['valueCount'] = $parts['arrayCount'] > 0 ? $parts['arrayCount'] : 1;
                    $parts['blockOffset'] = $palletBlockPointer;
                    $palletBlockPointer += $parts['additionalDataSize'];
                    break;
                case Constants::FIELD_COMPRESSION_NONE:
                    if ($parts['arrayCount'] > 0) {
                        $this->recordFormat[$fieldId]['valueCount'] = $parts['arrayCount'];
                    }
                    break;
            }
            $this->recordFormat[$fieldId]['storage'] = $parts;
        }
        if (!$this->hasIdBlock) {
            if ($this->idField >= $this->fieldCount) {
                throw new \Exception("Expected ID field " . $this->idField . " does not exist. Only found " . $this->fieldCount . " fields.");
            }
            if ($this->recordFormat[$this->idField]['valueCount'] != 1) {
                throw new \Exception("Expected ID field " . $this->idField . " reportedly has " . $this->recordFormat[$this->idField]['valueCount'] . " values per row");
            }
        }
        if ($this->relationshipDataSize) {
            $this->recordFormat[$this->totalFieldCount++] = [
                'valueLength' => 4,
                'size' => 4,
                'offset' => $this->recordSize,
                'type' => Constants::FIELD_TYPE_INT,
                'valueCount' => 1,
                'signed' => false,
                'storage' => [
                    'storageType' => Constants::FIELD_COMPRESSION_NONE
                ]
            ];
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @return WDC1
     */
    public function finalizeProcessing() : WDC1 {
        $this->populateIdMap();
        if ($this->hasEmbeddedStrings) {
            for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
                if ($this->recordFormat[$fieldId]['storage']['storageType'] != Constants::FIELD_COMPRESSION_NONE) {
                    throw new \Exception("DB2 with Embedded Strings has compressed field $fieldId");
                }
                unset($this->recordFormat[$fieldId]['offset']);
            }
            $this->populateRecordOffsets();
            if (is_null($this->stringFields)) {
                $this->detectEmbeddedStringFields();
            }
        }
        $this->guessFieldTypes();
        return $this;
    }

}