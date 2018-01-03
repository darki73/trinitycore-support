<?php namespace FreedomCore\TrinityCore\Support\Classes;

use FreedomCore\TrinityCore\Character\Models\Character;
use FreedomCore\TrinityCore\Support\Common\Helper;
use FreedomCore\TrinityCore\Support\DB2Reader;
use Illuminate\Support\Collection;

/**
 * Class Profession
 * @package FreedomCore\TrinityCore\Support\Classes
 */
class Profession
{

    /**
     * ID of the profession
     * @var int
     */
    protected $professionID = 0;

    /**
     * Skill level of the profession
     * @var int
     */
    protected $professionLevel = 0;

    /**
     * Min ID for the SkillLine DB2 file to extract professions
     * @var int
     */
    protected $minID = 129;

    /**
     * Max ID for the SkillLine DB2 file to extract professions
     * @var int
     */
    protected $maxID = 773;

    /**
     * Array of available professions
     * @var array
     */
    protected $professions = [];

    /**
     * Character professions collection
     * @var \Illuminate\Support\Collection
     */
    protected $characterProfessions = [];

    /**
     * Profession constructor.
     * @param int $profession
     * @param int $level
     */
    public function __construct(int $profession = 0, int $level = 0)
    {
        $this->professionID = $profession;
        $this->professionLevel = $level;
    }

    /**
     * Set profession id
     * @param int $profession
     * @return Profession
     */
    public function setProfessionID(int $profession) : Profession
    {
        $this->professionID = $profession;
        return $this;
    }

    /**
     * Set profession level
     * @param int $level
     * @return Profession
     */
    public function setProfessionLevel(int $level) : Profession
    {
        $this->professionLevel = $level;
        return $this;
    }

    /**
     * Get profession id
     * @return int
     */
    public function getProfessionID() : int
    {
        return $this->professionID;
    }

    /**
     * Get profession level
     * @return int
     */
    public function getProfessionLevel() : int
    {
        return $this->professionLevel;
    }

    /**
     * Load professions from SkillLine
     * @param DB2Reader $reader
     * @return Profession
     * @throws \Exception
     */
    public function loadProfessions(DB2Reader $reader) : Profession
    {
        $reader->openFile('SkillLine');
        $professions = [];
        foreach ($reader->generateRecords() as $index => $record) {
            if ($record['can_link'] && $index >= $this->minID && $index <= $this->maxID) {
                $record = array_merge(['id' => $index], $record);
                $professions[] = $record;
            }
        }
        $this->professions = $professions;
        return $this;
    }

    /**
     * Load character professions
     * @param Character $character
     */
    public function loadCharacterProfessions(Character $character)
    {
        if (empty($this->professions)) {
            throw new \RuntimeException('You have to call the loadProfessions() method before loading the professions for the character!');
        }
        $skills = $character->skills;
        $professions = [];
        foreach ($skills as $skill) {
            $searchResults = Helper::arrayMultiSearch($this->professions, 'id', $skill->skill);
            if (!empty($searchResults)) {
                $professions[] = $skill;
            }
        }
        $this->characterProfessions = collect($professions);
    }

    /**
     * Get list of available professions
     * @return array
     */
    public function getAvailableProfessions() : array
    {
        return $this->professions;
    }

    /**
     * Get character professions
     * @return \Illuminate\Support\Collection
     */
    public function getCharacterProfessions() : Collection
    {
        return $this->characterProfessions;
    }
}
