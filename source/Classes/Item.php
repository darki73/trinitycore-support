<?php namespace FreedomCore\TrinityCore\Support\Classes;
use FreedomCore\TrinityCore\Character\Models\CharacterInventory;
use FreedomCore\TrinityCore\Character\Models\ItemInstance;
use FreedomCore\TrinityCore\Support\Common\Helper;
use FreedomCore\TrinityCore\Support\DB2Reader;

/**
 * Class Item
 * @package FreedomCore\TrinityCore\Support\Classes
 */
class Item {

    /**
     * Character Inventory Model Instance
     * @var CharacterInventory|null
     */
    protected $inventory = null;

    /**
     * Item Instance Model Instance
     * @var ItemInstance|null
     */
    protected $instance = null;

    /**
     * DB2Reader Instance
     * @var null|DB2Reader
     */
    protected $reader = null;

    /**
     * Guid of the inventory reference
     * @var null|integer
     */
    protected $inventoryGuid = null;

    /**
     * Bag id
     * @var int
     */
    protected $bag = 0;

    /**
     * Item slot
     * @var null|integer
     */
    protected $slot = null;

    /**
     * Guid of the item
     * @var null|integer
     */
    protected $itemGuid = null;

    /**
     * Item id
     * @var null|integer
     */
    protected $itemEntry = null;

    /**
     * Who owns this item
     * @var null|integer
     */
    protected $ownerGuid = null;

    /**
     * Who created this item
     * @var int
     */
    protected $creatorGuid = 0;

    /**
     * Who gifted that item to the character
     * @var int
     */
    protected $giftCreatorGuid = 0;

    /**
     * Amount of item of that type in inventory
     * @var int
     */
    protected $count = 1;

    /**
     * When this item will be removed from inventory
     * @var int
     */
    protected $duration = 0;

    /**
     * Amount of charges left
     * @var null|string|integer
     */
    protected $charges = '';

    /**
     * Flags variable
     * @var int
     */
    protected $flags = 1;

    /**
     * Enchantments String
     * @var null|string
     */
    protected $enchantments = '0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 ';

    /**
     * Type of random property
     * @var int
     */
    protected $randomPropertyType = 0;

    /**
     * ID of random property
     * @var int
     */
    protected $randomPropertyId = 0;

    /**
     * Item durability
     * @var int
     */
    protected $durability = 0;

    /**
     * How long player used this item for
     * @var int
     */
    protected $playedTime = 0;

    /**
     * Text on the item
     * @var null|string
     */
    protected $text = '';

    /**
     * Transmogrigication id
     * @var int
     */
    protected $transmogrification = 0;

    /**
     * Upgrade id
     * @var int
     */
    protected $upgradeId = 0;

    /**
     * Enchant illusion id
     * @var int
     */
    protected $enchantIllusion = 0;

    /**
     * Battle pet species id
     * @var int
     */
    protected $battlePetSpeciesId = 0;

    /**
     * Battle pet breed data
     * @var int
     */
    protected $battlePetBreedData = 0;

    /**
     * Battle pet level
     * @var int
     */
    protected $battlePetLevel = 0;

    /**
     * Battle pet display id
     * @var int
     */
    protected $battlePetDisplayId = 0;

    /**
     * Unknown
     * @var int
     */
    protected $context = 0;

    /**
     * List of bonuses applied to the item
     * @var null|string
     */
    protected $bonusListIDs = '';

    /**
     * Should we automatically load item data once it is entered
     * @var bool
     */
    protected $enableDynamicDataLoading = true;

    /**
     * Should we load item data as soon as user provides Item ID
     * @var bool
     */
    protected $autoloadItemData = false;

    /**
     * Automatically Loaded Item Data
     * @var array
     */
    protected $autoloadedItemData = [];

    /**
     * Item constructor.
     * @param CharacterInventory|null $inventory
     * @param ItemInstance|null $instance
     */
    public function __construct(CharacterInventory $inventory = null, ItemInstance $instance = null) {
        $this->inventory = $inventory;
        $this->instance = $instance;
        if ($this->inventory !== null && $this->instance !== null)
            $this->processPassedData();
    }

