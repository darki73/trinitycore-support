<?php namespace FreedomCore\TrinityCore\Support;

use FreedomCore\TrinityCore\Support\DB2Reader\FileManager;
use FreedomCore\TrinityCore\Support\DB2Reader\Processor\BaseFormat;
use FreedomCore\TrinityCore\Support\DB2Reader\Processor\WDB2;
use FreedomCore\TrinityCore\Support\DB2Reader\Processor\WDB5;

/**
 * Class DB2Reader
 * @package FreedomCore\TrinityCore\Support
 */
class DB2Reader {

    /**
     * File manager instance
     * @var FileManager|null
     */
    protected $fileManager = null;

    /**
     * File system instance
     * @var Filesystem|null
     */
    protected $fileSystem = null;

    /**
     * Processor Instance
     * @var WDB5|WDB2|BaseFormat|null
     */
    protected $processor = null;

    /**
     * Language which will be used to open DBC files
     * @var string
     */
    protected $selectedLanguage = 'enUS';

    /**
     * Build used for extraction
     * @var null|integer
     */
    protected $build = null;

    /**
     * DB2Reader constructor.
     * @param bool $initializeImmediately
     */
    public function __construct(bool $initializeImmediately = false) {
        $this->fileManager = new FileManager();
        $this->fileSystem = $this->fileManager->getFileSystem();
        if ($initializeImmediately)
            $this->fileManager->loadEverything($this->selectedLanguage);
    }

    /**
     * Get file manager instance
     * @return FileManager
     */
    public function getFileManager() : FileManager {
        return $this->fileManager;
    }

    /**
     * Set new file manager instance
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager) {
        $this->fileManager = $fileManager;
    }

    /**
     * Get language currently used for processing
     * @return string
     */
    public function getLanguage() : string {
        return $this->selectedLanguage;
    }

    /**
     * Set language used for DBC processing
     * @param string $language
     * @param bool $throwIfAny
     */
    public function setLanguage(string $language, bool $throwIfAny = true) {
        if (in_array($language, $this->fileManager->getLanguageCodes())) {
            $this->selectedLanguage = $language;
            $this->fileManager->loadEverything($this->selectedLanguage);
        } else {
            if ($throwIfAny)
                throw new \RuntimeException('Language ' . $language . ' does not exists, you can choose from: [' . implode(', ', $this->fileManager->getLanguageCodes()) . ']');
        }
    }

    /**
     * Get build used for processing
     * @return int
     */
    public function getBuild() : int {
        return ($this->build === null) ? 0 : $this->build;
    }

    /**
     * Set build which will be used for processing
     * @param int $build
     */
    public function setBuild(int $build) {
        $this->build = $build;
    }

    /**
     * Open requested file
     * @param string $fileName
     * @throws \Exception
     */
    public function openFile(string $fileName) {
        if (!$this->fileManager->isReady())
            throw new \RuntimeException('Data sources are not loaded!');
        if ($this->fileManager->isFileAvailable($fileName)) {
            try {
                $fileName = $this->fileManager->formatFileName($fileName);
                $this->fileManager->setFileName($fileName);
                $this->fileManager->openFile($fileName);
                $this->processor = $this->fileManager->getProcessor();
            } catch (\Exception $exception) {
                throw new \RuntimeException('We have encountered an error during file processing procedure...' . PHP_EOL . $exception->getMessage());
            }
        } else {
            throw new \RuntimeException('File ' . $fileName . ' does not exists!');
        }
    }

    /**
     * Get record by ID
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getRecord(int $id) {
        return $this->processor->getRecord($id);
    }

    /**
     * Generate Records
     * @return \Generator
     * @throws \Exception
     */
    public function generateRecords() : \Generator {
        return $this->processor->generateRecords();
    }

    /**
     * Get indexes
     * @return array
     */
    public function getIndexes() : array {
        return $this->processor->getIDs();
    }

    /**
     * Get record as JSON
     * @param int $id
     * @param bool $appendID
     * @return string
     * @throws \Exception
     */
    public function getRecordAsJson(int $id, bool $appendID = true) {
        $finalArray = [];
        $record = $this->getRecord($id);
        if ($record === null)
            throw new \RuntimeException('Record #' . $id . ' was not found in the ' . $this->fileManager->getFileName() . ' file!');
        $record = ($appendID) ? array_merge([$id], $record, [$this->getBuild()]) : array_merge($record, [$this->getBuild()]);
        $record = DB2Reader::flattenRecord($record);
        if ($this->fileManager->structureExists()) {
            $fileStructure = explode(',', $this->fileManager->getStructure());
            foreach ($fileStructure as $index => $key) {
                $finalArray[$id][$key] = $record[$index];
            }
        } else {
            $finalArray[$id] = $record;
        }
        return json_encode($finalArray, JSON_PRETTY_PRINT);
    }

    /**
     * Flatten record
     * @param array $record
     * @return array
     */
    public static function flattenRecord(array $record) : array {
        $result = [];
        foreach ($record as $k => $v) {
            if (!is_array($v)) {
                $result[$k] = $v;
                continue;
            }
            $idx = 0;
            foreach ($v as $vv) {
                $result["$k-" . $idx++] = $vv;
            }
        }
        return array_values($result);
    }

}