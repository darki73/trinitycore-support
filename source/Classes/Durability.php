<?php namespace FreedomCore\TrinityCore\Support\Classes;

use FreedomCore\TrinityCore\Console\Classes\Item;

/**
 * Class Durability
 * @package FreedomCore\TrinityCore\Support\Classes
 * @see http://wowwiki.wikia.com/wiki/Durability
 */
class Durability {

    /**
     * Quality Multipliers Variables
     * @var array
     */
    protected static $qualityMultipliers = [
        0.92,
        0.92,
        0.92,
        1.11,
        1.32,
        1.61,
        0.0,
        0.0
    ];

    /**
     * Armor Multipliers Variables
     * @var array
     */
    protected static $armorMultipliers = [
        0.00, // Inventory Type: Non Equip
        0.60, // Inventory Type: Head
        0.00, // Inventory Type: Neck
        0.60, // Inventory Type: Shoulders
        0.00, // Inventory Type: Body
        1.00, // Inventory Type: Chest
        0.33, // Inventory Type: Waist
        0.72, // Inventory Type: Legs
        0.48, // Inventory Type: Feet
        0.33, // Inventory Type: Wrists
        0.33, // Inventory Type: Hands
        0.00, // Inventory Type: Finger
        0.00, // Inventory Type: Trinket
        0.00, // Inventory Type: Weapon
        0.72, // Inventory Type: Shield
        0.00, // Inventory Type: Ranged
        0.00, // Inventory Type: Cloack
        0.00, // Inventory Type: 2 Handed Weapon
        0.00, // Inventory Type: Bag
        0.00, // Inventory Type: Tabard
        1.00, // Inventory Type: Robe
        0.00, // Inventory Type: Weapon Main-hand
        0.00, // Inventory Type: Weapon Off-hand
        0.00, // Inventory Type: Holdable
        0.00, // Inventory Type: Ammo
        0.00, // Inventory Type: Thrown
        0.00, // Inventory Type: Ranged Right Hand
        0.00, // Inventory Type: Quiver
        0.00, // Inventory Type: Relic
    ];

    protected static $weaponMultipliers = [
        0.91, // Weapon Subclass: Axe
        1.00, // Weapon Subclass: Axe 2
        1.00, // Weapon Subclass: BOW
        1.00, // Weapon Subclass: GUN
        0.91, // Weapon Subclass: MACE
        1.00, // Weapon Subclass: MACE2
        1.00, // Weapon Subclass: POLEARM
        0.91, // Weapon Subclass: SWORD
        1.00, // Weapon Subclass: SWORD2
        1.00, // Weapon Subclass: WARGLAIVES
        1.00, // Weapon Subclass: STAFF
        0.00, // Weapon Subclass: EXOTIC
        0.00, // Weapon Subclass: EXOTIC2
        0.66, // Weapon Subclass: FIST_WEAPON
        0.00, // Weapon Subclass: MISCELLANEOUS
        0.66, // Weapon Subclass: DAGGER
        0.00, // Weapon Subclass: THROWN
        0.00, // Weapon Subclass: SPEAR
        1.00, // Weapon Subclass: CROSSBOW
        0.66, // Weapon Subclass: WAND
        0.66, // Weapon Subclass: FISHING_POLE
    ];

}