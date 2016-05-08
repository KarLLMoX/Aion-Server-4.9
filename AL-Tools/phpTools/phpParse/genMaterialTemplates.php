<html>
<head>
  <title>
    MaterialTemplates - Erzeugen material_templates.xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

$submit   = isset($_GET['submit'])   ? "J"               : "N";

if (!file_exists("../outputs/parse_output/mesh_materials"))
    mkdir("../outputs/parse_output/mesh_materials");
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Material-Templates-Datei</div>
  <div class="hinweis" id="hinw">
  Erzeugen der material_templates.xml-Datei.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genMaterialTemplates.php" target="_self">
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
// gerundeten Wert für TIME = FREQUENCY zurückgeben 
// ----------------------------------------------------------------------------
function getTimeValue($time)
{
    $ret = round(floatval($time),1);
    if (stripos($ret,".") === false)  $ret .= ".0";
    
    return $ret;
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// MaterialTemplates-Datei ausgeben
// ----------------------------------------------------------------------------
function generMaterialTemplatesFile()
{
    global $pathdata;
    
    $fileu16 = formFileName($pathdata."\\Material\\materials.xml");
    $fileout = "../outputs/parse_output/mesh_materials/material_templates.xml";
    
    $fileext = convFileToUtf8($fileu16);
    logHead("Generierung der Datei");
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $cntles = 0;
    $cntout = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,'<material_templates>'."\n");
    $cntout += 2;
    
    $lines = file($fileext);
    $domax = count($lines);
        
    $id   = $name = $level = $time = $targ = $sunny = $night = $obst = "";
    $doid = true;
    
    for ($l=0;$l<$domax;$l++)
    {
        $line = rtrim($lines[$l]);
        $cntles++;
        
        if (stripos($line,"<?xml") === false
        &&  stripos($line,"<path_group>") === false)
        {     
            // Start FlyPath
            if     (stripos($line,"<id>")             !== false)
                $id    = getXmlValue("id",$line);
            elseif (stripos($line,"<skill_name")      !== false
            ||      stripos($line,"</material>")      !== false)
            {
                // wenn alle notwendigen Angaben vorhanden sind, dann Zeilen ausgeben
                if ($name != "" && $level != "" && $time != "")
                {
                    if ($doid)
                    {
                        fwrite($hdlout,'    <material id="'.$id.'">'."\n");
                        $cntout++;
                        $doid = false;
                    }
                    
                    $skill = getSkillNameId($name);
                    $time  = getTimeValue($time);
                    
                    switch(strtoupper($targ))
                    {
                        case "NPC":          $targ = "NPC";             break;
                        case "PCWITHSUMMON": $targ = "PLAYER_WITH_PET"; break;
                        case "PC":           $targ = "PLAYER";          break;
                        default:             $targ = "PLAYER";          break;
                    }
                    
                    $xtime = "";
                    
                    if ($sunny != "" || $night != "")
                    {
                        // wenn beide Angaben vorhanden sind, dann ignorieren
                        if ($sunny != "" && $night == "") $xtime = ' time="DAY"';
                        if ($night != "" && $sunny == "") $xtime = ' time="NIGHT"';
                    }
                    
                    fwrite($hdlout,'        <skill id="'.$skill.'" level="'.$level.'" target="'.$targ.'" frequency="'.$time.'"'.$xtime.'/>'."\n");
                    $cntout++;
                    
                    $name = $level = $time = $targ = "";
                }
                // weiterer Skill
                if (stripos($line,"<skill_name") !== false)
                {
                    $xml   = getXmlKey($line);
                    $name  = getXmlValue($xml,$line);
                }
                else
                { 
                    // Ende zu diesem Material
                    if (!$doid)
                    {
                        fwrite($hdlout,'    </material>'."\n");
                        $cntout++;
                    }
                    
                    $id   = $name = $level = $time = $targ = $sunny = $night = $obst = "";
                    $doid = true;        
                }
            }
            elseif (stripos($line,"<skill_level")     !== false)
            {
                $xml   = getXmlKey($line);
                $level = getXmlValue($xml,$line);
            }
            elseif (stripos($line,"<skill_timer")     !== false)
            {
                $xml   = getXmlKey($line);
                $time  = getXmlValue($xml,$line);
            }
            elseif (stripos($line,"<skill_target")    !== false)
            {
                $xml   = getXmlKey($line);
                $targ  = getXmlValue($xml,$line);
            }
            elseif (stripos($line,"<sunny>")          !== false)
                $sunny = getXmlValue("sunny",$line);
            elseif (stripos($line,"<night>")          !== false)
                $night = getXmlValue("night",$line);
            elseif (stripos($line,"<obstacle_level>") !== false)
            {
                $obst = getXmlValue("obstacle_level",$line);
            
                fwrite($hdlout,'    <material id="'.$id.'" skill_obstacle="'.$obst.'"/>'."\n");
                $cntout++;
                
                $id = $obst = "";
            }
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</material_templates>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen Eingabedatei",$domax);
    logLine("Zeilen verarbeitet ",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/auto_inc_skill_names.php");
include("includes/inc_getautonameids.php");

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
        generMaterialTemplatesFile();
        
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