<html>
<head>
  <title>
    WorldZones - Erzeugen zones....xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

if (!file_exists("../outputs/parse_output/zones"))
    mkdir("../outputs/parse_output/zones");
if (!file_exists("../outputs/parse_output/zones/noWorldId"))
    mkdir("../outputs/parse_output/zones/noWorldId");

$submit   = isset($_GET['submit'])   ? "J"               : "N";

?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen World-Zones-Datei</div>
  <div class="hinweis" id="hinw">
    Erzeugen der zones_....xml-Datei.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genWorldZones.php" target="_self">
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
// Id der World zurückgeben
// ----------------------------------------------------------------------------
function getWorldNameId($name)
{
    global $tabWNames;
    
    $key = strtoupper($name);

    if (isset($tabWNames[$key]))
        return $tabWNames[$key];
    else
        return 0;
}
// ----------------------------------------------------------------------------
// ZoneIdName zurückgeben
// ----------------------------------------------------------------------------
function getZoneIdName($text)
{
    $zname = str_replace("'","",$text);
    $zname = str_replace("\\","_",$zname);
    $zname = str_replace("/" ,"_",$zname);
    $zname = str_replace("." ,"_",$zname);
    $zname = str_replace("," ,"_",$zname);
    $zname = str_replace("(" ,"_",$zname);
    $zname = str_replace(")" ,"_",$zname);
    
    // zum Schluss
    $zname = str_replace("__","_",$zname);
    
    return $zname;
}
// ----------------------------------------------------------------------------
// Name der Zone zurückgeben
// ----------------------------------------------------------------------------
function getZoneName($desc,$name,$world)
{
    global $tabZNames, $tabZoneId;
    
    $key = strtoupper($desc);
    $key = str_replace("'","",$key);
    $ret = "";
    $nr  = "";
    $txt = "";
    
    if (isset($tabZNames[$key]))
        $ret = strtoupper($tabZNames[$key]);
    else
    {
        $key = strtoupper($name);
        $key = str_replace("'","",$key);
        
        if (isset($tabZNames[$key]))
            $ret = strtoupper($tabZNames[$key]);
        else
            $ret = $key;
    }
    
    $ret = getZoneIdName($ret);
    
    // Zone-Name ist in der XSD-Datei als "xs_ID" definiert und ...
    
    // ... darf nicht numerisch beginnen
    if (is_numeric(substr($ret,0,1)))
        $ret = $world."_".$ret;
        
    // ... muss global eindeutig sein
    if (isset($tabZoneId[$ret]))
    {
        // lfd. Nummer ergänzen
        $tmp = $ret;
        $tabZoneId[$ret]++;
        $ret = $ret."_".$tabZoneId[$ret];
        /*
        while( isset($tabZoneId[$tmp]) )
        {
            $tmp = $ret.$txt;
            $nr++;
            $txt = "_".$nr;
        }
        $ret = $tmp;
        */
    }
    else
        $tabZoneId[$ret] = 0;
    
    return $ret;
}
// ----------------------------------------------------------------------------
// gerundeten Wert für x-/y-Position zurückgeben
// ----------------------------------------------------------------------------
function getRoundPos($pos,$dez)
{
    // das Runden wird nicht durchgängig in den alten SVN-Zone-Dateien durch-
    // geführt, daher wird es an dieser Stelle erst einmal unterlassen !!!!!!    
    $ret = round($pos,6);
    //$ret = round($pos,$dez);
    
    if (stripos($ret,".") === false)
        $ret .= ".0";
     
    return $ret;
}
// ----------------------------------------------------------------------------
// interne Tabelle der Welten umschlüsseln
// ----------------------------------------------------------------------------
function makeTabWorldIds()
{
    global $tabWNames;
    
    include("includes/auto_inc_world_infos.php");
    
    logHead("Erzeuge Tabelle der WorldIds");
    
    flush();
    
    while (list($key,$val) = each($tabWorldmaps))
    {
        $name = strtoupper($tabWorldmaps[$key]['offiname']);
        $tabWNames[$name] = $key;
    }
    
    logLine("Anzahl Welten gefunden",count($tabWNames));
}
// ----------------------------------------------------------------------------
// Flags-Value Zone zurückgeben
// ----------------------------------------------------------------------------
function getFlagsZone(&$tab,$x)
{
    $ret = 0;
    
    // Flags für SUB / FLY
    switch($tab['zone'][$x]['type'])
    {
        case "LIMIT": // Limit-Zone hat eigene Flags
            if ($tab['zone'][$x]['norecall'] != "TRUE") $ret += 2;
            if ($tab['zone'][$x]['noride']   != "TRUE") $ret += 4;
            
            break;
        case "PVP":   // PVP-Zone hat eigene Flags
            $ret = 64;
            break;
        default:      // Flags für SUB und FLY    
            // World allgemein
            if ($tab['bind'])                  $ret +=   1;
            if ($tab['recall'])                $ret +=   2;
            if ($tab['glide'])                 $ret +=   4;   
            if ($tab['ride'])                  $ret +=  16;  
            if ($tab['lvfly'])                 $ret +=  32; 
            
            // für die einzelnen Zonen
            if ($tab['zone'][$x]['fly'])       $ret +=   8;
            // noch offen, woher diese Werte kommen!!!!!
            if ($tab['zone'][$x]['pvp'])       $ret +=  64;
            if ($tab['zone'][$x]['same'])      $ret += 128;  // DUEL_SAME_RACE
            if ($tab['zone'][$x]['other'])     $ret += 256;  // DUEL_OTHER_RACE
        
            break;
    }
    return $ret;
}
// ----------------------------------------------------------------------------
// NUR ZUM TESTEN - Anzeige der Flags als XML-Kommentar 
// ----------------------------------------------------------------------------
function showFlags(&$tab,$x)
{
    $ret = "";
    
    // Flags für SUB / FLY
    switch($tab['zone'][$x]['type'])
    {
        case "LIMIT": // Limit-Zone hat eigene Flags
            if ($tab['zone'][$x]['norecall'] != "TRUE") $ret .= "norecall (2),";
            if ($tab['zone'][$x]['noride']   != "TRUE") $ret .= "noride (4),";
            
            break;
        case "PVP":   // PVP-Zone hat eigene Flags
        
            break;
        default:      // Flags für SUB und FLY    
            // World allgemein
            if ($tab['bind'])                  $ret .= "bind (1),";
            if ($tab['recall'])                $ret .= "recall (2),";
            if ($tab['glide'])                 $ret .= "glide (4),";   
            if ($tab['ride'])                  $ret .= "ride (16),";  
            if ($tab['lvfly'])                 $ret .= "lvfly (32),"; 
            
            // für die einzelnen Zonen
            if ($tab['zone'][$x]['fly'])       $ret .= "fly (8),";
            // noch offen, woher diese Werte kommen!!!!!
            if ($tab['zone'][$x]['pvp'])       $ret .= "pvp (64),";
            if ($tab['zone'][$x]['same'])      $ret .= "same (128),";  // DUEL_SAME_RACE
            if ($tab['zone'][$x]['other'])     $ret .= "other (256)";  // DUEL_OTHER_RACE
    
            break;
    }
    return "        <!-- FLAGS-FOR-TEST-ONLY: $ret -->";
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Scannen der bereits vergebenen Zonen-Namen aus den SVN-Dateien, die derzeit
// noch nicht neu erstellt werden können (weather/quest)
// ----------------------------------------------------------------------------
function scanZoneNamesSvn()
{
    global $pathsvn, $tabZoneId;
    
    $tabfiles = array( "zones_quest.xml",
                       "zones_weather.xml"
                     );
    $maxfiles = count($tabfiles);
    
    logHead("Scannen der bestehenden Zonen-Namen (quest/weather)");
    
    for ($f=0;$f<$maxfiles;$f++)
    {
        $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\zones\\".$tabfiles[$f]);
        $hdlsvn  = openInputFile($filesvn);
        
        if ($hdlsvn)
        {
            while (!feof($hdlsvn))
            {
                $line = rtrim(fgets($hdlsvn));
                
                if (stripos($line,"<zone ") !== false
                &&  stripos($line," name=") !== false)
                {
                    $key = strtoupper(getKeyValue("name",$line));
                    $tabZoneId[$key] = 0;
                }
            }
            fclose($hdlsvn);
        }
    }
    logLine("gefundene Zonen-Namen",count($tabZoneId));
}
// ----------------------------------------------------------------------------
// Scannen der Zonen-Namen aus den PS-String-Files
// ----------------------------------------------------------------------------
function scanClientZoneNames()
{
    global $pathstring, $tabZNames;
    
    $filestr = formFileName($pathstring."\\client_strings_level.xml");
    $hdlstr  = openInputFile($filestr);
    $cntles  = 0;
    $cntstr  = 0;
    
    logHead("Scannen der Zonen-Namen aus den PS-String-Dateien");
    
    if (!$hdlstr)
    {
        logLine("Fehler openInputFile",$filestr);
        return;
    }
    
    flush();
    
    while (!feof($hdlstr))
    {
        $line = rtrim(fgets($hdlstr));
        $cntles++;
        
        if     (stripos($line,"<name>") !== false)
            $name = getXmlValue("name",$line);
        elseif (stripos($line,"<body>") !== false)
        {            
            $tabZNames[$name] = getXmlValue("body",$line);
            
            $name = $body = "";
            $cntstr++;
        }
    }
    fclose($hdlstr);
    
    logLine("Anzahl Zeilen gelsen",$cntles);
    logLine("Anzahl Strings gefunden",$cntstr);
}
// ----------------------------------------------------------------------------
// Scannen aller im Client definierten Zonen
// ----------------------------------------------------------------------------
function scanAllClientZones()
{
    global $pathlevels, $tabZones;
    
    $ind = 0;
    
    logHead("Scanne die relevanten Zonen aus dem Client");
    
    $tabpathes = scandir($pathlevels);
    $maxpathes = count($tabpathes);

    for ($p=0;$p<$maxpathes;$p++)
    {
        if (substr($tabpathes[$p],0,1) != ".")
        {
            $tabZones[$ind]['w_id'] = getWorldNameId($tabpathes[$p]);
            $tabZones[$ind]['name'] = strtoupper($tabpathes[$p]);
            $tabZones[$ind]['pfad'] = formFileName($pathlevels."\\".$tabpathes[$p]);
            $ind++;           
        }
    } 
    sort($tabZones);    
    
    logLine("Anzahl Zonen gefunden",count($tabZones));    
}
// ----------------------------------------------------------------------------
// Scannen Zone-Mission-File
// liefert überwiegend: SUB und FLY
// ----------------------------------------------------------------------------
function scanZoneMissionFile($z,&$tab)
{
    global $pathlevels, $tabZones;
    
    $fileu16 = formFileName($tabZones[$z]['pfad']."\\mission_mission0.xml");
    
    if (!file_exists($fileu16))
        return;
        
    $fileext = $fileu16; //convFileToUtf8($fileu16);
    $hdlext  = openInputFile($fileext);
    $sub     = "";
    $dat     = 0;
    $insub   = false;
    $cntles  = 0;
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<subzone ")      !== false   // SUB-Zones
        ||      stripos($line,"<flying_zone ")  !== false)  // FLY-Zone
            $insub = true;
        elseif (stripos($line,"</subzone>")     !== false
        ||      stripos($line,"</flying_zone>") !== false)
            $insub = false;
                        
        if ($insub)
        {
            if (stripos($line,"<subzone ")      !== false
            ||  stripos($line,"<flying_zone ")  !== false)
            {                
                $sub = str_replace("'","",strtoupper(getKeyValue("name",$line)));
                $nr  = 1;
                
                while (isset($tab['zone'][$sub]))
                {
                    $sub = str_replace("'","",strtoupper(getKeyValue("name",$line)."_".$nr));
                    $nr++;
                }                
                // Zonenbezogene Flags vorinitialisieren
                $tab['zone'][$sub]['pvp']   = false;
                $tab['zone'][$sub]['same']  = false;
                $tab['zone'][$sub]['other'] = false;
                $tab['zone'][$sub]['fly']   = false;
                
                // Zonenbezogene Daten ermitteln
                $tab['zone'][$sub]['name'] = str_replace("'","",getKeyValue("name",$line));
                $tab['zone'][$sub]['prio'] = getKeyValue("priority",$line);
                $tab['zone'][$sub]['desc'] = str_replace("'","",getKeyValue("string",$line));
                
                $tab['miss'] = true;
                
                if (stripos($line,"<subzone ") !== false)
                {
                    $tab['zone'][$sub]['type'] = "SUB";
                    $tab['zone'][$sub]['fly']  = false;
                }
                else
                {
                    $tab['zone'][$sub]['type'] = "FLY";
                    $tab['zone'][$sub]['fly']  = true;
                }
            }
            elseif (stripos($line,"<points_info ") !== false)
            {
                $tab['zone'][$sub]['bot'] = getKeyValue("bottom",$line);
                $tab['zone'][$sub]['top'] = getKeyValue("top",$line);
                $tab['zone'][$sub]['typ'] = getKeyValue("type",$line);
                
                $dat        = 0;
            }
            elseif (stripos($line,"<points_size=") !== false)
            {
                $tab['zone'][$sub]['size'] = getKeyValue("points_size",$line);
                $dat               = 0;
            }
            elseif (stripos($line,"<data ") !== false)
            {
                $tab['zone'][$sub]['data'][$dat]['x'] = getKeyValue("x",$line);
                $tab['zone'][$sub]['data'][$dat]['y'] = getKeyValue("y",$line);
                
                $dat++;
            }
            elseif (stripos($line,"</subzone>")     !== false
            ||      stripos($line,"</flying_zone>") !== false)
            {
                $sub   = "";
                $dat   = 0;
                $insub = false;
            }
        }
    }
    fclose($hdlext);
}
// ----------------------------------------------------------------------------
// Scannen Data-World-Zone-File
// liefert zusätzlich: PVP und LIMIT
// ----------------------------------------------------------------------------
function scanDataWorldZoneFile($z,&$tab)
{
    global $pathdata, $tabZones;
    
    $fileu16 = formFileName($pathdata."\\World\\client_world_".strtolower($tabZones[$z]['name']).".xml");
    
    if (!file_exists($fileu16))
        return;
    
    $fileext = convFileToUtf8($fileu16);
    $hdlext  = openInputFile($fileext);
    
    if (!$hdlext)
        return;
    
    $sub     = "";
    $dat     = 0;
    $insub   = false;
    $typsub  = "";
    $cntles  = 0;
    $posx    = "";
    $posy    = "";
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<limitarea>")      !== false   // LIMIT-Zone
        ||      stripos($line,"<pvpzone>")        !== false   // PVP-Zone
        ||      stripos($line,"<item_use_area>")  !== false)  // ITEM_USE-Zone
        {
            $insub  = true;
            
            if     (stripos($line,"<limitarea>")  !== false)
                $typsub = "LIMIT";
            elseif (stripos($line,"<pvpzone>")    !== false)
                $typsub = "PVP";
            else
                $typsub = "ITEM_USE";
        }
        elseif (stripos($line,"</limitarea>")     !== false
        ||      stripos($line,"</pvpzone>")       !== false
        ||      stripos($line,"</item_use_area>") !== false)
        {
            $insub  = false;
            $typsub = "";
        }   
        
        if ($insub)
        {
            if (stripos($line,"<name>")         !== false)
            {                
                $sub = str_replace("'","",strtoupper(getXmlValue("name",$line)));
                $nr  = 1;
                
                while (isset($tab['zone'][$sub]))
                {
                    $sub = str_replace("'","",strtoupper(getXmlValue("name",$line)."_".$nr));
                    $nr++;
                }                
                // Zonenbezogene Flags vorinitialisieren
                $tab['zone'][$sub]['pvp']      = false;
                $tab['zone'][$sub]['same']     = false;
                $tab['zone'][$sub]['other']    = false;
                $tab['zone'][$sub]['fly']      = false;
                $tab['zone'][$sub]['norecall'] = true;
                $tab['zone'][$sub]['noride']   = true;
                
                // Zonenbezogene Daten ermitteln
                $tab['zone'][$sub]['name'] = $sub;
                $tab['zone'][$sub]['prio'] = "0";
                $tab['zone'][$sub]['desc'] = $sub;
                $tab['zone'][$sub]['type'] = $typsub;
                $tab['zone'][$sub]['size'] = 0;
                
                $tab['world'] = true;
            }
            elseif (stripos($line,"<priority>")   !== false)
                $tab['zone'][$sub]['prio']     = getXmlValue("priority",$line);
            elseif (stripos($line,"<norecall>")   !== false)
                $tab['zone'][$sub]['norecall'] = getXmlValue("norecall",$line);
            elseif (stripos($line,"<noride>")     !== false)
                $tab['zone'][$sub]['noride']   = getXmlValue("noride",$line);
            elseif (stripos($line,"<type>")       !== false)
                $tab['zone'][$sub]['typ']      = getXmlValue("type",$line);
            elseif (stripos($line,"<x>")          !== false)
                $xpos = getXmlValue("x",$line);
            elseif (stripos($line,"<y>")          !== false)
                $ypos = getXmlValue("y",$line);
            elseif (stripos($line,"</data>")      !== false)
            {
                $tab['zone'][$sub]['data'][$dat]['x'] = $xpos;
                $tab['zone'][$sub]['data'][$dat]['y'] = $ypos;
                
                $xpos = $ypos = "";
                
                $dat++;
            }
            elseif (stripos($line,"<bottom>")      !== false)
                $tab['zone'][$sub]['bot'] = getXmlValue("bottom",$line);
            elseif (stripos($line,"<top>")         !== false)
                $tab['zone'][$sub]['top'] = getXmlValue("top",$line);
            elseif (stripos($line,"</points_info>") !== false)
            {
                $sub   = "";
                $dat   = 0;
                $insub = false;
            }
        }
    }
    fclose($hdlext); 
    unlink($fileext);
}
// ----------------------------------------------------------------------------
// Scannen Zone-Leveldata-File
// liefert einen Teil der Flags
// ----------------------------------------------------------------------------
function scanZoneLeveldataFile($z,&$tab)
{
    global $pathlevels, $tabZones;
    
    $fileext = formFileName($tabZones[$z]['pfad']."\\leveldata.xml");
    
    if (!file_exists($fileext))
        return;
        
    $hdlext  = openInputFile($fileext);
    $inmiss  = false;
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        
        if (stripos($line,"<missions>") !== false)
            $inmiss = true;
        elseif (stripos($line,"</missions>") !== false)
            $inmiss = false;
        
        if ($inmiss
        &&  stripos($line,"Mission")     === false
        &&  stripos($line,"LevelOption") === false
        &&  stripos($line,"Initialize")  === false
        &&  stripos($line,"LightMap")    === false)
        {       
            $tab['level'] = true;
            
            if     (stripos($line,"<fly ") !== false)
                $tab['lvfly']  .= true;
            elseif (stripos($line,"<ride ") !== false)
                $tab['ride']     = true;
            elseif (stripos($line,"<bindarea ") !== false)
            {
                if (getKeyValue("IsPossible",$line) != "0") 
                    $tab['bind'] = true;
            }
            elseif (stripos($line,"<ReCall ") !== false)
            {
                if (getKeyValue("IsPossible",$line) != "0") 
                    $tab['recall'] = true;
            }
            elseif (stripos($line,"<Glide ") !== false)
            {
                if (getKeyValue("IsPossible",$line) != "0") 
                    $tab['glide'] = true;
            }
            else
            {
                logLine("Unknown Keyword ".getXmlKey($line),$line);
            }
        }
    }
    fclose($hdlext);
}
// ----------------------------------------------------------------------------
// Scannen Zone-Files
// ----------------------------------------------------------------------------
function scanZoneFiles($z)
{    
    $tab            = array();
    $tab['miss']    = false;
    $tab['level']   = false;
    $tab['world']   = false;
    
    $tab['bind']    = false;
    $tab['recall']  = false;
    $tab['glide']   = false;   
    $tab['lvfly']   = false; // aus leveldata für fly_ride
    $tab['ride']    = false; 
    $tab['flyride'] = false;
    
    // nachfolgende s.o. je Zone
    // $tab['fly']  = false;    
    // $tab['pvp']  = false;
    // $tab['same'] = false;
    // $tab['other']= false;
    
    scanZoneMissionFile($z,$tab);
    scanZoneLeveldataFile($z,$tab);
    scanDataWorldZoneFile($z,$tab);
    
    return $tab;
}
// ----------------------------------------------------------------------------
// Zonen-Datei für die aktuelle Zone erzeugen
// ----------------------------------------------------------------------------
function writeZoneFile($z,&$tab)
{
    global $tabZones;
    
    $ret = true;
    
    if ($tabZones[$z]['w_id'] != "0")
        $fileout = "../outputs/parse_output/zones/zones_".$tabZones[$z]['w_id'].".xml";
    else
    {
        $fileout = "../outputs/parse_output/zones/noWorldId/zones_".$tabZones[$z]['name'].".xml";
        $ret     = false;
    }
        
    $hdlout  = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,'<zones>'."\n");
    
    // Zonen ausgeben
    
    // Sortieren der Zonen nach dem Namen!
    $tabSortx = array_keys($tab['zone']);
    $ind      = 0;
    $sub      = count($tabSortx);
    $tabSort  = array();
    
    for ($s=0;$s<$sub;$s++)
    {
        $xkey                = $tabSortx[$s];
        $tabSort[$s]['name'] = str_replace(" ","_",getZoneName($tab['zone'][$xkey]['desc'], $tab['zone'][$xkey]['name'], $tabZones[$z]['name']));
        $tabSort[$s]['nind'] = $tabSortx[$s];
    }
    unset($tabSortx);
    
    sort($tabSort);
    $sub = count($tabSort);
    
    logLine($tabZones[$z]['name'],$sub,$fileout);
    flush();    
    
    // Flags sind für alle Zonen dieser World identisch
    $namnr = 0;
    $namtx = "";
    $oname = "";
    
    for ($s=0;$s<$sub;$s++)
    {
        $x = $tabSort[$s]['nind'];
        
        // den Namen evtl. um eine lfdNr erweitern, wenn identisch
        if ($oname == $tabSort[$s]['name'])
        {
            $namnr++;
            $namtx = "_".$namnr;
        }
        else
        {
            $oname = $tabSort[$s]['name'];
            $namtx = "";
            $namnr = 0;
        }
            
        // Start ZONE
        $zname = $tabSort[$s]['name'].$namtx;
        $zprio = "";
        
        switch($tab['zone'][$x]['type'])
        {
            case "SUB":     
                $zname .= "_".$tabZones[$z]['w_id'];
                $zprio  = ' priority="'.$tab['zone'][$x]['prio'].'"';
                break;
            case "LIMIT": // ist sonst n icht eindeutig!
                $zname .= "_".$tabZones[$z]['w_id'];
                break;
            default:    
                break;
        }
        $lout = '    <zone mapid="'.$tabZones[$z]['w_id'].'" name="'.$zname.'" area_type="'.strtoupper($tab['zone'][$x]['typ']).
                '" zone_type="'.$tab['zone'][$x]['type'].'"'.$zprio.' flags="'.getFlagsZone($tab,$x).'">';
        fwrite($hdlout,$lout."\n");
        // fwrite($hdlout,showFlags($tab,$x)."\n");
        
        // Start POINTS
        $lout = '        <points bottom="'.getRoundPos($tab['zone'][$x]['bot'],5).'" top="'.getRoundPos($tab['zone'][$x]['top'],5).'">';
        fwrite($hdlout,$lout."\n");
        
        
        // Anzahl Points
        $dat  = count($tab['zone'][$x]['data']);
        
        for ($p=0;$p<$dat;$p++)
        {
            $lout = '            <point x="'.getRoundPos($tab['zone'][$x]['data'][$p]['x'],4).'" '.
                    'y="'.getRoundPos($tab['zone'][$x]['data'][$p]['y'],4).'"/>';
            fwrite($hdlout,$lout."\n");
        }
        fwrite($hdlout,'        </points>'."\n");
        fwrite($hdlout,'    </zone>'."\n");
    }
    
    // Nachspann ausgeben
    fwrite($hdlout,'</zones>'."\n");
    fclose($hdlout);
    
    return $ret;
}
// ----------------------------------------------------------------------------
// WorldZones-Datei ausgeben
// ----------------------------------------------------------------------------
function generWorldZonesFile()
{
    global $tabZones;

    $maxZones = count($tabZones);
    $cntgen   = 0;
    $cnterr   = 0;
    $sora     = "<font color=orange>";
    $eora     = "</font>";
    
    logHead("Generierung der Dateien: zones....xml");
    logLine($sora."Zonen-Name".$eora,$sora."Zonen".$eora,$sora."Ausgabedatei".$eora);
    logLine("<hr>","<hr>","<hr>");
    
    flush();
    
    for ($z=0;$z<$maxZones;$z++)
    {           
        $tabPoint = scanZoneFiles($z);
        
        if (($tabPoint['miss'] || $tabPoint['world']) && $tabPoint['level'])
        {            
            if (writeZoneFile($z,$tabPoint))
                $cntgen++;
            else
                $cnterr++;                     
        }
    }
    logLine("","");
    logLine("generierte Dateien",$cntgen);
    logLine("Dateien ohne mapid",$cnterr,"(siehe Pfad /noWorldId)");
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

$tabZones  = array();
$tabZNames = array();
$tabWNames = array();
$tabZoneId = array();

$starttime = microtime(true);

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
        makeTabWorldIds();
        scanZoneNamesSvn();
        scanClientZoneNames();
        scanAllClientZones();
        generWorldZonesFile();
        
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