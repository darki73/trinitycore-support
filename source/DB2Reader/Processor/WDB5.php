<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

use FreedomCore\TrinityCore\Support\DB2Reader\Constants;

/**
 * Class WDB5
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
class WDB5 extends BaseFormat {

    /**
     * @inheritdoc
     */
    public function prepareFile() {
        $this->wdbVersion = intval(substr($this->fileManager->getFormat(), 3));
        if ($this->wdbVersion >= 6) {
            $this->preambleLength = 56;
            $this->headerFormat = 'V10x/v2y/V2z';
        } else {
            $this->preambleLength = 48;
            $this->headerFormat = 'V10x/v2y';
        }
        $this->fileManager->seekBytes(4);
        $parts = array_values(unpack($this->headerFormat, $this->fileManager->readBytes($this->preambleLength - 4)));
        $this->processFields($parts)
            ->evaluateHeaderSize()
            ->hasIdBlock()
            ->getBlocksPositions();
        $this->processFile();
    }

    /**
     * @inheritdoc
     */
    public function processFields($parts) {
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
        $this->totalFieldCount  = $this->wdbVersion >= 6 ? $parts[12] : $this->fieldCount;
        $this->commonBlockSize  = $this->wdbVersion >= 6 ? $parts[13] : 0;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function evaluateHeaderSize() {
        $this->headerSize = $this->preambleLength + $this->fieldCount * 4;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasIdBlock() {
        $this->hasEmbeddedStrings = ($this->flags & 1) > 0;
        $this->hasIdBlock = ($this->flags & 4) > 0;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBlocksPositions() {
        if ($this->hasEmbeddedStrings) {
            if (!$this->hasIdBlock) {
                throw new \Exception("File has embedded strings and no ID block, which was not expected, aborting");
            }
            $this->stringBlockPosition = $this->fileManager->getProcessedSize() - $this->copyBlockSize - $this->commonBlockSize - ($this->recordCount * 4);
            $this->indexBlockPosition = $this->stringBlockSize;
            $this->stringBlockSize = 0;
        } else {
            $this->stringBlockPosition = $this->headerSize + ($this->recordCount * $this->recordSize);
        }
        $this->idBlockPosition = $this->stringBlockPosition + $this->stringBlockSize;
        $this->copyBlockPosition = $this->idBlockPosition + ($this->hasIdBlock ? $this->recordCount * 4 : 0);
        $this->commonBlockPosition = $this->copyBlockPosition + $this->copyBlockSize;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function processFile() {
        $this->fileManager->seekBytes($this->preambleLength);
        $this->recordFormat = [];
        for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
            $this->recordFormat[$fieldId] = unpack('vbitShift/voffset', $this->fileManager->readBytes(4));
            $this->recordFormat[$fieldId]['valueLength'] = ceil((32 - $this->recordFormat[$fieldId]['bitShift']) / 8);
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

        $fieldID = $this->fieldCount - 1;
        $remainingBytes = $this->recordSize - $this->recordFormat[$fieldID]['offset'];
        $this->recordFormat[$fieldID]['valueCount'] = max(1, floor($remainingBytes / $this->recordFormat[$fieldID]['valueLength']));
        if ($this->recordFormat[$fieldID]['valueCount'] > 1 && (($this->recordSize % 4 == 0 && $remainingBytes <= 4) || (!$this->hasIdBlock && $this->idField == $fieldID))) {
            $this->recordFormat[$fieldID]['valueCount'] = 1;
        }
        if (!$this->hasIdBlock) {
            if ($this->idField >= $this->fieldCount) {
                throw new \Exception("Expected ID field " . $this->idField . " does not exist. Only found " . $this->fieldCount . " fields.");
            }
            if ($this->recordFormat[$this->idField]['valueCount'] != 1) {
                throw new \Exception("Expected ID field " . $this->idField . " reportedly has " . $this->recordFormat[$this->idField]['valueCount'] . " values per row");
            }
        }
        $this->findCommonFields();
        $this->populateIdMap();
        if ($this->hasEmbeddedStrings) {
            for ($fieldID = 0; $fieldID < $this->fieldCount; $fieldID++) {
                unset($this->recordFormat[$fieldID]['offset']);
            }
            $this->populateRecordOffsets();
            if (is_null($this->stringFields)) {
                $this->detectEmbeddedStringFields();
            }
        }
        $this->guessFieldTypes();
    }

}