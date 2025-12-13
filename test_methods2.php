<?php
require 'vendor/autoload.php';

use Kigkonsult\Icalcreator\Vcalendar;

$vc = new Vcalendar();
$methods = get_class_methods($vc);

// Afficher toutes les méthodes
echo "All available methods:\n\n";
foreach (array_keys($methods) as $i) {
    if (is_int($i)) continue;
    echo $methods[$i] . "\n";
}
