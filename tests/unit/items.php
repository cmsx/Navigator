<?php

require_once __DIR__ . '/../init.php';

use CMSx\DB;
use CMSx\Navigator;
use CMSx\Navigator\Exception;

class ItemsTest extends PHPUnit_Framework_TestCase
{
  /** @var DB */
  protected $db;

  function testCount()
  {
    $count = MyItem::Count();
    $this->assertEquals(20, $count, 'В таблице 20 записей');

    $n = makeNavigator('/hello/page:2/orderby:_id/');

    try {
      $n->getItems();

      $this->fail('Класс не указан - выкидывает исключение');
    } catch (Exception $e) {
      $this->assertEquals(Navigator::ERR_CLASS_IS_NOT_DEFINED, $e->getCode());
    }

    try {
      $n->setClass('BlaBla');
      $this->fail('Неверный класс выкидывает исключение');
    } catch (Exception $e) {
      $this->assertEquals(Navigator::ERR_CLASS_IS_NOT_ITEM, $e->getCode());
    }

    $n->setClass('\MyItem');
    $this->assertEquals(20, $n->getTotal(), 'Навигатор уже знает количество элементов');

    $arr = $n->getItems();
    $this->assertCount(20, $arr, 'Полная выборка на одной странице');

    $n->setOnpage(5);
    $n->addOrderBy('id');

    $this->assertEquals(4, $n->getTotalPages(), 'Количество страниц');

    $arr = $n->getItems();
    $this->assertCount(5, $arr, 'В выборке 5 элементов');

    /** @var MyItem $item */
    $item = current($arr);

    $this->assertEquals('Item #15', $item->get('name'), 'Выборка отсортирована');

    $n->setFuncCount('CountSome');
    $this->assertEquals(42, $n->getTotal(true), 'Вызов произвольной функции для подсчета');

    $n->setFuncFind('FindSome');
    $this->assertEquals(array(1, 2, 3), $n->getItems(), 'Вызов произвольной функции для выборки');
  }

  protected function setUp()
  {
    if (!$this->db = makeDB()) {
      $this->markTestSkipped('Не настроено подключение к БД');
    }

    $this->db->drop('test')->execute();

    $this->db->create('test')
      ->addId()
      ->addChar('name')
      ->addTimeCreated()
      ->execute();

    for ($i = 1; $i <= 20; $i++) {
      $this->db->insert('test')->setArray(array('name' => 'Item #' . $i))->execute();
    }
  }
}