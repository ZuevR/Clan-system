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
    $result = $db->prepare("SELECT id, nick, class, level from characters where user_id=(SELECT id from users where name=?)");
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

  public static function addCharacter($username, $char_name, $class, $lvl)
  {
    $db = Db::getConnection();
    $result = $db->prepare("INSERT INTO characters(user_id, nick, class, level) values((SELECT id from users where name=?),?,?,?)");
    return $result ->execute(array($username, $char_name, $class, $lvl));
  }

  public static function changeCharacter($username, $char_name, $class, $lvl)
  {
    $db = Db::getConnection();
    $result = $db->prepare("UPDATE characters set level=?, class=? where nick=? and user_id=(SELECT id from users where name=?)");
    return $result ->execute(array($lvl, $class, $char_name, $username));
  }

  public static function getItems($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT date, status, name, max_bid, image_url
      from auction_deals join dropped_items join items where is_finished=1 and winner_id=(SELECT id from users where name=?)
      ORDER BY date desc");
    $result->execute(array($username));
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }
}
