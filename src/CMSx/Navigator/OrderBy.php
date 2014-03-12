<?php

namespace CMSx\Navigator;

use CMSx\Navigator;
use CMSx\URL;

/** Представление для сортировки */
class OrderBy
{
  /** Имя параметра в URL */
  const URL_PARAM = 'orderby';

  protected $column;
  protected $name;
  protected $sql;
  protected $asc = true;

  /** @var Navigator */
  protected $navigator;

  /** @var URL */
  protected $url;

  /** Получение имени для передачи как параметра в URL */
  public function asUrlParameter($asc = null)
  {
    if (is_null($asc)) {
      $asc = $this->getAsc();
    }

    return ($asc ? '' : '_') . $this->getColumn();
  }

  /**
   * Ссылка на вариант сортировки
   *
   * @return URL
   */
  public function asUrl($asc = null)
  {
    return $this->getUrl()->setParameter(static::URL_PARAM, $this->asUrlParameter($asc));
  }

  /** Получение в виде инструкции для сортировки SQL */
  public function asSQL()
  {
    return ($this->getSql() ? : '`' . $this->getColumn() . '`') . ' ' . ($this->getAsc() ? 'ASC' : 'DESC');
  }

  public function setColumn($column)
  {
    $this->column = $column;

    return $this;
  }

  public function getColumn()
  {
    return $this->column;
  }

  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  public function getName()
  {
    return $this->name;
  }

  public function setSql($sql)
  {
    $this->sql = $sql;

    return $this;
  }

  public function getSql()
  {
    return $this->sql;
  }

  public function setAsc($asc)
  {
    $this->asc = $asc;

    return $this;
  }

  public function getAsc()
  {
    return $this->asc;
  }

  public function setUrl(URL $url)
  {
    $this->url = $url;

    return $this;
  }

  public function getUrl()
  {
    if ($this->url) {
      return $this->url;
    }

    if ($this->navigator) {
      return $this->navigator->getUrlClean();
    }

    return new URL;
  }

  public function setNavigator(Navigator $navigator)
  {
    $this->navigator = $navigator;

    return $this;
  }
}