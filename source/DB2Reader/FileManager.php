<?php namespace FreedomCore\TrinityCore\Support\DB2Reader;

use FreedomCore\TrinityCore\Support\Filesystem;
use FreedomCore\TrinityCore\Support\DB2Reader\Processor\WDC1;
use FreedomCore\TrinityCore\Support\DB2Reader\Processor\WDB5;
use FreedomCore\TrinityCore\Support\DB2Reader\Processor\WDB2;
use FreedomCore\TrinityCore\Support\DB2Reader\Processor\BaseFormat;

/**
 * Class FileManager
 * @package FreedomCore\TrinityCore\Support\DB2Manager
 */
class FileManager
{

    /**
     * Build Number
     * @var null|int
     */
    protected $build = null;

    /**
     * Filesystem Instance
     * @var Filesystem|null
     */
    protected $fileSystem = null;

    /**
     * File handle resource instance
     * @var null|resource
     */
    protected $fileHandle = null;

    /**
     * Currently opened file
     * @var null|string
     */
    protected $fileName = null;

    /**
     * Processor Instance
     * @var WDC1|WDB5|WDB2|BaseFormat|null
     */
    protected $processor = null;

    /**
     * Whether the structure for the file exists or not
     * @var bool
     */
    protected $structureExists = false;

    /**
     * File structure
     * @var null|array
     */
    protected $fileStructure = null;

    /**
     * Whether all required items have been loaded
     * @var bool
     */
    protected $loaded = false;

    /**
     * Structure for data folder
     * @var array
     */
    protected $dataFolderStructure = [];

    /**
     * List of available DBC languages
     * @var array
     */
    protected $availableLanguages = [];

    /**
     * Available DBC/DB2 files
     * @var array
     */
    protected $availableFiles = [];

    /**
     * Data acquired from initial processing
     * @var array
     */
    protected $initialProcessing = [];

    /**
     * FileManager constructor.
     * @param Filesystem $fs
     * @param int $build
     */
    public function __construct(Filesystem $fs, int $build)
    {
        $this->fileSystem = $fs;
        $this->build = $build;
    }

    /**
     * FileManager destructor.
     */
    public function __destruct()
    {
        if ($this->fileHandle !== null) {
            $this->closeFileHandle();
        }
    }

    /**
     * Get file system instance
     * @return Filesystem
     */
    public function getFileSystem() : Filesystem
    {
        return $this->fileSystem;
    }

    /**
     * Set file system instance
     * @param Filesystem $fs
     */
    public function setFileSystem(Filesystem $fs)
    {
        $this->fileSystem = $fs;
    }

    /**
     * Get file handle instance
     * @return null|resource
     */
    public function getFileHandle()
    {
        return $this->fileHandle;
    }

    /**
     * Set file name
     * @param string $fileName
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Get file name
     * @return string
     */
    public function getFileName() : string
    {
        return $this->fileName;
    }

    /**
     * Set file handle instance
     * @param $fileHandle
     */
    public function setFileHandle($fileHandle)
    {
        $this->fileHandle = $fileHandle;
    }

    /**
     * Check if file structure exists
     * @return bool
     */
    public function structureExists() : bool
    {
        return $this->structureExists;
    }

    /**
     * Get File Structure
     * @return null|array
     */
    public function getStructure() : array
    {
        return $this->fileStructure;
    }

    /**
     * Load structure of the data folder
     * @return array
     */
    public function loadDataDirectory() : array
    {
        $this->dataFolderStructure = [];
        foreach (Filesystem::foldersInFolder($this->fileSystem->getDataStorage()) as $folder) {
            $folderName = str_replace($this->fileSystem->getDataStorage() . DIRECTORY_SEPARATOR, '', $folder);
            $folderData = [
                'path'  =>  $folder,
                'name'  =>  $folderName,
                'type'  =>  $this->getFolderType($folderName)
            ];
            $this->dataFolderStructure[$folderName] = $folderData;
        }
        return $this->dataFolderStructure;
    }

    /**
     * Load information about all available DBC languages
     * @return array
     */
    public function loadAvailableLanguages() : array
    {
        if (array_key_exists('dbc', $this->dataFolderStructure)) {
            $this->availableLanguages = [];
            foreach (Filesystem::foldersInFolder($this->dataFolderStructure['dbc']['path']) as $folder) {
                $folderName = str_replace($this->dataFolderStructure['dbc']['path'] . DIRECTORY_SEPARATOR, '', $folder);
                $folderData = [
                    'path'  =>  $folder,
                    'name'  =>  $folderName,
                    'type'  =>  $this->getFolderType($folderName)
                ];
                $this->availableLanguages[] = $folderData;
            }
            return $this->availableLanguages;
        } else {
            throw new \RuntimeException('Data folder structure is not loaded!');
        }
    }

    /**
     * Load all available files
     * @param string $selectedLanguage
     * @return array
     */
    public function loadAvailableFiles(string $selectedLanguage) : array
    {
        $this->availableFiles = [];
        $filesFolder = $this->dataFolderStructure['dbc']['path'] . DIRECTORY_SEPARATOR . $selectedLanguage;
        foreach (Filesystem::filesInFolder($filesFolder) as $file) {
            $fileStructure = [
                'path'      =>  $file,
                'extension' =>  pathinfo($file, PATHINFO_EXTENSION),
                'size'      =>  filesize($file),
                'name'      =>  str_replace(['_', '-'], '', basename($file, '.' . pathinfo($file, PATHINFO_EXTENSION)))
            ];
            $this->availableFiles[$fileStructure['name']] = $fileStructure;
        }
        return $this->availableFiles;
    }

