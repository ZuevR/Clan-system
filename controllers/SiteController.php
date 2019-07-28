<?php

class SiteController
{
  public function actionIndex() {

    include ROOT . '/views/site/index.php';

    return true;
  }
}