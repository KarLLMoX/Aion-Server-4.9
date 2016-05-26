<?php
// ----------------------------------------------------------------------------
// Script : scanitem_apb.php
// Version: 1.00, Mariella, 05/2016
// Zweck  : erzeugt zu der ItemId die DecomposableItems-Angaben durch Scannen
//          der zugehörigen Item-Page bei aionpowerbook.com
// ----------------------------------------------------------------------------
/*
include("../includes/inc_globals.php");          // allgemeine Prozeduren
include("includes/parseItemUrlAPB.php");         // APB-Parser-Funktionen
include("../phpParse/includes/auto_inc_item_infos.php");

getConfData();
*/

include("../includes/inc_parseurlitems.php");       // zentrale Funktionen

$selfname = basename(__FILE__);
$infoname = "DecomposableItem-Definitionen generieren";

if (!file_exists("../outputs/gener_output"))
    mkdir("../outputs/gener_output");
if (!file_exists("../outputs/gener_output/decomposable_items"))
    mkdir("../outputs/gener_output/decomposable_items");
    
// ----------------------------------------------------------------------------
//
//                       F  U  N  K  T  I  O  N  E  N
//
// ----------------------------------------------------------------------------
// Items von der Seite "aionpowerbook.com" scannen
// ----------------------------------------------------------------------------
function scanDecomposableItems($itmid)
{
    // Parsen aller Items von der URL (aionpowerbook.com)
    // Parameter 2 = true = Datei zum Item erzeugen!
    $text = getDecomposableLines($itmid,true);
        
    // Ergebnis anzeigen
    logHead("Ergebnis");
    logSubHead('<br><textarea rows="20" cols="96">'.$text.'</textarea><br>');
}
// ----------------------------------------------------------------------------
//
//                                 M  A  I  N
//
// ----------------------------------------------------------------------------
// Übergabe-Parameter (GET) aufbereiten
// ----------------------------------------------------------------------------
$itmid =  isset($_GET['itmid']) ? $_GET['itmid'] : "";
// ----------------------------------------------------------------------------
// globale Definitionen
// ----------------------------------------------------------------------------
/*
$tabINames = array();
$tabSelect = array();
*/
putHtmlHead("$selfname - $infoname","DecomposableItem-Definitionen generieren");

// ----------------------------------------------------------------------------
// Start der Verarbeitung
// ----------------------------------------------------------------------------
logStart();
$starttime = microtime(true);
    
if ($itmid == "")
   echo "Ohne die notwendige ItemId-Vorgabe kann nichts generiert werden";
else
{  
    /*
    makeTabINames();
    makeTabSelect();
    scandecomposableItems();
    */
    scanDecomposableItems($itmid);
}    
logSubHead("<center><br><a href='javascript:history.back()'>zur&uuml;ck</a></center>");

logStop($starttime,true);
	
putHtmlFoot();
?>