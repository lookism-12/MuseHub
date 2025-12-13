<?php
require 'vendor/autoload.php';

use Kigkonsult\Icalcreator\Vcalendar;

$vc = new Vcalendar();
$methods = get_class_methods($vc);
$relevant = array_filter($methods, function($m) {
    return stripos($m, 'product') !== false || 
           stripos($m, 'version') !== false ||
           stripos($m, 'method') !== false ||
           stripos($m, 'calscale') !== false;
});

echo "Méthodes disponibles:\n";
print_r($relevant);

// Essayer les vraies méthodes
echo "\n\nTesting methods:\n";
$vc2 = new Vcalendar();
$vc2->setMethod('PUBLISH');
echo "setMethod: OK\n";

$vc2->setVersion('2.0');
echo "setVersion: OK\n";

try {
    $vc2->setProductid('-//Test//Test//EN');
    echo "setProductid: OK\n";
} catch (\Exception $e) {
    echo "setProductid: FAILED - " . $e->getMessage() . "\n";
}

try {
    $vc2->setCalscale('GREGORIAN');
    echo "setCalscale: OK\n";
} catch (\Exception $e) {
    echo "setCalscale: FAILED - " . $e->getMessage() . "\n";
}
