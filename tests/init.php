<?php

/**
 * Файл инициализации для запуска тестов
 *
 * Перед запуском сделать composer install
 * http://getcomposer.org/download/
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CMSx\DB;
use CMSx\URL;
use CMSx\DB\Item;
use CMSx\Navigator;
use Symfony\Component\HttpFoundation\Request;

function makeNavigator($uri, $post = array())
{
  $url = new URL($uri);
  $req = Request::create($uri, 'POST', $post);

  return new Navigator($req, $url);
}

function makeDB()
{
  try {
    return new DB(DB::PDO('localhost', 'test', 'test', 'test'));
  } catch (\Exception $e) {
    return false;
  }
}

class MyItem extends Item
{
  static $db;

  function getManager()
  {
    if (is_null(static::$db)) {
      static::$db = makeDB();
    }

    return static::$db;
  }

  /** Функция возвращает имя таблицы в БД */
  function getTable()
  {
    return 'test';
  }
}