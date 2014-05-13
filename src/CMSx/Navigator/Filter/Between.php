<?php

namespace CMSx\Navigator\Filter;

use CMSx\HTML;
use CMSx\Navigator;
use CMSx\DB\Builder;
use CMSx\Navigator\Filter;

class Between extends Filter
{
  protected $less_or_equal = true;
  protected $greater_or_equal = true;
  protected $is_date = false;

  public function process(Navigator $navigator)
  {
    if ($this->getCallable()) {
      parent::process();
    } else {
      if ($from = $this->prepareFromCondition()) {
        $navigator->addCondition($from);
      }

      if ($to = $this->prepareToCondition()) {
        $navigator->addCondition($to);
      }
    }
  }

  /** Подготовка условия для нижней границы диапазона */
  public function prepareFromCondition($val = null)
  {
    $cond = false;
    if (is_null($val)) {
      if ($val = $this->getCleanValueFrom()) {
        if ($this->getIsDate()) {
          $val = date('Y-m-d 00:00', strtotime($val));
        }
      }
    }

    if ($val) {
      $cond = sprintf(
        '%s %s %s',
        Builder::QuoteKey($this->getField()),
        $this->getGreaterOrEqual() ? '>=' : '>',
        Builder::QuoteValue($val)
      );
    }

    return $cond;
  }

  /** Подготовка условия для верхней границы диапазона */
  public function prepareToCondition($val = null)
  {
    $cond = false;
    if (is_null($val)) {
      if ($val = $this->getCleanValueTo()) {
        if ($this->getIsDate()) {
          $val = date('Y-m-d 23:59:59', strtotime($val));
        }
      }
    }

    if ($val) {
      $cond = sprintf(
        '%s %s %s',
        Builder::QuoteKey($this->getField()),
        $this->getLessOrEqual() ? '<=' : '<',
        Builder::QuoteValue($val)
      );
    }

    return $cond;
  }

  /**
   * @param boolean $is_date
   */
  public function setIsDate($is_date)
  {
    $this->is_date = $is_date;

    return $this;
  }

  /**
   * @return boolean
   */
  public function getIsDate()
  {
    return $this->is_date;
  }

  /**
   * Генерация тега INPUT для фильтра от.
   * Значение будет подставлено если оно проходит валидацию
   */
  public function asInputFrom($attr = null, $value = null)
  {
    if (is_null($value)) {
      $value = $this->getCleanValueFrom();
    }

    return HTML::Input($this->getColumnFrom(), $value, $attr);
  }

  /**
   * Генерация тега INPUT для фильтра до.
   * Значение будет подставлено если оно проходит валидацию
   */
  public function asInputTo($attr = null, $value = null)
  {
    if (is_null($value)) {
      $value = $this->getCleanValueTo();
    }

    return HTML::Input($this->getColumnTo(), $value, $attr);
  }

  /** Получение чистого значения фильтра после валидации */
  public function getCleanValueFrom()
  {
    $val = $this->getNavigator()->getParameter($this->getColumnFrom());

    return $this->validate($val) ? $val : false;
  }

  /** Получение чистого значения фильтра после валидации */
  public function getCleanValueTo()
  {
    $val = $this->getNavigator()->getParameter($this->getColumnTo());

    return $this->validate($val) ? $val : false;
  }

  /** Имя параметра "от" */
  public function getColumnFrom()
  {
    return $this->getColumn() . '_from';
  }

  /** Имя параметра "до" */
  public function getColumnTo()
  {
    return $this->getColumn() . '_to';
  }

  /** Сравнение > или >= */
  public function setGreaterOrEqual($greater_or_equal)
  {
    $this->greater_or_equal = $greater_or_equal;

    return $this;
  }

  /** Сравнение > или >= */
  public function getGreaterOrEqual()
  {
    return $this->greater_or_equal;
  }

  /** Сравнение < или <= */
  public function setLessOrEqual($less_or_equal)
  {
    $this->less_or_equal = $less_or_equal;

    return $this;
  }

  /** Сравнение < или <= */
  public function getLessOrEqual()
  {
    return $this->less_or_equal;
  }
}