<?php namespace FreedomCore\TrinityCore\Support\Tests\Unit\Classes;

use FreedomCore\TrinityCore\Support\Classes\Item;
use FreedomCore\TrinityCore\Support\Tests\BaseTest;

/**
 * Class ItemTest
 * @package FreedomCore\TrinityCore\Console\Tests\Unit\Classes
 */
class ItemTest extends BaseTest
{

    /**
     * Item ID used for test
     * @var int
     */
    protected $itemID = 49623;

    /**
     * Item Quantity used for test
     * @var int
     */
    protected $itemQuantity = 1;

    /**
     * Test that we are able to create items
     * @throws \Exception
     */
    public function testItemCreation()
    {
        $item = new Item();
        $item->setItemID($this->itemID);
        $item->setCount($this->itemQuantity);
        $this->assertEquals($this->itemID, $item->getItemID());
        $this->assertEquals($this->itemQuantity, $item->getCount());
    }

    /**
     * Test that we are able to create item with default quantity set to 1
     * @depends testItemCreation
     * @throws \Exception
     */
    public function testItemCreationWithDefaultQuantity()
    {
        $item = new Item();
        $item->setItemID($this->itemID);
        $this->assertEquals($this->itemID, $item->getItemID());
        $this->assertEquals($this->itemQuantity, $item->getCount());
    }

    /**
     * Test that conversion to array is working as expected
     * @depends testItemCreationWithDefaultQuantity
     * @throws \Exception
     */
    public function testItemToArray()
    {
        $item = new Item();
        $item->setItemID($this->itemID);
        $this->assertEquals(['id' => $this->itemID, 'count' => $this->itemQuantity], $item->getDataForConsoleAsArray());
    }

    /**
     * Test that conversion to command attribute is working as expected
     * @depends testItemToArray
     * @throws \Exception
     */
    public function testItemToCommandAttribute()
    {
        $item = new Item();
        $item->setItemID($this->itemID);
        $this->assertEquals(sprintf('%s:%s', $this->itemID, $this->itemQuantity), $item->getDataForConsoleAsCommandAttribute());
    }
}
