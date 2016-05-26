<html>
<head>
  <title>
    DecomposableItems - Erzeugen decomposable...xml-Dateien"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");
include("../includes/inc_parseurlitems.php");

getConfData();

$submit   = isset($_GET['submit'])   ? "J" : "N";
$domix    = isset($_GET['domix'])    ? "J" : "N";

if (!file_exists("../outputs/parse_output/decomposable_items"))
    mkdir("../outputs/parse_output/decomposable_items");
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Decomposable-Items-Dateien</div>
  <div class="hinweis" id="hinw">
  Erzeugen der Decomposable-Items-xml-Dateien.<br><br>
  ( decomposable_items.xml und decomposable_selectitems.xml )<br><br>
  <font color=red>ACHTUNG: die SVN-Datei item_templates.xml muss aktuell sein!</font><br><br>
  HINWEIS: Aktuell kann die Angabe zum Aufsplitten der einzelnen Items nicht generiert werden.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genDecomposableItems.php" target="_self">
 <br>
 <input type="checkbox" name="domix" value="MIX" <?php echo ($domix == "J") ? " checked" : ""; ?>"> neue Items in die sortierten SVN-Decompose-Dateien einbeziehen
 <br>
 <table width="700px">
   <colgroup>
     <col style="width:200px">
     <col style="width:500px">
   </colgroup>
   <tr><td colspan=2>&nbsp;</td></tr>
