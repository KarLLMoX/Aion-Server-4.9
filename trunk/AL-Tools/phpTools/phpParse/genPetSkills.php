<html>
<head>
  <title>
    PetSkills - Erzeugen pet_skills.xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

$submit   = isset($_GET['submit'])   ? "J"               : "N";

if (!file_exists("../outputs/parse_output/pet_skills"))
    mkdir("../outputs/parse_output/pet_skills");
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Pet-Skills-Datei</div>
  <div class="hinweis" id="hinw">
  Erzeugen der pet_skills.xml-Datei.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genPetSkills.php" target="_self">
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
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// ClientUltraSkills scannen
// ----------------------------------------------------------------------------
function scanUltraSkills()
{
    global $pathdata, $tabUltra;    
    
    $fileu16 = formFileName($pathdata."\\skills\\client_ultra_skills.xml");
    
    $fileext = convFileToUtf8($fileu16);
    
    logHead("Scanne die UltraSkills aus dem Client");
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    
    $cntles = 0;
    $akttab = 0;
    
    $order  = $pname = $ultra = "";
    
    $hdlext = openInputFile($fileext);
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<ultra_skill>")         !== false)
            $ultra = getXmlValue("ultra_skill",$line);
        elseif (stripos($line,"<pet_name>")            !== false)
            $pname = getXmlValue("pet_name",$line);
        elseif (stripos($line,"<order_skill>")         !== false)
            $order = getXmlValue("order_skill",$line);
        elseif (stripos($line,"</client_ultra_skill>") !== false)
        {            
            $tabUltra[$akttab]['order']  = $order;
            $tabUltra[$akttab]['ultra']  = $ultra;
            $tabUltra[$akttab]['pname']  = $pname;
            $akttab++;
            
            $order = $pname = $ultra = "";
        }
    }
    fclose($hdlext);
    
    unlink($fileext);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl Pets gefunden",count($tabUltra));
}
// ----------------------------------------------------------------------------
// PetSkills-Datei ausgeben
// ----------------------------------------------------------------------------
function generPetSkillsFile()
{
    global $pathdata, $tabUltra;
    
    $fileout = "../outputs/parse_output/pet_skills/pet_skills.xml";
    
    logHead("Generierung der Datei");
    logLine("Ausgabedatei",$fileout);
    
    $cntout = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="utf-8"?>'."\n");
    fwrite($hdlout,'<pet_skill_templates>'."\n");
    $cntout += 2;
    
    $domax = count($tabUltra);
            
    flush();
    
    for ($l=0;$l<$domax;$l++)
    {   
        $pet  = getNpcIdNameTab($tabUltra[$l]['pname']);
        
        $lout = '    <pet_skill'.
                ' order_skill="'.getSkillNameId($tabUltra[$l]['order']).'"'.
                ' pet_id="'.$pet['npcid'].'"'.
                ' skill_id="'.getSkillNameId($tabUltra[$l]['ultra']).'"/>';
        
        fwrite($hdlout,$lout."\n");
        $cntout++;
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</pet_skill_templates>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen ausgegeben  ",$cntout);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_getautonameids.php");
include("includes/auto_inc_skill_names.php");
include("includes/auto_inc_npc_infos.php");

$starttime = microtime(true);
$tabUltra  = array();

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
        scanUltraSkills();
        generPetSkillsFile();
        
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