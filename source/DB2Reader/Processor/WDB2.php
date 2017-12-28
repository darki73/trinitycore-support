<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

use FreedomCore\TrinityCore\Support\DB2Reader\Constants;

/**
 * Class WDB2
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
class WDB2 extends BaseFormat {

    /**
     * @inheritdoc
     * @return mixed|void
     */
    public function prepareFile() {
        $this->fileManager->seekBytes(4);
        $this->isWDBC = $this->fileManager->getFormat() == 'WDBC';
        $this->headerFieldCount = $this->isWDBC ? 4 : 11;
        $parts = array_values(
            unpack(
                'V' . $this->headerFieldCount . 'x',
                fread(
                    $this->fileManager->getFileHandle(),
                    4 * $this->headerFieldCount
                )
            )
        );
        $this->processFields($parts)
            ->evaluateHeaderSize()
            ->hasIdBlock()
            ->getBlocksPositions()
            ->validateEndOfFile();
        $this->processFile();
    }

    /**
     * Process General Fields
     * @param array $parts
     * @return WDB2|BaseFormat
     */
    public function processFields($parts) {
        $this->recordCount      = $parts[0];
        $this->fieldCount       = $parts[1];
        $this->recordSize       = $parts[2];
        $this->stringBlockSize  = $parts[3];
        $this->tableHash        = $this->isWDBC ? 0 : $parts[4];
        $this->build            = $this->isWDBC ? 0 : $parts[5];
        $this->timestamp        = $this->isWDBC ? 0 : $parts[6];
        $this->minId            = $this->isWDBC ? 0 : $parts[7];
        $this->maxId            = $this->isWDBC ? 0 : $parts[8];
        $this->locale           = $this->isWDBC ? 0 : $parts[9];
        $this->copyBlockSize    = $this->isWDBC ? 0 : $parts[10];
        $this->hasEmbeddedStrings = false;
        $this->totalFieldCount = $this->fieldCount;
        return $this;
    }

    /**
     * Calculate Header Size
     * @return WDB2|BaseFormat
     */
    public function evaluateHeaderSize() {
        $this->headerSize = 4 * ($this->headerFieldCount + 1);
        return $this;
    }

    /**
     * Check if file has ID Block
     * @return WDB2|BaseFormat
     */
    public function hasIdBlock() {
        $this->hasIdBlock = $this->maxId > 0;
        if ($this->hasIdBlock)
            $this->headerSize += 6 * ($this->maxId - $this->minId + 1);
        return $this;
    }

    /**
     * Get Blocks Positions
     * @return WDB2|BaseFormat
     */
    public function getBlocksPositions() {
        $this->idBlockPosition = $this->headerSize;
        $this->stringBlockPosition = $this->headerSize + ($this->recordCount * $this->recordSize);
        $this->copyBlockPosition = $this->stringBlockPosition + $this->stringBlockSize;
        return $this;
    }

    /**
     * Process File
     * @return mixed|void
     * @throws \Exception
     */
    public function processFile() {
        for ($id = 0; $id < $this->fieldCount; $id++) {
            $this->recordFormat[$id] = [
                'bitShift'      =>  0,
                'offset'        =>  $id * 4,
                'valueLength'   =>  4,
                'valueCount'    =>  1,
                'size'          =>  4,
                'type'          =>  Constants::FIELD_TYPE_UNKNOWN,
                'signed'        =>  false,
            ] ;
        }
        $this->idField = 0;
        $this->populateIDMap();
        $this->guessFieldTypes();
    }

}