<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

use FreedomCore\TrinityCore\Support\DB2Reader\Constants;
use FreedomCore\TrinityCore\Support\DB2Reader\FileManager;

/**
 * Class WDB2
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
class WDB2 extends BaseFormat
{

    /**
     * WDB2 constructor.
     * @param FileManager $fileManager
     * @param array $stringFields
     * @throws \Exception
     */
    public function __construct(FileManager $fileManager, array $stringFields = [])
    {
        parent::__construct($fileManager, $stringFields);
        $this->processBlocks()->processRecordFormat()->finalizeProcessing();
    }

    /**
     * @inheritdoc
     * @return WDB2
     * @throws \Exception
     */
    public function processBlocks() : WDB2
    {
        $this->idBlockPosition = $this->headerSize;
        if ($this->hasIdBlock) {
            $this->headerSize += 6 * ($this->maxId - $this->minId + 1);
        }
        $this->stringBlockPosition = $this->headerSize + ($this->recordCount * $this->recordSize);
        $this->copyBlockPosition = $this->stringBlockPosition + $this->stringBlockSize;
        $this->isEndOfFile($this->copyBlockPosition, $this->copyBlockSize);
        return $this;
    }

    /**
     * @inheritdoc
     * @return WDB2
     */
    public function processRecordFormat() : WDB2
    {
        $this->recordFormat = [];
        for ($field = 0; $field < $this->fieldCount; $field++) {
            $this->recordFormat[$field] = [
                'bitShift'      => 0,
                'offset'        => $field * 4,
                'valueLength'   => 4,
                'size'          => 4,
                'valueCount'    => 1,
                'type'          => Constants::FIELD_TYPE_UNKNOWN,
                'signed'        => false,
            ] ;
        }
        $this->idField = 0;
        return $this;
    }

    /**
     * @inheritdoc
     * @return WDB2
     */
    public function finalizeProcessing() : WDB2
    {
        $this->populateIdMap();
        $this->guessFieldTypes();
        return $this;
    }
}
