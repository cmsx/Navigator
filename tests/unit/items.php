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
    $f = new Filter(makeNavigator('/test:1/'), 'test');
    $this->assertTrue($f->validate($alnum), 'Валидатор не задан');

    $f->setValidator('is_numeric');
    $this->assertEquals(1, $f->getCleanValue(), 'Корректное значение из URL после валидации');

    $this->assertFalse($f->validate($alnum), 'Только числа');
    $this->assertTrue($f->validate(123), 'Цифры можно');

    $f->setValidator('/^[a-z]+$/');
    $this->assertFalse($f->getCleanValue(), 'Некорректное значение не передается');

    $this->assertFalse($f->validate($alnum), 'Только буквы');
    $this->assertTrue($f->validate('abc'), 'Буквы можно');

    $f->setValidator(false);

    $f->setOptions(array(1 => 'one', 2 => 'two'));
    $this->assertTrue($f->validate(1), 'Верное значение - ключ массива');
    $this->assertFalse($f->validate('one'), 'Нет такого ключа в массиве');
    $this->assertFalse($f->validate(false), 'Отсутствующий параметр');

    $this->assertTrue($f->validate(array(1, 2)), 'Все значения массива есть среди допустимых');
    $this->assertFalse($f->validate(array(1, 3)), 'Один из элементов вне диапазона');

    $exp = '<option value="1">one</option>' . "\n" . '<option selected="selected" value="2">two</option>' . "\n";
    $this->assertEquals($exp, $f->asSelectOptions(2), 'Явно указанное значение для Опции для SELECT');

    $exp = '<option selected="selected" value="1">one</option>' . "\n" . '<option value="2">two</option>' . "\n";
    $this->assertEquals($exp, $f->asSelectOptions(), 'Значение опций выбрано из URL');

    $exp = '<select name="test">' . "\n" . $exp . '</select>';
    $this->assertEquals($exp, $f->asSelect(), 'Генерация списка SELECT');

    $exp = '<input name="test" type="text" value="1" />';
    $this->assertEquals($exp, $f->asInput(), 'Фильтр как поле для ввода со значением по-умолчанию');

    $f->setValidator('/^[a-z]+$/');
    $exp = '<input name="test" type="text" value="" />';
    $this->assertEquals($exp, $f->asInput(), 'Значение не прошедшее валидацию не подставляется');

    $exp = '<input class="hello" name="test" type="text" value="" />';
    $this->assertEquals($exp, $f->asInput('hello'), 'Можно указать произвольные аттрибуты');

    $f->setOptions(array('one', 'two'), true);
    $this->assertTrue($f->validate('one'), 'Верное значение - значение массива');
    $this->assertFalse($f->validate('three'), 'Нет такого значения в массиве');
  }

  function testFilterBetween()
  {
    $n = makeNavigator('/some/age_from:today/age_to:20/');
    $f = $n->addFilterBetween('age');

    $this->assertEquals('today', $f->getCleanValueFrom(), 'Значение от');
    $this->assertEquals(20, $f->getCleanValueTo(), 'Значение до');

    $f->setValidator('/^[a-z]+$/');

    $this->assertEquals('today', $f->getCleanValueFrom(), 'Значение от проходит валидацию');
    $this->assertFalse($f->getCleanValueTo(), 'Значение от не проходит валидацию');

    $exp = '<input class="hello" name="age_from" type="text" value="today" />';
    $this->assertEquals($exp, $f->asInputFrom('hello'), 'Рендер инпута от');

    $exp = '<input id="123" name="age_to" type="text" value="" />';
    $this->assertEquals($exp, $f->asInputTo(array('id' => 123)), 'Рендер инпута до');
  }

  function testFilterItem()
  {
    $n = makeNavigator('/some/');

    $this->assertFalse($n->getFilters(), 'Фильтры не заданы');
    $this->assertFalse($n->checkFilterExists('name'), 'Фильтра name еще нет');

    try {
      $n->getFilter('name');
      $this->fail('Попытка получения фильтра, которого еще нет');
    } catch (Exception $e) {
      $this->assertEquals(Navigator::ERR_FILTER_NOT_EXISTS, $e->getCode(), 'Верный код ошибки');
    }

    $eq  = $n->addFilterEqual('name');
    $btw = $n->addFilterBetween('date');
    $lk  = $n->addFilterLike('addr');

    $f = $n->getFilter('name');
    $this->assertTrue($f instanceof Filter, 'Фильтр является объектом Filter');
    $this->assertEquals('name', $f->getColumn(), 'Фильтр получен верно');

    $this->assertEquals('/some/name:john/', $f->asUrl('john')->toString(), 'Адрес фильтра верный');

    // Equal
    $this->assertEquals('name', $eq->getField(), 'По-умолчанию поле SQL равно имени параметра');
    $eq->setField('fullname');
    $this->assertEquals('fullname', $eq->getField(), 'Явно заданное поле SQL');

    // Between
    $this->assertEquals('date_from', $btw->getColumnFrom(), 'Параметр от');
    $this->assertEquals('date_to', $btw->getColumnTo(), 'Параметр до');

    // Like
    $this->assertEquals('оЛолё%ло%123', $lk->cleanValue('оЛолё"""   ло 123'), 'Очистка значения по-умолчанию');
    $lk->setRegularCleaner('/[^а-я]+/u');
    $this->assertEquals('оло%ло', $lk->cleanValue(' оло"""   ло 123 '), 'Очистка значения по своей регулярке');

    $this->assertEquals('`addr` LIKE ("путе%")', $lk->prepareLikeCondition('путе'), 'Подготовка условия по-умолчанию');
    $lk->setLikeTemplate('`%s` LIKE ("%%%s%%") AND `name` LIKE("%2$s%%")');
    $this->assertEquals(
      '`addr` LIKE ("%абц%") AND `name` LIKE("абц%")',
      $lk->prepareLikeCondition('абц'),
      'Подготовка условия по своему шаблону'
    );
  }

  function testFilterStaticCallable()
  {
    $n = makeNavigator('/hello/');
    $f = $n->addFilter('test', null, 'BlaBla::Hello');

    $this->assertEquals('Hello', $f->process(), 'Вызов статичного метода');
  }

  function testFilterBetweenDate()
  {
    $n = makeNavigator('/hello/date_from:18.01.2014/date_to:20.01.2014/');
    $f = $n->addFilterBetween('date');

    $exp = array('`date` >= "18.01.2014"', '`date` <= "20.01.2014"');
    $this->assertEquals($exp, $n->processFilters(), 'По-умолчанию данные подставляются напрямую');

    $f->setIsDate(true);
    $exp = array('`date` >= "2014-01-18 00:00"', '`date` <= "2014-01-20 23:59"');
    $this->assertEquals($exp, $n->processFilters(), 'Обработка диапазона дат');
  }

  /** @dataProvider dataFilters() */
  function testFilters($url, $exp, $msg)
  {
    $n = makeNavigator($url);
    $n->addDefaultCondition('`id` > 7');
    $n->addFilterEqual('name', '/^[a-z]+$/');
    $n->addFilterLike('addr');
    $n->addFilterBetween('id', 'is_numeric')
      ->setGreaterOrEqual(false); // Возвращается фильтр и его можно донастроить
    $n->addFilter(
      'date',
      'is_numeric',
      function (Navigator $navi, Filter $filter) {
        if ($val = $filter->getCleanValue()) {
          $navi->addCondition('`created_at` >= "' . date('d.m.Y', strtotime('+' . $val . ' day')) . '"');
        }
      }
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
        array('`id` > 7', '`id` > 10', '`id` <= 20'),
        'Значение name не прошло фильтр, ID фильтруется с двух сторон'
      ),
      array('/hello/id_from:abc/id_to:10/', array('`id` > 7', '`id` <= 10'), 'ID фильтруется с одной стороны'),
      array(
        '/hello/date:1/',
        array('`id` > 7', '`created_at` >= "' . date('d.m.Y', strtotime('+1 day')) . '"'),
        'Пользовательский фильтр на дату'
      ),
      array('/hello/addr:при/', array('`id` > 7', '`addr` LIKE ("при%")'), 'Фильтр Like')
    );
  }

  protected function setUp()
  {
    if (!$this->db) {
      $this->db = makeDB();
    }

    if (!$this->db) {
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

  protected function tearDown()
  {
    if ($this->db) {
      $this->db->drop('test')->execute();
    }
  }
}