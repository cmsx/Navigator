<?php

namespace CMSx\Navigator\Filter;

use CMSx\Navigator;
use CMSx\Navigator\Filter;

class Like extends Filter
{
  protected $prepare;
  protected $like_template = '`%s` LIKE ("%s%%")';
  protected $regular_cleaner = '/[^a-zа-яёЁ0-9]+/ui';

  public function process(Navigator $navigator)
  {
    if (!$val = $navigator->getParameter($this->getColumn())) {
      return false;
    }

    if (!$this->validate($val)) {
      return false;
    }

    if ($cond = $this->prepareLikeCondition($val)) {
      $navigator->addCondition($cond);
    }
  }

  /** Подготовка SQL выражения по-умолчанию */
  public function prepareLikeCondition($value)
  {
    if ($val = $this->cleanValue($value)) {
      return sprintf($this->like_template, $this->getField(), $val);
    }
  }

  /** Очистка значения от недопустимых символов по регулярке */
  public function cleanValue($value)
  {
    $str = preg_replace($this->regular_cleaner, ' ', $value);
    $str = preg_replace('/^[\s]+/u', '', $str);
    $str = preg_replace('/[\s]+$/u', '', $str);

    return str_replace(' ', '%', $str);
  }

  /** Шаблон для поиска. По умолчанию `%s` LIKE ("%s") */
  public function setLikeTemplate($like_template)
  {
    $this->like_template = $like_template;

    return $this;
  }

  /** Шаблон для поиска. По умолчанию `%s` LIKE ("%s") */
  public function getLikeTemplate()
  {
    return $this->like_template;
  }

  /** Регулярное выражение для очистки значения */
  public function setRegularCleaner($regular_cleaner)
  {
    $this->regular_cleaner = $regular_cleaner;

    return $this;
  }

  /** Регулярное выражение для очистки значения */
  public function getRegularCleaner()
  {
    return $this->regular_cleaner;
  }
}