    /**
     * Load everything required for operation
     * @param string $selectedLanguage
     */
    public function loadEverything(string $selectedLanguage)
    {
        $this->loadDataDirectory();
        $this->loadAvailableLanguages();
        $this->loadAvailableFiles($selectedLanguage);
        $this->loaded = true;
    }

    /**
     * Check if we are ready to perform other tasks
     * @return bool
     */
    public function isReady() : bool
    {
        return $this->loaded;
    }

    /**
     * Get available languages
     * @return array
     */
    public function getLanguages() : array
    {
        return $this->availableLanguages;
    }

    /**
     * Get available languages as codes
     * @return array
     */
    public function getLanguageCodes() : array
    {
        return array_map(function (array $language) {
            return $language['name'];
        }, $this->getLanguages());
    }

    /**
     * Get data folder structure
     * @return array
     */
    public function getDataFolderStructure() : array
    {
        return $this->dataFolderStructure;
    }

    /**
     * Get processor instance
     * @return WDB5|WDB2|BaseFormat|null
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Check if requested file actually exists
     * @param string $fileName
     * @return bool
     */
    public function isFileAvailable(string $fileName) : bool
    {
        $fileName = $this->formatFileName($fileName);
        return (array_key_exists($fileName, $this->availableFiles));
    }

    /**
     * Open requested file
     * @param string $fileName
     * @param array $arguments
     * @throws \Exception
     */
    public function openFile(string $fileName, array $arguments = [])
    {
        $fileData = $this->availableFiles[$fileName];
        $this->fileName = $fileName;
        $this->fileHandle = fopen($fileData['path'], 'rb');
        $this->performInitialProcessing();
        $this->loadStructure($fileName);
        $this->createProcessor($arguments);
    }

    /**
     * Format file name string
     * @param string $fileName
     * @return string
     */
    public function formatFileName(string $fileName) : string
    {
        if (strstr($fileName, '.db2') || strstr($fileName, '.dbc')) {
            $fileName = str_replace(['.db2', '.dbc'], '', $fileName);
        }
        return str_replace(['_', '-'], '', $fileName);
    }

    /**
     * Seek Bytes In File
     * @param integer $bytes
     * @param mixed|null extra
     * @return int
     */
    public function seekBytes(int $bytes, $extra = null)
    {
        if ($extra !== null) {
            return fseek($this->fileHandle, $bytes, $extra);
        } else {
            return fseek($this->fileHandle, $bytes);
        }
    }

    /**
     * Read Bytes From File
     * @param integer $bytes
     * @return bool|string
     */
    public function readBytes(int $bytes)
    {
        return fread($this->fileHandle, $bytes);
    }

    /**
     * Get file format
     * @return string
     */
    public function getFormat() : string
    {
        return $this->initialProcessing['format'];
    }

    /**
     * Get size of processed file
     * @return int
     */
    public function getProcessedSize() : int
    {
        return $this->initialProcessing['size'];
    }

    /**
     * Close file handle
     */
    protected function closeFileHandle()
    {
        if ($this->fileHandle !== null) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    /**
     * Get folder type
     * @param string $folderName
     * @return string
     */
    protected function getFolderType(string $folderName) : string
    {
        $types = [
            'dbc'   =>  'DBClientFiles',
            'gt'    =>  'GT',
            'enUS'  =>  'language',
            'enGB'  =>  'language',
            'esES'  =>  'language',
            'esMX'  =>  'language',
            'ruRU'  =>  'language',
        ];
        return (array_key_exists($folderName, $types)) ? $types[$folderName] : 'unknown';
    }

    /**
     * Perform initial file processing
     */
    protected function performInitialProcessing()
    {
        $status = fstat($this->fileHandle);
        $this->initialProcessing = [
            'status'    =>  $status,
            'size'      =>  $status['size'],
            'format'    =>  $this->readBytes(4)
        ];
    }

    /**
     * Create processor instance
     * @param array $arguments
     * @throws \Exception
     */
    protected function createProcessor(array $arguments)
    {
        switch ($this->getFormat()) {
            case 'WDBC':
            case 'WDB2':
                $this->processor = new WDB2($this);
                break;
            case 'WDB5':
            case 'WDB6':
                if (!is_array($arguments)) {
                    throw new \Exception("You may only pass an array of string fields when loading a DB2");
                }
                $this->processor = new WDB5($this, $arguments);
                break;
            case 'WDC1':
                if (!is_array($arguments)) {
                    throw new \Exception("You may only pass an array of string fields when loading a DB2");
                }
                $this->processor = new WDC1($this, $arguments);
                break;
            default:
                throw new \Exception("Unknown DB2 format: " . $this->getFormat());
        }
        if ($this->structureExists()) {
            $this->processor->setFieldNames($this->getStructure());
        }
    }

    /**
     * Load file structure
     * @param string $fileName
     * @return $this
     */
    private function loadStructure(string $fileName)
    {
        $structureFile = $this->fileSystem->getStructuresFolder() . $this->build . DIRECTORY_SEPARATOR . $fileName . '.txt';
        $fileFound = Filesystem::fileExists($structureFile);
        if ($fileFound) {
            $this->structureExists = true;
            $this->fileStructure = file($structureFile, FILE_IGNORE_NEW_LINES);
        }
        return $this;
    }
}
