<html>
<head>
  <title>
    Pets - Erzeugen pet_....xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

$submit   = isset($_GET['submit'])   ? "J"               : "N";

if (!file_exists("../outputs/parse_output/pets"))
    mkdir("../outputs/parse_output/pets");
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Pets-Dateien</div>
  <div class="hinweis" id="hinw">
  Erzeugen der pet_....xml-Dateien (pet_doping.xml, pets.xml).
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genPets.php" target="_self">
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
// Funktionstyp umsetzen
// ----------------------------------------------------------------------------
function getFunctionType($type)
{    
    switch(strtoupper($type))
    {
        case "DOPING"   : return "DOPING";
        case "LOOTING"  : return "LOOT";
        case "FEEDING"  : return "FOOD";
        case "WAREHOUSE": return "WAREHOUSE";
        case "BAG"      : return "BAG";
        case "WING"     : return "WING";
        default:          return "??? ".$type;
    }
}
// ----------------------------------------------------------------------------
// gerundeten Wert zurückgeben 
// ----------------------------------------------------------------------------
function getRndValue($val)
{
    $ret = floatval($val);
    if (stripos($ret,".") === false)  $ret .= ".0";
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Funcpet-Name zurückgeben
// ----------------------------------------------------------------------------
function getFuncpetName($name)
{
    global $tabFuncpet;
    
    $key = strtoupper($name);
    
    if (isset($tabFuncpet[$key]))
        return $tabFuncpet[$key]['body'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// Funcpet-NameId zurückgeben
// ----------------------------------------------------------------------------
function getFuncpetNameId($name)
{
    global $tabFuncpet;
    
    $key = strtoupper($name);
    
    if (isset($tabFuncpet[$key]))
        return $tabFuncpet[$key]['id'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// FunktionsId zurückgeben
// ----------------------------------------------------------------------------
function getFunctionId($name)
{
    global $tabFuncname;
    
    $key = strtoupper($name);
    
    if (isset($tabFuncname[$key]))
        return $tabFuncname[$key]['id'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// FunktionsSlots zurückgeben
// ----------------------------------------------------------------------------
function getFunctionSlots($name)
{
    global $tabFuncname;
    
    $key = strtoupper($name);
    
    if (isset($tabFuncname[$key]))
        return $tabFuncname[$key]['slot'];
    else
        return "";
}
// ----------------------------------------------------------------------------
// Zeile für PetFunction zurückgeben
// ----------------------------------------------------------------------------
function getPetFunctionLine($type,$func)
{
    $ret = "";
    
    if ($type == "" || $func == "") return "";
    
    // MERCHANT und BUFF sind nicht in der EMU realisiert!
    if (strtoupper($type) == "MERCHANT" || strtoupper($type) == "BUFF") return "";
    
    $ret  = '        <petfunction id="'.getFunctionId($func).'"';
    $ret .= ' type="'.getFunctionType($type).'"';
    
    if (strtoupper($type) == "WAREHOUSE")
    {
        $slot = getFunctionSlots($func);
        if ($slot != "" && $slot != "0")
            $ret .= ' slots="'.$slot.'"';
    }
    $ret .= '/>';
    
    return $ret;
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Strings zu den Funcpets aus PS einlesen
// ----------------------------------------------------------------------------
function scanPsFuncpetStrings()
{
    global $pathstring, $tabFuncpet;
    
    $filestr = formFileName($pathstring."\\client_strings_funcpet.xml");
    $hdlstr  = openInputFile($filestr);
    
    logHead("Scanne PS-String-Datei");
    logLine("Eingabedatei",$filestr);
    
    $cntles = 0;
    $cntstr = 0;
    
    $id = $name = $body = "";
    
    while (!feof($hdlstr))
    {
        $line = rtrim(fgets($hdlstr));
        $cntles++;
        
        if     (stripos($line,"<id>")   !== false) $id   = getXmlValue("id",$line);
        elseif (stripos($line,"<name>") !== false) $name = strtoupper(getXmlValue("name",$line));
        elseif (stripos($line,"<body>") !== false) $body = getXmlValue("body",$line);
        elseif (stripos($line,"</string>") !== false)
        {
            $tabFuncpet[$name]['id']   = $id;
            $tabFuncpet[$name]['body'] = $body;
            $cntstr++;
            
            $id = $name = $body = "";
        }
    }
    fclose($hdlstr);
    
    logLine("Zeilen eingelesen",$cntles);
    logLine("Anzahl Strings gefunden",$cntstr);
}
// ----------------------------------------------------------------------------
// Client-Toypet-Dateien scannen
// ----------------------------------------------------------------------------
function scanClientToypetFiles()
{
    global $pathdata, $tabFuncname;
    
    $tabFiles = array( "toypet_looting.xml",
                       "toypet_doping.xml",
                       "toypet_warehouse.xml",
                       "toypet_feed.xml",
                       "toypet_item.xml"
                     );
    $maxFiles = count($tabFiles);
    
    logHead("Scanne die Client-Toypet-Function-Dateien");    
    
    for ($f=0;$f<$maxFiles;$f++)
    {
        $fileu16 = formFileName($pathdata."\\func_pet\\".$tabFiles[$f]);
        $fileext = convFileToUtf8($fileu16);
        $hdlext  = openInputFile($fileext);
        
        logSubHead("Scanne Datei $fileext");
        logLine("Eingabedatei UTF16",$fileu16);
        logLine("Eingabedatei UTF8",$fileext);
        
        $cntles = 0;
        $cntstr = 0;
        
        $id = $name = $slots = "";
        
        while (!feof($hdlext))
        {
            $line = rtrim(fgets($hdlext));
            $cntles++;
            
            if     (stripos($line,"<id>") !== false) $id = getXmlValue("id",$line);
            elseif (stripos($line,"<name>") !== false) $name = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"<warehouse_slot_count>") !== false) $slots = getXmlValue("warehouse_slot_count",$line);
            elseif (stripos($line,"</client_toypet") !== false)
            {
                if ($id != "" && $name != "")
                {
                    $tabFuncname[$name]['id']   = $id;
                    $tabFuncname[$name]['slot'] = $slots;
                    $cntstr++;
                }
                    
                $id = $name = "";
            }
        }
        fclose($hdlext);
        unlink($fileext);
        
        logLine("Zeilen eingelesen",$cntles);
        logLine("Anzahl Strings gefunden",$cntstr);
    }
    logLine("Anzahl gefundene Strings",count($tabFuncname));
}
// ----------------------------------------------------------------------------
// PetDoping-Datei ausgeben
// ----------------------------------------------------------------------------
function generPetDopingFile()
{
    global $pathdata;
    
    logHead("Generierung der Datei pet_doping.xml");
    
    $fileu16 = formFileName($pathdata."\\func_pet\\toypet_doping.xml");
    $fileext = convFileToUtf8($fileu16);
    $fileout = "../outputs/parse_output/pets/pet_doping.xml";        
    $hdlext  = openInputFile($fileext);
    
    if (!$hdlext)
    {
        logLine("Fehler openInputFile",$fileext);
        return;
    }
    
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8 ",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $hdlout = openOutputFile($fileout);
    
    $cntles = 0;
    $cntpet = 0;
    $cntout = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,'<dopings xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="pet_doping.xsd">'."\n");
    $cntout += 2;    
            
    flush();
    
    $id = $drink = $food = $scroll = "";
    
    while (!feof($hdlext))
    {   
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<id>")                !== false)
            $id     = getXmlValue("id",$line);
        elseif (stripos($line,"<use_doping_drink>")  !== false)
            $drink  = strtolower(getXmlValue("use_doping_drink",$line));
        elseif (stripos($line,"<use_doping_food>")   !== false)
            $food   = strtolower(getXmlValue("use_doping_food",$line));
        elseif (stripos($line,"<use_doping_scroll>") !== false)
            $scroll = getXmlValue("use_doping_scroll",$line);
        elseif (stripos($line,"</client_toypet_doping>") !== false)
        {
            fwrite($hdlout,'    <doping id="'.$id.'" usedrink="'.$drink.'" '.
                           'usefood="'.$food.'" usescroll="'.$scroll.'"/>'."\n");
            $cntout++;
            $cntpet++;
            
            $id = $drink = $food = $scroll = "";
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</dopings>");
    $cntout++;
    
    fclose($hdlext);
    fclose($hdlout);
    unlink($fileext);
    
    logLine("Zeilen eingelesen  ",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
    logLine("Anzahl Pets gefunden",$cntpet);
}
// ----------------------------------------------------------------------------
// PetFeed-Datei erzeugen
// ----------------------------------------------------------------------------
function generPetFeedFile()
{
    logHead("Generierung der Datei pet_feed.xml");
    
    logLine("HINWEIS","*** KEINE Informationen zum Generieren gefunden / erhalten ***");
}
// ----------------------------------------------------------------------------
// Pets-Datei erzeugen
// ----------------------------------------------------------------------------
function generPetsFile()
{
    global $pathdata;
    
    logHead("Generierung der Datei pets.xml");
    
    $fileu16 = formFileName($pathdata."\\func_pet\\toypets.xml");
    $fileext = convFileToUtf8($fileu16);
    $fileout = "../outputs/parse_output/pets/pets.xml";
    $hdlext  = openInputFile($fileext);
    
    if (!$hdlext)
    {
        logLine("Fehler openInputFile",$fileext);
        return;
    }
    
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $hdlout = openOutputFile($fileout); 
            
    $cntles = 0;
    $cntout = 0;
    $cntpet = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,'<pets xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="pets.xsd">'."\n");
    $cntout += 2;  
        
    $id   = $desc = $type1 = $func1 = $type2 = $func2 = $reward = 
    $wing = $bag  = $react = $scale = $alti  = $walk  = $run    = ""; 
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++; 

        if     (stripos($line,"<id>") !== false)
            $id    = getXmlValue("id",$line);
        elseif (stripos($line,"<desc>") !== false)
            $desc  = getXmlValue("desc",$line);
        elseif (stripos($line,"<func_type1>") !== false)
            $type1 = getXmlValue("func_type1",$line);
        elseif (stripos($line,"<func_type_name1>") !== false)
            $func1 = getXmlValue("func_type_name1",$line);
        elseif (stripos($line,"<func_type2>") !== false)
            $type2 = getXmlValue("func_type2",$line);
        elseif (stripos($line,"<func_type_name2>") !== false)
            $func2 = getXmlValue("func_type_name2",$line);
        elseif (stripos($line,"<pet_condition_reward>") !== false)
            $reward= getXmlValue("pet_condition_reward",$line);
        elseif (stripos($line,"<wing>") !== false)
            $wing  = getXmlValue("wing",$line);
        elseif (stripos($line,"<bag>") !== false)
            $bag   = getXmlValue("bag",$line);
        elseif (stripos($line,"<combat_reaction>") !== false)
            $react = getXmlValue("combat_reaction",$line);
        elseif (stripos($line,"<scale>") !== false)
            $scale = getXmlValue("scale",$line);
        elseif (stripos($line,"<altitude>") !== false)
            $alti  = getXmlValue("altitude",$line);
        elseif (stripos($line,"<art_org_move_speed_normal_walk>") !== false)
            $walk  = getXmlValue("art_org_move_speed_normal_walk",$line);
        elseif (stripos($line,"<art_org_speed_normal_run>") !== false)
            $run   = getXmlValue("art_org_speed_normal_run",$line);
        elseif (stripos($line,"</client_toypet>") !== false)
        {
            $name   = getFuncpetName($desc);
            $nameid = getFuncpetNameId($desc);
            $cntpet++;
            
            $lout = '    <pet id="'.$id.'" name="'.$name.'" nameid="'.$nameid.'" '.
                    'condition_reward="'.getClientItemId($reward).'">';
            fwrite($hdlout,$lout."\n");
            $cntout++;
            
            $lout = getPetFunctionLine("BAG",$bag);
            if ($lout != "")
            {
                fwrite($hdlout,$lout."\n");
                $cntout++;
            }
            $lout = getPetFunctionLine($type1,$func1);
            if ($lout != "")
            {
                fwrite($hdlout,$lout."\n");
                $cntout++;
            }
            $lout = getPetFunctionLine($type2,$func2);
            if ($lout != "")
            {
                fwrite($hdlout,$lout."\n");
                $cntout++;
            }
            $lout = getPetFunctionLine("WING",$wing);
            if ($lout != "")
            {
                fwrite($hdlout,$lout."\n");
                $cntout++;
            }
            
            $lout  = '        <petstats';
            if ($react != "")
                $lout .= ' reaction="'.$react.'"';
            $lout .= ' run_speed="'.getRndValue($run).'" '.
                     'walk_speed="'.getRndValue($walk).'" height="'.getRndValue($scale / 100).'"';
                     
            if ($alti != "" && $alti > 0)
                $lout .= ' altitude="'.getRndValue($alti).'"';
            $lout .= '/>';
            
            fwrite($hdlout,$lout."\n");
            fwrite($hdlout,'    </pet>'."\n");
            $cntout += 2;
        
            $id   = $desc = $type1 = $func1 = $type2 = $func2 = $reward = 
            $wing = $bag  = $react = $scale = $alti  = $walk  = $run    = "";
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</pets>");
    $cntout++;
    
    fclose($hdlext);
    fclose($hdlout);
    unlink($fileext);
    
    logLine("Zeilen eingelesen",$cntles);
    logLine("Zeilen ausgegeben",$cntout);
    logLine("Anzahl Pets gefunden",$cntpet);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_getautonameids.php");
include("includes/auto_inc_item_infos.php");
include("includes/auto_inc_npc_infos.php");

$starttime   = microtime(true);
$tabFuncpet  = array();
$tabFuncname = array();

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
        scanPsFuncpetStrings();
        scanClientToypetFiles();
        
        generPetDopingFile();
        generPetFeedFile();
        generPetsFile();
        
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