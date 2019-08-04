<?php

include_once ROOT.'/models/Profile.php';

class ProfileController
{
  public function actionDisplayInfo() {

    $dkp=Profile::getDKP('slep0v');
    $characters=Profile::getCharacters('slep0v');
    $events=Profile::getEvents('slep0v');
    //Profile::addCharacter('slep0v', 'консул', 'archer', 76);
    //$print_r(Profile::changeCharacter('slep0v', 'консул', 'mage', 25));
    print_r(Profile::getItems('slep0v'));
    echo "<br>";
    include ROOT . '/views/profile/index.php';

    return true;
  }
}