<?php   
// ----------------------------------------------------------------------------
//
//                       H I L F S F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// neue Items der aktuellen SVN-Tabelle hinzufügen
// ----------------------------------------------------------------------------
function addNewDecomposeItems(&$tabSvn,$deco)
{
    global $tabDecompose;
    
    reset($tabDecompose);
    
    $cntnew = 0;
    
    while (list($key,$val) = each($tabDecompose))
    {
        if ($tabDecompose[$key]['deco'] == $deco
        &&  !isset($tabSvn[$key]))
        {
            if ($deco == "S")
                $nline = '    <decomposable_selectitem item_id="'.$key.'"'.
                           ' name="'.$tabDecompose[$key]['name'].'"/> <!-- NEW -->';
            else
                $nline = '    <decomposable item_id="'.$key.'" decomposable_name="'.
                           $tabDecompose[$key]['name'].'"/> <!-- NEW -->';
                
            $tabSvn[$key][0] = $nline;
            $cntnew++;
        }
    }
    reset($tabDecompose);
    
    logLine("Anzahl neue Items",$cntnew);
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Scannen der aktuellen item_templates.xml aus dem SVN
// ----------------------------------------------------------------------------
function scanSvnItemTemplates()
{
    global $pathsvn, $tabDecompose, $tabSort;
    
    logHead("Scanne die Datei item_templates.xml aus dem SVN");
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\static_data\\items\\item_templates.xml");
    $hdlsvn  = openInputFile($filesvn);
    
    if (!$hdlsvn)
    {
        logLine("Fehler openInputFile",$filesvn);
        return;
    }
    
    logLine("Eingabedatei",$filesvn);
    
    $cntles = 0;
    $cntitm = 0;
    
    $id = $name = $deco = "";
    
    flush();
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        $cntles++;
        
        if     (stripos($line,"<item_template ") !== false)
        {
            // nur wenn auch Folgezeilen vorhanden sind
            if (stripos($line,"/>") === false)
            {
                $id   = getKeyValue("id",$line);
                $name = getKeyValue("name",$line);
                $deco = "";
            }
            else
                $id = $name = $deco = "";
        }
        elseif (stripos($line,"<decompose/>") !== false)
            $deco = "J";
        elseif (stripos($line,"<decompose select") !== false)
            $deco = "S";
        elseif (stripos($line,"</item_template>") !== false)
        {
            if ($deco != "")
            {
                $tabDecompose[$id]['name'] = $name;
                $tabDecompose[$id]['deco'] = $deco;
                $cntitm++;
            }
            $id = $name = $deco = "";
        }
    }
    fclose($hdlsvn);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl Items gefunden",$cntitm);
    
    // Sortiertabelle aufbauen (nach ItemId)
    $tabSort = array_keys($tabDecompose);
    sort($tabSort);
}
// ----------------------------------------------------------------------------
// Scannen und sortieren der aktuellen SVN-Datei decomposable_items.xml
// ----------------------------------------------------------------------------
function sortSvnDecomposableItems()
{
    global $pathsvn, $tabDecompose, $domix;
    global $cERRSELECT, $cTEMPLATE, $cDUPLICATE;
    
    logHead("Scanne und sortiere die SVN-Datei: decomposable_items.xml");
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\decomposable_items\\decomposable_items.xml");
    $hdlsvn  = openInputFile($filesvn);
    
    if (!$hdlsvn)
    {
        logLine("Fehler openInputFile",$filesvn);
        return;
    }
    
    logSubHead("Scanne die SVN-Datei ".basename($filesvn));
    logLine("Eingabedatei",$filesvn);
    
    $tabSvn = array();
    $tabWait= array();
    $cntles = 0;
    $cntitm = 0;
    $cntdup = 0;
    $id     = "";
    $ind    = 0;
    $wind   = 0;
    $wait   = false;
    
    flush();
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        $cntles++;
        
        if (stripos($line,"<decomposable ") !== false)
        {
            $id  = getkeyValue("item_id",$line);
            $ind = 0;
            
            // in der Item-Templates als SELECT markiert?
            if (isset($tabDecompose[$id]))
            {
                if ($tabDecompose[$id]['deco'] == "S")
                    logLine($cERRSELECT,$id." als SELECT markiert, aber in dieser Datei definiert!");
            }
            else
                logLine($cTEMPLATE,$id." = nicht in der item_templates.xml");
                
            if (isset($tabSvn[$id]))
            {
                $ind = count($tabSvn[$id]);
                $cntdup++;
                
                logLine($cDUPLICATE,"Item = ".$id." = Mehrfach vorhanden");
            }
            else
                $cntitm++;
        }
        
        if ($id != "")
        {
            // sind noch Zeilen in der Warteschlange?
            if ($wait)
            {
                $dowait = count($tabWait);
                
                for ($w=0;$w<$dowait;$w++)
                {
                    $tabSvn[$id][$ind] = $tabWait[$w];
                    $ind++;
                }
                
                $wait    = false;
                $wind    = 0;
                $tabWait = array();
            }
            $tabSvn[$id][$ind] = $line;
            $ind++;
        }
        else
        {
            if (stripos($line,"<?xml ")             === false
            &&  stripos($line,"decomposable_items") === false)  // START und ENDE
            {
                $tabWait[$wind] = $line;
                $wind++;
                
                $wait = true;
            }
        }
        
        if (stripos($line,"</decomposable>") !== false)
            $id = "";
    }
    fclose($hdlsvn);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl Items gefunden",$cntitm);
    logLine("Anzahl doppelte Items",$cntdup);
    
    if ($domix == "J")
    {
        logSubHead("Pr&uuml;fen/Erg&auml;nzen neuer Items");
        
        addNewDecomposeItems($tabSvn,"J");
    }
    
    logSubHead("Ausgabe der sortierten Items nach ID");
    
    $tabKeys = array_keys($tabSvn);
    sort($tabKeys);
    $domax   = count($tabKeys);
    
    $fileout = "../outputs/parse_output/decomposable_items/svn_".basename($filesvn);
    $hdlout  = openOutputFile($fileout);
    $cntout  = 0;
    $cntitm  = 0;
    
    logLine("Ausgabedatei",$fileout);
    
    // Vorspann  ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,'<decomposable_items>'."\n");
    $cntout += 2;
    
    flush();
    
    for ($s=0;$s<$domax;$s++)
    {
        $id      = $tabKeys[$s];
        $dolines = count($tabSvn[$id]);
        $cntitm++;
        
        for ($l=0;$l<$dolines;$l++)
        {   
            fwrite($hdlout,$tabSvn[$id][$l]."\n");
            $cntout++;
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,'</decomposable_items>');
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Anzahl Zeilen ausgegeben",$cntles);
    logLine("Anzahl Items ausgegeben",$cntitm);
}
// ----------------------------------------------------------------------------
// Scannen und sortieren der aktuellen SVN-Datei decomposable_selectitems.xml
// ----------------------------------------------------------------------------
function sortSvnDecomposableSelectItems()
{
    global $pathsvn, $tabDecompose, $domix;
    global $cERRSELECT, $cTEMPLATE, $cDUPLICATE;
    
    logHead("Scanne und sortiere die SVN-Datei: decomposable_selectitems.xml");
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\decomposable_items\\decomposable_selectitems.xml");
    $hdlsvn  = openInputFile($filesvn);
    
    if (!$hdlsvn)
    {
        logLine("Fehler openInputFile",$filesvn);
        return;
    }
    
    logSubHead("Scanne die SVN-Datei ".basename($filesvn));
    logLine("Eingabedatei",$filesvn);
    
    $tabSvn = array();
    $tabWait= array();
    $cntles = 0;
    $cntitm = 0;
    $cntdup = 0;
    $id     = "";
    $ind    = 0;
    $wind   = 0;
    $wait   = false;
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        $cntles++;
        
        if (stripos($line,"<decomposable_selectitem ") !== false)
        {
            $id  = getkeyValue("item_id",$line);
            $ind = 0;
            
            // wenn das Attribut Name noch nicht vorhanden ist, dann wird es 
            // hier ergänzt!
            if (stripos($line,' name="') === false)
            {
                if (isset($tabDecompose[$id]))
                {
                    $ntxt = ' name="'.$tabDecompose[$id]['name'].'"';
                    $line = str_replace(">",$ntxt.">",$line);
                    
                    // sofern vorhanden, Kommentarzeile vor dem Item entfernen
                    if ($wait)
                    {
                        if (count($tabWait) == 1  && $wind == 1
                        &&  stripos($tabWait[0],"<!-- ") !== false)
                        {
                            $tabWait = array();
                            $wait    = false;
                            $wind    = 0;
                        }
                    }
                }
            }
            // in der Item-Templates als SELECT markiert?
            if (isset($tabDecompose[$id]))
            {
                if ($tabDecompose[$id]['deco'] != "S")
                    logLine($cERRSELECT,$id." nicht als SELECT markiert, aber in dieser Datei definiert!");
            }
            else
                logLine($cTEMPLATE,$id." = nicht in der item_templates.xml");
                        
            if (isset($tabSvn[$id])) 
            {
                $ind = count($tabSvn[$id]);
                $cntdup++;
                
                logLine($cDUPLICATE,"Item = ".$id." mehrfach vorhanden ( evtl. andere Klasse? )");
            }
            else
                $cntitm++;
        }
        
        if ($id != "")
        {
            // sind noch Zeilen in der Warteschlange?
            if ($wait)
            {
                $dowait = count($tabWait);
                
                for ($w=0;$w<$dowait;$w++)
                {
                    $tabSvn[$id][$ind] = $tabWait[$w];
                    $ind++;
                }
                
                $wait    = false;
                $wind    = 0;
                $tabWait = array();
            }
            $tabSvn[$id][$ind] = $line;
            $ind++;
        }
        else
        {
            if (stripos($line,"<?xml ")                   === false
            &&  stripos($line,"decomposable_selectitems") === false)  // START und ENDE
            {
                $tabWait[$wind] = $line;
                $wind++;
                
                $wait = true;
            }
        }
        
        if (stripos($line,"</decomposable_selectitem>") !== false)
            $id = "";
    }
    fclose($hdlsvn);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl Items gefunden",$cntitm);  
    logLine("Anzahl doppelte Items",$cntdup);   
    
    if ($domix == "J")
    {
        logSubHead("Pr&uuml;fen/Erg&auml;nzen neuer Items");
        
        addNewDecomposeItems($tabSvn,"S");
    }
    
    logSubHead("Ausgabe der sortierten Items nach ID");
    $tabKeys = array_keys($tabSvn);
    sort($tabKeys);
    $domax   = count($tabKeys);
    
    $fileout = "../outputs/parse_output/decomposable_items/svn_".basename($filesvn);
    $hdlout  = openOutputFile($fileout);
    $cntout  = 0;
    $cntitm  = 0;
    
    logLine("Ausgabedatei",$fileout);
    
    // Vorspann  ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,'<decomposable_selectitems xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'."\n");
    $cntout += 2;
    
    flush();
    
    for ($s=0;$s<$domax;$s++)
    {
        $id      = $tabKeys[$s];
        $dolines = count($tabSvn[$id]);
        $cntitm++;
        
        for ($l=0;$l<$dolines;$l++)
        {   
            fwrite($hdlout,$tabSvn[$id][$l]."\n");
            $cntout++;
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,'</decomposable_selectitems>');
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Anzahl Zeilen ausgegeben",$cntles);
    logLine("Anzahl Items ausgegeben",$cntitm);
}
// ----------------------------------------------------------------------------
// DecomposableItems-Datei ausgeben
// ----------------------------------------------------------------------------
function generDecomposableItemsFile()
{
    global $tabDecompose, $tabSort;
    
    logHead("Generierung der Datei: decomposable_items.xml");
    
    $fileout = "../outputs/parse_output/decomposable_items/decomposable_items.xml"; 
    $hdlout  = openOutputFile($fileout);
    $cntout  = 0;
    $cntitm  = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<decomposable_items>'."\n");
    $cntout += 3;    
            
    flush();
    
    $domax = count($tabSort);
    
    for ($i=0;$i<$domax;$i++)
    {   
        if ($tabDecompose[$tabSort[$i]]['deco'] == "J")
        {
            fwrite($hdlout,'    <decomposable item_id="'.$tabSort[$i].'" decomposable_name="'.
                           $tabDecompose[$tabSort[$i]]['name'].'"/>'."\n");
            $cntout++;
            $cntitm++;
        }
    }
    
    // Nachspann ausgeben
    fwrite($hdlout,"</decomposable_items>");
    $cntout++;

    fclose($hdlout);
    
    logLine("Zeilen ausgegeben  ",$cntout);
    logLine("Anzahl Decomposable Items",$cntitm);
}
// ----------------------------------------------------------------------------
// DecomposableSelectItems-Datei ausgeben
// ----------------------------------------------------------------------------
function generDecomposableSelectItemsFile()
{
    global $tabDecompose, $tabSort;
    
    logHead("Generierung der Datei: decomposable_selectitems.xml");
    
    $fileout = "../outputs/parse_output/decomposable_items/decomposable_selectitems.xml"; 
    $hdlout  = openOutputFile($fileout);
    $cntout  = 0;
    $cntitm  = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<decomposable_selectitems xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'."\n");
    $cntout += 3;    
            
    flush();
    
    $domax = count($tabSort);
    // $test  = 10;
    
    for ($i=0;$i<$domax;$i++)
    {   
        if ($tabDecompose[$tabSort[$i]]['deco'] == "S")
        {
            // lt. XSD darf der Name auch als Attribut angegeben werden
            // fwrite($hdlout,'    <!-- '.$tabDecompose[$tabSort[$i]]['name'].' -->'."\n");
            fwrite($hdlout,'    <decomposable_selectitem item_id="'.$tabSort[$i].'"'.
                           ' name="'.$tabDecompose[$tabSort[$i]]['name'].'"/>'."\n");
            $cntout += 2;
            $cntitm++;
            
            /* Vorab-Test für die Nutzung der neuen URL-Parser-Routinen
               aktuell nicht aktiv !!!!!!!!!!
            if (substr($tabSort[$i],0,3) == "188" && $test > 0)
            {
                $tabtext = explode("\n",getDecomposableLines($tabSort[$i],true,false));
                $maxtext = count($tabtext);

                for ($t=0;$t<$maxtext;$t++)
                {                
                    fwrite($hdlout,rtrim($tabtext[$t])."\n");
                    $cntout++;
                }
                $test--;
            }
            */
        }
    }
    
    // Nachspann ausgeben
    fwrite($hdlout,"</decomposable_items>");
    $cntout++;

    fclose($hdlout);
    
    logLine("Zeilen ausgegeben  ",$cntout);
    logLine("Anzahl Decomposable Items",$cntitm);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_getautonameids.php");
include("includes/auto_inc_item_infos.php");
include("includes/auto_inc_npc_infos.php");

$starttime    = microtime(true);
$tabDecompose = array();
$tabSort      = array();

// Fehlertext-Konstanten
$cERRSELECT   = "<font color=red>Check SELECT</font>";
$cDUPLICATE   = "<font color=red>Check DUPLIKAT</font>";
$cTEMPLATE    = "<font color=yellow>Check ITEM-TEMPLATE</font>";

echo '
   <tr>
     <td colspan=2>
       <center>
       <br><br>
       <input type="submit" name="submit" value="Generierung starten">
       </center>
       <br>
     </td>
   </tr>
   <tr>
     <td colspan=2>';    

logStart();

if ($submit == "J")
{   
    if ($pathdata == "")
    {
        logLine("ACHTUNG","die Pfade sind anzugeben");
    }
    else
    {
        scanSvnItemTemplates();
        
        generDecomposableItemsFile();
        generDecomposableSelectItemsFile();
        
        sortSvnDecomposableItems();
        sortSvnDecomposableSelectItems();
        
        cleanPathUtf8Files();
    }
}    
logStop($starttime,true,true);

echo '
      </td>
    </tr>
  </table>';
?>
</form>
</body>
</html>