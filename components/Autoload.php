<?php

spl_autoload_register(function ($class_name) {
  $classPaths = [
    '/components/',
    '/models/'
  ];
  foreach ($classPaths as $path) {
    $path = ROOT . $path . $class_name . '.php';
    if (is_file($path)) {
      include_once $path;
    }
  }
});
