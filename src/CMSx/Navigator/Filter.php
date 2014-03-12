<?php

namespace CMSx\Navigator;

use CMSx\URL;
use CMSx\Navigator;
use CMSx\Navigator\Exception;

/** Условие для выборки */
class Filter
{
  protected $column;
  protected $callable;
  protected $validator;

  function __construct($column, $callable, $validator = null)
  {
    if (!is_null($callable) && !is_callable($callable)) {
      throw new Exception('В фильтр передано не callable значение', Navigator::ERR_FILTER_NOT_CALLABLE);
    }

    $this->column    = $column;
    $this->callable  = $callable;
    $this->validator = $validator;
  }

  /** Вызов фильтра */
  public function process(Navigator $navigator)
  {
    if ($c = $this->callable) {
      return $c($navigator, $this);
    }
  }

  /**
   * Проверка данных.
   * $value - значение или массив, тогда проверяются все значения массива
   * $validator - массив допустимых опций, callable или регулярка
   */
  public function validate($value)
  {
    if (is_null($this->validator)) {
      return true;
    }

    if (is_array($value)) {
      foreach ($value as $v) {
        if (!$this->validate($v, $this->validator)) {
          return false;
        }
      }

      return true;
    } else {
      if (is_callable($this->validator)) {
        return (bool)call_user_func_array($this->validator, array($value));
      } else {
        return (bool)preg_match($this->validator, $value);
      }
    }
  }

  /**
   * @param mixed $callable
   */
  public function setCallable($callable)
  {
    $this->callable = $callable;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getCallable()
  {
    return $this->callable;
  }

  /**
   * @param mixed $col
   */
  public function setColumn($col)
  {
    $this->column = $col;

    return $this;
  }

  public function getColumn()
  {
    return $this->column;
  }

  public function setValidator($validator)
  {
    $this->validator = $validator;

    return $this;
  }

  public function getValidator()
  {
    return $this->validator;
  }
}