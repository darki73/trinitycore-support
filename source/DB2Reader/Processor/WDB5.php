<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

use FreedomCore\TrinityCore\Support\DB2Reader\Constants;
use FreedomCore\TrinityCore\Support\DB2Reader\FileManager;

/**
 * Class WDB5
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
class WDB5 extends BaseFormat {

    /**
     * WDB5 constructor.
     * @param FileManager $fileManager
     * @param array $stringFields
     * @throws \Exception
     */
    public function __construct(FileManager $fileManager, array $stringFields = []) {
        parent::__construct($fileManager, $stringFields);
        $this->processBlocks()->processRecordFormat()->finalizeProcessing();
    }

    /**
     * @inheritdoc
     * @return WDB5
     */
    public function processBlocks() : WDB5 {
        if ($this->hasEmbeddedStrings) {
            if (!$this->hasIdBlock) {
                throw new \Exception("File has embedded strings and no ID block, which was not expected, aborting");
            }
            $this->stringBlockPosition = $this->fileSize - $this->copyBlockSize - $this->commonBlockSize - ($this->recordCount * 4);
            $this->indexBlockPosition = $this->stringBlockSize;
            $this->stringBlockSize = 0;
        } else {
            $this->stringBlockPosition = $this->headerSize + ($this->recordCount * $this->recordSize);
        }
        $this->idBlockPosition = $this->stringBlockPosition + $this->stringBlockSize;
        $this->copyBlockPosition = $this->idBlockPosition + ($this->hasIdBlock ? $this->recordCount * 4 : 0);
        $this->commonBlockPosition = $this->copyBlockPosition + $this->copyBlockSize;
        $this->isEndOfFile($this->commonBlockPosition, $this->commonBlockSize);
        return $this;
    }

    /**
     * @inheritdoc
     * @return WDB5
     */
    public function processRecordFormat() : WDB5 {
        fseek($this->fileHandle, $this->preambleLength);
        $this->recordFormat = [];
        for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
            $this->recordFormat[$fieldId] = unpack('vbitShift/voffset', fread($this->fileHandle, 4));
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
        $fieldId = $this->fieldCount - 1;
        $remainingBytes = $this->recordSize - $this->recordFormat[$fieldId]['offset'];
        $this->recordFormat[$fieldId]['valueCount'] = max(1, floor($remainingBytes / $this->recordFormat[$fieldId]['valueLength']));
        if ($this->recordFormat[$fieldId]['valueCount'] > 1 && (($this->recordSize % 4 == 0 && $remainingBytes <= 4) || (!$this->hasIdBlock && $this->idField == $fieldId))) {
            $this->recordFormat[$fieldId]['valueCount'] = 1;
        }
        if (!$this->hasIdBlock) {
            if ($this->idField >= $this->fieldCount) {
                throw new \Exception("Expected ID field " . $this->idField . " does not exist. Only found " . $this->fieldCount . " fields.");
            }
            if ($this->recordFormat[$this->idField]['valueCount'] != 1) {
                throw new \Exception("Expected ID field " . $this->idField . " reportedly has " . $this->recordFormat[$this->idField]['valueCount'] . " values per row");
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @return WDB5
     */
    public function finalizeProcessing() : WDB5 {
        $this->findCommonFields();
        $this->populateIdMap();
        if ($this->hasEmbeddedStrings) {
            for ($fieldId = 0; $fieldId < $this->fieldCount; $fieldId++) {
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