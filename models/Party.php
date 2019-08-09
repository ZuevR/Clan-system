<?php

class Party
{
  public static function getPartyName($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT parties.name from parties join users on users.party_id=parties.id where users.name=?");
    $result->execute(array($username));
    return $result->fetch(PDO::FETCH_NUM)[0];
  }
  public static function getMembersInfo($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT name, clan_role, dkp FROM users WHERE party_id=(SELECT party_id FROM users WHERE name=?) ORDER BY clan_role DESC");
    $result->execute(array($username));
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }
  public static function getSharedDKPInfo($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT uf.name as sender, ut.name as reciever, amount, date FROM dkp_share_history as t
      join users uf on uf.id=from_user join users ut on ut.id=to_user
      where t.party_id=(select party_id from users where name=?)
      ORDER BY date desc");
    $result->execute(array($username));
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }
  public static function shareDKP($from_user, $to_user, $amount) //добавить проверку на располагаемое дкп и вывод ошибок
  {
    $db = Db::getConnection();
    $in_same_party=$db->prepare("SELECT count(distinct a.party_id) as c from users a where name=? or name=?");
    $in_same_party->execute(array($from_user, $to_user));
    $in_same_party=$in_same_party->fetch(PDO::FETCH_NUM)[0]==1;
    if ($in_same_party) {
      $db->prepare("UPDATE users set dkp=dkp-? where name=?")->execute(array($amount, $from_user));
      $db->prepare("UPDATE users set dkp=dkp+? where name=?")->execute(array($amount, $to_user));
      $db->prepare("INSERT INTO dkp_share_history(from_user, to_user, amount, party_id) values ((SELECT id FROM users WHERE name=?),(SELECT id FROM users WHERE name=?),?,(SELECT party_id FROM users WHERE name=?))")->execute(array($from_user, $to_user, $amount, $from_user));
    }
  }
  public static function getInvites($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("SELECT parties.id as party_id, users.name as pl, parties.name as party_name
      FROM party_invites_history
      join parties on party_invites_history.party_id=parties.id
      join users on users.party_id=parties.id
      where clan_role='pl' and is_accepted=0 and invitee_id=(SELECT id FROM users WHERE name=?)
      ORDER BY party_invites_history.id DESC");
    $result->execute(array($username));
    return $result->fetchAll(PDO::FETCH_ASSOC);
  }
  public static function acceptInvite($username, $party_id) //второй параметр id или name?
  {
    $db = Db::getConnection();
    $result = $db->prepare("UPDATE users set party_id=? where name=?;
      UPDATE party_invites_history set is_accepted=1 where invitee_id=(SELECT id FROM users WHERE name=?) and party_id=? and is_accepted=0;
      DELETE from party_invites_history where invitee_id=(SELECT id FROM users WHERE name=?) and is_accepted=0");
    $result->execute(array($party_id, $username, $username, $party_id, $username));
  }
  public static function disband($username) //нужно ли хранить записи принятых инвайтов после дизбанда?
  {
    $db = Db::getConnection();
    $result = $db->prepare("DELETE from parties where id=(select party_id from users where name=?);
      UPDATE users set clan_role='member' where name=? and clan_role='pl'")->execute(array($username, $username));
  }
  public static function createParty($username, $party_name)
  {
    $db = Db::getConnection();
    $result = $db->prepare("INSERT INTO parties set name=?;
      UPDATE users set party_id=(SELECT id from parties where name=?) where name=?;
      UPDATE users set clan_role='pl' where name=? and clan_role='member' ")->execute(array($party_name, $party_name, $username, $username,));
  }
  public static function sendInvite($username, $invitee)
  {
    $db = Db::getConnection();
    $is_sent=$db->prepare("SELECT COUNT(*) FROM party_invites_history
    where party_leader_id=(select id from users where name=?) and invitee_id=(select id from users where name=?) and is_accepted=0");
    $is_sent->execute(array($username, $invitee));
    $is_sent=$is_sent->fetch(PDO::FETCH_NUM)[0];

    $has_party=$db->prepare("SELECT IFNULL((select party_id from users where name=?) ,0)");
    $has_party->execute(array($invitee));
    $has_party=$has_party->fetch(PDO::FETCH_NUM)[0];

    if (!$is_sent && !$has_party) {
      $result = $db->prepare("INSERT INTO party_invites_history
        SET
          party_id=(SELECT party_id from users where name=?),
          party_leader_id=(select id from users where name=?),
          invitee_id=(select id from users where name=?)")->execute(array($username, $username, $invitee));
        }
        elseif ($is_sent) {
          echo "инвайт уже отправлен";
        }
        else {
          echo "уже в пати или неактивирован";
        }
    }

  public static function changeLeader($username, $new_pl)
  {
    $db = Db::getConnection();
    $result = $db->prepare("UPDATE users set clan_role='pl' where name=? and clan_role='member';
      UPDATE users set clan_role='member' where name=? and clan_role='pl' ")->execute(array($new_pl, $username));
  }

  public static function leaveParty($username)
  {
    $db = Db::getConnection();
    $result = $db->prepare("UPDATE users set party_id=NULL where name=?")->execute(array($username));
  }

}
