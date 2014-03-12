<?php

namespace CMSx;

use CMSx\URL;
use CMSx\Navigator\Exception;
use CMSx\Navigator\OrderBy;
use Symfony\Component\HttpFoundation\Request;

class Navigator
{
  const ERR_NO_ORDERBY_OPTION = 10;

  protected static $err_arr = array(
    self::ERR_NO_ORDERBY_OPTION => 'Поля "%s" нет в опциях для сортировки',
  );

  /** @var Request */
  protected $request;

  /** @var URL */
  protected $url;

  /** Количество элементов на странице */
  protected $onpage;

  /** Варианты сортировки */
  protected $orderby_options;

  /** Поле сортировки по-умолчанию */
  protected $default_orderby;
  protected $default_orderby_asc;

  /** Текущее значение сортировки */
  protected $orderby;
  protected $orderby_asc;

  function __construct(Request $request, URL $url = null)
  {
    if (is_null($url)) {
      $url = new URL();
      $url->load(); // Request убивает двоеточия в URL, поэтому используется $_SERVER['REQUEST_URI']
    }

    $this->setRequest($request);
    $this->setUrl($url);

    $this->init();
  }

  /**
   * Добавление возможности сортировки по колонке
   *
   * @return OrderBy
   */
  public function addOrderBy($column, $name = null, $sql = null)
  {
    $o = new OrderBy;
    $o->setColumn($column);
    $o->setName($name);
    $o->setSql($sql);
    $o->setUrl($this->getUrl());

    $this->orderby_options[$column] = $o;

    return $o;
  }

  /**
   * Список возможных вариантов сортировки
   *
   * @return OrderBy[]
   */
  public function getOrderByOptions()
  {
    return $this->orderby_options ? : false;
  }

  /**
   * Вариант сортировки по имени колонки
   *
   * @return OrderBy
   */
  public function getOrderByOption($column)
  {
    $opt = $this->getOrderByOptions();

    if (!isset($opt[$column])) {
      throw new Exception(sprintf(static::$err_arr[self::ERR_NO_ORDERBY_OPTION], $column), self::ERR_NO_ORDERBY_OPTION);
    }

    return $opt[$column];
  }

  /** Явно указываем поле для сортировки */
  public function setOrderBy($column, $asc = true)
  {
    $this->orderby     = $column;
    $this->orderby_asc = $asc;

    return $this;
  }

  /** Установка сортировки по-умолчанию */
  public function setDefaultOrderBy($column, $asc = true)
  {
    $this->default_orderby     = $column;
    $this->default_orderby_asc = $asc;

    return $this;
  }

  /** Сортировка по-умолчанию */
  public function getDefaultOrderBy()
  {
    if (!$this->default_orderby) {
      return false;
    }

    return $this->getOrderByOption($this->default_orderby)->setAsc($this->default_orderby_asc);
  }

  /**
   * Текущее значение сортировки
   *
   * @return OrderBy
   */
  public function getOrderBy()
  {
    if (!$this->orderby) {
      return $this->getDefaultOrderBy();
    }

    return $this->getOrderByOption($this->orderby)->setAsc($this->orderby_asc);
  }

  /** Явное указание текущего номера страницы */
  public function setPage($page)
  {
    $this->getUrl()->setParameter('page', $page);

    return $this;
  }

  /** Получение текущего номера страницы */
  public function getPage()
  {
    return $this->getUrl()->getPage();
  }

  /** Получение параметра из URL или из Request */
  public function getParameter($name, $default = false)
  {
    return $this->getUrl()->getParameter($name, null, $this->getRequest()->get($name, $default));
  }

  public function setOnpage($onpage)
  {
    $this->onpage = $onpage;

    return $this;
  }

  public function getOnpage()
  {
    return $this->onpage;
  }

  public function setRequest(Request $request)
  {
    $this->request = $request;

    return $this;
  }

  public function getRequest()
  {
    return $this->request;
  }

  public function setUrl(URL $url)
  {
    $this->url = $url;

    return $this;
  }

  public function getUrl()
  {
    return $this->url;
  }

  public function cloneUrl()
  {
    return clone $this->url;
  }

  protected function init()
  {
  }
}