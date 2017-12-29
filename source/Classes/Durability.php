<?php namespace FreedomCore\TrinityCore\Support\Classes;

use FreedomCore\TrinityCore\Console\Classes\Item;

/**
 * Class Durability
 * @package FreedomCore\TrinityCore\Support\Classes
 * @see http://wowwiki.wikia.com/wiki/Durability
 */
class Durability {

    /**
     * Armor Durability Values
     * @var array
     */
    protected static $armorDurability = [
        0   =>  [
            5   =>  [
                1   =>  60, // Head
                3   =>  60, // Shoulders
                5   =>  100,  // Chest
                6   =>  35, // Waist
                7   =>  75,  // Legs
                8   =>  50,  // Feet
                9   =>  35, // Wrists
                10  =>  35, // Hands
            ],  // Mail
            6   =>  [
                1   =>  70, // Head
                3   =>  70, // Shoulders
                5   =>  115,  // Chest
                6   =>  40, // Waist
                7   =>  85,  // Legs
                8   =>  44,  // Feet
                9   =>  40, // Wrists
                10  =>  40, // Hands
            ],  // Plate
            7   =>  [
                1   =>  45, // Head
                3   =>  45, // Shoulders
                5   =>  70,  // Chest
                6   =>  25, // Waist
                7   =>  55,  // Legs
                8   =>  35,  // Feet
                9   =>  25, // Wrists
                10  =>  25, // Hands
            ],  // Cloth
            8   =>  [
                1   =>  50, // Head
                3   =>  50, // Shoulders
                5   =>  85,  // Chest
                6   =>  30, // Waist
                7   =>  65,  // Legs
                8   =>  45,  // Feet
                9   =>  30, // Wrists
                10  =>  30, // Hands
            ]   // Leather
        ],  // Poor
        1   =>  [
            5   =>  [
                1   =>  60, // Head
                3   =>  60, // Shoulders
                5   =>  100,  // Chest
                6   =>  35, // Waist
                7   =>  75,  // Legs
                8   =>  50,  // Feet
                9   =>  35, // Wrists
                10  =>  35, // Hands
            ],  // Mail
            6   =>  [
                1   =>  70, // Head
                3   =>  70, // Shoulders
                5   =>  115,  // Chest
                6   =>  40, // Waist
                7   =>  85,  // Legs
                8   =>  44,  // Feet
                9   =>  40, // Wrists
                10  =>  40, // Hands
            ],  // Plate
            7   =>  [
                1   =>  45, // Head
                3   =>  45, // Shoulders
                5   =>  70,  // Chest
                6   =>  25, // Waist
                7   =>  55,  // Legs
                8   =>  35,  // Feet
                9   =>  25, // Wrists
                10  =>  25, // Hands
            ],  // Cloth
            8   =>  [
                1   =>  50, // Head
                3   =>  50, // Shoulders
                5   =>  85,  // Chest
                6   =>  30, // Waist
                7   =>  65,  // Legs
                8   =>  45,  // Feet
                9   =>  30, // Wrists
                10  =>  30, // Hands
            ]   // Leather
        ],  // Common
        2   =>  [
            5   =>  [
                1   =>  60, // Head
                3   =>  60, // Shoulders
                5   =>  100,  // Chest
                6   =>  35, // Waist
                7   =>  75,  // Legs
                8   =>  50,  // Feet
                9   =>  35, // Wrists
                10  =>  35, // Hands
            ],  // Mail
            6   =>  [
                1   =>  70, // Head
                3   =>  70, // Shoulders
                5   =>  115,  // Chest
                6   =>  40, // Waist
                7   =>  85,  // Legs
                8   =>  44,  // Feet
                9   =>  40, // Wrists
                10  =>  40, // Hands
            ],  // Plate
            7   =>  [
                1   =>  45, // Head
                3   =>  45, // Shoulders
                5   =>  70,  // Chest
                6   =>  25, // Waist
                7   =>  55,  // Legs
                8   =>  35,  // Feet
                9   =>  25, // Wrists
                10  =>  25, // Hands
            ],  // Cloth
            8   =>  [
                1   =>  50, // Head
                3   =>  50, // Shoulders
                5   =>  85,  // Chest
                6   =>  30, // Waist
                7   =>  65,  // Legs
                8   =>  45,  // Feet
                9   =>  30, // Wrists
                10  =>  30, // Hands
            ]   // Leather
        ],  // Uncommon
        3   =>  [
            5   =>  [
                1   =>  70, // Head
                3   =>  70, // Shoulders
                5   =>  120,  // Chest
                6   =>  40, // Waist
                7   =>  90,  // Legs
                8   =>  60,  // Feet
                9   =>  40, // Wrists
                10  =>  40, // Hands
            ],  // Mail
            6   =>  [
                1   =>  80, // Head
                3   =>  80, // Shoulders
                5   =>  135,  // Chest
                6   =>  45, // Waist
                7   =>  100,  // Legs
                8   =>  65,  // Feet
                9   =>  45, // Wrists
                10  =>  45, // Hands
            ],  // Plate
            7   =>  [
                1   =>  50, // Head
                3   =>  50, // Shoulders
                5   =>  80,  // Chest
                6   =>  30, // Waist
                7   =>  65,  // Legs
                8   =>  40,  // Feet
                9   =>  30, // Wrists
                10  =>  30, // Hands
            ],  // Cloth
            8   =>  [
                1   =>  60, // Head
                3   =>  60, // Shoulders
                5   =>  100,  // Chest
                6   =>  35, // Waist
                7   =>  75,  // Legs
                8   =>  50,  // Feet
                9   =>  35, // Wrists
                10  =>  35, // Hands
            ]   // Leather
        ],  // Rare
        4   =>  [
            5   =>  [
                1   =>  85, // Head
                3   =>  85, // Shoulders
                5   =>  140,  // Chest
                6   =>  50, // Waist
                7   =>  105,  // Legs
                8   =>  70,  // Feet
                9   =>  50, // Wrists
                10  =>  50, // Hands
            ],  // Mail
            6   =>  [
                1   =>  100, // Head
                3   =>  100, // Shoulders
                5   =>  165,  // Chest
                6   =>  55, // Waist
                7   =>  120,  // Legs
                8   =>  75,  // Feet
                9   =>  55, // Wrists
                10  =>  55, // Hands
            ],  // Plate
            7   =>  [
                1   =>  60, // Head
                3   =>  60, // Shoulders
                5   =>  100,  // Chest
                6   =>  35, // Waist
                7   =>  75,  // Legs
                8   =>  50,  // Feet
                9   =>  35, // Wrists
                10  =>  35, // Hands
            ],  // Cloth
            8   =>  [
                1   =>  70, // Head
                3   =>  70, // Shoulders
                5   =>  120,  // Chest
                6   =>  40, // Waist
                7   =>  90,  // Legs
                8   =>  60,  // Feet
                9   =>  40, // Wrists
                10  =>  40, // Hands
            ]   // Leather
        ]   // Epic
    ];

