<?php

include_once ROOT.'/models/Events.php';

class EventsController
{
  public static function actionIndex()
  {
    //print_r(Events::getEvents("registration"));
    //print_r(Events::joinEvent('roma', 3, 'test.ru'));
    //print_r(Events::displayInfo(2, 'slep0v'));
  }
}