    /**
     * Attach DB2Reader to get the item info
     * @param DB2Reader|null $reader
     * @return Item
     */
    public function attachReader(DB2Reader $reader = null) : Item {
        if ($reader === null) {
            $reader = new DB2Reader(true);
            $this->enableDynamicDataLoading = false;
        }
        if (!$reader->isFileOpened())
            Helper::throwRuntimeException('You need to pass instance of the DB2Reader with opened ItemSparse file!');
        $this->reader = $reader;
        return $this;
    }

    /**
     * Automatically load item data once the id is provided
     * @param bool $increaseMemoryLimit
     * @return Item
     * @throws \Exception
     */
    public function autoloadItemData(bool $increaseMemoryLimit = false) : Item {
        if ($increaseMemoryLimit) {
            ini_set('memory_limit', '512M');
            set_time_limit(0);
        }
        $memoryLimit = ini_get('memory_limit');
        if (strstr($memoryLimit, 'M')) {
            $memoryLimit = intval(str_replace('M', '', $memoryLimit));
        } else if (strstr($memoryLimit, 'G')) {
            $memoryLimit = intval(str_replace('G', '', $memoryLimit)  * 1024);
        } else {
            Helper::throwRuntimeException('We are unable to process memory limit: ' . $memoryLimit);
        }
        if ($memoryLimit < 512)
            Helper::throwRuntimeException('Outside of the console this method requires at least 512M of memory dedicated to PHP. We can try to automatically increase this parameter if you call this method in the following manner autoloadItemData(true)');
        $this->autoloadItemData = true;
        return $this;
    }

    /**
     * Set Inventory Guid
     * @param int $inventoryGuid
     * @return Item
     */
    public function setInventoryGuid(int $inventoryGuid) : Item {
        $this->inventoryGuid = $inventoryGuid;
        return $this;
    }

    /**
     * Get inventory Guid
     * @return int
     */
    public function getInventoryGuid() : int {
        return $this->inventoryGuid;
    }

    /**
     * Set bag slot
     * @param int $bagSlot
     * @return Item
     */
    public function setBagSlot(int $bagSlot) : Item {
        $this->bag = $bagSlot;
        return $this;
    }

    /**
     * Get bag slot
     * @return int
     */
    public function getBagSlot() : int {
        return $this->bag;
    }

    /**
     * Set item slot
     * @param int $slot
     * @return Item
     */
    public function setSlot(int $slot) : Item {
        $this->slot = $slot;
        return $this;
    }

    /**
     * Get item slot
     * @return int
     */
    public function getSlot() : int {
        return $this->slot;
    }

    /**
     * Set item guid
     * @param int $itemGuid
     * @return Item
     */
    public function setItemGuid(int $itemGuid) : Item {
        $this->itemGuid = $itemGuid;
        return $this;
    }

    /**
     * Get item guid
     * @return int
     */
    public function getItemGuid() : int {
        return $this->itemGuid;
    }

    /**
     * Set item id
     * @param int $itemID
     * @return Item
     * @throws \Exception
     */
    public function setItemID(int $itemID) : Item {
        $this->itemEntry = $itemID;
        if ($this->autoloadItemData) {
            if ($this->enableDynamicDataLoading) {
                $this->autoloadedItemData = $this->reader->getRecord($this->itemEntry);
            } else {
                $this->autoloadItemData();
                $this->autoloadedItemData = $this->reader->getRecord($this->itemEntry);
            }
        }
        return $this;
    }

    /**
     * Get item id
     * @return int
     */
    public function getItemID() : int {
        return $this->itemEntry;
    }

    /**
     * Set owner guid
     * @param int $ownerGuid
     * @return Item
     */
    public function setOwnerGuid(int $ownerGuid) : Item {
        $this->ownerGuid = $ownerGuid;
        return $this;
    }

    /**
     * Get owner guid
     * @return int
     */
    public function getOwnerGuid() : int {
        return $this->ownerGuid;
    }

    /**
     * Set creator guid
     * @param int $creatorGuid
     * @return Item
     */
    public function setCreatorGuid(int $creatorGuid) : Item {
        $this->creatorGuid = $creatorGuid;
        return $this;
    }

    /**
     * Get creator guid
     * @return int
     */
    public function getCreatorGuid() : int {
        return $this->creatorGuid;
    }

