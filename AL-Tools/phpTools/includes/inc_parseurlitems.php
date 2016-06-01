<?php
// ----------------------------------------------------------------------------
// Script : inc_parseurlitems.php
// Version: 1.00, Mariella, 05/2016
// Zweck  : zentrale Funktion zur Erzeugung eines kompletten Decomposable-
//          XML-Blocks. Die Funktionen beinhalten bereits grunhdlegende
//          Steuerungsfunktionen für den mehrfachen Aufruf.
//
// Aufruf : 1. Dieses INclude muss eingebunden werden
//          2. für jedes Item wird die ParseFunktion aufgerufen:
//
//             getDecomposableLines($itemid,$dofile,$doprot)
//
//             $itemid = zu scannendes Item von aionpowerbook.com
//             $dofile = Ausgabedatei für das Item erzeugen
//                       das Ergebnis wird grundsätzlich erst einmal in eine 
//                       Datei ausgegeben. Wenn $dofile=true ist, dann bleibt
//                       diese Datei bestehen, sonst wird sie gelöscht.
//             $doprot = Scanner-Protokoll ausgeben
// ----------------------------------------------------------------------------
include_once("../includes/inc_globals.php");          // allgemeine Prozeduren
include_once("../phpParse/includes/auto_inc_item_infos.php");

getConfData();

// globale definitionen
$tabINames = array();
$tabSelect = array();
    
