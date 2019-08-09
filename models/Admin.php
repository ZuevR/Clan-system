<?php

class Admin
{
  /*админка по вещам*/
  public static function addItem($name, $dkp_value, $image_url)
  {
    $db = Db::getConnection();
    $db->prepare("INSERT INTO items SET name=?, dkp_value=?, image_url=?")->execute(array($name, $dkp_value, $image_url));
  }
  public static function changeItem($item_id, $name, $dkp_value, $image_url)
  {
    $db = Db::getConnection();
    $db->prepare("UPDATE items SET name=?, dkp_value=?, image_url=? WHERE id=?")->execute(array($name, $dkp_value, $image_url, $item_id));
  }
  /*админка по юзерам*/
  public static function getActiveUsersList()
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT users.name as 'username', clan_role, dkp, parties.name as 'party_name'
      from users left join parties on party_id=parties.id where clan_role!='inactive'");
    $result->execute();
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }
  public static function getInactiveUsersList()
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT id, name, email from users where clan_role='inactive'");
    $result->execute();
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }
  public static function activateUser($user_id)
  {
    $db = Db::getConnection();
    $db->prepare("UPDATE users SET clan_role='member' where id=?")->execute(array($user_id));
  }
  public static function inspectUser($user_id)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT name, dkp from users where id=?");
    $result->execute(array($user_id));
    $result = $result->fetch(PDO::FETCH_ASSOC);

    $party_name = $db->prepare("SELECT parties.name from parties join users on party_id=parties.id where users.id=?");
    $party_name->execute(array($user_id));
    $party_name = $party_name->fetch(PDO::FETCH_NUM)[0];

    $teammates = $db->prepare("SELECT name, dkp from users where party_id=(select party_id from users where id=?) and id!=?");
    $teammates->execute(array($user_id, $user_id));
    $teammates = $teammates->fetchAll(PDO::FETCH_ASSOC);

    $events = $db->prepare("SELECT events.date, type, events.boss_name, description, screenshot_url, dkp_recieved from participated_users join events on events.id=event_id
      where user_id=? order by date desc");
    $events->execute(array($user_id));
    $events = $events->fetchAll(PDO::FETCH_ASSOC);

    $share_history = $db->prepare("SELECT uf.name as sendername, ut.name as reciever, pt.name as reciever_party, amount, date
      FROM dkp_share_history as t join users uf on uf.id=from_user join users ut on ut.id=to_user
      join parties pt on pt.id=ut.party_id where uf.id=?
      order by date desc");
    $share_history->execute(array($user_id));
    $share_history = $share_history->fetchAll(PDO::FETCH_ASSOC);

    $items = $db->prepare("SELECT name, image_url max_bid, status
      from auction_deals join dropped_items on dropped_item_id=dropped_items.id join items on dropped_items.item_id=items.id
      where winner_id=? order by date desc");
    $items->execute(array($user_id));
    $items = $items->fetchAll(PDO::FETCH_ASSOC);

    return array(
      'username' => $result['name'],
      'dkp' => $result['dkp'],
      'party_name' => $party_name,
      'teammates' => $teammates,
      'events' => $events,
      'share_history' => $share_history,
      'items' => $items,
    );

  }
  public static function punishUser($user_id, $amount) //обнулить дкп юзеру и раздать всем поровну
  {
    $db = Db::getConnection();
    $available_dkp = $db->prepare("SELECT dkp from users where id=?");
    $available_dkp->execute(array($user_id));
    $can_punish = $available_dkp->fetch(PDO::FETCH_NUM)[0]>=$amount;

    if ($can_punish) {
      $num_users = $db->prepare("SELECT count(*) from users where clan_role!='inactive'");
      $num_users->execute();
      $num_users = $num_users->fetch(PDO::FETCH_NUM)[0]-1; //делим очки на всех кроме наказуемого

      $db->prepare("UPDATE users set dkp=dkp+$amount/$num_users where clan_role!='inactive' and id!=?;
        UPDATE users set dkp=dkp-$amount where id=?")->execute(array($user_id, $user_id));
    }
    return $can_punish;
  }
  public static function deleteUser($user_id)
  {
    $db = Db::getConnection();
    $available_dkp = $db->prepare("SELECT dkp from users where id=?");
    $available_dkp->execute(array($user_id));
    $available_dkp =  $available_dkp->fetch(PDO::FETCH_NUM)[0];

    $num_users = $db->prepare("SELECT count(*) from users where clan_role!='inactive'");
    $num_users->execute();
    $num_users = $num_users->fetch(PDO::FETCH_NUM)[0]-1;

    $db->prepare("UPDATE users set dkp=dkp+$available_dkp/$num_users where clan_role!='inactive' and id!=?;
      DELETE FROM users where id=?")->execute(array($user_id, $user_id));
  }
  /*админка по ивентам*/
  public static function arangeEvent($type, $description, $boss_name)
  {
    $db = Db::getConnection();
    $db->prepare("INSERT INTO events SET type=?, description=?, boss_name=?")->execute(array($type, $description, $boss_name));
    $event_id = $db->prepare("SELECT MAX(id) from events");
    $event_id->execute();
    return $event_id->fetch(PDO::FETCH_NUM)[0];
  }
  public static function changeEventStatus($event_id, $status, $reward_params=array('k'=> 1, 'b'=>0))
  {
    $db = Db::getConnection();
    $flag = True;
    if ($status=='finished'){
      $already_finished = $db->prepare("SELECT status from events where id=?");
      $already_finished->execute(array($event_id));
      $already_finished = $already_finished->fetch(PDO::FETCH_NUM)[0]=='finished';

      if (!$already_finished) {
        $total_dkp = $db->prepare("SELECT sum(dkp_value) FROM `dropped_items` join items on item_id=items.id where event_id=?");
        $total_dkp->execute(array($event_id));
        $total_dkp = $total_dkp->fetch(PDO::FETCH_NUM)[0];
        $total_dkp = $total_dkp==null? 0 : $total_dkp;

        $num_users =  $db->prepare("SELECT count(*) from participated_users where event_id=?");
        $num_users->execute(array($event_id));
        $num_users = $num_users->fetch(PDO::FETCH_NUM)[0];

        $dkp_reward = $reward_params['k']*$total_dkp/$num_users+$reward_params['b'];
        $db->prepare("UPDATE participated_users set dkp_recieved=$dkp_reward where event_id=?")->execute(array($event_id));


        $user_ids = $db->prepare("SELECT user_id from participated_users where event_id=?");
        $user_ids->execute(array($event_id));
        $user_ids = $user_ids->fetchAll(PDO::FETCH_NUM);
        for ($i=0; $i <count($user_ids) ; $i++) {
          $user_ids[$i] =  $user_ids[$i][0];
        }
        $string = "UPDATE users set dkp=dkp+$dkp_reward";
        echo $string;
        $string = $string . " WHERE id in (" .implode(',', array_map('intval', $user_ids)) . ')';
        $db->prepare($string)->execute();
      }
      else {
        $flag = False;
      }

    }
    $db->prepare("UPDATE events set status=? where id=?")->execute(array($status, $event_id));
    return $flag;
  }
  public static function changeEventInfo($event_id, $type, $description, $boss_name)
  {
    $db = Db::getConnection();
    $db->prepare("UPDATE events SET type=?, description=?, boss_name=? where id=?")->execute(array($type, $description, $boss_name, $event_id));
  }
  public static function getEventInfo($event_id)
  {
    $db = Db::getConnection();
    $event_info = $db->prepare("SELECT * from events where id=?");
    $event_info->execute(array($event_id));
    $event_info = $event_info->fetch(PDO::FETCH_ASSOC);

    $participated_users = $db->prepare("SELECT users.name FROM participated_users join users on user_id=users.id where event_id=?");
    $participated_users->execute(array($event_id));
    $participated_users = $participated_users->fetchAll(PDO::FETCH_ASSOC);

    $dropped_items = $db->prepare("SELECT name, image_url from dropped_items join items on item_id=items.id where event_id=?");
    $dropped_items->execute(array($event_id));
    $dropped_items=$dropped_items->fetchAll(PDO::FETCH_ASSOC);

    return array(
      'event_info=' => $event_info,
      'participated_users' => $participated_users,
      'dropped_items' => $dropped_items
    );
  }
  public static function addDroppedItems($event_id, $items_array)
  {
    $db = Db::getConnection();
    foreach ($items_array as $item_id) {
      $db->prepare("INSERT INTO dropped_items set event_id=? item_id=?")->execute(array($event_id, $item_id));
    }
  }
}
