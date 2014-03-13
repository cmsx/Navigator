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
    $n = makeNavigator('/hello/world/id:3/', array('one' => 'two'));

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

    $this->assertEquals('/orderby:name/', $o->asUrl()->toString(), 'Адрес для сортировки без указанного URL');

    $o->setUrl(new URL('test', 'me', array('orderby' => 'id', 'id' => 2)));

    $this->assertEquals('/test/me/orderby:name/id:2/', $o->asUrl()->toString(), 'Адрес для сортировки с указанным URL');

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
    $n = makeNavigator('/hello/id:1/');

    $this->assertFalse($n->getOrderByOptions(), 'Опции для сортировки еще не указаны');

    $n->addOrderBy('id', 'ID');
    $n->addOrderBy('name')
      ->setTmplAscName('%s вверх')
      ->setTmplDescName('%s вниз');
    $n->addOrderBy('count', 'Total', 'count(*)');

    $this->assertCount(3, $n->getOrderByOptions(), 'Всего три опции для сортировки');
    $this->assertArrayHasKey('id', $n->getOrderByOptions(), 'Ключи массива - имена столбцов');

    $o = $n->getOrderByOption('name');
    $this->assertEquals('Name вверх', $o->getAscName(), 'Шаблон для имени сортировки вверх');
    $this->assertEquals('Name вниз', $o->getDescName(), 'Шаблон для имени сортировки вниз');

    $exp = '<option value="id">ID по-возрастанию</option>' . "\n"
      . '<option value="_id">ID по-убыванию</option>' . "\n"
      . '<option value="name">Name вверх</option>' . "\n"
      . '<option value="_name">Name вниз</option>' . "\n"
      . '<option value="count">Total по-возрастанию</option>' . "\n"
      . '<option value="_count">Total по-убыванию</option>' . "\n";
    $this->assertEquals($exp, $n->getOrderByAsSelectOptions(), 'Генерация опций для SELECT');

    $exp = '<select name="orderby">' . "\n" . $exp . '</select>';
    $this->assertEquals($exp, $n->getOrderByAsSelect(), 'Генерация SELECT целиком');

    $this->assertFalse($n->checkOrderByOptionExists('blabla'), 'Опция не существует');

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

    $s = $n->getOrderBy()->asUrl()->toString();
    $this->assertEquals('/hello/orderby:_count/', $s, 'Адрес для сортировки без лишних параметров');
  }

  function testCleanUrl()
  {
    $n = makeNavigator('/hello/one:two/id:1/id:2/bla:bla/page:3/orderby:name/');

    $this->assertEquals('/hello/', $n->getUrlClean()->toString(), 'В очищенном URL изначально нет лишних параметров');

    $n->addOrderBy('name');

    $this->assertEquals(array('name', true), $n->processOrderColumnFromUrl('name'), 'Прямая сортировка в URL');
    $this->assertEquals(array('name', false), $n->processOrderColumnFromUrl('_name'), 'Обратная сортировка в URL');

    $o = $n->getOrderBy();

    $this->assertNotEmpty($o, 'Значение сортировки получено из адреса');
    $this->assertEquals('/hello/orderby:name/', $o->asUrl()->toString(), 'Адрес значения корректный');
    $this->assertEquals('/hello/orderby:name/', $n->getUrlClean()->toString(), 'Добавили возможность сортировки по name');

    $n = makeNavigator('/hello/page:3/orderby:_name/');
    $n->addOrderBy('name');

    $o = $n->getOrderBy();

    $this->assertEquals('/hello/orderby:_name/', $n->getUrlClean()->toString(), 'Сортировка по name по-убыванию');
    $this->assertEquals('/hello/orderby:_name/', $o->asUrl()->toString(), 'Адрес значения по убыванию корректный');

    $n = makeNavigator('/hello/');
    $n->setDefaultOrderBy('name', false);
    $n->addFilterEqual('name');

    $this->assertEquals('_name', $n->getOrderBy()->asUrlParameter(), 'Сортировка по-умолчанию есть');
    $this->assertEquals('/hello/', $n->getUrlClean()->toString(), 'Сортировка по-умолчанию не подставляется в URL');

    $n = makeNavigator('/hello/name:0/');
    $this->assertEquals('/hello/', $n->getUrlClean()->toString(), 'Параметр не может быть нулем!');

  }

  function testPagination()
  {
    $n = makeNavigator('/hello/page:3/some:value/');

    $this->assertEquals(3, $n->getPage(), 'Открыта страница №3');
    $this->assertEquals('/hello/page:3/', $n->getPageUrl()->toString(), 'Адрес текущей страницы');

    $this->assertEquals(0, $n->getTotal(), 'Количество неизвестно');

    $this->assertFalse($n->getTotalPages(), 'Количество страниц неизвестно');
    $this->assertFalse($n->getLastPage(), 'Неизвестна последняя страница');
    $this->assertFalse($n->getLastPageUrl(), 'Неизвестен адрес последней страницы');

    $this->assertEquals('/hello/page:15/', $n->getPageUrl(15)->toString(), 'Адрес для произвольной страницы');

    $n->setTotal(56);

    $this->assertEquals(1, $n->getTotalPages(), 'Если есть количество без onpage, то всегда будет 1 страница');

    $n->setOnpage(10);
    $this->assertEquals(6, $n->getTotalPages(), 'Количество страниц при 10 штуках на странице');

    $this->assertEquals(2, $n->getPrevPage(), 'Предыдущая страница');
    $this->assertEquals('/hello/page:2/', $n->getPrevPageUrl()->toString(), 'Адрес предыдущей страница');

    $this->assertEquals(4, $n->getNextPage(), 'Следующая страница');
    $this->assertEquals('/hello/page:4/', $n->getNextPageUrl()->toString(), 'Адрес следующей страница');

    $this->assertEquals(1, $n->getFirstPage(), 'Первая страница');
    $this->assertEquals('/hello/page:1/', $n->getFirstPageUrl()->toString(), 'Адрес первой страницы');

    $this->assertEquals(6, $n->getLastPage(), 'Последняя страница');
    $this->assertEquals('/hello/page:6/', $n->getLastPageUrl()->toString(), 'Адрес последней страница');

    $n->setPage(1);
    $this->assertFalse($n->getPrevPage(), 'Нет предыдущей страницы');
    $this->assertFalse($n->getPrevPageUrl(), 'Нет URL для предыдущей страницы');

    $n->setPage(6);
    $this->assertFalse($n->getNextPage(), 'Нет следующей страницы');
    $this->assertFalse($n->getNextPageUrl(), 'Нет URL для следующей страницы');

    $n->addFilterEqual('some');

    $this->assertEquals('/hello/some:value/page:2/', $n->getPageUrl(2)->toString(), 'Фильтры сохраняются при навигации');
  }
}