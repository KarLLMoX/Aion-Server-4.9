<html>
<head>
  <title>
    Instance - Erzeugen instance....xml-Dateien"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

$submit   = isset($_GET['submit'])   ? "J"               : "N";

if (!file_exists("../outputs/parse_output/instance_bonusattr"))
    mkdir("../outputs/parse_output/instance_bonusattr");
if (!file_exists("../outputs/parse_output/instance_cooltimes"))
    mkdir("../outputs/parse_output/instance_cooltimes");
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Instance-Dateien</div>
  <div class="hinweis" id="hinw">
  Erzeugen der Instance-xml-Dateien.<br><br>
  ( instance_bonusattr.xml, instance_cooltimes.xml und <font color=gray>instance_exit.xml</font> )
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genInstance.php" target="_self">
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
// interne Umschlüsselung der WorldMap-Tabelle nach Name => ID
// ----------------------------------------------------------------------------
function makeWorldNameTab()
{
    global $tabWorlds, $tabWorldmaps;
    
    while (list($key,$val) = each($tabWorldmaps))
    {
        $tkey = strtoupper($tabWorldmaps[$key]['offiname']);
        
        $tabWorlds[$tkey] = $key;
    }
}
// ----------------------------------------------------------------------------
// BonusAttr-Name zurückgeben (Besonderheiten abfangen, sonst normal)
// ----------------------------------------------------------------------------
function getInstBonusAttrName($name)
{
    $key = strtoupper($name);
    
    switch ($key)
    {
        case "HEALSKILLBOOST": return "HEAL_SKILL_BOOST"; 
        case "ARALL"         : return "ABNORMAL_RESISTANCE_ALL";
        default              : return getBonusAttrName($key);
    }
    
    return "";
}
// ----------------------------------------------------------------------------
// WorldId zur Instance ermitteln
// ----------------------------------------------------------------------------
function getInstanceWorldId($name)
{
    global $tabWorlds;
    
    $key = strtoupper($name);
    
    if (isset($tabWorlds[$key]))
        return $tabWorlds[$key];
    else
        return "";
}
// ----------------------------------------------------------------------------
// WorldName zur Instance zurückgeben
// ----------------------------------------------------------------------------
function getInstanceWorldName($name)
{
    global $tabNames;
    
    $key = strtoupper($name);
    
    if (isset($tabNames[$key]))
        return $tabNames[$key]['body'];
    else
        return "";
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// PS-Strings in interne Tabelle einlesen
// ----------------------------------------------------------------------------
function scanClientStrings()
{
    global $pathstring,$tabNames;
        
    $tabString = array( "client_strings_level.xml"
                      );
    $maxString = count($tabString);

    for ($s=0;$s<$maxString;$s++)
    {    
        $fileext = formFileName($pathstring."\\".$tabString[$s]);
        
        logSubHead("Scanne PS-String-Datei");
        logLine("Eingabedatei",$fileext);
        
        $cntles = 0;    
        $hdlext = openInputFile($fileext);
        
        $id = $name = $body = "";
        
        flush();
        
        while (!feof($hdlext))
        {
            $line = rtrim(fgets($hdlext));
            $cntles++;
            
            if     (stripos($line,"<id>")    !== false)
                $id   = strtoupper(getXmlValue("id",$line));
            elseif (stripos($line,"<name>")    !== false)
                $name = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"<body>")  !== false)
                $body = getXmlValue("body",$line);
            elseif (stripos($line,"</string>") !== false)
            {
                $tabNames[$name]['body'] = $body;
                $tabNames[$name]['id']   = $id;
                
                $id = $name = $body = "";
            }
        }
        fclose($hdlext);
        
        logline("Zeilen eingelesen",$cntles);
    }
    logLine("gefundene Strings",count($tabNames));
}
// ----------------------------------------------------------------------------
// ermitteln der Client-Instance-Cooltime-Daten
// ----------------------------------------------------------------------------
function scanClientCooltimeFile()
{
	global $pathdata, $tabCTime;
        
    logSubHead("Scanne die Instance-Cooltime-Datei");

    $fileext = formFileName($pathdata."\\World\\client_instance_cooltime.xml");
    $fileext = convFileToUtf8($fileext);
    
    logLine("Eingabedatei",$fileext);
    
    $cntles  = 0;
    $cntcool = 0;            
    
    $hdlext  = openInputFile($fileext);
    
    flush();
    
    $id   = $name = $desc = $type = $race = $ente = $enta = $mine = $maxe = $mina = $maxa = $ment = $ctid = $unit = "";
    
    while (!feof($hdlext))
    {	
        $line = trim(fgets($hdlext));
        $cntles++; 
        
        if     (stripos($line,"<id>") !== false)
            $id    = getXmlValue("id",$line);
        elseif (stripos($line,"<name>") !== false)
            $name  = getXmlValue("name",$line);
        elseif (stripos($line,"<desc>") !== false)
            $desc  = getXmlValue("desc",$line);
        elseif (stripos($line,"<indun_type>") !== false)
            $type  = getXmlValue("indun_type",$line);
        elseif (stripos($line,"<race>") !== false)
            $race  = getXmlValue("race",$line);
        elseif (stripos($line,"<max_member_light>") !== false)
            $ente  = getXmlValue("max_member_light",$line);
        elseif (stripos($line,"<max_member_dark>") !== false)
            $enta  = getXmlValue("max_member_dark",$line);
        elseif (stripos($line,"<enter_min_level_light>") !== false)
            $mine  = getXmlValue("enter_min_level_light",$line);
        elseif (stripos($line,"<enter_max_level_light>") !== false)
            $maxe  = getXmlValue("enter_max_level_light",$line);
        elseif (stripos($line,"<enter_min_level_dark>") !== false)
            $mina  = getXmlValue("enter_min_level_dark",$line);
        elseif (stripos($line,"<enter_max_level_dark>") !== false)
            $maxa  = getXmlValue("enter_max_level_dark",$line);
        elseif (stripos($line,"<can_enter_mentor>") !== false)
            $ment  = getXmlValue("can_enter_mentor",$line);
        elseif (stripos($line,"<coolt_tbl_id>") !== false)
            $ctid  = getXmlValue("coolt_tbl_id",$line);
        elseif (stripos($line,"<alarm_unit_score>") !== false)
            $unit  = getXmlValue("alarm_unit_score",$line);
        elseif (stripos($line,"</client_instance_cooltime>") !== false)
        {           
            $tabCTime[$id]['id']   = $id;
            $tabCTime[$id]['name'] = strtoupper($name);
            $tabCTime[$id]['desc'] = strtoupper($desc);
            $tabCTime[$id]['type'] = strtoupper($type);
            $tabCTime[$id]['race'] = strtoupper($race);
            $tabCTime[$id]['ente'] = $ente;
            $tabCTime[$id]['enta'] = $enta;
            $tabCTime[$id]['mine'] = $mine;
            $tabCTime[$id]['maxe'] = $maxe;
            $tabCTime[$id]['mina'] = $mina;
            $tabCTime[$id]['maxa'] = $maxa;
            $tabCTime[$id]['ment'] = strtolower($ment);
            $tabCTime[$id]['ctid'] = $ctid;
            $tabCTime[$id]['unit'] = $unit;

            $cntcool++;   
            $id   = $name = $desc = $type = $race = $ente = $enta = $mine = $maxe = $mina = $maxa = $ment = $ctid = $unit = "";          
        }            
    }	
    fclose($hdlext);      
    unlink($fileext);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl Cooltimes gefunden",$cntcool);
}
// ----------------------------------------------------------------------------
// ermitteln der Client-Instance-Cooltime-Table-Daten
// ----------------------------------------------------------------------------
function scanClientCtTablesFile()
{
	global $pathdata, $tabTCool;
        
    logSubHead("Scanne die Instance-Cooltime-Table-Datei");

    $fileext = formFileName($pathdata."\\World\\client_instance_cooltime2.xml");
    $fileext = convFileToUtf8($fileext);
    
    logLine("Eingabedatei",$fileext);
    
    $cntles  = 0;
    $cntcool = 0;            
    
    $hdlext  = openInputFile($fileext);
    
    flush();
    
    $id   = $type = $tval = $valu = $maxc = "";
    
    while (!feof($hdlext))
    {	
        $line = trim(fgets($hdlext));
        $cntles++; 
        
        if     (stripos($line,"<id>") !== false)
            $id    = getXmlValue("id",$line);
        elseif (stripos($line,"<type>") !== false)
            $type  = getXmlValue("type",$line);
        elseif (stripos($line,"<typevalue>") !== false)
            $tval  = getXmlValue("typevalue",$line);
        elseif (stripos($line,"<value>") !== false)
            $valu  = getXmlValue("value",$line);
        elseif (stripos($line,"<maxcount>") !== false)
            $maxc  = getXmlValue("maxcount",$line);
        elseif (stripos($line,"</client_instance_cooltime2>") !== false)
        {           
            $tabTCool[$id]['id']   = $id;
            $tabTCool[$id]['type'] = strtoupper($type);
            $tabTCool[$id]['tval'] = $tval;
            $tabTCool[$id]['valu'] = $valu;
            $tabTCool[$id]['maxc'] = $maxc;
            
            $id   = $type = $tval = $valu = $maxc = "";

            $cntcool++;            
        }            
    }	
    fclose($hdlext);      
    unlink($fileext);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl TimeTables gefunden",$cntcool);
}
// ----------------------------------------------------------------------------
// InstanceBonusattr-Datei ausgeben
// ----------------------------------------------------------------------------
function generInstBonusattrFile()
{
    global $pathdata;
      
    $fileu16 = formFileName($pathdata."\\World\\client_instance_bonusattr.xml");
    $fileout = "../outputs/parse_output/instance_bonusattr/instance_bonusattr.xml";    
    $fileext = convFileToUtf8($fileu16);
    
    logHead("Generierung der Datei: ".basename($fileout));
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
        
    $cntles = 0;
    $cntout = 0;
    $cntatt = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<instance_bonusattrs xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="instance_bonusattr.xsd">'."\n");
    $cntout += 3;
    
    $lines = file($fileext);
    $domax = count($lines);
        
    $id  = $time = $typ = "";
    $pos = array();
    
    for ($l=0;$l<$domax;$l++)
    {
        $line = rtrim($lines[$l]);
        $cntles++;
        
        if (stripos($line,"<?xml") === false
        &&  stripos($line,"<path_group>") === false)
        {     
            // Start InstanceBonusattr
            if     (stripos($line,"<id>")          !== false) 
            {
                fwrite($hdlout,'    <instance_bonusattr buff_id="'.getXmlValue("id",$line).'">'."\n");
                $cntout++;
                $cntatt++;
            }
            elseif (stripos($line,"<penalty_attr") !== false) 
            {
                $xml  = getXmlKey($line);
                $attr = explode(" ",getXmlValue($xml,$line));
                $func = "ADD";
                
                if (stripos($attr[1],"%") !== false)
                {
                    $func    = "PERCENT";
                    $attr[1] = trim(str_replace("%","",$attr[1]));
                }
                $attr[1] = str_replace("+","",$attr[1]);
                
                fwrite($hdlout,'        <penalty_attr stat="'.getInstBonusAttrName($attr[0]).'"'.
                               ' func="'.$func.'" value="'.$attr[1].'"/>'."\n");
                $cntout++;
            }
            elseif (stripos($line,"</client_instance_bonusattr>") !== false)
            {
                fwrite($hdlout,'    </instance_bonusattr>'."\n");
                $cntout++;
            }
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</instance_bonusattrs>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen Eingabedatei",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
    logLine("Anzahl BonusAttrs  ",$cntatt);
}
// ----------------------------------------------------------------------------
// sofern eine Wert vorhanden ist, die Ausgabezeile zurückgeben
// ----------------------------------------------------------------------------
function getCoooltimeLine($tkey,$field,$xmltag)
{
    global $tabCTime;
    
    $ret = "";
    
    if (isset($tabCTime[$key][$field]))
        $ret = '        <'.$xmltag.'>'.$tabCTime[$tkey][$field].'</'.$xmltag.'>';
        
    return $ret;
}
// ----------------------------------------------------------------------------
// eine Ausgabezeile in die Datei schreiben
// ----------------------------------------------------------------------------
function writeOutputLine($hdl,$xml,$val)
{
    if ($val != "")
    {
        fwrite($hdl,'        <'.$xml.'>'.$val.'</'.$xml.'>'."\n");
        return 1;
    }
    else
        return 0;
}
// ----------------------------------------------------------------------------
// wenn zu dem Feld $fld in der Tabelle $tab zu Schlüssel $key ein Wert
// existiert, dann diesen zurückgeben
// ----------------------------------------------------------------------------
function getTabValue($tab,$key,$fld)
{
    if (isset($tab[$key][$fld]))
        return $tab[$key][$fld];
    else
        return "";
}
// -----------------------------------------------------------------------------
// InstanceCooltime-Datei ausgeben
// -----------------------------------------------------------------------------
function generInstCooltimesFile()
{
    global $tabCTime, $tabTCool;
    
    $fileout = "../outputs/parse_output/instance_cooltimes/instance_cooltimes.xml";
    
    logHead("Generierung der Datei: instance_cooltimes.xml");
    logLine("Ausgabedatei",$fileout);
    
    reset($tabCTime);
    
    $hdlout = openOutputFile($fileout);
    $cntout = 0;
    $cntcts = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");    
    fwrite($hdlout,'<instance_cooltimes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="instance_cooltimes.xsd">'."\n");
    $cntout += 3;
    
    while (list($tkey,$tval) = each($tabCTime))
    {
        $cntcts++;
        
        $wid  = getInstanceWorldId( getTabValue($tabCTime,$tkey,"name") );
        $name = getInstanceWorldName( getTabValue($tabCTime,$tkey,"desc") );
        $race = "PC_ALL";
        $type = getTabValue($tabCTime,$tkey,"type"); 
        
        switch($type)
        {
            case "NORMAL":
            case "BATTLEFIELD":
                    break;
            default: 
                    $type = "NORMAL";
                    break;
        }
        $tabCTime[$tkey]['type'] = $type;
        switch ($tabCTime[$tkey]['race'])
        {
            case "LIGHT": $race = "ELYOS";     break;
            case "DARK" : $race = "ASMODIANS"; break;
            default     : $race = "PC_ALL";    break;
        }
        fwrite($hdlout,'    <!-- '.$name.' -->'."\n");
        fwrite($hdlout,'    <instance_cooltime race="'.$race.'" worldId="'.$wid.'" id="'.$tkey.'">'."\n");
        $cntout += 2;
        
        $ctid    = getTabValue($tabCTime,$tkey,"ctid");
        
        //wenn keine Referenz-Id auf die Cooltime-Tablle exisitert, dann die aktuelle Instanz-Id nehmen!?
        if ($ctid == "") $ctid = $tkey;       
        
        $cntout += writeOutputLine($hdlout,"type"                 ,getTabValue($tabTCool,$ctid,"type"));   
        $cntout += writeOutputLine($hdlout,"typevalue"            ,getTabValue($tabTCool,$ctid,"tval")); 
        $cntout += writeOutputLine($hdlout,"ent_cool_time"        ,getTabValue($tabTCool,$ctid,"valu"));        
        $cntout += writeOutputLine($hdlout,"indun_type"           ,getTabValue($tabCTime,$tkey,"type"));
        $cntout += writeOutputLine($hdlout,"max_member_light"     ,getTabValue($tabCTime,$tkey,"ente"));
        $cntout += writeOutputLine($hdlout,"max_member_dark"      ,getTabValue($tabCTime,$tkey,"enta"));
        $cntout += writeOutputLine($hdlout,"enter_min_level_light",getTabValue($tabCTime,$tkey,"mine"));
        $cntout += writeOutputLine($hdlout,"enter_max_level_light",getTabValue($tabCTime,$tkey,"maxe"));
        $cntout += writeOutputLine($hdlout,"enter_min_level_dark" ,getTabValue($tabCTime,$tkey,"mina"));
        $cntout += writeOutputLine($hdlout,"enter_max_level_dark" ,getTabValue($tabCTime,$tkey,"maxa"));
        $cntout += writeOutputLine($hdlout,"alarm_unit_score"     ,getTabValue($tabCTime,$tkey,"unit"));
        $cntout += writeOutputLine($hdlout,"can_enter_mentor"     ,getTabValue($tabCTime,$tkey,"ment"));
        $cntout += writeOutputLine($hdlout,"maxcount"             ,getTabValue($tabTCool,$ctid,"maxc"));
        
        fwrite($hdlout,'    </instance_cooltime>'."\n");
        $cntout++; 
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</instance_cooltimes>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen ausgegeben  ",$cntout);
    logLine("Anzahl Cooltimes   ",$cntcts);
}
// ---------------------------------------------------------------------------
// InstanceExit-Datei ausgeben                           KEINE DATEN IM CLIENT
// ---------------------------------------------------------------------------
function generInstanceExitsFile()
{
    logHead("Generierung der Datei: instance_exit.xml");
    
    logLine("<font color=red>keine Generierung</font>","*** KEINE Informationen zum Generieren gefunden / erhalten ***");
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/auto_inc_world_infos.php");
include("includes/inc_bonusattrs.php");

$tabWorlds = array();
$tabNames    = array();
$tabCTime    = array();
$tabTCool    = array();
$starttime   = microtime(true);

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
        logHead("Scannen der Client-Dateien");
        
        makeWorldNameTab();
        scanClientStrings();
        scanClientCooltimeFile();
        scanClientCtTablesFile();
        
        generInstBonusattrFile();
        generInstCooltimesFile();
        generInstanceExitsFile();
        
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