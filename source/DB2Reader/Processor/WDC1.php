<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

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
        parent::__construct($fileManager, $stringFields);
        $this->processBlocks()->processRecordFormat()->finalizeProcessing();
    }

    /**
     * @inheritdoc
     * @return WDC1
     */
    public function processBlocks() : WDC1 {

        return $this;
    }

    /**
     * @inheritdoc
     * @return WDC1
     */
    public function processRecordFormat() : WDC1 {

        return $this;
    }

    /**
     * @inheritdoc
     * @return WDC1
     */
    public function finalizeProcessing() : WDC1 {

        return $this;
    }

}