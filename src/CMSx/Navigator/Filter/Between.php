<?php

namespace CMSx\Navigator\Filter;

use CMSx\Navigator;
use CMSx\DB\Builder;
use CMSx\Navigator\Filter;

class Between extends Filter
{
  protected $less_or_equal = true;
  protected $greater_or_equal = true;

  public function process(Navigator $navigator)
  {
    $from = $this->getColumn() . '_from';
    $to   = $this->getColumn() . '_to';

    if ($from_val = $navigator->getParameter($from)) {
      if ($this->validate($from_val)) {
        $s = $this->getGreaterOrEqual() ? '>=' : '>';
        $c = sprintf('`%s` %s %s', $from, $s, Builder::QuoteValue($from_val));

        $navigator->addCondition($c);
      }
    }

    if ($to_val = $navigator->getParameter($to)) {
      if ($this->validate($to_val)) {
        $s = $this->getLessOrEqual() ? '<=' : '<';
        $c = sprintf('`%s` %s %s', $to, $s, Builder::QuoteValue($to_val));

        $navigator->addCondition($c);
      }
    }
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