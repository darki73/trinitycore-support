<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Processor;

/**
 * Interface IFormat
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Processor
 */
interface IFormat {

    /**
     * Prepare file for parsing
     * @return mixed
     */
    public function prepareFile();

    /**
     * Process General Fields
     * @param array $parts
     * @return mixed
     */
    public function processFields($parts);

    /**
     * Calculate Header Size
     * @return mixed
     */
    public function evaluateHeaderSize();

    /**
     * Check if file has ID Block
     * @return mixed
     */
    public function hasIdBlock();

    /**
     * Get Blocks Positions
     * @return mixed
     */
    public function getBlocksPositions();

    /**
     * Validate That File Sizes Match
     * @throws \Exception
     */
    public function validateEndOfFile();

    /**
     * Process File
     * @return mixed
     */
    public function processFile();


}