<?php

ini_set("display_errors", 1);
error_reporting(E_ERROR | E_WARNING);

header('Content-Type: application/javascript; charset=UTF-8'); // Správný MIME typ + UTF-8

require_once "../config.php"; // Připojení k databázi pomocí MeekroDB

// Ujistíme se, že PHP výstup používá UTF-8
mb_internal_encoding("UTF-8");
mb_http_output("UTF-8");

// Načteme všechny školy a jejich údaje
$schools = DB::query("SELECT * FROM school_data");

$schoolDataJS = [];

// Pro každou školu vytvoříme objekt s informacemi
foreach ($schools as $school) {
    // Konverze dat do UTF-8 (pro jistotu, i když MeekroDB by měl vracet správně)
    $school_entry = [];
    
    foreach ($school as $key => $value) {
        $school_entry[$key] = mb_convert_encoding($value, "UTF-8", "auto");
    }

    $schoolDataJS[] = $school_entry;
}

// Vygenerujeme JavaScript proměnnou `presetLocations`
echo "const presetLocations = " . json_encode($schoolDataJS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";";

?>
