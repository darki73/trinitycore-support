<?php namespace FreedomCore\TrinityCore\Support;

/**
 * Class Filesystem
 * @package FreedomCore\TrinityCore\Support
 */
class Filesystem {

    /**
     * Path to the public folder
     * @var null|string
     */
    protected $publicFolder = null;

    /**
     * Path to the storage folder
     * @var null|string
     */
    protected $storageFolder = null;

    protected $dataFilesStorage = null;

    /**
     * Filesystem constructor.
     */
    public function __construct() {
        $this->initializeVariables();
    }

    /**
     * Get public folder path
     * @return string
     */
    public function getPublicFolder() : string {
        return $this->publicFolder;
    }

    /**
     * Get storage folder path
     * @return string
     */
    public function getStorageFolder() : string {
        return $this->storageFolder;
    }

    /**
     * Get data store folder path
     * @return string
     */
    public function getDataStorage() : string {
        return $this->dataFilesStorage;
    }

    /**
     * Get Structures folder path
     * @return string
     */
    public function getStructuresFolder() : string {
        return __DIR__ . DIRECTORY_SEPARATOR . 'DB2Reader' . DIRECTORY_SEPARATOR . 'Structures' . DIRECTORY_SEPARATOR;
    }

    /**
     * Set public folder path
     * @param string $path
     */
    public function setPublicFolder(string $path) {
        $this->publicFolder = $path;
    }

    /**
     * Set storage folder path
     * @param string $path
     */
    public function setStorageFolder(string $path) {
        $this->storageFolder = $path;
    }

    /**
     * Set data store folder path
     * @param string $path
     */
    public function setDataStorage(string $path) {
        $this->dataFilesStorage = $path;
    }

    /**
     * Check if file exists
     * @param string $path
     * @param string $fileName
     * @return bool
     */
    public static function fileExists(string $path, string $fileName = '') : bool {
        $fullPath = (strlen($fileName) > 1) ? $path . DIRECTORY_SEPARATOR . $fileName : $path;
        return file_exists($fullPath);
    }

    /**
     * Get folders in the folder
     * @param string $path
     * @return array
     */
    public static function foldersInFolder(string $path) : array {
        return array_map(function(string $value) {
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $value);
        }, glob($path . '/*', GLOB_ONLYDIR));
    }

    /**
     * Get files in the folder
     * @param string $path
     * @return array
     */
    public static function filesInFolder(string $path) : array {
        return array_map(function(string $value) {
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $value);
        }, glob($path . '/*', GLOB_NOSORT));
    }

    /**
     * Initialize required variables.
     */
    private function initializeVariables() {
        $this->publicFolder = getcwd();
        $this->storageFolder = function_exists('storage_path') ?
            storage_path() :
            (strstr($this->publicFolder, 'public') ?
                str_replace('public', 'storage', $this->publicFolder) :
                $this->publicFolder . DIRECTORY_SEPARATOR . 'storage'
            );
        $this->dataFilesStorage = $this->storageFolder . DIRECTORY_SEPARATOR . 'data';
    }
}