    /**
     * Set gift creator guid
     * @param int $creatorGuid
     * @return Item
     */
    public function setGiftCreatorGuid(int $creatorGuid) : Item {
        $this->giftCreatorGuid = $creatorGuid;
        return $this;
    }

    /**
     * Get gift creator guid
     * @return int
     */
    public function getGiftCreatorGuid() : int {
        return $this->giftCreatorGuid;
    }

    /**
     * Set count
     * @param int $count
     * @return Item
     */
    public function setCount(int $count) : Item {
        $this->count = $count;
        return $this;
    }

    /**
     * Get count
     * @return int
     */
    public function getCount() : int {
        return $this->count;
    }

    /**
     * Set duration
     * @param int $duration
     * @return Item
     */
    public function setDuration(int $duration) : Item {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Set charges
     * @param string $charges
     * @return Item
     */
    public function setCharges(string $charges) : Item {
        $this->charges = $charges;
        return $this;
    }

    /**
     * Set flags
     * @param int $flags
     * @return Item
     */
    public function setFlags(int $flags) : Item {
        $this->flags = $flags;
        return $this;
    }

    /**
     * Set enchantments
     * @param string $enchantments
     * @return Item
     */
    public function setEnchantments(string $enchantments) : Item {
        $this->enchantments = $enchantments;
        return $this;
    }

    /**
     * Set random property
     * @param int $type
     * @param int $id
     * @return Item
     */
    public function setRandomProperty(int $type, int $id) : Item {
        $this->randomPropertyType = $type;
        $this->randomPropertyId = $id;
        return $this;
    }

    /**
     * Set durability
     * @param int $durability
     * @return Item
     */
    public function setDurability(int $durability) : Item {
        $this->durability = $durability;
        return $this;
    }

    /**
     * Set played time
     * @param int $playTime
     * @return Item
     */
    public function setPlayedTime(int $playTime) : Item {
        $this->playedTime = $playTime;
        return $this;
    }

    /**
     * Set text
     * @param string $text
     * @return Item
     */
    public function setText(string $text) : Item {
        $this->text = $text;
        return $this;
    }

    /**
     * Set transmogrification
     * @param int $transmog
     * @return Item
     */
    public function setTransmogrification(int $transmog) : Item {
        $this->transmogrification = $transmog;
        return $this;
    }

    /**
     * Set upgrade id
     * @param int $upgradeID
     * @return Item
     */
    public function setUpgradeID(int $upgradeID) : Item {
        $this->upgradeId = $upgradeID;
        return $this;
    }

    /**
     * Set enchant illusion
     * @param int $illusionID
     * @return Item
     */
    public function setEnchantIllusion(int $illusionID) : Item {
        $this->enchantIllusion = $illusionID;
        return $this;
    }

    /**
     * Set bonus list
     * @param array $bonuses
     * @return Item
     */
    public function setBonusList(array $bonuses) : Item {
        $this->bonusListIDs = implode(',', $bonuses);
        return $this;
    }

    /**
     * Get Character Inventory Entry
     * @return CharacterInventory
     */
    public function getInventory() : CharacterInventory {
        return $this->inventory;
    }

    /**
     * Get updated Character Inventory Entry
     * @return CharacterInventory
     */
    public function getUpdatedInventory() : CharacterInventory {
        return new CharacterInventory([
            'guid'      =>  $this->inventoryGuid,
            'bag'       =>  $this->bag,
            'slot'      =>  $this->slot,
            'item'      =>  $this->itemGuid
        ]);
    }

    /**
     * Get Item Instance
     * @return ItemInstance
     */
    public function getInstance() : ItemInstance {
        return $this->instance;
    }

    /**
     * Get updated Item Instance
     * @return ItemInstance
     */
    public function getUpdatedInstance() : ItemInstance {
        return new ItemInstance([
            'guid'                  =>  $this->itemGuid,
            'itemEntry'             =>  $this->itemEntry,
            'owner_guid'            =>  $this->ownerGuid,
            'creatorGuid'           =>  $this->creatorGuid,
            'giftCreatorGuid'       =>  $this->giftCreatorGuid,
            'count'                 =>  $this->count,
            'duration'              =>  $this->duration,
            'charges'               =>  $this->charges,
            'flags'                 =>  $this->flags,
            'enchantments'          =>  $this->enchantments,
            'randomPropertyType'    =>  $this->randomPropertyType,
            'randomPropertyId'      =>  $this->randomPropertyId,
            'durability'            =>  $this->durability,
            'playedTime'            =>  $this->playedTime,
            'text'                  =>  $this->text,
            'transmogrification'    =>  $this->transmogrification,
            'upgradeId'             =>  $this->upgradeId,
            'enchantIllusion'       =>  $this->enchantIllusion,
            'battlePetSpeciesId'    =>  $this->battlePetSpeciesId,
            'battlePetBreedData'    =>  $this->battlePetBreedData,
            'battlePetLevel'        =>  $this->battlePetLevel,
            'battlePetDisplayId'    =>  $this->battlePetDisplayId,
            'context'               =>  $this->context,
            'bonusListIDs'          =>  $this->bonusListIDs
        ]);
    }

    /**
     * Are we working with the armor gear piece
     * @return bool
     */
    public function isArmor() : bool {
        if ($this->slot === null)
            Helper::throwRuntimeException('Unable to determine if piece of gear is belongs to armor type without slot specified!');
        $armorSlots = [0, 2, 4, 5, 6, 7, 8, 9, 14];
        if (in_array($this->slot, $armorSlots))
            return true;
        return false;
    }

    /**
     * Are we working with the weapon gear piece
     * @return bool
     */
    public function isWeapon() : bool {
        if ($this->slot === null)
            Helper::throwRuntimeException('Unable to determine if piece of gear is belongs to weapon type without slot specified!');
        $weaponSlots = [15, 16, 17];
        if (in_array($this->slot, $weaponSlots))
            return true;
        return false;
    }

    /**
     * Get item quality
     * @return int
     */
    public function getQuality() : int {
        if (empty($this->autoloadedItemData))
            Helper::throwRuntimeException('Item must be loaded automatically to enable use of this method!');
        return $this->autoloadedItemData['quality'];
    }

    /**
     * Get item level
     * @return int
     */
    public function getItemLevel() : int {
        if (empty($this->autoloadedItemData))
            Helper::throwRuntimeException('Item must be loaded automatically to enable use of this method!');
        return $this->autoloadedItemData['item_level'];
    }

    /**
     * Get level required for this item to be able to equip it
     * @return int
     */
    public function getRequiredLevel() : int {
        if (empty($this->autoloadedItemData))
            Helper::throwRuntimeException('Item must be loaded automatically to enable use of this method!');
        return $this->autoloadedItemData['required_level'];
    }

    /**
     * Get inventory type
     * @return int
     */
    public function getInventoryType() : int {
        if (empty($this->autoloadedItemData))
            Helper::throwRuntimeException('Item must be loaded automatically to enable use of this method!');
        return $this->autoloadedItemData['inventory_type'];
    }

    /**
     * Get item sub class
     * @return int
     */
    public function getSubClass() : int {
        Helper::throwRuntimeException('This method is not implemented!');
    }

    /**
     * Manually set autoloaded item data
     * @param array $itemData
     * @return Item
     */
    public function debugSetAutoloadedItemData(array $itemData) : Item {
        $this->autoloadedItemData = $itemData;
        return $this;
    }

    /**
     * Get data for freedomcore/trinitycore-console as array
     * @return array
     */
    public function getDataForConsoleAsArray() : array {
        return [
            'id'    =>  $this->itemEntry,
            'count' =>  $this->count
        ];
    }

    /**
     * Get data for freedomcore/trinitycore-console as command attribute
     * @return string
     */
    public function getDataForConsoleAsCommandAttribute() : string {
        return implode(':', $this->getDataForConsoleAsArray());
    }

    /**
     * Process data passed by the user
     */
    private function processPassedData() {
        foreach ($this->instance->toArray() as $key => $value) {
            if ($key === 'guid')
                $this->itemGuid = $value;
            else if ($key === 'owner_guid')
                $this->ownerGuid = $value;
            else
                if (property_exists($this, $key))
                    $this->$key = $value;
        }
        foreach ($this->inventory->toArray() as $key => $value) {
            if ($key === 'guid')
                $this->inventoryGuid = $value;
            else
                if (property_exists($this, $key))
                    $this->$key = $value;
        }
    }
}