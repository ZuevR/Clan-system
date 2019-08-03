<?php

include_once ROOT.'/models/Profile.php';

class ProfileController
{
  public function actionDisplayInfo() {

    $dkp=Profile::getDKP('slep0v');
    $characters=Profile::getCharacters('slep0v');
    $events=Profile::getEvents('slep0v');
    include ROOT . '/views/profile/index.php';

    return true;
  }
}
