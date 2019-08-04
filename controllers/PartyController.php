<?php

include_once ROOT.'/models/Party.php';

class PartyController
{
  public static function actionIndex()
  {
    print_r(Party::getMembersInfo('slep0v'));
    echo "<br>";
    print_r(Party::getSharedDKPInfo('slep0v'));
    //Party::shareDKP('slep0v', 'roma', 0.5);
    echo "<br>";
    print_r(Party::getInvites('roma'));
    echo "<br>";
    //Party::disband('consul');
    //Party::createParty('consul', 'dauni');
    //Party::sendInvite('consul', 'roma');
    //Party::acceptInvite('roma', 4);
    Party::changeLeader('consul', 'roma');
    Party::leaveParty('consul');

  }
}
