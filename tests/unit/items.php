<?php

require_once __DIR__ . '/../init.php';

use CMSx\DB;
use CMSx\Navigator;
use CMSx\Navigator\Filter;
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

    $n->setSearchable(false);
    $this->assertEquals(0, $n->getTotal(true), 'Количество найденного - 0');
    $this->assertFalse($n->getItems(true), 'Поставлен флаг что выборка отсутствует');
  }

  function testValidators()
  {
    $alnum = 'abc123';
    $f = new Filter('test', 'is_numeric');
    $this->assertTrue($f->validate($alnum), 'Валидатор не задан');

    $f->setValidator('is_numeric');
    $this->assertFalse($f->validate($alnum), 'Только числа');
    $this->assertTrue($f->validate(123), 'Цифры можно');

    $f->setValidator('/^[a-z]+$/');
    $this->assertFalse($f->validate($alnum), 'Только буквы');
    $this->assertTrue($f->validate('abc'), 'Буквы можно');
  }

  /** @dataProvider dataFilters() */
  function testFilters($url, $exp, $msg)
  {
    $n = makeNavigator($url);
    $n->addDefaultCondition('`id` > 7');
    $n->addFilterEqual('name', '/^[a-z]+$/');
    $n->addFilterBetween('id', 'is_numeric')
      ->setGreaterOrEqual(false); // Возвращается фильтр и его можно донастроить
    $n->addFilter(
      'date',
      function(Navigator $navi, Filter $filter){
        if ($val = $navi->getParameter($filter->getColumn())) {
          if ($filter->validate($val)) {
            $navi->addCondition('`created_at` >= "' . date('d.m.Y', strtotime('+' . $val . ' day')) . '"');
          }
        }
      },
      'is_numeric'
    );

    $this->assertEquals($exp, $n->processFilters(), $msg);
  }

  function dataFilters()
  {
    return array(
      array('/hello/', array('`id` > 7'), 'Фильтры не указаны - условие по-умолчанию'),
      array('/hello/some:value/', array('`id` > 7'), 'Указаны не используемые параметры - условие по-умолчанию'),
      array('/hello/name:abc/', array('`id` > 7', 'name' => 'abc'), 'Условие Equal по-умолчанию'),
      array(
        '/hello/name:123/id_from:10/id_to:20/',
        array('`id` > 7', '`id_from` > 10', '`id_to` <= 20'),
        'Значение name не прошло фильтр, ID фильтруется с двух сторон'
      ),
      array('/hello/id_from:abc/id_to:10/', array('`id` > 7', '`id_to` <= 10'), 'ID фильтруется с одной стороны'),
      array(
        '/hello/date:1/',
        array('`id` > 7', '`created_at` >= "' . date('d.m.Y', strtotime('+1 day')) . '"'),
        'Пользовательский фильтр на дату'
      ),
    );
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