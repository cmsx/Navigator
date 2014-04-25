<?php

namespace CMSx\Navigator\Filter;

use CMSx\Navigator;
use CMSx\Navigator\Filter;

class Equal extends Filter
{
  public function process(Navigator $navigator)
  {
    if ($val = $this->getCleanValue()) {
      $navigator->addCondition($val, $this->getField());
    }
  }
}