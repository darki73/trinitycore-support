<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

/**
 * Interface IFormat
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
interface IFormat
{

    /**
     * Process Blocks Information
     * @throws \Exception
     * @return $this
     */
    public function processBlocks();

    /**
     * Process Record Format Information
     * @throws \Exception
     * @return $this
     */
    public function processRecordFormat();

    /**
     * Finalize Data Processing
     * @throws \Exception
     * @return $this
     */
    public function finalizeProcessing();
}
