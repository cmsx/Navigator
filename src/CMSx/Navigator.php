<?php

namespace CMSx;

use CMSx\URL;
use CMSx\DB\Item;
use CMSx\Navigator\Filter;
use CMSx\Navigator\OrderBy;
use CMSx\Navigator\Exception;
use CMSx\Navigator\Filter\Equal;
use CMSx\Navigator\Filter\Between;
use Symfony\Component\HttpFoundation\Request;

class Navigator
{
  const ERR_NO_ORDERBY_OPTION    = 10;
  const ERR_CLASS_IS_NOT_ITEM    = 20;
  const ERR_CLASS_IS_NOT_DEFINED = 30;
  const ERR_FILTER_NOT_CALLABLE  = 40;

  protected static $err_arr = array(
    self::ERR_NO_ORDERBY_OPTION    => 'Поля "%s" нет в опциях для сортировки',
    self::ERR_CLASS_IS_NOT_ITEM    => 'Класс "%s" не является наследником Item',
    self::ERR_CLASS_IS_NOT_DEFINED => 'Класс для выборки не указан',
  );

  /** @var Request */
  protected $request;

  /** @var URL */
  protected $url;

  /** Количество элементов на странице */
  protected $onpage;

  /** Количество элементов всего */
  protected $total;

  /** Варианты сортировки */
  protected $orderby_options;

  /** Поле сортировки по-умолчанию */
  protected $default_orderby;
  protected $default_orderby_asc;

  /** Текущее значение сортировки */
  protected $orderby;
  protected $orderby_asc;

  /** Класс для объекта Item */
  protected $class;

  /** Имя функции для выборки элементов */
  protected $func_find = 'Find';

  /** Имя функции для подсчета элементов */
  protected $func_count = 'Count';

  /** @var Filter[] */
  protected $filters;

  /** Сгенерированные условия для выборки */
  protected $conditions;

  /** Предзаданные условия для выборки */
  protected $default_conditions;

  /** Флаг, что выборка по запросу отсутствует */
  protected $searchable = true;

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
   * Получение массива с элементами, должен быть указан класс.
   * Может быть настроено имя функции через setFuncFind или переопределено при наследовании
   */
  public function getItems()
  {
    if (!$this->getSearchable()) {
      return false;
    }

    return $this->callStatic(
      $this->getFuncFind(),
      array($this->processFilters(), $this->processOrderBy(), $this->getOnpage(), $this->getPage())
    );
  }

  /**
   * В $callable фильтра при вызове передаются аргументы Navigator, Filter
   *
   * @return Filter
   */
  public function addFilter($col, $callable, $validator = null)
  {
    return $this->filters[$col] = new Filter($col, $callable, $validator);
  }

  /** Фильтр значение равно */
  public function addFilterEqual($col, $validator = null)
  {
    return $this->filters[$col] = new Equal($col, null, $validator);
  }

  /** Фильтр значение между */
  public function addFilterBetween($col, $validator = null)
  {
    return $this->filters[$col] = new Between($col, null, $validator);
  }

  /** Добавление условия для выборки Where */
  public function addCondition($value, $key = null)
  {
    if (!is_null($key)) {
      $this->conditions[$key] = $value;
    } else {
      $this->conditions[] = $value;
    }

    return $this;
  }

  /** Добавление базового условия выборки Where */
  public function addDefaultCondition($value, $key = null)
  {
    if (!is_null($key)) {
      $this->default_conditions[$key] = $value;
    } else {
      $this->default_conditions[] = $value;
    }

    return $this;
  }

  /** Указание базовых условий выборки */
  public function setDefaultConditions($where)
  {
    $this->default_conditions = $where;

    return $this;
  }

  /** Получение условия для выборки */
  public function processFilters()
  {
    $this->conditions = false;

    if ($this->filters) {
      foreach ($this->filters as $f) {
        $f->process($this);
      }
    }

    if (!$this->conditions) {
      return $this->default_conditions;
    }

    return $this->default_conditions
      ? array_merge($this->default_conditions, $this->conditions)
      : $this->conditions;
  }

  /** Получение SQL для сортировки */
  public function processOrderBy()
  {
    if ($o = $this->getOrderBy()) {
      return $o->asSQL();
    }

    return null;
  }

  /** Установка класса для модели с которой работаем. Класс должен наследоваться от Item */
  public function setClass($class)
  {
    $o = new $class;

    if (!($o instanceof Item)) {
      throw new Exception(sprintf(static::$err_arr[static::ERR_CLASS_IS_NOT_ITEM], $class), static::ERR_CLASS_IS_NOT_ITEM);
    }

    $this->class = $class;

    return $this;
  }

  /** Общее количество элементов */
  public function getTotal($refresh = false)
  {
    if (!$this->getSearchable()) {
      return 0;
    }

    if ($refresh || !$this->total) {
      $this->total = $this->countTotal();
    }

    return $this->total ? : 0;
  }

  /** Общее количество элементов */
  public function setTotal($total)
  {
    $this->total = $total;

    return $this;
  }

