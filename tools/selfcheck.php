<?php
require __DIR__.'/../core/tn_autoloader.php';
$loader = new TN_Autoloader(); $loader->register();

$classes = ['TN_SessionManager','TN_Validator','TN_UserModel','TN_Logger','TN_ErrorHandler'];
echo "<pre>";
foreach ($classes as $c) {
    echo str_pad($c, 20) . ': ' . (class_exists($c) ? 'OK' : 'MISSING') . PHP_EOL;
}
echo "</pre>";
