<?php


class UserController
{
  public function actionRegister() {

    include ROOT . '/views/user/register.php';

    return true;
  }

}