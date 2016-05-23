<html>
<head>
  <title>
    NpcFactions - Erzeugen npc_factions.xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

$submit   = isset($_GET['submit'])   ? "J"               : "N";

if (!file_exists("../outputs/parse_output/npc_factions"))
    mkdir("../outputs/parse_output/npc_factions");
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen NPC-Factions-Datei</div>
  <div class="hinweis" id="hinw">
  Erzeugen der npc_factions.xml-Datei.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genNpcFactions.php" target="_self">
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
// NameId zur Factiuon zurückgeben 
// ----------------------------------------------------------------------------
function getFactionNameId($name)
{
    global $tabNames;
    
    $key = strtoupper($name);
    
    if (isset($tabNames[$key]))
        return $tabNames[$key]['id'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// Name der Faction zurückgeben
// ----------------------------------------------------------------------------
function getFactionName($name)
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
// Scannen der PS-String-Dateien
// ----------------------------------------------------------------------------
function scanPsStringFiles()
{
    global $pathstring, $tabNames;
    
    $tabFiles = array(
                  array( "client_strings_msg.xml",false),
                  array( "client_strings_quest.xml",true)
                );
    $maxFiles = count($tabFiles);
    
    logHead("Scannen der PS-String-Dateien");
    
    for ($f=0;$f<$maxFiles;$f++)
    {
        $filestr = formFileName($pathstring."\\".$tabFiles[$f][0]);
        $hdlstr  = openInputFile($filestr);
        $cntles  = 0;
        $cntstr  = 0;
        
        logSubHead("Scanne Datei ".$filestr);
        
        $id = $name = $body = "";
        
        flush();
        
        while (!feof($hdlstr))
        {
            $line = rtrim(fgets($hdlstr));
            $cntles++;
            
            if     (stripos($line,"<id>")      !== false)  $id   = getXmlValue("id",$line);
            elseif (stripos($line,"<name>")    !== false)  $name = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"<body>")    !== false)  $body = getXmlValue("body",$line);
            elseif (stripos($line,"</string>") !== false)
            {
                if ($tabFiles[$f][1])
                    $id = ($id * 2) + 1;
                    
                $tabNames[$name]['id']   = $id;
                $tabNames[$name]['body'] = $body;
                $cntstr++;
                
                $id = $name = $body = "";
            }
        }
        
        fclose($hdlstr);
        
        logLine("Anzahl Zeilen gelesen",$cntles);
        logLine("Anzahl gefundene Strings",$cntstr);
    }
}
// ----------------------------------------------------------------------------
// NPCs mit Faction-Vorgabe scannen
// ----------------------------------------------------------------------------
function scanFactionNpcs()
{
    global $pathdata, $tabFacts;    
    
    $fileu16 = formFileName($pathdata."\\Npcs\\client_npcs_npc.xml");
    
    $fileext = convFileToUtf8($fileu16);
    
    logHead("Scanne die Faction-Npcs aus dem Client");
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    
    $cntles = 0;
    $cntnpc = 0;
    
    $id = $fact = "";
    
    $hdlext = openInputFile($fileext);
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<id>")                 !== false)
            $id = getXmlValue("id",$line);
        elseif (stripos($line,"<npcfaction_name>") !== false)
        {
            $fact = strtoupper(getXmlValue("npcfaction_name",$line));
            
            $tabFacts[$fact][$id] = 1;
            $cntnpc++;
            
            $id = $fact = "";
        }
    }
    fclose($hdlext);
    
    unlink($fileext);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl NPCs gefunden",$cntnpc);
}
// ----------------------------------------------------------------------------
// NpcFactions-Datei ausgeben
// ----------------------------------------------------------------------------
function generNpcFactionsFile()
{
    global $pathdata, $tabFacts;
    
    $fileu16 = formFileName($pathdata."\\Faction\\NpcFactions.xml");
    $fileout = "../outputs/parse_output/npc_factions/npc_factions.xml";
    
    $fileext = convFileToUtf8($fileu16);
    logHead("Generierung der Datei: ".basename($fileout));
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $cntles = 0;
    $cntout = 0;
    $cnterr = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<npc_factions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="npc_factions.xsd">'."\n");
    $cntout += 3;
    
    $lines = file($fileext);
    $domax = count($lines);
        
    $id   = $name = $desc = $categ = $minlv = $maxlv = $race = $skill = "";
    
    flush();
    
    for ($l=0;$l<$domax;$l++)
    {
        $line = rtrim($lines[$l]);
        $cntles++;
        
        if (stripos($line,"<?xml") === false
        &&  stripos($line,"<npcfaction>") === false)
        {     
            // Start NpcFaction
            if     (stripos($line,"<id>")                  !== false)
                $id    = getXmlValue("id",$line);
            elseif (stripos($line,"<name>")                !== false)
                $name  = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"<desc>")                !== false)
                $desc  = strtoupper(getXmlValue("desc",$line));
            elseif (stripos($line,"<category>")            !== false)
                $categ = strtoupper(getXmlValue("category",$line));
            elseif (stripos($line,"<minlevel_permitted>")  !== false)
                $minlv = getXmlValue("minlevel_permitted",$line);
            elseif (stripos($line,"<maxlevel_permitted>")  !== false)
                $maxlv = getXmlValue("maxlevel_permitted",$line);
            elseif (stripos($line,"<race_permitted>")      !== false)
                $race  = getXmlValue("race_permitted",$line);
            elseif (stripos($line,"<combine_skillpoint>")  !== false)
                $skill = getXmlValue("combine_skillpoint",$line);
            elseif (stripos($line,"</npcfaction>")         !== false)
            {
                if (isset($tabFacts[$name]))
                {
                    while (list($key,$val) = each($tabFacts[$name]))
                    {
                        // Sonderbehandlung für die Kategorie
                        if ($categ == "DAILYQUEST")
                        {
                            if     ($skill != "")                     $categ = "COMBINESKILL";
                            elseif (stripos($desc,"SHUGO") !== false) $categ = "SHUGO";
                            else                                      $categ = "DAILY";
                        }
                        
                        $lout = '    <npc_faction id="'.$id.'" name="'.getFactionName($desc).'" npcid="'.$key.
                                '" nameId="'.getFactionNameId($desc).'" category="'.$categ.'"';

                        if ($minlv != "")   $lout .= ' minlevel="'.$minlv.'"';
                        if ($maxlv != "")   $lout .= ' maxlevel="'.$maxlv.'"';
                        if ($skill != "")   $lout .= ' skill_points="'.$skill.'"';

                        $lout .= ' race="';

                        switch (strtoupper($race))
                        {
                            case "PC_LIGHT":  $lout .= 'ELYOS';     break;
                            case "PC_DARK" :  $lout .= 'ASMODIANS'; break;
                            default        :  $lout .= 'NPC';       break;
                        }                
                        $lout .= '"/>';
                        
                        fwrite($hdlout,$lout."\n");
                        $cntout++;
                    }
                }
                else
                    $cnterr++;
                    
                $id   = $name = $desc = $categ = $minlv = $maxlv = $race = $skill = "";
            }
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</npc_factions>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen Eingabedatei",$domax);
    logLine("Zeilen verarbeitet ",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
    logLine("Anzahl fehlende Factions",$cnterr);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

$starttime = microtime(true);
$tabNames  = array();
$tabFacts  = array();

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
        scanPsStringFiles();
        scanFactionNpcs();
        generNpcFactionsFile();
        
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