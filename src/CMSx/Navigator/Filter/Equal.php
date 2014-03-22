<?php

namespace CMSx\Navigator\Filter;

use CMSx\Navigator;
use CMSx\Navigator\Filter;

class Equal extends Filter
{
  public function process(Navigator $navigator)
  {
    if (!$val = $navigator->getParameter($this->getColumn())) {
      return false;
    }

    if (!$this->validate($val)) {
      return false;
    }

    $navigator->addCondition($val, $this->getField());
  }
}