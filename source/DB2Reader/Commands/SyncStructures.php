<?php namespace FreedomCore\TrinityCore\Support\DB2Reader\Commands;

use File;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class SyncStructures
 * @package FreedomCore\TrinityCore\Support\DB2Reader\Commands
 */
class SyncStructures extends Command
{

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'db2:structure:sync';

    /**
     * @inheritdoc
     * @var string
     */
    protected $signature = 'db2:structure:sync {--build=25549}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Synchronize structures with TrinityCore Github Repository';

    /**
     * Game Build Number
     * @var null|int
     */
    protected $build = null;

    /**
     * Github Content
     * @var null|string
     */
    protected $content = null;

    /**
     * Where structures will be saved
     * @var null|string
     */
    protected $saveLocation = null;

    /**
     * Array of parsed structures
     * @var array
     */
    protected $structures = [];

    /**
     * Structure Info Array
     * @var array
     */
    protected $structureInfo = [
        'name'      =>  null,
        'start'     =>  null,
        'end'       =>  null,
        'fields'    =>  []
    ];

    /**
     * Array for items which require to be replaced
     * @var array
     */
    protected $replaceArray = [
        'LocalizedString*',
        'uint32',
        'uint16',
        'uint8',
        'int32',
        'int16',
        'int8',
        'float',
        ';',
        'char',
        'const*',
        'const',
        'DBCPosition3D',
        'DBCPosition2D'
    ];

    /**
     * Fire Installation
     * @return mixed
     */
    public function fire()
    {
        return $this->handle();
    }

    /**
     * Handle Installation
     */
    public function handle()
    {
        $this->build = intval($this->option('build'));
        $this->loadSourceCode();
        $this->prepareStructures();
        $this->info('Total amount of structures: ' . count($this->structures));
        $this->saveStructures();
    }

    /**
     * Load Source Code
     */
    private function loadSourceCode()
    {
        $this->content = file_get_contents('https://raw.githubusercontent.com/TrinityCore/TrinityCore/master/src/server/game/DataStores/DB2Structure.h');
        $this->saveLocation = str_replace('Commands', 'Structures', __DIR__) . DIRECTORY_SEPARATOR;
        $extra = '/*' . $this->getBetween($this->content, '/*', 'struct LocalizedString;') . 'struct LocalizedString;';
        $this->content = str_replace($extra, '', $this->content);
    }

    /**
     * Get text between two strings
     * @param string $string
     * @param string $start
     * @param string $end
     * @return string
     */
    private function getBetween(string $string, string $start, string $end) : string
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    /**
     * Prepare Structures
     */
    private function prepareStructures()
    {
        $skipToEnd = false;
        foreach (explode("\n", $this->content) as $index => $line) {
            $line = trim($line);
            if (strlen($line) < 2) {
                continue;
            }
            if (strstr($line, '#define') || strstr($line, '#pragma pack(pop)') || strstr($line, '#endif')) {
                continue;
            }

            if (strstr($line, 'struct')) {
                $this->structureInfo['name'] = trim($this->getBetween($line, 'struct', 'Entry'));
                if ($this->structureInfo['name'] === 'Criteria') {
                    $skipToEnd = true;
                }
                $this->structureInfo['start'] = $index;
            }
            if (strstr($line, '};')) {
                $skipToEnd = false;
                $this->structureInfo['end'] = $index;
                if (!empty($this->structureInfo['fields'])) {
                    $this->structures[] = $this->structureInfo;
                }
                $this->structureInfo = [
                    'name'      =>  null,
                    'start'     =>  null,
                    'end'       =>  null,
                    'fields'    =>  []
                ];
            }
            if ($index > $this->structureInfo['start'] + 1) {
                if ($skipToEnd) {
                    continue;
                }
                $fieldName = trim(strtok(str_replace($this->replaceArray, '', $line), '/'));
                if (strstr($fieldName, 'bool') || strstr($fieldName, 'return')) {
                    $skipToEnd = true;
                    continue;
                }
                $converted = $this->convertNames($fieldName);
                if (!strstr($converted, '}') && !$skipToEnd) {
                    $this->structureInfo['fields'][] = $converted;
                }
            }
        }
    }

    /**
     * Save Structures
     */
    private function saveStructures()
    {
        File::makeDirectory($this->saveLocation . $this->build, 0775, true);
        $progress = new ProgressBar(new ConsoleOutput(), count($this->structures));
        $progress->start();
        foreach ($this->structures as $structure) {
            if (strlen($structure['name']) > 2) {
                $filePointer = fopen($this->saveLocation . $this->build . DIRECTORY_SEPARATOR . $structure['name'] . '.txt', 'w');
                fwrite($filePointer, implode(PHP_EOL, $structure['fields']));
                fclose($filePointer);
                $progress->advance();
            }
        }
        $progress->finish();
        $this->info(PHP_EOL . PHP_EOL . 'Successfully created files for ' . count($this->structures) . ' structures!');
    }

    /**
     * Convert field names to appropriate ones
     * @param string $fieldName
     * @return mixed|string
     */
    private function convertNames(string $fieldName)
    {
        if ($fieldName === 'helpers' || $fieldName === 'Helpers' || strstr($fieldName, '(')) {
            return '}';
        }
        if (strstr($fieldName, 'PvP')) {
            $fieldName = str_replace('PvP', 'Pvp', $fieldName);
        }
        $splitted = preg_split('/(?<=\\w)(?=[A-Z])/', $fieldName);
        $splitted = array_map('strtolower', $splitted);
        return $this->replaceKnown(implode('_', $splitted));
    }

    /**
     * Replace known issues with fields names
     * @param string $fieldName
     * @return mixed|string
     */
    private function replaceKnown(string $fieldName)
    {
        $replaceData = [
            ['_i_d', 'i_d', '_id', 'id'],
            ['_u_i', 'u_i', '_ui', 'ui'],
            ['_u_w', 'u_w', '_uw', 'uw'],
            ['_w_m_o', 'w_m_o', '_wmo', 'wmo']
        ];
        foreach ($replaceData as $array) {
            $chunk = array_chunk($array, count($array) / 2);
            $fieldName = str_replace($chunk[0], $chunk[1], $fieldName);
        }
        if (strstr($fieldName, '[')) {
            return strstr($fieldName, '[', true);
        }
        return $fieldName;
    }
}