  /** Количество страниц */
  public function getTotalPages()
  {
    if (!$total = $this->getTotal()) {
      return false;
    }

    if (!$onpage = $this->getOnpage()) {
      return 1;
    }

    return ceil($total / $onpage);
  }

  /** Номер последней страницы */
  public function getLastPage()
  {
    return $this->getTotalPages();
  }

  /** @return bool|URL */
  public function getLastPageUrl()
  {
    $p = $this->getLastPage();

    return $p ? $this->getPageUrl($p) : false;
  }

  /** Номер первой страницы */
  public function getFirstPage()
  {
    return 1;
  }

  /** @return bool|URL */
  public function getFirstPageUrl()
  {
    $p = $this->getFirstPage();

    return $p ? $this->getPageUrl($p) : false;
  }

  /** Номер следующей страницы */
  public function getNextPage()
  {
    $p = $this->getPage();

    return $p < $this->getTotalPages() ? $p + 1 : false;
  }

  /** @return bool|URL */
  public function getNextPageUrl()
  {
    $p = $this->getNextPage();

    return $p ? $this->getPageUrl($p) : false;
  }

  /** Номер предыдущей страницы */
  public function getPrevPage()
  {
    $p = $this->getPage();

    return $p > 1 ? $p - 1 : false;
  }

  /** @return bool|URL */
  public function getPrevPageUrl()
  {
    $p = $this->getPrevPage();

    return $p ? $this->getPageUrl($p) : false;
  }

  /**
   * Ссылка на страницу с нужным номером
   * Если номер не указан - будет возвращен адрес текущей страницы
   *
   * @return URL
   */
  public function getPageUrl($page = null)
  {
    if (is_null($page)) {
      $page = $this->getPage();
    }

    return $this->getUrlClean()->setParameter('page', $page);
  }

  /**
   * Получение URL без лишних параметров и постраничности
   *
   * @return URL
   */
  public function getUrlClean()
  {
    $this->process();

    $u = $this->cloneUrl()->cleanParameters();

    if ($o = $this->getOrderBy()) {
      $u->addParameter('orderby', $o->asUrlParameter());
    }

    return $u;
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
    $o->setNavigator($this);

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

    list($col, $asc) = $this->processOrderColumnFromUrl($column);

    if (!$this->checkOrderByOptionExists($col)) {
      throw new Exception(sprintf(static::$err_arr[self::ERR_NO_ORDERBY_OPTION], $column), self::ERR_NO_ORDERBY_OPTION);
    }

    $o = $opt[$col];

    return $o->setAsc($asc);
  }

  /** Определение колонки и порядка сортировки. Возвращает массив [colname, asc] */
  public function processOrderColumnFromUrl($column)
  {
    // Обратная сортировка
    if (false !== strpos($column, '_')) {
      $col = substr($column, 1);
      if ($this->checkOrderByOptionExists($col)) {
        return array($col, false);
      }
    }

    return array($column, true);
  }

  /** Проверка, что опция сортировки существует */
  public function checkOrderByOptionExists($column)
  {
    $opt = $this->getOrderByOptions();

    return isset($opt[$column]);
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
    $this->process();

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

  /** Имя функции для подсчета элементов */
  public function setFuncCount($func_count)
  {
    $this->func_count = $func_count;

    return $this;
  }

  /** Имя функции для подсчета элементов */
  public function getFuncCount()
  {
    return $this->func_count;
  }

  /** Имя функции для выборки элементов */
  public function setFuncFind($func_find)
  {
    $this->func_find = $func_find;

    return $this;
  }

  /** Имя функции для выборки элементов */
  public function getFuncFind()
  {
    return $this->func_find;
  }

  public function setSearchable($searchable)
  {
    $this->searchable = $searchable;

    return $this;
  }

  public function getSearchable()
  {
    return $this->searchable;
  }

  protected function init()
  {
  }

  /**
   * Функция подсчета количества элементов.
   * Если указан класс Item то работает автоматически от фильтра
   * Может быть настроено имя функции через setFuncCount или переопределено при наследовании
   */
  protected function countTotal()
  {
    if ($this->class) {
      if (!$this->total = $this->callStatic($this->getFuncCount(), array($this->processFilters()))) {
        $this->setSearchable(false);
      }
    }

    return $this->total;
  }

  /** Вызов статической функции по классу */
  protected function callStatic($func, $args = array())
  {
    if (!$this->class) {
      throw new Exception(static::$err_arr[static::ERR_CLASS_IS_NOT_DEFINED], static::ERR_CLASS_IS_NOT_DEFINED);
    }

    return $this->class
      ? call_user_func_array($this->class . '::' . $func, $args)
      : false;
  }

  /** Обработка URL и Request для получения параметров */
  protected function process()
  {
    if ($orderby = $this->getParameter(OrderBy::URL_PARAM)) {
      list($col, $asc) = $this->processOrderColumnFromUrl($orderby);
      if ($this->checkOrderByOptionExists($col)) {
        $this->setOrderBy($col, $asc);
      }
    }
  }
}