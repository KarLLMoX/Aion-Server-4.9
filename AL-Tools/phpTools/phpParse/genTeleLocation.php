<html>
<head>
  <title>
    Teleporter - Erzeugen teleport_location.xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");
include("includes/inc_getautonameids.php");
include("includes/auto_inc_world_names.php");
include("includes/inc_worldmaps.php");

getConfData();

if (!file_exists("../outputs/parse_output"))
    mkdir("../outputs/parse_output");

$submit   = isset($_GET['submit'])   ? "J" : "N";
$withcom  = isset($_GET['withcom'])  ? "J" : "N";
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Teleport-Location-Datei</div>
  <div class="hinweis" id="hinw">
    Erzeugen der teleport_location.xml-Datei.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genTeleLocation.php" target="_self">
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
// WorldId zum Namen suchen
// ----------------------------------------------------------------------------
function getWorldId($desc)
{
    $key = strtoupper($desc);
    
    if (substr($key,0,4) == "STR_")            $key = substr($key,4);
    if (stripos($key,"_AIRPORT")    !== false) $key = substr($key,0,stripos($key,"_AIRPORT"));
    if (stripos($key,"_DYNAIRPORT") !== false) $key = substr($key,0,stripos($key,"_DYNAIRPORT"));
    
    if     ($key == "HLFP")      $key = "HOUSING_LF_PERSONAL";
    elseif ($key == "HDFP")      $key = "HOUSING_DF_PERSONAL";
    elseif ($key == "HLFPLEVEL") $key = "HOUSING_LC_LEGION";
    elseif ($key == "HDFPLEVEL") $key = "HOUSING_DC_LEGION";
    elseif ($key == "LDF5_F")    $key = "LDF5_FORTRESS";
    
    return getWorldNameId($key);
}   
// ----------------------------------------------------------------------------
// Name der Welt zur Id ermitteln
// ----------------------------------------------------------------------------
function getLocationName($desc)     
{
    global $tabNames;
    
    $key = strtoupper($desc);
    
    if (isset($tabNames[$key]))
        return $tabNames[$key]['body'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// NameId zur Location ermitteln
// ----------------------------------------------------------------------------
function getLocationNameId($desc)  
{
    global $tabNames;
    
    $key = strtoupper($desc);
    
    if (isset($tabNames[$key]))
        return $tabNames[$key]['id'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// Position aus der Client-Level-Datei mission_mission0.xml ermittteln
// ----------------------------------------------------------------------------
function getPosFromMissionFile($locakey)
{
    global $pathlevels, $tabLocas, $tabWorldmaps;
    
    $tabret      = array();
    $tabret['x'] = "0";
    $tabret['y'] = "0";
    $tabret['z'] = "0";
    $tabret['h'] = "0";
    
    $doloop      = true;
    
    flush();
    
    if (isset($tabWorldmaps[$tabLocas[$locakey]['mapid']]))
    {
        $wname = $tabWorldmaps[$tabLocas[$locakey]['mapid']]['offiname'];
        
        $fileext = formFileName($pathlevels."\\".$wname."\\mission_mission0.xml");
        
        if (!file_exists($fileext))
        {
            logLine("- MissionFile fehlt",$fileext);
            return $tabret;
        }
        
        logLine("- im MissionFile",$fileext);
        
        $hdlext  = openInputFile($fileext);
        
        while (!feof($hdlext) && $doloop)
        {
            $line = rtrim(fgets($hdlext));
            
            if (stripos($line,"<object ") !== false
            &&  stripos($line," Name=")   !== false
            &&  stripos($line," Pos=")    !== false)
            {
                $such = "Location".$wname;
                $szus = $wname;
                $desc = $tabLocas[$locakey]['desc'];
                
                if (stripos($desc,"SUB_L_") !== false)
                {
                    $szus = substr($desc,stripos($desc,"SUB_L_") + 6);
                    $szus = substr($szus,0,stripos($szus,"_"));
                    
                    echo "\<br>ermitteln SZUS=$szus aus $desc";
                }
                
                if (stripos($desc,"SUB_D_") !== false)
                {
                    $szus = substr($desc,stripos($desc,"SUB_D_") + 6);
                    $szus = substr($szus,0,stripos($szus,"_"));
                    
                    echo "\<br>ermitteln SZUS=$szus aus $desc";
                }
                
                $name = getKeyValue("name",$line);
                
                // Name entspricht ca. dem akt. Airport?
                if (stripos($name,$such) !== false  && stripos($name,$szus) !== false)
                {
                    $pos    = getKeyValue("pos",$line);
                    $tabpos = explode(",",$pos);
                    $tabret['x'] = $tabpos[0];
                    $tabret['y'] = $tabpos[1];
                    $tabret['z'] = $tabpos[2];
                }
            }
        }
        fclose($hdlext);
    }
    
    return $tabret;
}
// ----------------------------------------------------------------------------
// NPCs zu der aktuellen AirLine suchen
// ----------------------------------------------------------------------------
function getAirlineNpcs($name)
{
    global $tabLines;
   
    $key = strtoupper($name);
    $aid = "";
    
    if (isset($tabLines[$key]))
    {
        $aid = $tabLines[$key];
    }   
    else
    {
        while (list($akey,$val) = each($tabLines))
        {
            if (stripos($akey,$key) !== false)
                $aid = $tabLines[$akey];
        }
        reset($tabLines);
    }   
    
    if ($aid != "")
        return $tabLines[$aid]['npcs'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// Position aus dem Spawn-File ermitteln
// ----------------------------------------------------------------------------
function getPosFromSpawnFile($key)
{
    global $pathsvn, $tabLocas, $tabFiles;
    
    $tabret      = array();
    $tabret['x'] = "0";
    $tabret['y'] = "0";
    $tabret['z'] = "0";
    $tabret['h'] = "0";
    
    $wid      = $tabLocas[$key]['mapid'];
    
    if (!isset($tabFiles[$wid]))
    {
        logLine("- SpawnFile fehlt zu",$wid);
        return $tabret;
    }
    
    $npcs     = getAirlineNpcs($tabLocas[$key]['cname']);
    
    if ($npcs != "")
    {       
        $filename = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\Spawns\\Npcs\\".$tabFiles[$wid]);
        
        if (file_exists($filename))
        {
            logLine(" - im SpawnFile",$filename);
            logLine("   nach den Npcs",$npcs);
            
            $hdlsvn = openInputFile($filename);
            $donpc  = false;
            $doloop = true;
            
            while (!feof($hdlsvn) && $doloop)
            {
                $line = rtrim(fgets($hdlsvn));
                
                if (stripos($line,"npc_id=") !== false)
                {
                    $npcid = getKeyValue("npc_id",$line);
                    
                    if (stripos($npcs,$npcid) !== false)
                        $donpc = true;
                }
                if ($donpc  && stripos($line,"<spot ") !== false)
                {
                    $tabret['x'] = getKeyValue("x",$line);
                    $tabret['y'] = getKeyValue("y",$line);
                    $tabret['z'] = getKeyValue("z",$line);
                    $tabret['h'] = getKeyValue("h",$line);
                    
                    $doloop = false;
                }
            }
            fclose($hdlsvn);
        }
        else
            logLine("- Spawn-File nicht gefunden",$filename);
    }    
    
    // wenn nichts gefunden, dann im Mission_file suchen    
    if ($tabret['x'] == "0")
        $tabret = getPosFromMissionFile($key);  
        
    return $tabret;    
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
// alle Infos aus der client_airports.xml selektieren
// ----------------------------------------------------------------------------
function scanClientAirports()
{
    global $pathdata, $tabLocas;
    
    logSubHead("Scannen der Airports");
    
    $fileext = formFileName($pathdata."\\FlightPath\\client_airports.xml");
    $fileext = convFileToUtf8($fileext);
    $hdlext  = openInputFile($fileext);
    
    if (!$hdlext)
    {
        logLine("Fehler openInputFile",$fileext);
        return;
    }
    
    logLine("- Eingabedatei",$fileext);
    
    $id = $desc = $name = "";
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        
        if     (stripos($line,"<id>")              !== false)
            $id   = getXmlValue("id",$line);
        elseif (stripos($line,"name")              !== false)
            $name = strtoupper(getXmlValue("name",$line));
        elseif (stripos($line,"desc")              !== false)
            $desc = strtoupper(getXmlValue("desc",$line));
        elseif (stripos($line,"</client_airport>") !== false)
        {            
            if ($id  != "" && $desc != "")
            {
                if (stripos($name,"airport") !== false)
                {
                    $tabLocas[$id]['id']    = $id; 
                    $tabLocas[$id]['cname'] = $name;
                    $tabLocas[$id]['desc']  = $desc;
                    $tabLocas[$id]['type']  = "";
                    $tabLocas[$id]['posx']  = "";
                    $tabLocas[$id]['posy']  = "";
                    $tabLocas[$id]['posz']  = "";
                    $tabLocas[$id]['posh']  = "";
                    $tabLocas[$id]['mapid'] = getWorldId($desc);
                    $tabLocas[$id]['name']  = getLocationName($desc);
                    $tabLocas[$id]['namid'] = getLocationNameId($desc);
                    $tabLocas[$id]['doit']  = "N";
                    $tabLocas[$id]['check'] = "J";
                }
            }
            
            $id = $desc = $name = "";
        }
    }
    fclose($hdlext);
    unlink($fileext);
    
    logLine("- Anzahl Airports gefunden",count($tabLocas));
}
// ----------------------------------------------------------------------------
// alle Infos aus der client_airlines.xml selektieren
// ----------------------------------------------------------------------------
function scanClientAirlines()
{
    global $pathdata, $tabLines;
    
    logSubHead("Scannen der AirLines");
    
    $fileext = formFileName($pathdata."\\FlightPath\\client_airline.xml");
    $fileext = convFileToUtf8($fileext);
    $hdlext  = openInputFile($fileext);
    $cntair  = 0;
    
    if (!$hdlext)
    {
        logLine("Fehler openInputFile",$fileext);
        return;
    }
    
    logLine("- Eingabedatei",$fileext);
    
    $id = $name = "";
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        
        if     (stripos($line,"<id>")               !== false)
            $id   = getXmlValue("id",$line);
        elseif (stripos($line,"<cur_airport_name>") !== false)
            $name = strtoupper(getXmlValue("cur_airport_name",$line));
        elseif (stripos($line,"</client_airline>")     !== false)
        {            
            if ($id  != "" && $name != "")
        {
                $tabLines[$id]['name']  = $name;
                $tabLines[$id]['npcs']  = "";
                
                $tabLines[$name]        = $id;
                
                $cntair++;
            }
            
            $id = $name = "";
        }
    }
    fclose($hdlext);
    unlink($fileext);
    
    logLine("- Anzahl AirLines gefunden",$cntair);
}
// ----------------------------------------------------------------------------
// scannen der SVN-Datei npc_teleporter.xml
// ----------------------------------------------------------------------------
function scanSvnTeleporter()
{
    global $pathsvn, $tabLocas, $tabLines;
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\npc_teleporter.xml");
    
    logSubHead("Scanne SVN-Teleporter-Datei");
    logLine("Eingabedatei",$filesvn);
    
    $hdlsvn = openInputFile($filesvn);
    $cntles = 0;
    $cntloc = 0;
    $cntair = 0;
    
    $id = $type = "";
    
    flush();
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        $cntles++;
        
        if (stripos($line,"loc_id=") !== false)
        {
            $cntloc++;
            
            $id = getKeyValue("loc_id",$line);
            
            if (stripos($line,"type=")   !== false)
                $type = getKeyValue("type",$line);
                
            if (isset($tabLocas[$id]))
            {
                $loctype               = ($type == "REGULAR") ? "REGULAR" : "FLIGHT";
                
                // bei gem ischten Typen den FLIGHT nutzen
                if (($loctype == "FLIGHT" && $tabLocas[$id]['type'] != "FLIGHT")
                ||  ($tabLocas[$id]['type'] == "FLIGHT"))
                    $tabLocas[$id]['type'] = "FLIGHT";
                else
                    $tabLocas[$id]['type'] = $loctype;
                $tabLocas[$id]['doit'] = "J";
            }
        }
        elseif (stripos($line,"teleportId=") !== false)
        {
            $tid = getKeyValue("teleportId",$line);
            $tabLines[$tid]['npcs'] = getKeyValue("npc_ids",$line);
            $cntair++;
        }
    }
    fclose($hdlsvn);    
    
    logLine("Zeilen eingelesen",$cntles);
    logLine("Anzahl Locations",$cntloc);
    logLine("Anzahl Airlines",$cntair);
}
// ----------------------------------------------------------------------------
// scannen der SVN-Datei teleport_location.xml
// ----------------------------------------------------------------------------
function scanSvnTeleLocation()
{
    global $pathsvn, $tabLocas;
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\teleport_location.xml");
    
    logSubHead("Scanne SVN-Teleport-Location-Datei");
    logLine("Eingabedatei",$filesvn);
    
    $hdlsvn = openInputFile($filesvn);
    $cntles = 0;
    $cntloc = 0;
    $id = "";
    
    flush();
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        $cntles++;
        
        if (stripos($line,"loc_id=") !== false)
        {
            $cntloc++;
            
            $id = getKeyValue("loc_id",$line);
                            
            if (isset($tabLocas[$id]))
            {
                $tabLocas[$id]['posx'] = getKeyValue("posX",$line);
                $tabLocas[$id]['posy'] = getKeyValue("posY",$line);
                $tabLocas[$id]['posz'] = getKeyValue("posZ",$line);
                $tabLocas[$id]['posh'] = getKeyValue("heading",$line);
                
                $tabLocas[$id]['check']= "N";
            }
        }
    }
    fclose($hdlsvn);    
    
    logLine("Zeilen eingelesen",$cntles);
    logLine("Anzahl Locations",$cntloc);
}
// ----------------------------------------------------------------------------
// alle Spawn-Files im SVN ermitteln
// ----------------------------------------------------------------------------
function getAllSpawnFileNames()
{
    global $pathsvn, $tabFiles;
    
    $scanpath = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\Spawns\\Npcs");
    $tabpath  = scandir($scanpath);
    $maxpath = count($tabpath);
    
    for ($p=0;$p<$maxpath;$p++)
    {
        if (substr($tabpath[$p],0,1) != ".")
        {
            $wid = substr($tabpath[$p],0,9);
            $tabFiles[$wid] = $tabpath[$p];
        }
    }
}
// ----------------------------------------------------------------------------
// zu evtl. neuen Locations die Positionen aus der mission0-Datei ermitteln
//
// Ablauf:
// - über den Airport-Namen wird die AirlineId gesucht
// - mit der AirlineId werden die Teleporter-NPCS gesucht
// - zu den TeleporterNpcs wird aus dem SpawnFile die Position ermittelt 
// ----------------------------------------------------------------------------
function scanMissingPosition()
{
    global $pathlevel, $tabLocas;
    
    $cntrel = 0;
    $cntpos = 0;
    $cntnot = 0;
    $cntsvn = 0;
        
    logSubHead("Prüfen der Koordinaten für evtl. neue Locations");
    
    flush();
    
    getAllSpawnFileNames();
    
    while (list($key,$val) = each($tabLocas))
    {
        if ($tabLocas[$key]['doit'] == "J")
        {
            $cntrel++;
            
            if ($tabLocas[$key]['type']  == "REGULAR"
            &&  $tabLocas[$key]['check'] == "J")
            {
                logLine("Suche zu Location ",$key);
                
                $tabpos = getPosFromSpawnFile($key);
                
                $tabLocas[$key]['posx']  = $tabpos['x'];
                $tabLocas[$key]['posy']  = $tabpos['y'];
                $tabLocas[$key]['posz']  = $tabpos['z'];
                $tabLocas[$key]['posh']  = ($tabpos['h'] == "?") ? "0" : $tabpos['h'];
                $tabLocas[$key]['check'] = "J";
                
                $cntpos++;
            }
            else
            {
                if ($tabLocas[$key]['type'] == "FLIGHT")
                    $cntnot++;
                else
                    $cntsvn++;
            }
        }
    }
    
    logLine("relevante Locations",$cntrel);
    logLine("- Flugpunkte ohne XYZ",$cntnot);
    logLine("- aus SVN ermittelt",$cntsvn);
    logLine("- neu ermittelt",$cntpos,"(Check IT)");
    
    reset($tabLocas);
}
// ----------------------------------------------------------------------------
// Teleporter-Datei ausgeben
// ----------------------------------------------------------------------------
function generTeleLocationFile()
{
    global $tabLocas;
        
    logSubHead("Erzeugen der Ausgabedatei");
        
    reset($tabLocas);
    
    $fileout = "../outputs/parse_output/teleport_location.xml";
    $hdlout  = openOutputFile($fileout);
    $cntout  = 0;
    
    logLine("- Ausgabedatei",$fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<teleport_location xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="teleport_location.xsd">'."\n");
    $cntout += 3;
    
    flush();
    
    $oldgrp  = "";
    
    while (list($key,$val) = each($tabLocas))
    {
        if ($tabLocas[$key]['doit'] == "J")
        {
            if ($oldgrp != $tabLocas[$key]['type'])
            {
                $oldgrp = $tabLocas[$key]['type'];
                fwrite($hdlout,'    <!-- '.(($tabLocas[$key]['type'] == "REGULAR") ? "teleport" : "flying").' -->'."\n");
                $cntout++;
            }
            
            if ($tabLocas[$key]['type'] == "REGULAR")            
                fwrite($hdlout,'    <teleloc_template'.
                               ' loc_id="'.$tabLocas[$key]['id'].'"'.
                               ' mapid="'.$tabLocas[$key]['mapid'].'"'.
                               ' name="'.$tabLocas[$key]['name'].'"'.
                               ' name_id="'.$tabLocas[$key]['namid'].'"'.
                               ' posX="'.$tabLocas[$key]['posx'].'"'.
                               ' posY="'.$tabLocas[$key]['posy'].'"'.
                               ' posZ="'.$tabLocas[$key]['posz'].'"'.
                               ' heading="'.$tabLocas[$key]['posh'].'"'.
                               '/>'.
                               (($tabLocas[$key]['check'] == "J") ? '  <!-- CHECK POSITION -->' : '').
                               "\n");     
            else                             
                fwrite($hdlout,'    <teleloc_template'.
                               ' loc_id="'.$tabLocas[$key]['id'].'"'.
                               ' mapid="'.$tabLocas[$key]['mapid'].'"'.
                               ' name="'.$tabLocas[$key]['name'].'"'.
                               ' name_id="'.$tabLocas[$key]['namid'].'"'.
                               '/>'."\n");                  
            $cntout++;
        }                  
    }    
    
    // Nachspann ausgeben
    fwrite($hdlout,'</teleport_location>');
    fclose($hdlout);
    $cntout++;
    
    logLine("- Anzahl Zeilen ausgegeben",$cntout);    
}
// ----------------------------------------------------------------------------
//          T   E   S   T
// ----------------------------------------------------------------------------
function showTestOutputTabLocas()
{
    global $tabLocas;
 
    reset($tabLocas);
    
    $hdlout = openOutputFile("teleLoc_log.txt");
    
    while (list($key,$val) = each($tabLocas))
    {
        if ($tabLocas[$key]['doit'] == "J")
        {
            fwrite($hdlout,"ID='".$tabLocas[$key]['id']."', ".
                           "CNAME='".$tabLocas[$key]['cname']."', ".
                           "DESC='".$tabLocas[$key]['desc']."', ".
                           "TYPE='".$tabLocas[$key]['type']."', ".
                           "X='".$tabLocas[$key]['posx']."', ".
                           "Y='".$tabLocas[$key]['posy']."', ".
                           "Z='".$tabLocas[$key]['posz']."', ".
                           "H='".$tabLocas[$key]['posh']."', ".
                           "MAPID='".$tabLocas[$key]['mapid']."', ".
                           "NAME='".$tabLocas[$key]['name']."', ".
                           "NAMEID='".$tabLocas[$key]['namid']."', ".
                           "CHECK='".$tabLocas[$key]['check']."'\n");
        }
    }
    fclose($hdlout);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

$tabLocas    = array();
$tabLineId   = array();
$tabLineName = array();
$tabNames    = array();
$tabFiles    = array();

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
        logHead("Generierung der Datei: teleport_location.xml");;
        
        scanClientStrings();
        scanClientAirports();
        scanClientAirlines();
        scanSvnTeleporter();
        scanSvnTeleLocation();
        scanMissingPosition();
        generTeleLocationFile();
        //showTestOutputTabLocas();
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