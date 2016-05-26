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
// Item-Datei für den Zugriff über ID umschlüsseln
// ----------------------------------------------------------------------------
/*
function makeTabINames()
{
    global $tabINames, $tabItemInfos;
    
    logHead("Erzeuge Tabelle mit den Item-Namen");
    
    $cntitm = 0;
    
    flush();
    
    while (list($key,$val) = each($tabItemInfos))
    {        
        $tabINames[$tabItemInfos[$key]['id']] = $tabItemInfos[$key]['name'];
        
        $cntitm++;
    }
    logLine("Anzahl Items gefunden",$cntitm);
}
// ----------------------------------------------------------------------------
// ermitteln der Select Decomposable Items
// ----------------------------------------------------------------------------
function makeTabSelect()
{
    global $pathsvn, $tabSelect;
    
    logHead("Erzeuge Tabelle mit den DecomposableSelect-Items");
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\".
                            "decomposable_items\\decomposable_selectitems.xml");
    $hdlsvn  = openInputFile($filesvn);
    
    if (!$hdlsvn)
    {
        logLine("<font color=red>Fehler openInputFile</font>",$filesvn);
        return;
    }
    $cntitm = 0;
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        
        if (stripos($line,"<decomposable_selectitem") !== false
        &&  stripos($line," item_id=")                !== false)
        {
            $itemid = getKeyValue("item_id",$line);
            $tabSelect[$itemid] = 1;
        }
    }
    fclose($hdlsvn);
    
    logLine("Anzahl gedundene Items",count($tabSelect));
}
// ----------------------------------------------------------------------------
// Name zur ItemId zurückgeben
// ----------------------------------------------------------------------------
function getItemName($item)
{
    global $tabINames;
    
    if (isset($tabINames[$item]))
        return $tabINames[$item];
    else
        return "";
}
// ----------------------------------------------------------------------------
// prüfen, ob die items über ihren Namen gruppiert werden könnten
// ----------------------------------------------------------------------------
function checkItemGroupNames(&$tabitems)
{
    $maxitems = count($tabitems);
    
    $tabitems[0]['group'] = $tabitems[0]['tdgrp'];
    $oldnames = explode(" ",getItemName($tabitems[0]['item']));
    $oldmax   = count($oldnames);
    
    for ($i=1;$i<$maxitems;$i++)
    {
        $newnames = explode(" ",getItemName($tabitems[$i]['item']));
        $newmax   = count($newnames);
        
        $domax    = ($oldmax > $newmax) ? $newmax : $oldmax;
        $txtok    = 0;
        
        for ($n=0;$n<$domax;$n++)
        {   
            if ($oldnames[$n] = $newnames[$n])
                $txtok++;
        }
        
        if ($txtok > 1)
            $tabitems[$i]['group'] = $tabitems[$i - 1]['group'];
        else
            $tabitems[$i]['group'] = $tabitems[$i]['tdgrp'];
        
        $oldnames = $newnames;
        $oldmax   = $newmax;    
    }
}
// ----------------------------------------------------------------------------
// alle Items zum vorgegebenen Item ermitteln
// ----------------------------------------------------------------------------
function makeDecomposableItems()
{
    global $itmid;
   
    // Seite bei aionpowerbook lesen/scannen
    // Tabelle enthält: race,class,level,item,tdgrp,count
    // wenn count != 1, dann kam das Item mehrfach vor
    $tabitems = getItemsFromAPB($itmid,getItemName($itmid),false);
    $maxitems = count($tabitems);
    
    checkItemGroupNames($tabitems);
    
    $fileout  = "../outputs/gener_output/decomposable_items/$itmid.xml";
    $hdlout   = openOutputFile($fileout);
    
    logHead("Scanne die Angaben zum Item: $itmid");    
    logLine("Anzahl Items gefunden",$maxitems);
    logLine("Ausgabedatei",$fileout);
        
    fwrite($hdlout,'    <decomposable item_id="'.$itmid.'" name="'.
           getItemName($itmid).'">'."\n");
    
    $doblock  = true;    
    
    for ($i=0;$i<$maxitems;$i++)
    {        
        if ($doblock)    
        {        
            $lout = '        <items';
        
            if ($tabitems[$i]['level'] != "")
            {
                $lev = $tabitems[$i]['level'];
                
                if (stripos($lev,"-") !== false)
                {
                    $xtab = explode("-",$lev);
                    $min  = trim($xtab[0]);
                    $max  = trim($xtab[1]);
                }
                else
                {
                    $min = $lev;
                    $max = $lev;
                }
                $lout .= ' minlevel="'.$min.'" maxlevel="'.$max.'"';
            }
            $lout .= '>';
            fwrite($hdlout,$lout."\n");
        }
        
        // Decomposable Item ausgeben
        $lout  = '            <item id="'.$tabitems[$i]['item'].'" name="'.
                 getItemName($tabitems[$i]['item']).'"';
        
        if ($tabitems[$i]['count'] > 1)
            $lout .= ' rnd_min="1" rnd_max="'.$tabitems[$i]['count'].'"';
            
        if ($tabitems[$i]['race'] != "")
            $lout .= ' race="'.$tabitems[$i]['race'].'"';
        
        if ($tabitems[$i]['class'] != "")    
            $lout .= ' player_class="'.$tabitems[$i]['class'].'"';
            
        $lout .= '/>';
        fwrite($hdlout,$lout."\n");
        
        // Gruppenwechsel prüfen!
        if (($i + 1) == $maxitems)
            $doblock = true;
        else
        {
            // bei anderem Level oder anderer group neuer Block
            if ($tabitems[$i + 1]['level'] != $tabitems[$i]['level']
            ||  $tabitems[$i + 1]['group'] != $tabitems[$i]['group'])
                $doblock = true;
            else
            {
                // bei gleicher Gruppe, gleicher Block
                if ($tabitems[$i + 1]['group'] == $tabitems[$i]['group'])
                    $doblock = false; 
                // bei gleichem Item und anderer Klasse, gleicher Block
                elseif ($tabitems[$i + 1]['item']  == $tabitems[$i]['item']
                    &&  $tabitems[$i + 1]['class'] != $tabitems[$i]['class'])
                    $doblock = false;
                // bei gleicher Klasse und anderem Item, gleicher Block
                elseif ($tabitems[$i + 1]['item']  != $tabitems[$i]['item']
                    &&  $tabitems[$i + 1]['class'] == $tabitems[$i]['class']
                    &&  $tabitems[$i + 1]['class'] != "")
                    $doblock = false;
                else
                    $doblock = true;
            }
        }
        
        if ($doblock)        
            fwrite($hdlout,'        </items>'."\n");
    }
    fwrite($hdlout,'    </decomposable>');
    fclose($hdlout);
    
    $text = file_get_contents($fileout);

    logHead("Ergebnis");
    logSubHead('<br><textarea rows="20" cols="99">'.$text.'</textarea><br>');
}
// ----------------------------------------------------------------------------
// alle Items zum vorgegebenen Select-Item ermitteln
// ----------------------------------------------------------------------------
function makeDecomposableSelectItems()
{
    global $itmid;
   
    // Seite bei aionpowerbook lesen/scannen
    // Tabelle enthält: race,class,level,item,tdgrp,count
    // wenn count != 1, dann kam das Item mehrfach vor
    $tabitems = getItemsFromAPB($itmid,getItemName($itmid),false);
    $maxitems = count($tabitems);
    
    checkItemGroupNames($tabitems);
    
    $fileout  = "../outputs/gener_output/decomposable_items/$itmid.xml";
    $hdlout   = openOutputFile($fileout);
    
    logHead("Scanne die Angaben zum Item: $itmid");    
    logLine("Anzahl Items gefunden",$maxitems);
    logLine("Ausgabedatei",$fileout);
        
    fwrite($hdlout,'    <decomposable_selectitem item_id="'.$itmid.'" name="'.
           getItemName($itmid).'">'."\n");
    
    $oclass = ""; 
    $doende = false;    
    
    for ($i=0;$i<$maxitems;$i++)
    {        
        if ($tabitems[$i]['class'] != $oclass)    
        {        
            $oclass = $tabitems[$i]['class'];
            $lout = '        <items player_class="'.$oclass.'">';
            fwrite($hdlout,$lout."\n");
        }
        
        // Decomposable Item ausgeben
        $lout  = '            <item id="'.$tabitems[$i]['item'].'" count="'.$tabitems[$i]['count'].'"';
        
        if ($tabitems[$i]['race'] != "")
            $lout .= ' race="'.$tabitems[$i]['race'].'"';
                    
        $lout .= '/>  <!-- '.getItemName($tabitems[$i]['item']).' -->';
        fwrite($hdlout,$lout."\n");
        
        // Gruppenwechsel prüfen!
        if (($i + 1) == $maxitems)
            $doende = true;
        elseif ($tabitems[$i + 1]['class'] != $tabitems[$i]['class'])
            $doende = true;
        else
            $doende = false;
        
        if ($doende)        
            fwrite($hdlout,'        </items>'."\n");
    }
    fwrite($hdlout,'    </decomposable_selectitem>');
    fclose($hdlout);
    
    $text = file_get_contents($fileout);

    logHead("Ergebnis");
    logSubHead('<br><textarea rows="20" cols="99">'.$text.'</textarea><br>');
}
// ----------------------------------------------------------------------------
// erzeugen der Ausgabe
// ----------------------------------------------------------------------------
function scanDecomposableItems()
{
    global $itmid, $tabSelect;
   
    if (isset($tabSelect[$itmid]))
        makeDecomposableSelectItems();
    else
        makeDecomposableItems();
}
*/
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