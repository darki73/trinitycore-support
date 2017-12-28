<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

use FreedomCore\TrinityCore\Support\DB2Reader\Constants;

/**
 * Class WDB1
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
class WDC1 extends BaseFormat {

    /**
     * @inheritdoc
     * @return mixed|void
     */
    public function prepareFile() {
        $this->headerLength = 84;
        $this->headerFormat = 'V10x/v2y/V9z';
        $this->fileManager->seekBytes(4);
        $parts = array_values(unpack($this->headerFormat, $this->fileManager->readBytes($this->headerLength - 4)));
        $this->processFields($parts)
            ->evaluateHeaderSize()
            ->hasIdBlock()
            ->getBlocksPositions();
        $this->processFile();
    }

    /**
     * @inheritdoc
     * @param array $parts
     * @return $this|mixed
     */
    public function processFields($parts) {
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
        return $this;
    }

    /**
     * @inheritdoc
     * @return $this|mixed
     */
    public function evaluateHeaderSize() {
        $this->headerSize = $this->headerLength + $this->fieldCount * 4;
        return $this;
    }

    /**
     * @inheritdoc
     * @return $this|mixed
     */
    public function hasIdBlock() {
        $this->hasEmbeddedStrings = ($this->flags & 1) > 0;
        $this->hasIdBlock = ($this->flags & 4) > 0;
        return $this;
    }

    /**
     * @inheritdoc
     * @return $this|mixed
     */
    public function getBlocksPositions() {
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
        $this->fieldStorageInfoPosition = $this->copyBlockPosition + $this->copyBlockSize;
        $this->palletDataPosition = $this->fieldStorageInfoPosition + $this->fieldStorageInfoSize;
        $this->commonBlockPosition = $this->palletDataPosition + $this->palletDataSize;
        $this->relationshipDataPosition = $this->commonBlockPosition + $this->commonBlockSize;
        $eof = $this->relationshipDataPosition + $this->relationshipDataSize;
        if ($eof != $this->fileManager->getProcessedSize()) {
            throw new \Exception("Expected size: $eof, actual size: " . $this->fileManager->getProcessedSize());
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @return mixed|void
     */
    public function processFile() {
        $this->fileManager->seekBytes($this->headerLength);
        $this->recordFormat = [];
        for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
            $this->recordFormat[$fieldId] = unpack('sbitShift/voffset', $this->fileManager->readBytes(4));
            $this->recordFormat[$fieldId]['valueLength'] = ceil((32 - $this->recordFormat[$fieldId]['bitShift']) / 8);
            $this->recordFormat[$fieldId]['size'] = $this->recordFormat[$fieldId]['valueLength'];
            $this->recordFormat[$fieldId]['type'] = ($this->recordFormat[$fieldId]['size'] != 4) ? Constants::FIELD_TYPE_INT : Constants::FIELD_TYPE_UNKNOWN;
            if ($this->hasEmbeddedStrings && $this->recordFormat[$fieldId]['type'] == Constants::FIELD_TYPE_UNKNOWN && !is_null($this->stringFields) && in_array($fieldId, $this->stringFields)) {
                $this->recordFormat[$fieldId]['type'] = Constants::FIELD_TYPE_STRING;
            }
            $this->recordFormat[$fieldId]['signed'] = false;
            if ($fieldId > 0) {
                $this->recordFormat[$fieldId - 1]['valueCount'] = floor(($this->recordFormat[$fieldId]['offset'] - $this->recordFormat[$fieldId - 1]['offset']) / $this->recordFormat[$fieldId - 1]['valueLength']);
            }
        }
        $fieldId = $this->fieldCount - 1;
        $remainingBytes = $this->recordSize - $this->recordFormat[$fieldId]['offset'];
        $this->recordFormat[$fieldId]['valueCount'] = max(1, floor($remainingBytes / $this->recordFormat[$fieldId]['valueLength']));
        if ($this->recordFormat[$fieldId]['valueCount'] > 1 && (($this->recordSize % 4 == 0 && $remainingBytes <= 4) || (!$this->hasIdBlock && $this->idField == $fieldId))) {
            $this->recordFormat[$fieldId]['valueCount'] = 1;
        }
        $commonBlockPointer = 0;
        $palletBlockPointer = 0;
        $this->fileManager->seekBytes($this->fieldStorageInfoPosition);
        $storageInfoFormat = 'voffsetBits/vsizeBits/VadditionalDataSize/VstorageType/VbitpackOffsetBits/VbitpackSizeBits/VarrayCount';
        for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
            $parts = unpack($storageInfoFormat, $this->fileManager->readBytes(24));
            if ($parts['arrayCount'] > 0) {
                $this->recordFormat[$fieldId]['valueCount'] = $parts['arrayCount'];
            }
            switch ($parts['storageType']) {
                case Constants::FIELD_COMPRESSION_COMMON:
                    $this->recordFormat[$fieldId]['size'] = 4;
                    $this->recordFormat[$fieldId]['type'] = Constants::FIELD_TYPE_INT;
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
                    break;
                case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED:
                case Constants::FIELD_COMPRESSION_BITPACKED_INDEXED_ARRAY:
                    $this->recordFormat[$fieldId]['size'] = 4;
                    $this->recordFormat[$fieldId]['type'] = Constants::FIELD_TYPE_INT;
                    $this->recordFormat[$fieldId]['offset'] = floor($parts['offsetBits'] / 8);
                    $this->recordFormat[$fieldId]['valueLength'] = ceil(($parts['offsetBits'] + $parts['sizeBits']) / 8) - $this->recordFormat[$fieldId]['offset'] + 1;
                    $parts['blockOffset'] = $palletBlockPointer;
                    $palletBlockPointer += $parts['additionalDataSize'];
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
    }

}