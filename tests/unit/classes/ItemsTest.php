<?php namespace FreedomCore\TrinityCore\Support\Tests\Unit\Classes;

use FreedomCore\TrinityCore\Support\Classes\Item;
use FreedomCore\TrinityCore\Support\Classes\Items;
use FreedomCore\TrinityCore\Support\Tests\BaseTest;

/**
 * Class ItemsTest
 * @package FreedomCore\TrinityCore\Support\Tests\Unit\Classes
 */
class ItemsTest extends BaseTest
{

    /**
     * Items Data For Tests
     * @var array
     */
    protected $itemsData = [
        ['id'   =>  49623, 'count' => 1],
        ['id'   =>  49624, 'count' => 3],
        ['id'   =>  49625, 'count' => 5],
        ['id'   =>  49626, 'count' => 10]
    ];

    /**
     * Test items creation
     * @throws \Exception
     */
    public function testItemsCreation()
    {
        $itemsObject = $this->createItemsObject();
        $items = new Items($itemsObject);
        $this->assertEquals(4, $items->count());
    }

    /**
     * Test that we are able to create Items object where Item has default value
     * @depends testItemsCreation
     * @throws \Exception
     */
    public function testItemsCreationDefault()
    {
        $itemsObject = $this->createItemsObject(true);
        $items = new Items($itemsObject);
        foreach ($items->getItems() as $index => $item) {
            /**
             * @var $item Item
             */
            $this->assertEquals($this->itemsData[$index]['id'], $item->getItemID());
            $this->assertEquals(1, $item->getCount());
        }
    }

    /**
     * Test that we are able to convert Items object ot array
     * @throws \Exception
     */
    public function testItemsToArray()
    {
        $itemsObject = $this->createItemsObject();
        $items = new Items($itemsObject);
        foreach ($items->toArray() as $index => $item) {
            $this->assertEquals($this->itemsData[$index], $item);
        }
    }

    /**
     * Test that we are able to convert Items to command attribute
     * @throws \Exception
     */
    public function testItemsToCommandAttribute()
    {
        $itemsObject = $this->createItemsObject();
        $items = new Items($itemsObject);
        $generatedCommandAttributes = [];
        foreach ($this->itemsData as $item) {
            $generatedCommandAttributes[] = implode(':', $item);
        }
        $this->assertEquals($items->toCommandAttribute(), implode(' ', $generatedCommandAttributes));
    }

    /**
     * Create simple items object
     * @param bool $useDefault
     * @return array
     * @throws \Exception
     */
    protected function createItemsObject(bool $useDefault = false)
    {
        $itemsObject = [];
        foreach ($this->itemsData as $index => $item) {
            $newItem = new Item();
            $newItem->setItemID($item['id']);
            if (!$useDefault)
                $newItem->setCount($item['count']);
            $itemsObject[] = $newItem;
        }
        return $itemsObject;
    }
}