$doINames  = true;
$doSelect  = true;
// ----------------------------------------------------------------------------
//
//                 U R L - P A R S E - F U N K T I O N E N 
//
// ----------------------------------------------------------------------------
// URL aufbereiten und zurückgeben
// ----------------------------------------------------------------------------
function getUrlName($item)
{
    return "http://aionpowerbook.com/powerbook/Item:".$item;
}
// ----------------------------------------------------------------------------
// die Page von AionPowerBook lokal speichern (wird nur im Test benötigt)
// ----------------------------------------------------------------------------
function writeAPBPageFile($item,&$htmltext)
{
    if (!file_exists("gener_temp"))
        mkdir("gener_temp");
            
    $filelog = "gener_temp/log_$item.html";
    
    file_put_contents($filelog,$htmltext);
}
// ----------------------------------------------------------------------------
// Tabellen-Zeile: Inhalt der Zelle ermitteln!
// ----------------------------------------------------------------------------
function getTdValue($tdtext)
{    
    $text = substr($tdtext,stripos($tdtext,">") + 1);
        
    return $text;
}
// ----------------------------------------------------------------------------
// Rassenname gem. EMU zurückgeben
// ----------------------------------------------------------------------------
function getRaceName($race)
{
    if (stripos($race,'class="pc_') !== false)
    {
        $race = getKeyValue("title",$race);
        
        if (stripos($race,"Elyos")    !== false) return "ELYOS";
        if (stripos($race,"ASMODIAN") !== false) return "ASMODIANS";
        
        return "";
            
    }
    return "";
}
// ----------------------------------------------------------------------------
// Klassenname gem. EMU zurückgeben
// ----------------------------------------------------------------------------
function getClassName($class)
{    
    // Klassenangabe vorhanden?
    if (stripos($class,'class="pc_') !== false)
    {
        $class = getKeyValue("title",$class);
           
        switch(strtoupper($class))
        {
            case "SPIRITMASTER": return "SPIRIT_MASTER";
            case "AETHERTECH"  : return "RIDER";
            default            : return strtoupper($class);
        }
    }
    return "";
}
// ----------------------------------------------------------------------------
// Klassenname gem. EMU zurückgeben (es können mehrere vorhanden sein)
// ----------------------------------------------------------------------------
function getAllClassNames($class,$name)
{
    $ret    = array();
    $ret[0] = "";
    $ind    = 0;
    
    $ctab = explode("</span>",$class);
    $cmax = count($ctab);
    
    for ($c=0;$c<$cmax;$c++)
    {
        $cemu = getClassName($ctab[$c]);
        
        if ($cemu != "")
        {
            $ret[$ind] = $cemu;
            $ind++;
        }
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// nicht immer sind Klassen angegeben, daher wird hier je Item versucht, eine
// Klasse aus dem Namen abzuleiten
// ----------------------------------------------------------------------------  
function getAllItemClasses($name)
{
    $ret    = array();
    $ret[0] = "";
    
    if     (stripos($name," PLATE ") !== false)
    {
        $ret[0] = "GLADIATOR";
        $ret[1] = "TEMPLAR";
    }
    elseif (stripos($name," CHAIN ") !== false)
    {
        if (stripos($name," MAGIC ") !== false)
            $ret[0] = "RIDER";
        else
        {
            $ret[0] = "CHANTER";
            $ret[1] = "CLERIC";
            $ret[2] = "RIDER";
        }
    }
    elseif (stripos($name," LEATHER ") !== false)
    {
        if (stripos($name," MAGIC ") !== false)
            $ret[0] = "GUNNER";
        else
        {
            $ret[0] = "ASSASSIN";
            $ret[1] = "RANGER";
        }
    }
    elseif (stripos($name," CLOTH ") !== false)
    {
        $ret[0] = "BARD";
        $ret[1] = "SORCERER";
        $ret[2] = "SPIRIT_MASTER";
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// versucht von der URL "http://aionpowerbook.com/powerbook/Item:<itemid>" die 
// dort vorgegebene Aufsplittung zu scannen.
// ----------------------------------------------------------------------------
function getItemsFromAPB($item,$name,$logit=false)
{
    $ret      = array();
    $ind      = 0;
    
    // Tabelle vorinitialisieren (als Fehler, wird dann bei Erfolg überschrieben)
    $ret[0]['id']  = "ERROR";
   
    $savefile = "gener_temp/log_$item.html";

    if (file_exists($savefile))
        $htmltext = file_get_contents($savefile);
    else
    {
        $filename = getUrlName($item);
        $htmltext = file_get_contents($filename);
    }
    $itemtext = "";
    
    if ($logit)
        writeAPBPageFile($item,$htmltext);
    
    $itemtext = substr($htmltext,stripos($htmltext,'<table class="edit_table"'));
    $itemtext = substr($itemtext,0,stripos($itemtext,"</table"));
    $tabitems = explode("<tr>",$itemtext);
    $maxitems = count($tabitems);
    
    // Jede Tabellenzeile enthält nun 1 Item-Zeile (td) von aionpowerbook
    for ($l=1;$l<$maxitems;$l++)
    {        
        $tabtemp = explode("</td>",$tabitems[$l]);
        $maxtemp = count($tabtemp);
        
        if ($maxtemp < 4)
        {
            echo "FEHLER ??? Anzahl Zellen &lt; 4";
            return $ret;
        }
        
        // Rasse
        $race = getTdValue($tabtemp[0]);
        $race = getRaceName($race);
        
        // Klasse
        $class = getTdValue($tabtemp[1]);
        $class = getAllClassNames($class,$name);
        $cmax  = count($class);
            
        // Level
        $level = getTdValue($tabtemp[2]);
        
        // Items
        $itext = getTdValue($tabtemp[3]);
        $zvon  = $ind;
        $zbis  = $ind;
        
        if (stripos($itext,"tooltipId=") !== false)
        {
            while (stripos($itext,"tooltipId=") !== false)
            {
                $idpos  = stripos($itext,"tooltipId=") + 11;
                $itemid = substr($itext,$idpos,9);
                $itext  = substr($itext,$idpos + 10);
                
                $found  = false;
                
                // prüfen, ob identisches Item schon existiert                
                for ($x=0;$x<$ind;$x++)
                {
                    if ($ret[$x]['race']  == $race
                    &&  $ret[$x]['class'] == $class[0]
                    &&  $ret[$x]['level'] == $level
                    &&  $ret[$x]['item']  == $itemid
                    &&  $ret[$x]['tdgrp'] == $l)
                    {
                        // wenn ja, dann Anzahl erhöhen
                        $found = true;
                        $ret[$x]['count']++;
                    }
                }
                
                // neues Item in die Tabelle übernehmen
                if (!$found)
                {
                    $ret[$ind]['race']  = $race;
                    $ret[$ind]['class'] = $class[0];
                    $ret[$ind]['level'] = $level;
                    $ret[$ind]['item']  = $itemid;
                    $ret[$ind]['tdgrp'] = $l;
                    $ret[$ind]['count'] = 1;
                    
                    $ind++;
                }
            } 
            // das vervielfältigen der Items bei mehreren Klassen erfolgt erst
            // hier, da sonst die Klassen-Items auseinander gerissen werden
            if ($cmax > 1)  
            {
                $zbis = $ind;
                
                // für alle weiteren Klassen
                for ($x=1;$x<$cmax;$x++)
                {
                    // für alle Items dieser Zeile (td)
                    for ($y=$zvon;$y<$zbis;$y++)
                    {
                        $ret[$ind] = $ret[$y];
                        $ret[$ind]['class'] = $class[$x];
                        $ind++;
                    }
                }
            }            
        }
    }  
    // wenn keine Klasse vorgegeben wurde, dann evtl. eine aus dem Namen 
    // des Items ableiten!
    $class = getAllItemClasses($name);    
    
    if ($class[0] != "")
    {
        $cmax = count($class);
        $last = count($ret);
        
        // ermittelte Klassen einfügen
        for ($c=0;$c<$cmax;$c++)
        {
            for ($i=0;$i<$last;$i++)
            {
                // bei den Haupt-Items nur die Klasse einsetzen
                if ($c == 0)
                    $ret[$i]['class'] = $class[$c];
                else
                {
                    // Items kopieren und Klasse einsetzen
                    $ret[$ind]          = $ret[$i];
                    $ret[$ind]['class'] = $class[$c];
                    $ind++;
                }
            }
        }
    }
    return $ret;
}
// ----------------------------------------------------------------------------
//
//                     H I L F S - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Vorbereitungen für die Verarbeitung (Initialisierung)
// ----------------------------------------------------------------------------
function initParseItemsAPB()
{
    global $doINames, $doSelect;
    
    if ($doINames) makeTabINames();
    if ($doSelect) makeTabSelect();
}
// ----------------------------------------------------------------------------
// Ausgabedateiname aufbereiten und zurückgeben
// ----------------------------------------------------------------------------
function getFileoutName($itmid)
{
    return "../outputs/gener_output/decomposable_items/$itmid.xml";
}
// ----------------------------------------------------------------------------
// Item-Datei für den Zugriff über ID umschlüsseln
// ----------------------------------------------------------------------------
function makeTabINames()
{
    global $tabINames, $doINames, $tabItemInfos;
    
    logHead("Erzeuge Tabelle mit den Item-Namen");
    
    $cntitm = 0;
    
    flush();
    
    while (list($key,$val) = each($tabItemInfos))
    {        
        $tabINames[$tabItemInfos[$key]['id']] = $tabItemInfos[$key]['name'];
        
        $cntitm++;
    }
    logLine("Anzahl Items gefunden",$cntitm);
    
    $doINames = false;
}
// ----------------------------------------------------------------------------
// ermitteln der Select Decomposable Items
// ----------------------------------------------------------------------------
function makeTabSelect()
{
    global $pathsvn, $doSelect, $tabSelect;
    
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
    
    logLine("Anzahl gefundene Items",count($tabSelect));
    
    $doSelect = false;
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
//
//                       S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// alle Items zum vorgegebenen Item ermitteln
// ----------------------------------------------------------------------------
function makeDecomposableItems($itmid,$dofile,$doprot)
{   
    // Seite bei aionpowerbook lesen/scannen
    // Tabelle enthält: race,class,level,item,tdgrp,count
    // wenn count != 1, dann kam das Item mehrfach vor
    $tabitems = getItemsFromAPB($itmid,getItemName($itmid),false);
    $maxitems = count($tabitems);
    
    checkItemGroupNames($tabitems);
    
    $fileout  = getFileoutName($itmid);
    $hdlout   = openOutputFile($fileout);
    
    if ($doprot)
    {
        logHead("Scanne die Angaben zum Item: $itmid");    
        logLine("Anzahl Items gefunden",$maxitems);
        logLine("Ausgabedatei",$fileout);
    }  
    
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
                $max   = ($max > 65) ? 65 : $max;
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

    if (!$dofile) unlink($fileout);
    
    return $text;
}
// ----------------------------------------------------------------------------
// alle Items zum vorgegebenen Select-Item ermitteln
// ----------------------------------------------------------------------------
function makeDecomposableSelectItems($itmid,$dofile,$doprot)
{
    // Seite bei aionpowerbook lesen/scannen
    // Tabelle enthält: race,class,level,item,tdgrp,count
    // wenn count != 1, dann kam das Item mehrfach vor
    $tabitems = getItemsFromAPB($itmid,getItemName($itmid),false);
    $maxitems = count($tabitems);
    
    checkItemGroupNames($tabitems);
    
    $fileout  = getFileoutName($itmid);
    $hdlout   = openOutputFile($fileout);
    
    if ($doprot)
    {
        logHead("Scanne die Angaben zum Item: $itmid");    
        logLine("Anzahl Items gefunden",$maxitems);
        logLine("Ausgabedatei",$fileout);
    }
    
    fwrite($hdlout,'    <decomposable_selectitem item_id="'.$itmid.'" name="'.
           getItemName($itmid).'">'."\n");
    
    $oclass = "?"; 
    $doende = false;    
    
    for ($i=0;$i<$maxitems;$i++)
    {        
        if ($tabitems[$i]['class'] != $oclass)    
        {        
            $oclass = $tabitems[$i]['class'];
            
            if ($tabitems[$i]['class'] != "")
            {
                $lout = '        <items player_class="'.$oclass.'">';
            }
            else
                $lout = '        <items>'; 
        
            fwrite($hdlout,$lout."\n");
        }
        
        // Decomposable Item ausgeben
        $lout  = '            <item id="'.$tabitems[$i]['item'].'" '.
                 'name="'.getItemName($tabitems[$i]['item']).'" '.
                 'count="'.$tabitems[$i]['count'].'"';
        
        if ($tabitems[$i]['race'] != "")
            $lout .= ' race="'.$tabitems[$i]['race'].'"';
                     
        fwrite($hdlout,$lout.'/>'."\n");
        
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

    if (!$dofile) unlink($fileout);
    
    return $text;
}
// ----------------------------------------------------------------------------
//
//                      M A I N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Erzeugen der Decomposable-Items Ausgabe
// ----------------------------------------------------------------------------
function getDecomposableLines($itmid,$dofile=false,$doprot=false)
{
    global $doINames, $doSelect, $tabSelect;
    
    if ($doINames || $doSelect)
        initParseItemsAPB();
        
    if (isset($tabSelect[$itmid]))
        return makeDecomposableSelectItems($itmid,$dofile,$doprot);
    else
        return makeDecomposableItems($itmid,$dofile,$doprot);
}
?>