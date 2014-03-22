<?php

namespace CMSx\Navigator\Filter;

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
    $from = $this->getColumnFrom();
    $to   = $this->getColumnTo();

    if ($from_val = $navigator->getParameter($from)) {
      if ($this->validate($from_val)) {
        if ($this->getIsDate()) {
          $from_val = date('Y-m-d 00:00', strtotime($from_val));
        }

        $s = $this->getGreaterOrEqual() ? '>=' : '>';
        $c = sprintf('`%s` %s %s', $this->getField(), $s, Builder::QuoteValue($from_val));

        $navigator->addCondition($c);
      }
    }

    if ($to_val = $navigator->getParameter($to)) {
      if ($this->validate($to_val)) {
        if ($this->getIsDate()) {
          $to_val = date('Y-m-d 23:59', strtotime($to_val));
        }

        $s = $this->getLessOrEqual() ? '<=' : '<';
        $c = sprintf('`%s` %s %s', $this->getField(), $s, Builder::QuoteValue($to_val));

        $navigator->addCondition($c);
      }
    }
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