    /**
     * Weapon Durability Values
     * @var array
     */
    protected static $weaponDurability = [
        0   =>  [

        ],
        1   =>  [

        ],
        2   =>  [

        ],
        3   =>  [

        ],
        4   =>  [

        ]
    ];

    /**
     * Item Type Data Variables
     * @var array
     */
    protected static $itemTypeData = [
        1   =>  0.59, // Head
        3   =>  0.59, // Shoulders
        5   =>  1.0,  // Chest
        6   =>  0.35, // Waist
        7   =>  0.75,  // Legs
        8   =>  0.49,  // Feet
        9   =>  0.35, // Wrists
        10  =>  0.35, // Hands
    ];

    /**
     * Armor Type Data Variables
     * @var array
     */
    protected static $armorTypeData = [
        5   =>  0.89,   // Mail
        6   =>  1.0,    // Plate
        7   =>  0.63,   // Cloth
        8   =>  0.76    // Leather
    ];

    /**
     * Quality Type Data Variables
     * @var array
     */
    protected static $qualityTypeData = [
        2   =>  1.0,    // Uncommon
        3   =>  1.17,   // Rare
        4   =>  1.37    // Epic
    ];

    /**
     * Calculate durability for armor
     * @param Item $item
     * @param array $itemData
     * @return int
     */
    public static function calculateForArmor(Item $item, array $itemData = []) : int {
        if (empty($itemData))
            $itemData = $item->getData();
        $calculated = [
            'quality'   =>  ($itemData['quality'] !== 0) ? $itemData['quality'] : 2,
            'material'  =>  $itemData['material'],
            'slot'      =>  $item->getSlot() + 1
        ];
        try {
            if (isset(Durability::$itemTypeData[$calculated['slot']]))
                return intval(7 * round(23 * Durability::$qualityTypeData[$calculated['quality']] * Durability::$itemTypeData[$calculated['slot']] * Durability::$armorTypeData[$calculated['material']]));
            return 0;
        } catch (\Exception $exception) {
            dd([
                'error'         =>  $exception->getMessage(),
                'item'          =>  $item,
                'calculated'    =>  $calculated
            ]);
        }
    }

}