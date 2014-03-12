<?php

require_once __DIR__ . '/../init.php';

use CMSx\URL;
use CMSx\Navigator;
use CMSx\Navigator\OrderBy;
use Symfony\Component\HttpFoundation\Request;

class NavigatorTest extends PHPUnit_Framework_TestCase
{
  function testCreate()
  {
    $n = $this->makeNavigator('/hello/world/id:3/', array('one' => 'two'));

    $this->assertEquals('two', $n->getParameter('one'), 'Получаем параметр из Request');
    $this->assertEquals(3, $n->getParameter('id'), 'Получаем параметр из URL');
    $this->assertFalse($n->getParameter('blabla'), 'Несуществующий параметр');
    $this->assertTrue($n->getParameter('blabla', true), 'Несуществующий параметр и значение по-умолчанию');

    $n = new Navigator(Request::create('/'), new URL('my', 'url'));
    $this->assertEquals('/my/url/', $n->getUrl()->toString(), 'URL передан явно в конструктор');

    $n->cloneUrl()->setParameter('some', 'value');
    $this->assertEquals('/my/url/', $n->getUrl()->toString(), 'В результате клонирования основной URL не изменился');
  }

  function testOrderByItem()
  {
    $o = new OrderBy();
    $o->setColumn('name');

    $this->assertEquals('/orderby:name/', $o->asURL()->toString(), 'Адрес для сортировки без указанного URL');

    $o->setUrl(new URL('test', 'me', array('orderby' => 'id', 'id' => 2)));

    $this->assertEquals('/test/me/orderby:name/id:2/', $o->asURL()->toString(), 'Адрес для сортировки с указанным URL');

    $this->assertEquals('`name` ASC', $o->asSQL(), 'Сортировка по имени колонки по-возрастанию');

    $this->assertEquals('name', $o->asUrlParameter(), 'Сортировка по-возрастанию');
    $this->assertEquals('_name', $o->asUrlParameter(false), 'Сортировка по-убыванию указанная явно');

    $o->setAsc(false);
    $this->assertEquals('_name', $o->asUrlParameter(), 'Сортировка по-убыванию указанная через свойство объекта');
    $this->assertEquals('`name` DESC', $o->asSQL(), 'Сортировка по имени колонки по-убыванию');

    $o->setSql('count(*)');
    $this->assertEquals('count(*) DESC', $o->asSQL(), 'Сортировка по указанному условию SQL по-убыванию');
  }

  function testOrderBy()
  {
    $n = $this->makeNavigator('/hello/id:1/');

    $this->assertFalse($n->getOrderByOptions(), 'Опции для сортировки еще не указаны');

    $n->addOrderBy('id', 'ID');
    $n->addOrderBy('name');
    $n->addOrderBy('count', 'Total', 'count(*)');

    $this->assertCount(3, $n->getOrderByOptions(), 'Всего три опции для сортировки');
    $this->assertArrayHasKey('id', $n->getOrderByOptions(), 'Ключи массива - имена столбцов');

    try {
      $n->getOrderByOption('blabla');

      $this->fail('Выбрасывается исключение на отсутствующий выбор');
    } catch (Navigator\Exception $e) {
      $this->assertEquals(Navigator::ERR_NO_ORDERBY_OPTION, $e->getCode(), 'Проверяем код ошибки');
    }

    $this->assertTrue($n->getOrderByOption('name') instanceof OrderBy, 'Получаем объект сортировки');
    $this->assertFalse($n->getOrderBy(), 'В адресе порядок сортировки не выбран');

    $n->setDefaultOrderBy('name', false);

    $this->assertNotEmpty($n->getDefaultOrderBy(), 'Прямое обращение к сортировке по-умолчанию');
    $this->assertNotEmpty($n->getOrderBy(), 'Обращение к текущей сортировке');
    $this->assertEquals('`name` DESC', $n->getOrderBy()->asSQL(), 'Сортировка по-умолчанию, как SQL');

    $n->setOrderBy('count', false);

    $this->assertEquals('count(*) DESC', $n->getOrderBy()->asSQL(), 'Явно указанная сортировка, как SQL');

    $this->assertEquals('/hello/id:1/orderby:_count/', $n->getOrderBy()->asURL()->toString(), 'Адрес для сортировки');
  }

  function makeNavigator($uri, $post = array())
  {
    $url = new URL($uri);
    $req = Request::create($uri, 'POST', $post);

    return new Navigator($req, $url);
  }
}