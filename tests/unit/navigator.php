<?php

require_once __DIR__ . '/../init.php';

use CMSx\URL;
use CMSx\Navigator;
use Symfony\Component\HttpFoundation\Request;

class NavigatorTest extends PHPUnit_Framework_TestCase
{
  function testCreate()
  {
    $uri = '/hello/world/id:3/';
    $url = new URL($uri);
    $req = Request::create($uri, 'POST', array('one' => 'two'));

    $n = new Navigator($req, $url);

    $this->assertEquals('two', $n->getParameter('one'), 'Получаем параметр из Request');
    $this->assertEquals(3, $n->getParameter('id'), 'Получаем параметр из URL');
    $this->assertFalse($n->getParameter('blabla'), 'Несуществующий параметр');
    $this->assertTrue($n->getParameter('blabla', true), 'Несуществующий параметр и значение по-умолчанию');

    $n = new Navigator($req, new URL('my', 'url'));
    $this->assertEquals('/my/url/', $n->getUrl()->toString(), 'URL передан явно в конструктор');

    $u = $n->cloneUrl()->setParameter('some', 'value');
    $this->assertEquals('/my/url/', $n->getUrl()->toString(), 'В результате клонирования основной URL не изменился');
  }
}