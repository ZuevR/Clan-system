<?php

class Events
{
  public static function getEvents($status)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT * from events where status = ? order by date desc");
    $result->execute(array($status));
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function joinEvent($username, $event_id, $screenshot_url)
  {
    $db = Db::getConnection();
    $event_status = $db->prepare("SELECT status from events where id=?");
    $event_status->execute(array($event_id));
    $event_status=$event_status->fetch(PDO::FETCH_NUM)[0];

    $record_exists = $db->prepare("SELECT count(*) from participated_users join users on user_id=users.id where users.name=?");
    $record_exists->execute(array($username));
    $record_exists = $record_exists->fetch(PDO::FETCH_NUM)[0];

    if ($event_status=='registration' && !$record_exists) {
      $db->prepare("INSERT INTO participated_users set user_id=(SELECT id from users where name=?), event_id=?, screenshot_url=?")
      ->execute(array($username, $event_id, $screenshot_url));
      return True;
    }
    else {
      return False;
    }
  }

  public static function displayInfo($event_id, $username)
  {
    $db = Db::getConnection();
    $event_info = $db->prepare("SELECT * from events where id=?");
    $event_info->execute(array($event_id));
    $event_info = $event_info->fetch(PDO::FETCH_ASSOC);

    $participated_users = $db->prepare("SELECT users.name FROM participated_users join users on user_id=users.id where event_id=?");
    $participated_users->execute(array($event_id));
    $participated_users = $participated_users->fetchAll(PDO::FETCH_ASSOC);

    $user_dkp_recieved = $db->prepare("SELECT dkp_recieved FROM participated_users where event_id=? and user_id=(SELECT id from users where name=?)");
    $user_dkp_recieved->execute(array($event_id, $username));
    $user_dkp_recieved = $user_dkp_recieved->fetch(PDO::FETCH_NUM)[0];
    $user_dkp_recieved = $user_dkp_recieved == null ? -1 : $user_dkp_recieved;

    $dropped_items = $db->prepare("SELECT name, image_url from dropped_items join items on item_id=items.id where event_id=?");
    $dropped_items->execute(array($event_id));
    $dropped_items=$dropped_items->fetchAll(PDO::FETCH_ASSOC);

    return array(
      'event_info=' => $event_info,
      'participated_users' => $participated_users,
      'user_dkp_recieved' => $user_dkp_recieved,
      'dropped_items' => $dropped_items
    );
  }
}
