<?php

namespace CMSx;

use CMSx\URL;
use CMSx\Navigator\Exception;
use Symfony\Component\HttpFoundation\Request;

class Navigator
{
  /** @var Request */
  protected $request;

  /** @var URL */
  protected $url;

  /** Количество элементов на странице */
  protected $onpage;

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

  /** Получение параметра из URL или из Request */
  public function getParameter($name, $default = false)
  {
    return $this->getUrl()->getParameter($name, null, $this->getRequest()->get($name, $default));
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