<?php


class Profile
{
  public static function getDKP($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT dkp from users where name=?");
    $result->execute(array($username));
    return $result->fetch(PDO::FETCH_NUM)[0];
  }

  public static function getCharacters($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT id, class, level from characters where user_id=(SELECT id from users where name=?)");
    $result->execute(array($username));
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getEvents($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT date, type, status, dkp_recieved from events JOIN participated_users join users where name=?"); //заменять колонку с типом ивента на имя босса, если фармили босса
    $result->execute(array($username));
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }
}
