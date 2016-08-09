<html>
<head>
  <title>
    Skills - Erzeugen skill....xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

$submit   = isset($_GET['submit'])   ? "J"               : "N";

if (!file_exists("../outputs/parse_output/skills"))
    mkdir("../outputs/parse_output/skills");
if (!file_exists("../outputs/parse_output/skill_tree"))
    mkdir("../outputs/parse_output/skill_tree"); 
    
$docomp   = isset($_GET['compare']) ? $_GET['compare'] : "";
$chkarrow = isset($_GET['charrow']) ? $_GET['charrow'] : "";
$chkrobot = isset($_GET['chrobot']) ? $_GET['chrobot'] : "";

$doCompareFile = $docomp    == "DO" ? true : false;  
$doCheckArrow  = $chkarrow  == "DO" ? true : false;
$doCheckRobot  = $chkrobot  == "DO" ? true : false;
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Skill-Dateien</div>
  <div class="hinweis" id="hinw">
  Erzeugen der skill....xml-Dateien.<br>
  (skill_charge.xml, skill_templates.xml und skill_tree.xml)
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genSkills.php" target="_self">
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
// Value für Feld zurückgeben
// ----------------------------------------------------------------------------
function getTabValue($key,$fname,$deflt)
{
    global $tabcskill;
    
    if (isset($tabcskill[$key][$fname]))
        return $tabcskill[$key][$fname];
    else
        return $deflt;
}
// ----------------------------------------------------------------------------
// spezielle Text-Manipulationen überprüfen
// ----------------------------------------------------------------------------
function checkSpecialText($fname,$fvalue)
{
    // Tabelle Grossbuchstaben-Rückgabe
    // enthält die EMU-Feldnamen
    $gtab = array( // Skill-header
                   "skilltype", "skillsubtype", "tslot", "dispel_category",
                   "activation", "counter_skill",
                   // properties
                   "first_target", "target_relation", "target_type",
                   "target_status", "direction", "target_species"
                 );
    $gmax = count($gtab);
    
    // Prüfen auf Rückgabe in Grossbuchstaben
    for ($f=0;$f<$gmax;$f++)
    {
        if ($gtab[$f] == $fname)
        {
            $fvalue = strtoupper($fvalue);
            $f      = $gmax;
        }
    }
    
    // spezielle Feldbehandlungen / -umwandlungen
    switch($fname)
    {
        // SKILL-HEADER
        case "cooldown":   // Zeit muss durch 100 geteilt werden
            if ($fvalue > 0)
                return $fvalue / 100;
            else
                return $fvalue;
        case "dispel_category":
            if ($fvalue == "DEBUFFMEN")     return "DEBUFF_MENTAL";
            if ($fvalue == "DEBUFFPHY")     return "DEBUFF_PHYSICAL";
            if ($fvalue == "NPC_DEBUFFPHY") return "NPC_DEBUFF_PHYSICAL";
            return $fvalue;
        case "cancel_rate":
            if ($fvalue == "0")             return "";
        case "tslot":
            if ($fvalue == "SPECIAL2")      return "SPEC2";
            if ($fvalue == "SPECIAL")       return "SPEC";
            return $fvalue;
        // PROPERTIES
        case "awr":
            if ($fvalue == "1")             return "true";
            return "";  
        case "target_species":
            if ($fvalue == "ALL")           return ""; 
            return $fvalue;     
        case "first_target_range":
            if ($fvalue == "0")             return "";
            return $fvalue;            
        // SONSTIGE        keine Veränderungen!        
        default: break;
    }
    
    return $fvalue;
}
// ----------------------------------------------------------------------------
// Feld-Text aus Tabelle zurückgeben
// Params:  $key        Key in der Tabelle ( = id)
//          $cxml       Xml-Tag-Name Client
//          $fname      Ausgabename für Feld
//          $deflt      Default-Wert, wenn nicht vorhanden
// ----------------------------------------------------------------------------
function getTabFieldText($key,$cxml,$fname,$deflt)
{
    global $tabcskill;
    
    $tmp = "";
    
    // ? = lfd.Nummer einsetzen!
    if (stripos($cxml,"?") !== false)
    {
        for ($i=1;$i<6;$i++)
        {
            $nfld = str_replace("?",$i,$cxml);
            
            if (isset($tabcskill[$key][$nfld]))
                $tmp .= " ".$tabcskill[$key][$nfld];
        }
        $tmp = trim($tmp);
    }
    else
    {
        if (isset($tabcskill[$key][$cxml]))
            $tmp = $tabcskill[$key][$cxml];
    }
    if ($tmp == "" && $deflt != "") $tmp = $deflt;
    
    $tmp = checkSpecialText($fname,$tmp);
        
    if ($tmp != "")
        return ' '.$fname.'="'.$tmp.'"';
    else
        return "";
}
// ----------------------------------------------------------------------------
// FeldText zurückgeben
// ----------------------------------------------------------------------------
function getFieldText($fname,$fvalue)
{
    if ($fvalue != "" && $fvalue != "?")
    {
        return ' '.$fname.'="'.$fvalue.'"';
    }
    else
        return "";
}
// ----------------------------------------------------------------------------
// Stack-Name zurückgeben
// ----------------------------------------------------------------------------
function getStackName($key)
{
    global $tabcskill;
    
    $ret = strtoupper($tabcskill[$key]['desc']);
    
    if (substr($ret,0,4) == "STR_")
        $ret = substr($ret,4);
    
    // evtl. den letzten Namensteil abschneiden!
    $pos = strripos($ret,"_");
    $txt = substr($ret,$pos);
      
    if (strlen($txt) == 3)
            $ret = substr($ret,0,$pos); 
    else
    {
        $spec = substr($ret,-3,3);
        
        if ($spec == "1_1" || $spec == "1_2" || $spec == "1_3" || $spec == "1_4" || $spec == "1_5")
            $ret = substr($ret,0,strlen($ret) - 2);
    }
    /*
       durch die obige , allgemeine Routine wird immer der letzte Namensteil abgeschnitten!
       wenn das zu Allgemein ist, dann müsste die nachfolgende SWITCH-Anweisung wieder aktiviert werden
       
    // evtl. die letzten 3 Stellen am Ende abschneiden
    switch(substr($ret,-3,3))
    {
        //           alle _Gn
        case "_G1":  
        case "_G2":
        case "_G3":
        case "_G4":
        case "_G5":  
        case "_G6": 
        case "_G7": 
        case "_G8":   
        case "_G9":  
        //           alle _nn        
        case "_01": 
        case "_02":
        case "_03":
        case "_04":
        case "_05":
        case "_06":
        case "_07":
        case "_08":
        case "_09":
        case "_10":
        case "_20":
        case "_30":
        case "_35":
        case "_40":
        case "_50":  
        case "_60":
        case "_70":
        case "_80":
        case "_90":
        //           sonstige Kombinationen
        case "_AE":
        case "_AN":
        case "_BL":
        case "_BT":
        case "_CR":
        case "_D3":
        case "_HP":
        case "_KD":
        case "_LC":
        case "_LF":
        case "_LH":
        case "_LR": 
        case "_MO":
        case "_MP":
        case "_MU":
        case "_NA":
        case "_NR":
        case "_OD":
        case "_PO":
        case "_RF":
        case "_RH":
        case "_SP":
        case "_ST":
        case "_TH":
            $ret = substr($ret,0,strlen($ret) - 3);
            break;
        //           1_n wird zu 1
        case "1_1":
        case "1_2":
        case "1_3":
        case "1_4":
        case "1_5":
            $ret = substr($ret,0,strlen($ret) - 2);
            break;
        default:
            break;
    } 
    */
    
    if ($ret != "")
        return ' stack="'.$ret.'"';
    else
        return "";
}
// ----------------------------------------------------------------------------
// Skill-Name zurückgeben
// ----------------------------------------------------------------------------
function getIntSkillName($desc)
{
    global $tabSNames;
    
    $key = strtoupper($desc);
     
    if (isset($tabSNames[$key]))
        return $tabSNames[$key]['body'];
    else
        return "???";
}
// ----------------------------------------------------------------------------
// Skill-Name-ID zurückgeben
// ----------------------------------------------------------------------------
function getIntSkillNameId($desc)
{
    global $tabSNames;
    
    $key = strtoupper($desc);
    
    if (isset($tabSNames[$key]))
        return $tabSNames[$key]['id'];
    else
    {
        $key = str_replace("STR_","",$key);
        
        if (isset($tabSNames[$key]))
            return $tabSNames[$key]['id'];
        else
            return "???";
    }
}
// ----------------------------------------------------------------------------
// Effekt-Skill-Name-ID zurückgeben
// ----------------------------------------------------------------------------
function getRefSkillNameId($tkey,$sname)
{
    global $tabrskill;
    
    $key = strtoupper($sname);
    $org = $key;
    
    // direkte Suche
    if (isset($tabrskill[$key]))
        return $tabrskill[$key];
        
    // direkte Suche mit bekannten Erweiterungen
    if     ($org == "SIMPLEMOVEBACK"
    ||      $org == "SPIN"
    ||      $org == "STAGGER"
    ||      $org == "STUMBLE")
    {
        $key = "NORMALATTACK_".$org;
        
        if (isset($tabrskill[$key]))
            return $tabrskill[$key];
    }
    elseif ($org == "NSTUMBLE"
    ||      $org == "NSTAGGER"
    ||      $org == "NPULLED" )
    {
        $key = "NEVADE_".substr($org,1);
        
        if (isset($tabrskill[$key]))
            return $tabrskill[$key];
    }
    elseif ($org == "STAGGER2"
    ||      $org == "STAGGER3"
    ||      $org == "STAGGER4"
    ||      $org == "STUMBLE2"
    ||      $org == "STUMBLE3"
    ||      $org == "STUMBLE4" )
    {
        $key = "NORMALATTACK_".substr($org,0,7)."_".substr($org,-1,1);
        
        if (isset($tabrskill[$key]))
            return $tabrskill[$key];
    }
    //
    //  Text-Ersetzungen 1. Versuch
    //
    $len = strlen($org);
    
    // einige Angaben hinten abschneiden!
    $key = $org;
    $key = (substr($org,-2,2) == "_N")     ? substr($key,0,$len - 2) : $key;
    $key = (substr($org,-4,4) == "_NPC")   ? substr($key,0,$len - 4) : $key;
    
    // einige Angaben entfernen
    $key = str_replace("PR_N_DARK_"          ,"",$key);
    $key = str_replace("PR_N_LIGHT_"         ,"",$key);
    $key = str_replace("PR_DARK_"            ,"",$key);
    $key = str_replace("PR_LIGHT_"           ,"",$key);
    $key = str_replace("RA_DARK_"            ,"",$key);
    $key = str_replace("RA_LIGHT_"           ,"",$key);
    $key = str_replace("STR_"                ,"",$key);
    $key = str_replace("ABYSS_RANKERSKILL_L_","",$key);
    $key = str_replace("ABYSS_RANKERSKILL_D_","",$key);
    
    // einige Angaben ersetzen
    $key = str_replace("HOLYSILIKA_CRYSTAL_","HOLYSERVENT_",$key);
    $key = str_replace("HOLYSILIKA_"        ,"HOLYSERVENT_",$key);
    $key = str_replace("_DARK_TORNADO_"     ,"_SA_TORNADO_",$key);
    $key = str_replace("_LIGHT_TORNADO_"    ,"_SA_TORNADO_",$key);
    $key = str_replace("_SKILL_NPC01"       ,"_SKILL_NPC_AREADAMAGE",$key);
    $key = str_replace("_ESCAPEROBOT_G1_D"  ,"_ESCAPEROBOT_G1_SYS" ,$key);
    $key = str_replace("_ESCAPEROBOT_G1_L"  ,"_ESCAPEROBOT_G1_SYS" ,$key);
    if (isset($tabrskill[$key]))
        return $tabrskill[$key];
    //
    // Text-Ersetzungen 2. Versuch  
    // (für z.B. PR_N_LIGHT_HOLYSERVENT_G6_NPC)
    //
    $key = $org;
    $key = (substr($org,-2,2) == "_N")     ? substr($key,0,$len - 2) : $key;
    $key = (substr($org,-4,4) == "_NPC")   ? substr($key,0,$len - 4) : $key;
    
    $key = str_replace("_LIGHT_","_",$key);
    $key = str_replace("_DARK_" ,"_",$key);
    
    if (isset($tabrskill[$key]))
        return $tabrskill[$key];
    
    logLine("<font color=yellow>- RefSkillId nicht gefunden",$tkey.' = '.$org);
    
    return "?";
}
// ----------------------------------------------------------------------------
// PenaltySkillId zurückgeben
// ----------------------------------------------------------------------------
function getPenaltySkillId($key)
{
    global $tabcskill;
    
    if (isset($tabcskill[$key]['penalty_skill_succ']))
    {
        $pskill = getSkillNameId($tabcskill[$key]['penalty_skill_succ']);
        
        if ($pskill)
            return ' penalty_skill_id="'.$pskill.'"';
    }
    return "";
}
// ----------------------------------------------------------------------------
// StanceStatus zurückgeben
// ----------------------------------------------------------------------------
function getStanceStatus($key)
{
    global $tabcskill;
    
    if (isset($tabcskill[$key]['change_stance']))
        return ' stance="true"';
    else
        return "";
}
// ----------------------------------------------------------------------------
// AvatarStatus zurückgeben
// ----------------------------------------------------------------------------
function getAvatarStatus($key)
{
    global $tabcskill;
    
    if (isset($tabcskill[$key]['desc']))
    {
        $iname = $tabcskill[$key]['desc'];
        
        if (stripos($iname,"_Avatar_") !== false)
            return ' avatar="true"';
    }
    return "";
}
// ----------------------------------------------------------------------------
// GroundStatus zurückgeben
// ----------------------------------------------------------------------------
function getGroundStatus($key)
{
    global $tabcskill;
    
    if (isset($tabcskill[$key]['target_flying_restriction']))
    {
        $xname = $tabcskill[$key]['target_flying_restriction'];
        
        if (strtoupper($xname) == "GROUND")
            return ' ground="true"';
    }
    return "";
}
// ----------------------------------------------------------------------------
// NoRemoveAtDieStatus zurückgeben
// ----------------------------------------------------------------------------
function getNoremoveStatus($key)
{
    global $tabcskill;
    
    if (isset($tabcskill[$key]['no_remove_at_die']))
    {
        $xname = $tabcskill[$key]['no_remove_at_die'];
        
        if ($xname == "1")
            return ' noremoveatdie="true"';
    }
    return "";
}
// ----------------------------------------------------------------------------
// alle relevanten Waffentypen zurückgeben
// ----------------------------------------------------------------------------
function getWeapons($key)
{
    global $tabcskill;
    
    // neue Sortierung, nun in der Reihenfolge wie in der akt. EMU
    $tabweaps = array(
                  array( "required_2hsword", "SWORD_2H"), 
                  array( "required_book"   , "BOOK_2H"),    
                  array( "required_bow"    , "BOW"), 
                  array( "required_dagger" , "DAGGER_1H"),
                  array( "required_mace"   , "MACE_1H"),
                  array( "required_orb"    , "ORB_2H"),
                  array( "required_polearm", "POLEARM_2H"),
                  array( "required_staff"  , "STAFF_2H"),
                  array( "required_sword"  , "SWORD_1H"),   
                  array( "required_gun"    , "GUN_1H"),
                  array( "required_cannon" , "CANNON_2H"),
                  array( "required_harp"   , "HARP_2H"),
                  array( "required_keyblade","KEYBLADE_2H")
                     );
    $maxweaps = count($tabweaps);
    $ret      = "";
    
    for ($w=0;$w<$maxweaps;$w++)
    {
        $wbin = getTabValue($key,$tabweaps[$w][0],"?");
        
        if ($wbin != "?")
            $ret .= $tabweaps[$w][1]." ";
    }
    return rtrim($ret);
}
// ----------------------------------------------------------------------------
// SkillChargeId zurückgeben
// ----------------------------------------------------------------------------
function getChargeNameId($name)
{
    global $tabcharge;
    
    $key = strtoupper($name);
    
    if (isset($tabcharge[$key]))
        return $tabcharge[$key];
    else
        return "";
}
// ----------------------------------------------------------------------------
// StatSetId zurückgeben
// ----------------------------------------------------------------------------
function getStatSetId($name)
{
    global $tabastats;
    
    $key = strtoupper($name);
    
    if (isset($tabastats[$key]))
        return $tabastats[$key];
    else
        return "0";
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Scannen der PS-Client-Strings für die Skill-Namen
// ----------------------------------------------------------------------------
function scanPsSkillNames()
{
    global $tabSNames, $pathstring;
    
    $tabfiles = array( 
                  array("client_strings_skill.xml",true),
                  array("client_strings_ui.xml",true),
                  array("client_strings_item.xml",true),
                  array("client_strings_item2.xml",true),
                  array("client_strings_item3.xml",true),
                  array("client_strings_monster.xml",true)
                     );
    $maxfiles = count($tabfiles);
    
    logHead("Scannen der PS-String-Dateien");
    
    for ($f=0;$f<$maxfiles;$f++)
    {
        $filestr = formFileName($pathstring."\\".$tabfiles[$f][0]);
        $cntles  = 0;
        $cntstr  = 0;
        
        logSubHead("Scanne PS-String-Datei: ".basename($filestr));
        
        if (!file_exists($filestr))
        {
            logLine("Datei nicht gefunden",$filestr);
            return;
        }
        $hdlstr = openInputFile($filestr);
        
        if (!$hdlstr)
        {
            logLine("Fehler openInputFile",$filestr);
            return;
        }
        
        logLine("Eingabedatei",$filestr);
        
        flush();
        
        $id = $name = $body = "";
        
        while (!feof($hdlstr))
        {
            $line = rtrim(fgets($hdlstr));
            $cntles++;        
            
            if     (stripos($line,"<id>") !== false)
                $id   = getXmlValue("id",$line);
            elseif (stripos($line,"<name>") !== false)
                $name = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"body") !== false)
                $body = getXmlValue("body",$line);
            elseif (stripos($line,"</string>") !== false)
            {
                $tabSNames[$name]['id']   = ($tabfiles[$f][1]) ? ($id * 2) + 1: $id;
                $tabSNames[$name]['body'] = $body;
                $cntstr++;
                
                $id = $name = $body = "";
            }
        }
        fclose($hdlstr);
    
        logLine("Anzahl Zeilen gelesen",$cntles);
        logLine("Anzahl Namen gefunden",$cntstr);
    }
}
// ----------------------------------------------------------------------------
// Scannen der SkillCharges aus client_skill_charge.xml
// ----------------------------------------------------------------------------
function scanSkillCharges()
{
    global $pathdata, $tabcharge;
    
    $fileu16 = formFileName($pathdata."\\skills\\client_skill_charge.xml");
    $fileext = convFileToUtf8($fileu16);
    
    logHead("Scanne die SkillCharges aus dem Client");
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    
    $cntles  = 0;
    $cntids  = 0;
    
    $id = $name = "";
    
    $hdlext = openInputFile($fileext);
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<id>")   !== false) 
            $id   = getXmlValue("id",$line);
        elseif (stripos($line,"<name>") !== false)
            $name = strtoupper(getXmlValue("name",$line));
        elseif (stripos($line,"</skill_charge_client>") !== false)
        {
            $tabcharge[$name] = $id;
            $cntids++;
            
            $id = $name = "";
        }
    }
    fclose($hdlext);
    unlink($fileext);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl Charges gefunden",$cntids);
}
// ----------------------------------------------------------------------------
// Scannen der AbsoluteStat aus client_absolute_stat_to_pc.xml
// ----------------------------------------------------------------------------
function scanAbsoluteStat()
{
    global $pathdata, $tabastats;
    
    $fileu16 = formFileName($pathdata."\\skills\\client_absolute_stat_to_pc.xml");
    $fileext = convFileToUtf8($fileu16);
    
    logHead("Scanne die AbsoluteStats aus dem Client");
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    
    $cntles  = 0;
    $cntids  = 0;
    
    $id = $name = "";
    
    $hdlext = openInputFile($fileext);
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<id>")   !== false) 
            $id   = getXmlValue("id",$line);
        elseif (stripos($line,"<name>") !== false)
            $name = strtoupper(getXmlValue("name",$line));
        elseif (stripos($line,"</absolute_stat_to_pc_client>") !== false)
        {
            $tabastats[$name] = $id;
            $cntids++;
            
            $id = $name = "";
        }
    }
    fclose($hdlext);
    unlink($fileext);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl AbsStats gefunden",$cntids);
}
// ----------------------------------------------------------------------------
// Scannen der ClientSkills aus client_skills.xml
// ----------------------------------------------------------------------------
function scanClientSkills()
{
    global $pathdata, $tabcskill, $tabxskill;    
    
    $fileu16 = formFileName($pathdata."\\skills\\client_skills.xml");
    
    $fileext = convFileToUtf8($fileu16);
    
    logHead("Scanne die Skills aus dem Client");
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    
    $cntles  = 0;
    $cntids  = 0;  
    $inskill = false;    
    $id      = "";
    
    $hdlext = openInputFile($fileext);
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        // Skill-Block-Ende?
        if (stripos($line,"</skill_base_client>") !== false)
        {
            $inskill = false;
            $id      = "";
        }
        
        // in einem Skill-Block? (alle Werte merken)         
        if ($inskill)
        {
            if (stripos($line,"<id>") !== false)
            {
                $id = getXmlValue("id",$line);
                $xmlkey = "id";
            }
            else
            { 
                $xmlkey                  = getXmlKey($line);
                $tabcskill[$id][$xmlkey] = getXmlValue($xmlkey,$line);
            }
            
            $tabxskill[$xmlkey] = 1;
        }
        // Skill-Block-Anfang?
        if     (stripos($line,"<skill_base_client>")   !== false)
            $inskill = true;
    }
    fclose($hdlext);
    
    unlink($fileext);
    
    logLine("Anzahl Zeilen gelesen",$cntles);
    logLine("Anzahl Skills gefunden",count($tabcskill));
}
// ---------------------------------------------------------------------------
// Scannen aller definierten Effekte aus der EMU-XSD-Datei
// ---------------------------------------------------------------------------
function scanEmuXsdEffects()
{
    global $pathsvn,$tabeffxsd;
    
    logHead("Scanne die definierten Effekte aus der EMU-XSD-Datei (skills.xsd)");
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\skills\skills.xsd");
    $hdlsvn  = openInputFile($filesvn);
    $ineff   = false;

    logLine("Eingabedatei",$filesvn);
    
    while (!feof($hdlsvn))
    {
        $line = fgets($hdlsvn);
        
        if (stripos($line,'complexType')     !== false
        &&  stripos($line,' name="Effects"') !== false)
            $ineff = true;
            
        if ($ineff)
        {
            if (stripos($line,"element") !== false
            &&  stripos($line," name=")  !== false)
            {
                $key = getKeyValue("name",$line);
                $tabeffxsd[$key] = 0;
            }
            elseif (stripos($line,"</xs:sequence") !== false)
                $ineff = false;
        }
    }
    logLine("Anzahl Effekte gefunden",count($tabeffxsd));
}
// ---------------------------------------------------------------------------
// Skill-Referenz-Tabelle aufbauen
// ---------------------------------------------------------------------------
function makeSkillsRefTab()
{
    global $tabcskill, $tabrskill;
    
    logHead("Erzeuge interne Skill-Referenz-Tabelle");
    
    flush();
    
    while (list($key,$val) = each($tabcskill))
    {
        $name             = strtoupper($tabcskill[$key]['name']);
        $tabrskill[$name] = $key;
    }
    reset($tabcskill);
    
    logLine("Anzahl Skills gefunden",count($tabrskill));
}
// ---------------------------------------------------------------------------
//
//                            P R O P E R T I E S
//
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: Properties
// ---------------------------------------------------------------------------
function getPropertiesLines($key)
{
    global $tabcskill;
    
    // spezielle Conditions für die Feld-Zeilen-Tabelle
    // Cond-Tabelle: 0=true/false, 1=Feldname
    $ctab = array(
              0 => array(true ,""),                      // Dummy-Condition
              1 => array(true ,"target_range_opt4"),     // = isset
              2 => array(false,"target_range_opt4")      // = isnotset
                 );
    // Feld-Tabelle: 0=EXT-Feldname, 1=EMU-Feldname, 2=Default, 3=ConditionIndex (0=dummy)
    $ftab = array(
              array("first_target"               ,"first_target"      ,"",0),
              array("first_target_valid_distance","first_target_range","",0),
              array("target_relation_restriction","target_relation"   ,"",0),
              array("target_range"               ,"target_type"       ,"",0),
              array("add_wpn_range"              ,"awr"               ,"",0),
              array("target_valid_status?"       ,"target_status"     ,"",0),  // ? = 1 bis 5
              array("target_maxcount"            ,"target_maxcount"   ,"",0),
              array("revision_distance"          ,"revision_distance" ,"",0),
              array("target_range_opt3"          ,"effective_altitude","",0),
              array("target_range_opt2"          ,"effective_dist"    ,"",1),  // wenn isset opt4!
              array("target_range_opt2"          ,"effective_angle"   ,"",2),  // ohne isnotset opt4!
              array("target_range_opt1"          ,"effective_range"   ,"",0),
              array("target_range_opt4"          ,"direction"         ,"",0),
              array("target_species_restriction" ,"target_species"    ,"",0)
           // array("","target_distance","")                                   // akt. NotUsed in EMU
                 );
    $fmax = count($ftab);
    $ret  = "";
    
    for ($f=0;$f<$fmax;$f++)
    {
        // Condition gesetzt / erfüllt?
        if ($ftab[$f][3] > 0)
        {
            $cind = $ftab[$f][3];
            $cfld = $ctab[$cind][1];
            
            // Feld gesetzt und muss vorhanden sein oder
            // Feld nicht gesetzt und darf nicht vorhanden sein,
            // dann Feld-Zeile der Tabelle berücksichtigen!
            if ( isset($tabcskill[$key][$cfld]) && $ctab[$cind][0] == true
            ||  !isset($tabcskill[$key][$cfld]) && $ctab[$cind][0] == false)
                $ret .= getTabFieldText($key,$ftab[$f][0],$ftab[$f][1],$ftab[$f][2]);
        }
        else
            // keine Condition vorhanden, also Feld-Zeile berücksichtigen
            $ret .= getTabFieldText($key,$ftab[$f][0],$ftab[$f][1],$ftab[$f][2]);
    }
    
    if ($ret != "")
        $ret = '        <properties'.$ret.'/>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
//
//                          C O N D I T I O N S
//
// Möglichkeiten gem. aktueller XSD-Datei im SVN
//
// <xs:complexType name="Conditions">
//     <xs:sequence minOccurs="0" maxOccurs="unbounded">
//         name="mp" type="MpCondition"
//         name="hp" type="HpCondition"
//         name="dp" type="DpCondition"
//         name="target" type="TargetCondition"
//         name="move_casting" type="PlayerMovedCondition"
//         name="arrowcheck" type="ArrowCheckCondition"
//         name="robotcheck" type="RobotCheckCondition"
//         name="abnormal" type="AbnormalStateCondition"
//         name="onfly" type="OnFlyCondition"
//         name="noflying" type="NoFlyingCondition"
//         name="weapon" type="WeaponCondition"
//         name="lefthandweapon" type="LeftHandCondition"
//         name="targetflying" type="TargetFlyingCondition"
//         name="selfflying" type="SelfFlyingCondition"
//         name="combatcheck" type="CombatCheckCondition"
//         name="chain" type="ChainCondition"
//         name="back" type="BackCondition"
//         name="front" type="FrontCondition"
//         name="form" type="FormCondition"
//         name="charge" type="ItemChargeCondition"
//         name="chargeweapon" type="ChargeWeaponCondition"
//         name="chargearmor" type="ChargeArmorCondition"
//         name="polishchargeweapon" type="PolishChargeCondition"
//         name="skillcharge" type="SkillChargeCondition"
//     </xs:sequence>
// </xs:complexType>
//
// Zuordnungen aktuell (gem. SVN):
// startconditions          dp, mp, chain, target, selfflying, weapon, combatcheck,
//                          form, targetflying, skillcharge, hp, lefthandweapon
// - zusätzlich zum SVN     arrowcheck, robotcheck
//          
// endconditions            chargeweapon, chargearmor, polishchargeweapon
//
// useconditions            move_casting
//
// useequipmentconditions   lefthandweapon
//
// noch nicht zugeordnet, auch aktuell im SVN nicht vorhanden:
//                          abnormal, onfly, noflying, back, front, charge
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: StartConditions
// ---------------------------------------------------------------------------
function getStartConditionLines($key)
{
    global $tabcskill;
    global $doCheckArrow,$doCheckRobot;
    
    $ret = "";
    
    $chain = strtoupper(getTabValue($key,"chain_category_name","?"));
    $comb  = getTabValue($key,"nouse_combat_state","?");
    $dp    = getTabValue($key,"cost_dp","?");
    $form  = strtoupper(getTabValue($key,"allow_use_form_category","?"));
    $hpmp  = strtolower(getTabValue($key,"cost_parameter","?"));
    $left  = strtoupper(getTabValue($key,"required_leftweapon","?"));
    $selff = strtoupper(getTabValue($key,"self_flying_restriction","?"));
    $skill = getTabValue($key,"charge_set_name","?");
    $targ  = strtoupper(getTabValue($key,"target_species_restriction","?"));
    $targf = strtoupper(getTabValue($key,"target_flying_restriction","?"));
    $weapn = getWeapons($key);
    
    $arrow = getTabValue($key,"use_arrow","?");
    $robot = getTabValue($key,"required_ride_robot","?");
    
    $rtxt  = "";
    $dtxt  = "";
    
    if ($targ != "?" && $targ != "ALL")
        $ret .= '            <target value="'.$targ.'"/>'."\n";
        
    if ($dp != "?")
        $ret .= '            <dp value="'.$dp.'"/>'."\n";
    
    if ($hpmp != "?")
    {
        $cost = getTabValue($key,'cost_end','0');
        
        if ($cost != "?" && $cost != "0")
        {
            $delt = getTabValue($key,'cost_end_lv','0');
            
            // RATIO
            if (stripos($hpmp,"_ratio") !== false)
            {
                $hpmp = str_replace("_ratio","",$hpmp);
                $rtxt = ' ratio="true"';
            }
        
            // DELTA
            if ($hpmp == "mp" || $hpmp = "hp")
            {
                // Delta nur, wenn kein Ratio bzw. wenn RATIO und Wert != 0
                if ($rtxt == "" || ($rtxt != "" && $delt != "0"))
                    $dtxt = ' delta="'.$delt.'"';
            }
                    
            $ret .= '            <'.$hpmp.' value="'.$cost.'"'.$dtxt.$rtxt.'/>'."\n";       
        }
    }        
    
    if ($left != "?")
        $ret .= '            <lefthandweapon type="'.$left.'"/>'."\n";
    
    if ($weapn != "")
        $ret .= '            <weapon weapon="'.$weapn.'"/>'."\n";    
    
    if ($chain != "?")
    {
        $cpre = strtoupper(getTabValue($key,"prechain_category_name","?"));
        $pcnt = getTabValue($key,"prechain_count","?");
        $time = getTabValue($key,"chain_time","?");
        $scnt = getTabValue($key,"self_chain_count","?");
        
        $ret .= '            <chain category="'.$chain.'"';
        if ($cpre != "?") $ret .= ' precategory="'.$cpre.'"';
        if ($time != "?") $ret .= ' time="'.$time.'"';
        if ($scnt != "?") $ret .= ' selfcount="'.$scnt.'"';
        if ($pcnt != "?") $ret .= ' precount="'.$pcnt.'"';
        $ret .= "/>\n";
    }    
    
    if ($targf != "?")
        $ret .= '            <targetflying restriction="'.$targf.'"/>'."\n";
        
    if ($selff != "?")
        $ret .= '            <selfflying restriction="'.$selff.'"/>'."\n";
        
    if ($comb == "1")
        $ret .= '            <combatcheck/>'."\n";
    
    if ($arrow == "1" && $doCheckArrow)
        $ret .= '            <arrowcheck/>'."\n";
        
    if ($robot == "1" && $doCheckRobot)
        $ret .= '            <robotcheck/>'."\n";
    
    if ($form != "?")
        $ret .= '            <form value="'.$form.'"/>'."\n";
     
    if ($skill != "?")
    {
        $skid = getChargeNameId($skill);
        $ret .= '            <skillcharge value="'.$skid.'"/>'."\n";
    }
    
    if ($ret != "")
        $ret = '        <startconditions>'."\n".
               $ret.'        </startconditions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: UseConditions
// ---------------------------------------------------------------------------
function getUseConditionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // Gemäss akt. SVN-Datei nur "move_casting" ermittelt!
    // wenn nicht gesetzt ("?") oder "0", dann ausgeben!
    $move = getTabValue($key,"move_casting","?");
    
    if ($move != "1")
        $ret = '            <move_casting allow="false"/>'."\n";
                
    if ($ret != "")
        $ret = '        <useconditions>'."\n".
               $ret.'        </useconditions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: EndConditions
// ---------------------------------------------------------------------------
function getEndConditionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    $weapn = getTabValue($key,"cost_charge_weapon","?");
    $armor = getTabValue($key,"cost_charge_armor","?");
    $polis = getTabValue($key,"polish_charge_weapon","?");
    
    if ($weapn != "?" && $weapn != "0")
        $ret .= '            <chargeweapon value="'.$weapn.'"/>'."\n";
    
    if ($armor != "?" && $armor != "0")
        $ret .= '            <chargearmor value="'.$armor.'"/>'."\n";
    
    if ($polis != "?" && $polis != "0")
        $ret .= '            <polishchargeweapon value="'.$polis.'"/>'."\n";
    
    if ($ret != "")
        $ret = '        <endconditions>'."\n".
               $ret.'        </endconditions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: UseEquipmentConditions
// ---------------------------------------------------------------------------
function getUseEquipConditionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    $left = getTabValue($key,"required_leftweapon","?");
    
    if ($left != "?")
        $ret .= '            <lefthandweapon type="'.strtoupper($left).'"/>'."\n";
        
    if ($ret != "")
        $ret = '        <useequipmentconditions>'."\n".
               $ret.'        </useequipmentconditions>';
        
    return $ret;
}
// ----------------------------------------------------------------------------
//
//                               E F F E C T S
//
// ----------------------------------------------------------------------------
//        H I L F S - F U N K T I O N E N   W E R T E R M I T T L U N G
// ----------------------------------------------------------------------------
// StatsNames zurückgeben
// ----------------------------------------------------------------------------
function getStatNames($name)
{
    $name = strtolower($name);
    
    // wenn Waffe vorgegeben, dann hierzu den StatsNamen ermitteln
    if (substr($name,0,2) == "1h" 
    ||  substr($name,0,2) == "2h"
    ||  $name             == "bow")
        return getEffectAttackType($name);
        
    switch($name)
    {
        case "?":                               return "";
        case "activedefend":                    return "EVASION,PARRY,BLOCK";            
        case "agi":                             return "AGILITY";            
        case "allpara":                         return "ALLPARA";            
        case "allresist":                       return "ALLRESIST";            
        case "allspeed":                        return "SPEED,FLY_SPEED";            
        case "arall":                           return "ABNORMAL_RESISTANCE_ALL";            
        case "arbind":                          return "BIND_RESISTANCE";        
        case "arblind":                         return "BLIND_RESISTANCE";
        case "ardeform":                        return "DEFORM_RESISTANCE";            
        case "arfear":                          return "FEAR_RESISTANCE";            
        case "arparalyze":                      return "PARALYZE_RESISTANCE";            
        case "arpulled":                        return "PULLED_RESISTANCE";            
        case "arroot":                          return "ROOT_RESISTANCE";            
        case "arsilence":                       return "SILENCE_RESISTANCE";            
        case "arsleep":                         return "SLEEP_RESISTANCE";            
        case "arsnare":                         return "SNARE_RESISTANCE";            
        case "arspin":                          return "SPIN_RESISTANCE";            
        case "arstagger":                       return "STAGGER_RESISTANCE";            
        case "arstumble":                       return "STUMBLE_RESISTANCE";            
        case "arstun":                          return "STUN_RESISTANCE";            
        case "arstunlike":                      return "STUN_RESISTANCE,STUMBLE_RESISTANCE,STAGGER_RESISTANCE,SPIN_RESISTANCE,OPENAREIAL_RESISTANCE";            
        case "attackdelay":                     return "ATTACK_SPEED";            
        case "attackrange":                     return "ATTACK_RANGE";            
        case "block":                           return "BLOCK";            
        case "boostcastingtime":                return "BOOST_CASTING_TIME";            
        case "boostchargetime":                 return "BOOST_CHARGE_TIME";            
        case "buff":                            return "BOOST_DURATION_BUFF";            
        case "concentration":                   return "CONCENTRATION";            
        case "critical":                        return "PHYSICAL_CRITICAL";            
        case "debuff":                          return "BOOST_RESIST_DEBUFF";            
        case "dex":                             return "ACCURACY";            
        case "dodge":                           return "EVASION";            
        case "elementaldefendair":              return "WIND_RESISTANCE";            
        case "elementaldefendall":              return "WATER_RESISTANCE,WIND_RESISTANCE,FIRE_RESISTANCE,EARTH_RESISTANCE";            
        case "elementaldefenddark":             return "ELEMENTAL_RESISTANCE_DARK";            
        case "elementaldefendearth":            return "EARTH_RESISTANCE";            
        case "elementaldefendfire":             return "FIRE_RESISTANCE";            
        case "elementaldefendlight":            return "ELEMENTAL_RESISTANCE_LIGHT";            
        case "elementaldefendwater":            return "WATER_RESISTANCE";            
        case "erair":                           return "ERAIR";            
        case "erearth":                         return "EREARTH";            
        case "erfire":                          return "ERFIRE";            
        case "erwater":                         return "ERWATER";            
        case "flyspeed":                        return "FLY_SPEED";            
        case "fpregen":                         return "REGEN_FP";            
        case "healskillboost":                  return "HEAL_BOOST";            
        case "hitaccuracy":                     return "PHYSICAL_ACCURACY";            
        case "hp":                              return "HP";            
        case "hpregen":                         return "REGEN_HP";            
        case "kno":                             return "KNOWLEDGE";            
        case "knowil":                          return "KNOWIL";            
        case "magicalattack":                   return "MAGICAL_ATTACK";            
        case "magicalcritical":                 return "MAGICAL_CRITICAL";            
        case "magicalcriticaldamagereduce":     return "MAGICAL_CRITICAL_DAMAGE_REDUCE";            
        case "magicalcriticalreducerate":       return "MAGICAL_CRITICAL_RESIST";            
        case "magicaldefend":                   return "MAGICAL_DEFEND";            
        case "magicalhitaccuracy":              return "MAGICAL_ACCURACY";            
        case "magicalresist":                   return "MAGICAL_RESIST";            
        case "magicalskillboost":               return "BOOST_MAGICAL_SKILL";            
        case "magicalskillboostresist":         return "MAGIC_SKILL_BOOST_RESIST";            
        case "maxfp":                           return "FLY_TIME";            
        case "maxhp":                           return "MAXHP";            
        case "maxmp":                           return "MAXMP";            
        case "mp":                              return "MP";            
        case "mpregen":                         return "REGEN_MP";            
        case "openareial_arp":                  return "OPENAREIAL_RESISTANCE_PENETRATION";            
        case "parry":                           return "PARRY";  
        case "paralyze_arp":                    return "PARALYZE_RESISTANCE_PENETRATION";        
        case "phyattack":                       return "PHYSICAL_ATTACK";            
        case "physicalcriticaldamagereduce":    return "PHYSICAL_CRITICAL_DAMAGE_REDUCE";            
        case "physicalcriticalreducerate":      return "PHYSICAL_CRITICAL_RESIST";            
        case "physicaldefend":                  return "PHYSICAL_DEFENSE";            
        case "pmattack":                        return "PHYSICAL_ATTACK,MAGICAL_ATTACK";            
        case "pmdefend":                        return "PHYSICAL_DEFENSE,MAGICAL_RESIST";            
        case "procreducerate":                  return "PROC_REDUCE_RATE";            
        case "pveattackratio":                  return "PVE_ATTACK_RATIO";            
        case "pvedefendratio":                  return "PVE_DEFEND_RATIO";                    
        case "pvpattackratio":                  return "PVP_ATTACK_RATIO";            
        case "pvpattackratio_magical":          return "PVP_ATTACK_RATIO_MAGICAL";            
        case "pvpattackratio_physical":         return "PVP_ATTACK_RATIO_PHYSICAL";             
        case "pvpdefendratio":                  return "PVP_DEFEND_RATIO";    
        case "pvpdefendratio_magical":          return "PVP_DEFEND_RATIO_MAGICAL";         
        case "pvpdefendratio_physical":         return "PVP_DEFEND_RATIO_PHYSICAL";
        case "silence_arp":                     return "SILENCE_RESISTANCE_PENETRATION";
        case "speed":                           return "SPEED";            
        case "spin_arp":                        return "SPIN_RESISTANCE_PENETRATION";            
        case "stagger_arp":                     return "STAGGER_RESISTANCE_PENETRATION";            
        case "str":                             return "POWER";            
        case "stumble_arp":                     return "STUMBLE_RESISTANCE_PENETRATION";            
        case "stun_arp":                        return "STUN_RESISTANCE_PENETRATION";            
        case "vit":                             return "HEALTH";            
        case "wil":                             return "WILL";            
        case "xpboost":                         return "BOOST_CRAFTING_XP_RATE,BOOST_GATHERING_XP_RATE,BOOST_GROUP_HUNTING_XP_RATE,BOOST_HUNTING_XP_RATE";            
        default:                                return $name;
    }
    return "";
}
// ----------------------------------------------------------------------------
// EffectFunc zurückgeben
// ----------------------------------------------------------------------------
function getEffectFunc($efftyp,$key,$field)
{
    $val = getTabValue($key,$field,"?");
    
    switch ($efftyp)
    {
        // wenn Feld = "1", dann PERCENT
        case "statup":
        case "statdown":        
            return ( ($val == "1") ? "PERCENT" : "ADD" );
        
        // wenn Feld ungleich "1", dann PERCENT
        default:                                
            return ( ($val != "1") ? "PERCENT" : "ADD" );
    }
}
// ----------------------------------------------------------------------------
// Vorzeichen für die Value-Angabe ermitteln
// ----------------------------------------------------------------------------
function getValueSign($efftyp,$stat,$value,$tbneg)
{    
    $ret  = 1;
    $mneg = count($tbneg);
    $stat = strtoupper($stat);
    
    // nur für Werte ungleich 0
    if ($value != 0 && $value != "?")
    {
        // wenn Feld in Tabelle enthalten, dann negieren
        if ($stat != "" && $mneg > 0)
        {
            for ($n=0;$n<$mneg;$n++)
            {
                if ($stat == $tbneg[$n])
                {
                    $ret = -1;
                    $n   = $mneg;
                }
            }
        }
        // bei StatDOWN immer Negativ, ausser $tbneg-Stats
        if ($efftyp == "statdown")
            $ret *= -1;
    }  
    
    return $ret;
}
// ----------------------------------------------------------------------------
// EffectWeaponType zurückgeben
// ----------------------------------------------------------------------------
function getEffectWeaponType($weapn)
{    
    $weapn = strtoupper($weapn);
    
    switch($weapn)
    {
        case "1H_SWORD":    return "SWORD_1H"; 
        case "1H_DAGGER":   return "DAGGER_1H";  
        case "1H_MACE":     return "MACE_1H";  
        case "1H_GUN":      return "GUN_1H";   
        case "2H_SWORD":    return "SWORD_2H";  
        case "2H_POLEARM":  return "POLEARM_2H"; 
        case "2H_STAFF":    return "STAFF_2H";  
        case "2H_BOOK":     return "BOOK_2H"; 
        case "2H_ORB":      return "ORB_2H"; 
        case "2H_CANNON":   return "CANNON_2H"; 
        case "2H_HARP":     return "HARP_2H";  
        case "2H_KEYBLADE": return "KEYBLADE_2H"; 
        case "BOW":         return "BOW";
        default:            return $weapn;
    }
    return $weapn;
}
// ----------------------------------------------------------------------------
// EffectAttackType zurückgeben
// ----------------------------------------------------------------------------
function getEffectAttackType($weapn)
{
    switch(strtoupper($weapn))
    {
        // physische Angriffe
        case "1H_DAGGER":    
        case "1H_GUN":   
        case "1H_MACE":   
        case "1H_SWORD":      
        case "2H_CANNON":     
        case "2H_HARP":     
        case "2H_KEYBLADE": 
        case "2H_POLEARM": 
        case "2H_STAFF":    
        case "2H_SWORD": 
        case "BOW":         return "PHYSICAL_ATTACK";     
        // magische Angriffe
        case "2H_BOOK": 
        case "2H_ORB":      return "MAGICAL_ATTACK";    
        default:            return "PHYSICAL_ATTACK";
    }
    return "PHYSICAL_ATTACK";    
}
// ----------------------------------------------------------------------------
//
//  S P E Z I E L L E   W E R T E R M I T T L U N G E N   B A S I C - L I N E
//
// ----------------------------------------------------------------------------
// Wert ermitteln für: CHECKTIME
// ----------------------------------------------------------------------------
function getEffValChecktime($efftyp,$key,$ename)
{
    $ret = "?";
    
    if ($efftyp == "bleed"
    ||  $efftyp == "dpheal"
    ||  $efftyp == "fpatk"
    ||  $efftyp == "fpheal"
    ||  $efftyp == "heal"
    ||  $efftyp == "mpattack"
    ||  $efftyp == "mpheal"
    ||  $efftyp == "poison"
    ||  $efftyp == "spellatk" 
    ||  $efftyp == "spellatkdrain" )
    {
        $ret = getTabValue($key,$ename."checktime","?");
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: CRITPROBMOD2
// ----------------------------------------------------------------------------
function getEffValCritPM2($efftyp,$key,$ename)
{
    $ret = "?";
    
    // inkl. Rückgabe von "0" und ungleich "100"
    if     ($efftyp == "delaydamage"
    ||      $efftyp == "dispelbuff"
    ||      $efftyp == "noreducespellatk"
    ||      $efftyp == "signetburst" 
    ||      $efftyp == "spellatkdraininstant"  )
    {
        $ret = getTabValue($key,$ename."critical_prob_mod2","0");
        $ret = ($ret == "100") ? "?" : $ret;
    }
    // Rückgabe ungleich "0" / "100"
    elseif ($efftyp == "poison"
    ||      $efftyp == "procatk_instant"
    ||      $efftyp == "skillatk" 
    ||      $efftyp == "skillatkdraininstant"   
    ||      $efftyp == "spellatk" 
    ||      $efftyp == "spellatkinstant"  )
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."critical_prob_mod2","?") );
        $ret = ($ret == "100") ? "?" : $ret;
    }
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: DELTA
// ----------------------------------------------------------------------------
function getEffValDelta($efftyp,$key,$ename)
{
    $ret = "?";
    
    // aus reserved1 (aber ungleich 0))   
    if     ($efftyp == "blind"
    ||      $efftyp == "dispelbuff"
    ||      $efftyp == "dispeldebuff"
    ||      $efftyp == "dispeldebuffmental"
    ||      $efftyp == "dispeldebuffphysical"
    ||      $efftyp == "dispelnpcbuff"
    ||      $efftyp == "dispelnpcdebuff"
    ||      $efftyp == "dphealinstant"
    ||      $efftyp == "healcastoronatk"
    ||      $efftyp == "healcastorontargetdead"
    ||      $efftyp == "healinstant"
    ||      $efftyp == "hostileup"
    ||      $efftyp == "noreducespellatk"
    ||      $efftyp == "procatk_instant"
    ||      $efftyp == "procfphealinstant"
    ||      $efftyp == "prochealinstant"
    ||      $efftyp == "procmphealinstant"
    ||      $efftyp == "signetburst" 
    ||      $efftyp == "spellatkdraininstant" 
    ||      $efftyp == "spellatkinstant"   )
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved1","?") );
    }
    // aus reserved3
    elseif ($efftyp == "backdash"
    ||      $efftyp == "dash")
    {
        $ret = getTabValue($key,$ename."reserved3","0");
    } 
    // aus reserved6 (aber ungleich 0)
    elseif ($efftyp == "mpshield"
    ||      $efftyp == "shield")
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved6","?") );
    }
    // aus reserved7 (aber ungleich 0)
    elseif ($efftyp == "convertheal"
    //||      $efftyp == "mpshield"
    ||      $efftyp == "reflector")
    //||      $efftyp == "shield")
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved7","?") );
    }
    // aus reserved8 (aber ungleich 0)
    elseif ($efftyp == "bleed"
    ||      $efftyp == "dpheal"
    ||      $efftyp == "heal"
    ||      $efftyp == "mpattack"
    ||      $efftyp == "poison"
    ||      $efftyp == "spellatk"
    ||      $efftyp == "spellatkdrain" )
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved8","?") );
    } 
    // aus reserved11 (aber ungleich 0)
    elseif ($efftyp == "skillcooltimereset")
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved11","?") );
    } 
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: DISTANCE
// ----------------------------------------------------------------------------
function getEffValDistance($efftyp,$key,$ename)
{
    $ret = "?";
    
    if     ($efftyp == "aura")
    {
        $ret = getTabValue($key,$ename."reserved3","?");
    }
    elseif ($efftyp == "backdash")
    {
        $ret = getTabValue($key,$ename."reserved12","?");
    }
    elseif ($efftyp == "flyoff")
    {
        $ret = getTabValue($key,$ename."reserved4","?");
    }
    elseif ($efftyp == "targetteleport")
    {
        $ret = getTabValue($key,$ename."reserved11","?");
    }
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: ELEMENT
// ----------------------------------------------------------------------------
function getEffValElement($efftyp,$key,$ename)
{
    // Skills ohne Element-Angabe
    if ($efftyp == "armormastery"
    ||  $efftyp == "carvesignet"
    ||  $efftyp == "caseheal"
    ||  $efftyp == "skillcooltimereset"
    ||  $efftyp == "targetteleport"   )
    {
        return "?";
    }
    
    $x10 = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved10","?") );
            
    // nur definierte Angaben (siehe XSD) zulassen!
    switch($x10)
    {
        // gem. XSD zugelassene Angaben
        case "DARK"  :
        case "EARTH" :
        case "FIRE"  :
        case "LIGHT" :
        case "WATER" :
        case "WIND"  : return $x10;
        // Umsetzung gem. EMU
        case "AIR"   : return "WIND";
        // sonstiges = keine Angabe
        default      : return "?";
    }
    return "?";
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: MODE
// ----------------------------------------------------------------------------
function getEffValMode($efftyp,$key,$ename)
{
    $ret = false;
    
    // CARVESIGNET, SKILLATK
    if     ($efftyp == "carvesignet"
    ||      $efftyp == "skillatk"
    ||      $efftyp == "skillatkdraininstant" )
    {
        // wenn der skillatk-Value-Wert aus reserved2 genommen wurde, dann PERCENT (s.u.)
        $x02 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2" ,"?") );
        $x04 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4" ,"?") );
        
        if ($x04 == "?" && $x02 != "?")     $ret = true;
    }
    // MOVEBEHIND
    elseif ($efftyp == "movebehind")
    {
        // wenn reserved2 gesetzt ist (ungleich "0")
        $x02   = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","?") );
        
        if ($x02 != "?")                   $ret = true;
    }
    // SPELLATKDRAININSTANT
    elseif ($efftyp == "spellatkdraininstant"
    ||      $efftyp == "spellatkinstant")
    {
        // wenn reserved6 = "1" gesetzt ist
        $x06 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved6","?") );
        
        if ($x06 == "1")                    $ret = true;
    }   
    
    if ($ret == true)
        return ' mode="PERCENT"';
    else
        return '';
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: PERCENT
// ----------------------------------------------------------------------------
function getEffValPercent($efftyp,$key,$ename)
{
    $ret = "?";
    
    if     ($efftyp == "convertheal"
    ||      $efftyp == "delayedfpatk_instant"
    ||      $efftyp == "dpheal"
    ||      $efftyp == "fpatk"
    ||      $efftyp == "fpatkinstant"
    ||      $efftyp == "fpheal"
    ||      $efftyp == "fphealinstant"
    ||      $efftyp == "heal"
    ||      $efftyp == "healcastoronatk"
    ||      $efftyp == "healcastorontargetdead"
    ||      $efftyp == "healinstant"
    ||      $efftyp == "mpattack"
    ||      $efftyp == "mpattackinstant"
    ||      $efftyp == "mpheal"
    ||      $efftyp == "mphealinstant"
    ||      $efftyp == "mpshield"
    ||      $efftyp == "noreducespellatk"
    ||      $efftyp == "procfphealinstant"
    ||      $efftyp == "prochealinstant"
    ||      $efftyp == "procmphealinstant"
    ||      $efftyp == "procvphealinstant"
    ||      $efftyp == "protect"
    ||      $efftyp == "reflector"
    ||      $efftyp == "shield" )
    {
        $ret = getTabValue($key,$ename."reserved6","0");
        $ret = ($ret == "1") ? "true" : "?";
    }
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: SKILL_ID
// ----------------------------------------------------------------------------
function getEffValSkillid($efftyp,$key,$ename)
{
    $skill = "?";
    
    if     ($efftyp == "summonservant"
    ||      $efftyp == "summonskillarea"
    ||      $efftyp == "summontrap"
    ||      $efftyp == "summontotem")
    {
        $skill = getTabValue($key,$ename."reserved9","?");
    }   
    elseif ($efftyp == "aura"
    ||      $efftyp == "delayedskill"
    ||      $efftyp == "skilllauncher")
    {
        $skill = getTabValue($key,$ename."reserved1","?");
    } 
    elseif ($efftyp == "condskilllauncher")
    {
        $skill = getTabValue($key,$ename."reserved3","?");
    }
    elseif ($efftyp == "provoker")
    {
        $skill = getTabValue($key,$ename."reserved17","?");
    }
    elseif ($efftyp == "rebirth"
    ||      $efftyp == "resurrect"
    ||      $efftyp == "resurrectbase"
    ||      $efftyp == "resurrectpos")
    {
        $skill = getTabValue($key,$ename."reserved6","?");
    }
    
    if ($skill != "?")
        return getRefSkillNameId($key,$skill);
    else
        return "?";
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: STATE
// ----------------------------------------------------------------------------
function getEffValState($efftyp,$key,$ename)
{
    $ret = "?";
    
    if ($efftyp == "polymorph" 
    ||  $efftyp == "shapechange")
    {
        $ret = getEffSpecial( "state" ,getTabValue($key,$ename."reserved13","?") );
    } 
    elseif ($efftyp == "deform") 
    {
        $ret = "DEFORM";
    }
    elseif ($efftyp == "hide")
    {
        $x07 = getTabValue($key,$ename."reserved7","?");
        
        if ($x07 != "?") $ret = "HIDE".$x07;
    }
    elseif ($efftyp == "search")
    {
        $x07 = getTabValue($key,$ename."reserved7","?");
        
        if ($x07 != "?") $ret = "SEARCH".$x07;
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: TIME
// ----------------------------------------------------------------------------
function getEffValTime($efftyp,$key,$ename)
{
    $ret = "?";
    
    if (stripos($efftyp,"summon") !== false)
    {
        if     ($efftyp == "summonfunctionalnpc"
        ||      $efftyp == "summongroupgate"
        ||      $efftyp == "summonhousegate")
            $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","?") );
        elseif ($efftyp == "summonhoming")
            $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved5","?") );
        else
            $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4","?") ); 
    } 
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: TYPE
// ----------------------------------------------------------------------------
function getEffValType($efftyp,$key,$ename)
{ 
    $ret = "?";
    
    // Effekte ohne TYPE
    if ($efftyp == "aura"
    ||  $efftyp == "armormastery"
    ||  $efftyp == "bleed"
    ||  $efftyp == "carvesignet"
    ||  $efftyp == "delaydamage"
    ||  $efftyp == "delayedfpatk_instant"
    ||  $efftyp == "dispel"
    ||  $efftyp == "dispelbuffcounteratk"
    ||  $efftyp == "dispeldebuff"
    ||  $efftyp == "dispeldebuffmental"
    ||  $efftyp == "dispeldebuffphysical"
    ||  $efftyp == "dispelnpcbuff"
    ||  $efftyp == "dispelnpcdebuff"
    ||  $efftyp == "dpheal"
    ||  $efftyp == "dphealinstant"
    ||  $efftyp == "fpatk"
    ||  $efftyp == "fpatkinstant"
    ||  $efftyp == "fpheal"
    ||  $efftyp == "heal"
    ||  $efftyp == "hostileup"
    ||  $efftyp == "movebehind"
    ||  $efftyp == "mpattack"
    ||  $efftyp == "mpattackinstant"
    ||  $efftyp == "mpheal"
    ||  $efftyp == "mpshield"
    ||  $efftyp == "petorderuseultraskill"    
    ||  $efftyp == "poison"  
    ||  $efftyp == "procatk_instant"  
    ||  $efftyp == "protect"  
    ||  $efftyp == "randommoveloc"   
    ||  $efftyp == "reflector" 
    ||  $efftyp == "riderobot"
    ||  $efftyp == "shield"
    ||  $efftyp == "shieldmastery"
    ||  $efftyp == "signetburst"
    ||  $efftyp == "silence"
    ||  $efftyp == "skillatk" 
    ||  $efftyp == "skillatkdraininstant"
    ||  $efftyp == "spellatk" 
    ||  $efftyp == "spellatkdrain"
    ||  $efftyp == "spellatkdraininstant"
    ||  $efftyp == "spellatkinstant"
    ||  $efftyp == "statup"
    ||  $efftyp == "stun"
    ||  $efftyp == "summontrap"  )
    {
        return $ret;    
    }    

    // aus reserved 13    
    if     ($efftyp == "caseheal" 
    ||      $efftyp == "condskilllauncher"
    ||      $efftyp == "convertheal" )
        $ret = getEffSpecial( "upper",getTabValue($key,$ename."reserved13","?") );
    // aus reserved 4
    elseif ($efftyp == "hide")
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4","?") ); 
    // aus reserved5
    elseif ($efftyp == "onetimeboostskillattack")
        $ret = getEffSpecial( "upper",getTabValue($key,$ename."reserved5","?") );
    // konstant        
    elseif (substr($efftyp,0,8) == "healcast")
        $ret = "HP";
    // aus reserved8 (Default)
    else
        $ret = getEffSpecial( "upper",getTabValue($key,$ename."reserved8","?") );
        
    // Effekte ohne TYPE, wenn TYPE = 0
    if (substr($efftyp,0,6) == "always")
    {        
        if ($ret == "0") $ret = "?";
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Wert ermitteln für: VALUE
// ----------------------------------------------------------------------------
function getEffValValue($efftyp,$key,$ename)
{
    $ret = "?";
    
    // aus reserved2
    if     ($efftyp == "boostskillcost"
    ||      $efftyp == "blind"
    ||      $efftyp == "delaydamage"
    ||      $efftyp == "flyoff"
    ||      $efftyp == "procatk_instant" )
    {
        $ret = getTabValue($key,$ename."reserved2","?");
    }
    // aus reserved2 (aber ungleich 0)
    elseif ($efftyp == "caseheal"
    ||      $efftyp == "delayedfpatk_instant"
    ||      $efftyp == "delayedskill"
    ||      $efftyp == "dispelbuff"
    ||      $efftyp == "dispelbuffcounteratk"
    ||      $efftyp == "dispeldebuff"
    ||      $efftyp == "dispeldebuffmental"
    ||      $efftyp == "dispeldebuffphysical"
    ||      $efftyp == "dispelnpcbuff"
    ||      $efftyp == "displenpcdebuff"
    ||      $efftyp == "dphealinstant"
    ||      $efftyp == "fpatkinstant"
    ||      $efftyp == "fphealinstant"
    ||      $efftyp == "healcastoronatk"
    ||      $efftyp == "healcastorontargetdead"
    ||      $efftyp == "healinstant"
    ||      $efftyp == "hostileup"
    ||      $efftyp == "magiccounteratk"
    ||      $efftyp == "mpattackinstant"
    ||      $efftyp == "mphealinstant"
    ||      $efftyp == "noreducespellatk"
    ||      $efftyp == "onetimeboostskillattack"
    ||      $efftyp == "procdphealinstant" 
    ||      $efftyp == "procfphealinstant"
    ||      $efftyp == "prochealinstant" 
    ||      $efftyp == "procmphealinstant" 
    ||      $efftyp == "procvphealinstant"
    ||      $efftyp == "skilllauncher"
    ||      $efftyp == "spellatkdraininstant"
    ||      $efftyp == "spellatkinstant"
    ||      $efftyp == "stumble" 
    ||      $efftyp == "stun"   )
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","?") );
    }
    // aus reserved4
    elseif ($efftyp == "backdash"
    ||      $efftyp == "condskilllauncher"
    ||      $efftyp == "dash"
    ||      $efftyp == "onetimeboostskillcritical")
    {
        $ret = getTabValue($key,$ename."reserved4","?");
    }
    // aus reserved6 (aber ungleich 0))
    elseif ($efftyp == "wpndual")
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved6","?") );
    }
    // aus reserved8 
    elseif ($efftyp == "convertheal"
    ||      $efftyp == "reflector")
    {
        $ret = getTabValue($key,$ename."reserved8","?");
    }
    // aus reserved8 (aber ungleich 0))
    elseif ($efftyp == "mpshield"
    ||      $efftyp == "shield")
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved8","?") );
    }
    // aus reserved9
    elseif (substr($efftyp,0,6) == "always"
    ||      $efftyp == "poison")
    {
        $ret = getTabValue($key,$ename."reserved9","?") ;
    }
    // aus reserved9 ( aber ungleich 0)
    elseif ($efftyp == "bleed"
    ||      $efftyp == "dpheal"
    ||      $efftyp == "fpatk"
    ||      $efftyp == "fpheal"
    ||      $efftyp == "heal"
    ||      $efftyp == "mpattack"
    ||      $efftyp == "mpheal"
    ||      $efftyp == "protect"
    ||      $efftyp == "skillcooltimereset"
    ||      $efftyp == "spellatk"  
    ||      $efftyp == "spellatkdrain"  )
    {
        $ret = getEffSpecial( "nozero",getTabValue($key,$ename."reserved9","?") );
    }    
    // aus mehreren Feldern je nach Effekt
    //
    // movebehind: reserved2 oder reserved4
    elseif ($efftyp == "movebehind")
    {
        $x02   = getTabValue($key,$ename."reserved2","?");
        $x04   = getTabValue($key,$ename."reserved4","?");
        
        if     ($x02 != "?"  &&  $x02 != "0")
            $ret = $x02;
        elseif ($x04 != "?"  &&  $x04 != "0")
            $ret = $x04;
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// zusätzliche EffektAttribute ermitteln/aufbereiten
//
// ACHTUNG: die Textfelder werden direkt verändert (siehe Adressübergabe &...)
// ----------------------------------------------------------------------------
function getEffValAddText($efftyp,$key,$ename,  &$txkey,  &$txval,  &$txlst)
{
    // ----------------------------------------------------
    // gem. Effekt zusätzlich einfügen
    // ----------------------------------------------------
    // ABSSTATBUFF / ABSSTATDEBUFF ------------------------
    if ($efftyp == "absstatbuff"
    ||  $efftyp == "absstatdebuff")
    {
        // statsetid
        $x01 = getTabValue($key,$ename."reserved1","?");
        $sid = getStatSetId($x01);
        
        if ($sid != "?")  $txkey .= ' statsetid="'.$sid.'"';
    }     
    // ARMORMASTERY ---------------------------------------
    if ($efftyp == "armormastery")
    {
        // armor
        $x05 = getEffSpecial( "upper",getTabValue($key,$ename."reserved5","?") );
        
        if ($x05 != "?")  $txkey .= ' armor="'.$x05.'"';
    } 
    // CARVESIGNET ----------------------------------------
    elseif ($efftyp == "carvesignet")
    {
        // signet,signetid,signetlvlstart,signetlvl,prob,value,delta,mod
        $x02   = getTabValue($key,$ename."reserved2","?");
        $x04   = getTabValue($key,$ename."reserved4","?");
        $x10   = getTabValue($key,$ename."reserved10","0");
        $x13   = getTabValue($key,$ename."reserved13","");
        $x14   = getTabValue($key,$ename."reserved14","");
        $x16   = getTabValue($key,$ename."reserved16","0");
        $sig   = "signet".$x13."_".$x14;
        $sid   = getRefSkillNameId($key,$sig);
        $mode  = getEffValMode($efftyp,$key,$ename);
        $val   = "?";
        $dta   = "";        
        $txkey.= ' signet="SYSTEM_SKILL_SIGNET'.$x13.'" signetid="'.$sid.'"';
        
        if ($x10 > "1")          $txval .= ' signetlvlstart="'.$x10.'"';
            
        $txval .= ' signetlvl="'.$x14.'"';
        
        if ($x16 > 0  && $x16 != "100")
            $txval .= ' prob="'.$x16.'"';
        
        if ($x04 != "?")
        {
            $val    = $x04;
            $dta    = getTabValue($key,$ename."reserved3","?");
        }
        elseif ($x02 != "?")
        {
            $val    = $x02;
            $dta    = getTabValue($key,$ename."reserved1","?");            
        }  
        if ($val != "?")
        {  
            $dta    = ($dta != "?" && $dta != "0") ? ' delta="'.$dta.'"' : '';      
            $txval .= $mode.' value="'.$val.'"'.$dta;
        }            
    }
    // CONVERTHEAL ----------------------------------------
    elseif ($efftyp == "convertheal")
    {
        // hitpercent,hitvalue
        $x02 = getTabValue($key,$ename."reserved2","0");
        
        if ($x02 != "0") $txval .= ' hitpercent="true" hitvalue="'.$x02.'"';
    }
    // DISPEL... ------------------------------------------
    elseif ($efftyp == "dispel")
    {
        // dispel_type
        $x01   = getEffSpecial( "upper",getTabValue($key,$ename."reserved1","?") );        
        $x01   = str_replace("_","",$x01);            
        $txval.= ' dispeltype="'.$x01.'"';         
    }
    elseif ($efftyp == "dispelbuff"
    ||      $efftyp == "dispelbuffcounteratk"
    ||      $efftyp == "dispeldebuff"
    ||      $efftyp == "dispeldebuffmental"
    ||      $efftyp == "dispeldebuffphysical"
    ||      $efftyp == "dispelnpcbuff"
    ||      $efftyp == "dispelnpcdebuff")
    {
        // hitvalue,hitdelta
        if ($efftyp == "dispelbuffcounteratk")
        {
            $x08 = getTabValue($key,$ename."reserved8","0");
            $x09 = getTabValue($key,$ename."reserved9","0");
            
            if ($x09 != "0") $txval .= ' hitvalue="'.$x09.'"';
            if ($x08 != "0") $txval .= ' hitdelta="'.$x08.'"';
        }
        
        // dispel_level,power
        $x16 = getTabValue($key,$ename."reserved16","0");
        $x18 = getTabValue($key,$ename."reserved18","0");
        
        if ($x16 != "0") $txval .= ' dispel_level="'.$x16.'"';
        if ($x18 != "0") $txval .= ' power="'.$x18.'"';
        
        // dpower
        if ($efftyp == "dispeldebuffphysical")
        {
            $x17 = getTabValue($key,$ename."reserved17","0");
            
            if ($x17 != "0") $txval .= ' dpower="'.$x17.'"';
        }    
    }
    // EVADE ----------------------------------------------
    elseif ($efftyp == "evade")
    {
        // dispel_type
        $x01   = getEffSpecial( "upper",getTabValue($key,$ename."reserved1","?") );        
        $x01   = str_replace("_","",$x01);         
                
        if (stripos($x01,"TYPE") !== false)      $txval .= ' dispeltype="'.$x01.'"';
    }
    // FEAR -----------------------------------------------
    elseif ($efftyp == "fear")
    {
        // resistchance
        $x02 = getTabValue($key,$ename."reserved2","0");
        
        if ($x02 != "0"  &&  $x02 != "100")      $txval .= ' resistchance="'.$x02.'"';
    }
    // HEALCAST... ----------------------------------------
    elseif ($efftyp == "healcastoronatk"
    ||      $efftyp == "healcastorontargetdead")
    {
        // range
        $x03 = getTabValue($key,$ename."reserved3","0");
        $x04 = getTabValue($key,$ename."reserved4","?");
        
        if ($x04 != "?"  && $x04 != "0")         $txval .= ' range="'.$x04.'.'.$x03.'"';
    }
    // HIDE -----------------------------------------------
    elseif ($efftyp == "hide")
    {
        // bufcount
        $x03   = getTabValue($key,$ename."reserved3","0");
        
        if ($x03 != "0")                         $txval .= ' bufcount="'.$x03.'"';
    }
    // MAGICCOUNTERATK ------------------------------------
    elseif ($efftyp == "magiccounteratk")
    {
        // maxdmg
        $x05 = getTabValue($key,$ename."reserved5","?");
        
        if ($x05 != "?"  &&  $x05 != "0")        $txval .= ' maxdmg="'.$x05.'"';
        
    }
    // MOVEBEHIND -----------------------------------------
    elseif ($efftyp == "movebehind")
    {
        // mode
        $txval .= getEffValMode($efftyp,$key,$ename);
    }
    // MPSHIELD / SHIELD ----------------------------------
    elseif ($efftyp == "mpshield"
    ||      $efftyp == "shield")
    {
        // mp_value,mp_delate,hitvalue,hitdelta,hittype,hittypeprob2
        $x01 = getTabValue($key,$ename."reserved1","?");
        $x02 = getTabValue($key,$ename."reserved2","?");
        $x03 = getTabValue($key,$ename."reserved3","?");
        $x04 = getTabValue($key,$ename."reserved4","?");
        $hit = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved_cond1","?") );
        $htp = getEffSpecial( "nozero",getTabValue($key,$ename."reserved_cond1_prob2","0") );
                
        if ($x04 != "?" && $x04 != "0")          $txval .= ' mp_value="'.$x04.'"';
        if ($x03 != "?" && $x03 != "0")          $txval .= ' mp_delta="'.$x03.'"';
        if ($x02 != "?" && $x02 != "0")          $txval .= ' hitvalue="'.$x02.'"';
        if ($x01 != "?" && $x01 != "0")          $txval .= ' hitdelta="'.$x01.'"'; 
        
        if ($hit != "EVERYHIT" && $hit != "?")   $txlst .= ' hittype="'.$hit.'"';
        if ($htp != "100"      && $htp != "?")   $txlst .= ' hittypeprob2="'.$htp.'"';     
    }
    // ONETIME...-Texte -----------------------------------
    elseif ($efftyp == "onetimeboostskillattack"
    ||      $efftyp == "onetimeboostskillcritical")
    {
        // count
        $x05 = getTabValue($key,$ename."reserved7","?");
        
        if ($x05 != "?" && $x05 != "0")          $txval .= ' count="'.$x05.'"';
    }
    // PETORDERUSEULTRASKILL ------------------------------
    elseif ($efftyp == "petorderuseultraskill")
    {
        // release
        $x01 = getTabValue($key,$ename."reserved1","0");
        $x02 = getTabValue($key,$ename."reserved2","0");
        
        $txval .= ' ultra_skill="'.$x01.'"';
        
        if ($x02 == "1")                         $txval .= ' release="true"';
    }
    // PROCVPHEALINSTANT ----------------------------------
    elseif ($efftyp == "procvphealinstant")
    {
        // value2
        $x03 = getTabValue($key,$ename."reserved3","0");
        
        if ($x03 != "?" && $x03 != "0")          $txval .= ' value2="'.$x03.'"';
    }
    // PROTECT --------------------------------------------
    elseif ($efftyp == "protect")
    {
        // hitvalue, radius
        $x02 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","0") );
        $x05 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved5","0") );
        
        if ($x02 != "?")                         $txval .= ' hitvalue="'.$x02.'"';
        if ($x05 != "?")                         $txval .= ' radius="'.$x05.'"';
    }
    // PROVOKER -------------------------------------------
    elseif ($efftyp == "provoker")
    {
        // provoke_target,radius,hittype,hittypeprob2
        $x14 = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved14","?") );
        $x05 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved5","0") );
        $hit = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved_cond1","?") );
        $htp = getEffSpecial( "nozero",getTabValue($key,$ename."reserved_cond1_prob2","0") );
        
        if ($x14 != "?")                         $txval .= ' provoke_target="'.$x14.'"';
        if ($x05 != "?")                         $txval .= ' radius="'.$x05.'"';
        
        if ($hit != "EVERYHIT" && $hit != "?")   $txlst .= ' hittype="'.$hit.'"';
        if ($htp != "100"      &&  $htp != "?")  $txlst .= ' hittypeprob2="'.$htp.'"';
    }
    // RANDOMMOVELOC --------------------------------------
    elseif ($efftyp == "randommoveloc")
    {
        // direction, distance
        $x03 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved3","0") );
        $x02 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","0") );
        
        if ($x03 != "?")                         $txval .= ' direction="'.($x03 - 1).'"';
        if ($x02 != "?")                         $txval .= ' distance="'.$x02.'"';
    }
    // REBIRTH --------------------------------------------
    elseif ($efftyp == "rebirth")
    {
        // resurrect_percent
        $x02 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","0") );
        
        if ($x02 != "?")                         $txval .= ' resurrect_percent="'.$x02.'"';
    }
    // REFLECTOR ------------------------------------------
    elseif ($efftyp == "reflector")
    {
        // hitvalue,hitdelta,radius,minradius,hittype,hittypeprob2
        $x02 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","0") );
        $x01 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved1","0") );
        $x05 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved5","0") );
        $x04 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4","0") );
        $hit = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved_cond1","?") );
        $htp = getEffSpecial( "nozero",getTabValue($key,$ename."reserved_cond1_prob2","0") );
        
        if ($x02 != "?")                         $txval .= ' hitvalue="'.$x02.'"';
        if ($x01 != "?")                         $txval .= ' hitdelta="'.$x01.'"';
        if ($x05 != "?")                         $txval .= ' radius="'.$x05.'"';
        if ($x04 != "?")                         $txval .= ' minradius="'.$x04.'"';
        if ($hit != "EVERYHIT" && $hit != "?")   $txlst .= ' hittype="'.$hit.'"';
        if ($htp != "100"      && $htp != "?")   $txlst .= ' hittypeprob2="'.$htp.'"';        
    }
    // ROOT -----------------------------------------------
    elseif ($efftyp == "root")
    {
        // resistchance
        $x02 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","0") );
        
        if ($x02 != "100"      &&  $x02 != "?") $txval .= ' resistchance="'.$x02.'"';  
    }
    // SIGNETBURST ----------------------------------------
    elseif ($efftyp == "signetburst")
    {
        // signetlvl,signet,value
        $x02   = getTabValue($key,$ename."reserved2","");
        $x07   = getTabValue($key,$ename."reserved7","");
        $x08   = getTabValue($key,$ename."reserved8","");
        
        $txval.= ' signetlvl="'.$x08.'" signet="SYSTEM_SKILL_SIGNET'.$x07.'"'.
                 ' value="'.$x02.'"';
    } 
    // SKILLATK -------------------------------------------
    elseif ($efftyp == "skillatk")
    {
        $x11  =                         getTabValue($key,$ename."reserved11","0");
        $x08  =                         getTabValue($key,$ename."reserved8" ,"0");
        $x12  = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved12","?") );
        $x02  = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2" ,"?") );
        $x04  = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4" ,"?") );
        $dmg  = "";
        $shr  = "";
        $mode = getEffValMode($efftyp,$key,$ename);
        
        if ($x11 == "2")      $txval .= ' cannotmiss="true"';
        if ($x08 != "0")      $dmg    = ' rnddmg="'.$x08.'"';
        if ($x12 == "ADDDMG") $shr    = ' shared="true"';   
        
        $txval .= $mode.$dmg.$shr;
        
        if     ($x04 != "?") 
        {
            $dta    = getTabValue($key,$ename."reserved3","?");
            $dta    = ($dta != "?" && $dta != "0") ? ' delta="'.$dta.'"' : '';
            $txval .= ' value="'.$x04.'"'.$dta; 
        }     
        elseif ($x02 != "?")
        {
            $dta    = getTabValue($key,$ename."reserved1","?");
            $dta    = ($dta != "?" && $dta != "0") ? ' delta="'.$dta.'"' : '';
            $txval .= ' value="'.$x02.'"'.$dta;
        } 
            
        $delta = "?";  
        $value = "?";
    }
    // SKILLATKDRAININSTANT -------------------------------
    elseif ($efftyp == "skillatkdraininstant")
    {
        $x15 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved15","?") );
        $x17 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved17","?") );
        $x12 = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved12","?") );        
        $x02 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2" ,"?") );
        $x04 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4" ,"?") );
        $shr = "";
        $mode = getEffValMode($efftyp,$key,$ename);
        
        if ($x15 != "?")      $txval .= ' hp_percent="'.$x15.'"';
        if ($x17 != "?")      $txval .= ' mp_percent="'.$x17.'"';
        if ($x12 == "ADDDMG") $shr    = ' shared="true"';   
        
        $txval .= $mode.$shr; 
        
        if     ($x04 != "?") 
        {
            $dta    = getTabValue($key,$ename."reserved3","?");
            $dta    = ($dta != "?" && $dta != "0") ? ' delta="'.$dta.'"' : '';
            $txval .= ' value="'.$x04.'"'.$dta; 
        }     
        elseif ($x02 != "?")
        {
            $dta    = getTabValue($key,$ename."reserved1","?");
            $dta    = ($dta != "?" && $dta != "0") ? ' delta="'.$dta.'"' : '';
            $txval .= $shr.' value="'.$x02.'"'.$dta;
        } 
            
        $delta = "?";  
        $value = "?";
    }
    // SKILLCOOLTIMERESET ---------------------------------
    elseif ($efftyp == "skillcooltimereset")
    {
        $x01 = getTabValue($key,$ename."reserved1","?");
        $x02 = getTabValue($key,$ename."reserved2","?");
        
        if ($x01 != "?"  &&  $x02 != "?")
        {
            $txval .= ' last_cd="'.$x02.'" first_cd="'.$x02.'"';
        }
    }
    // SPELLATKDRAIN
    elseif ($efftyp == "spellatkdrain")
    {
        $x15 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved15","?") );
        
        if ($x15 != "?")
        {
            $txval .= ' hp_percent="'.$x15.'"';
        }
    }
    // SPELLATKDRAININSTANT -------------------------------
    elseif ($efftyp == "spellatkdraininstant" )
    {
        $x15 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved15","?") );
        $x17 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved17","?") );
        $x04 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4" ,"?") );
        
        if ($x15 != "?")      $txval .= ' hp_percent="'.$x15.'"';
        if ($x17 != "?")      $txval .= ' mp_percent="'.$x17.'"';
        if ($x04 == "ADDDMG") $txval .= ' shared="true"'; 
        
        $txval .= getEffValMode($efftyp,$key,$ename); 
    }
    // SPELLATKDRAININSTANT -------------------------------
    elseif ($efftyp == "spellatkinstant" )
    {
        $x04 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4" ,"?") );
        
        if ($x04 == "ADDDMG") $txval .= ' shared="true"'; 
        
        $txval .= getEffValMode($efftyp,$key,$ename); 
    }
    // SUMMONFUNCTIONALNPC --------------------------------
    elseif ($efftyp == "summonfunctionalnpc")
    {
        // owner
        $x07 = getEffSpecial( "upper",getTabValue($key,$ename."reserved7","?") );
        
        switch($x07)
        {
            case "FORCE"  : $x07 = "ALLIANCE"; break;
            case "PARTY"  : $x07 = "GROUP";    break;
            case "GUILD"  : $x07 = "LEGION";   break;
            case "PRIVATE": $x07 = "PRIVATE";  break;
            default:        $x07 = "?";        break;
        }
        if ($x07 != "?")  $txkey .= ' owner="'.$x07.'"';
    } 
    // TARGETTELEPORT -------------------------------------
    elseif ($efftyp == "targetteleport")
    {
        $x09 = getTabValue($key,$ename."reserved9","?");
        
        if ($x09 == "0")  $txval .= ' same_map="true"';
        else              $txval .= ' same_map="false"';
    }    
    // WPNMASTERY -----------------------------------------
    elseif ($efftyp == "wpnmastery")
    {
        // weapon
        $x05 = getEffSpecial( "weapon",getTabValue($key,$ename."reserved5","?") );
        
        if ($x05 != "?")  $txkey .= ' weapon="'.$x05.'"';
    }      
}
// ----------------------------------------------------------------------------
//    H I L F S - F U N K T I O N E N   Z E I L E N A U F B E R E I T U N G
// ----------------------------------------------------------------------------
// Zeilen für die Changes aufbereiten
// ----------------------------------------------------------------------------
function getChangeStats($efftyp,$key,$e,$tbneg)
{        
    // Tabelle für alle Client-Felder, die STAT-Werte enthalten
    $tabstats = array(
                  //     Name         Value        Stats???
                  array("reserved5" ,"reserved2" ,"reserved2" ), 
                  array("reserved13","reserved2" ,"reserved6" ),
                  array("reserved14","reserved4" ,"reserved6" ),
                  array("reserved18","reserved16","reserved17"),
                  array("reserved22","reserved20","reserved19")
                     );
    $maxstats = count($tabstats);
    
    $ename    = "effect".$e."_";
    $ret      = "";
    $func     = "";
    $res9     = getTabValue($key,$ename."reserved9","?");
    $res1     = getTabValue($key,$ename."reserved1","?");
    $res2     = getTabValue($key,$ename."reserved2","?");
    $chend    = "/>";     
    
    // Condition ONFLY aufbereiten
    if ($res9 == "1")
        $chend = ">\n".
                 '                    <conditions>'."\n".
                 '                        <onfly/>'."\n".
                 '                    </conditions>'."\n".
                 '                </change>';
    
    if (stripos($efftyp,"skillxpboost") !== false)
    {
        $btext = getTabValue($key,"desc","?");
        $bname = getIntSkillName($btext);
    } 
    
    // spezielle Conditions vorab prüfen (Default-Changes !!!)
    switch($efftyp)
    {
        case "absoluteslow":
            if ($res2 != "?")
                $ret = '                <change stat="ATTACK_SPEED" func="REPLACE" value="'.($res2 * 100 ).'"/>'."\n"; 
            return $ret;
        case "absolutesnare":
            if ($res2 != "?")
                $ret = '                <change stat="SPEED" func="REPLACE" value="'.($res2 * 100 ).'"/>'."\n". 
                       '                <change stat="FLY_SPEED" func="REPLACE" value="'.($res2 * 100 ).'"/>'."\n";
            return $ret;   
        case "apboost":
            if ($res2 != "?")
            {
                $func = getTabValue($key,$ename."reserved1","?");
                $func = ($func == "?") ? "ADD" : "PERCENT";
                
                $ret = '                <change stat="AP_BOOST" func="'.$func.'" value="'.$res2.'"/>'."\n"; 
            }
            return $ret;   
        case "armormastery":
            $ret = '                <change stat="PHYSICAL_DEFENSE" func="PERCENT" value="'.$res2.'"/>'."\n";
            return $ret;
        case "boostdroprate":
            $ret = '                <change stat="BOOST_DROP_RATE" func="ADD" value="'.$res2.'"/>'."\n";
            return $ret;
        case "boosthate":
            $ret = '                <change stat="BOOST_HATE" func="PERCENT" value="'.$res2.'"/>'."\n";
            return $ret;
        case "boostheal":
            $ret = '                <change stat="HEAL_SKILL_BOOST" func="PERCENT" value="'.$res2.'"'.$chend."\n";
            return $ret;
        case "boostskillcastingtime":
            $zus = strtolower(getTabValue($key,$ename."reserved3","?"));
            
            switch($zus)
            {
                case "summontrap"  : $zus = "_TRAP";         break;
                case "summon"      : $zus = "_SUMMON";       break;
                case "summonhoming": $zus = "_SUMMONHOMING"; break;
                case "heal"        : $zus = "_HEAL";         break;
                case "attack"      : $zus = "_ATTACK";       break;
                default            : $zus = "";              break;
            }
            $ret = '                <change stat="BOOST_CASTING_TIME'.$zus.'" func="PERCENT" value="'.$res2.'"/>'."\n";
            return $ret;
        case "boostskillcost":      // keine Changes
            return "";  
        case "boostspellattack":    
            $ret = '                <change stat="BOOST_SPELL_ATTACK" func="PERCENT" value="'.$res2.'"'.$chend."\n";
            return $ret;      
        case "curse":
            if ($res2 != "?")
            {
                $func = getTabValue($key,$ename."reserved6","?");
                $func = ($func == "1") ? "PERCENT" : "ADD";
                
                $ret  = '                <change stat="MAXHP" func="'.$func.'" value="'.($res2 * -1).'"/>'."\n".
                        '                <change stat="MAXMP" func="'.$func.'" value="'.($res2 * -1).'"/>'."\n";                
            }
            return $ret;              
        case "deboostheal":
            if ($res2 != "?"  &&  $res2 != "0")
            {                
                $ret  = '                <change stat="HEAL_SKILL_DEBOOST" func="PERCENT" value="'.($res2 * -1).'"/>'."\n"; 
            }
            else
            {
                $res1 = getTabValue($key,$ename."reserved1","0");
                
                if ($res1 != "?"  &&  $res1 != "0")
                {                
                    $ret  = '                <change stat="HEAL_SKILL_DEBOOST" func="ADD" value="'.($res1 * -1).'"/>'."\n"; 
                }
            }
            return $ret; 
        case "drboost":
            $func = getTabValue($key,$ename."reserved1","?");
            $func = ($func == "1") ? "PERCENT" : "ADD";
            
            if ($res2 != "?"  &&  $res2 != "0")
                $ret  = '                <change stat="DR_BOOST" func="'.$func.'" value="'.$res2.'"/>'."\n";
            else
                $ret  = '                <change stat="DR_BOOST" func="'.$func.'"/>'."\n"; 
            return $ret;
        case "extendedaurarange":
            $func = getTabValue($key,$ename."reserved9","?");
            $func = ($func == "1") ? "PERCENT" : "ADD";
            $dta  = ($res1 != "?" && $res1 != "0") ? ' delta="'.$res1.'"' : '';
            
            if ($res2 != "?"  &&  $res2 != "0")
                $ret .= '                <change stat="BOOST_MANTRA_RANGE" func="'.$func.'" value="'.$res2.'"'.$dta.$chend."\n";
            return $ret;
        case "hide":
            if ($res2 != "?"  &&  $res2 != "100")
                $ret .= '                <change stat="SPEED" func="PERCENT" value="'.($res2  - 100).'"/>'."\n";
            return $ret;
        case "onetimeboostheal":
            $x04 = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4","0") );
            if ($x04 != "?")
                $ret .= '                <change stat="HEAL_SKILL_BOOST" func="PERCENT" value="'.$x04.'"/>'."\n";
            return $ret;
        case "shieldmastery":
            $dta = ($res1 != "?" && $res1 != "0") ? ' delta="'.$res1.'"' : '';
            $ret = '                <change stat="BLOCK" func="PERCENT"'.$dta.' value="'.$res2.'"/>'."\n";
            return $ret;
        case "skillxpboost#combine":
            if ($res2 != "?")
            {
                $func = getTabValue($key,$ename."reserved1","?");
                $func = ($func == "1") ? "PERCENT" : (stripos($bname,"%") !== false) ? "PERCENT" : "ADD";
                
                $ret .= '                <change stat="BOOST_COOKING_XP_RATE" func="'.$func.'" value="'.$res2.'"/>'."\n".
                        '                <change stat="BOOST_WEAPONSMITHING_XP_RATE" func="'.$func.'" value="'.$res2.'"/>'."\n".
                        '                <change stat="BOOST_ARMORSMITHING_XP_RATE" func="'.$func.'" value="'.$res2.'"/>'."\n".
                        '                <change stat="BOOST_TAILORING_XP_RATE" func="'.$func.'" value="'.$res2.'"/>'."\n".
                        '                <change stat="BOOST_ALCHEMY_XP_RATE" func="'.$func.'" value="'.$res2.'"/>'."\n".
                        '                <change stat="BOOST_HANDICRAFTING_XP_RATE" func="'.$func.'" value="'.$res2.'"/>'."\n";
            }
            return $ret;
        case "skillxpboost#extract":
            if ($res2 != "?")
            {
                $func = getTabValue($key,$ename."reserved1","?");
                $func = ($func == "1") ? "PERCENT" : (stripos($bname,"%") !== false) ? "PERCENT" : "ADD";
                
                $ret .= '                <change stat="BOOST_AETHERTAPPING_XP_RATE" func="'.$func.'" value="'.$res2.'"'.$chend."\n";
            }
            return $ret;
        case "skillxpboost#gather":
            if ($res2 != "?")
            {
                $func = getTabValue($key,$ename."reserved1","?");
                $func = ($func == "1") ? "PERCENT" : (stripos($bname,"%") !== false) ? "PERCENT" : "ADD";
                
                $ret .= '                <change stat="BOOST_ESSENCETAPPING_XP_RATE" func="'.$func.'" value="'.$res2.'"'.$chend."\n".
                        '                <change stat="BOOST_AETHERTAPPING_XP_RATE" func="'.$func.'" value="'.$res2.'"'.$chend."\n";
            }
            return $ret;
        case "skillxpboost#menuisier":
            if ($res2 != "?")
            {
                $func = getTabValue($key,$ename."reserved1","?");
                $func = ($func == "1") ? "PERCENT" : (stripos($bname,"%") !== false) ? "PERCENT" : "ADD";
                
                $ret .= '                <change stat="BOOST_MENUISIER_XP_RATE" func="'.$func.'" value="'.$res2.'"'.$chend."\n";
            }
            return $ret;
        case "slow":
            if ($res2 != "?")
            {
                $dta  = ($res1 != "?" && $res1 != "0") ? ' delta="'.($res1 * -1).'"' : '';
                $func = getTabValue($key,$ename."reserved6","?");
                $func = ($func == "1") ? "PERCENT" : "ADD";
                $ret  = '                <change stat="ATTACK_SPEED" func="'.$func.'"'.$dta.' value="'.($res2 * -1).'"'.'/>'."\n";
            }
            return $ret;
        case "snare":
            if ($res2 != "?")
            {
                $dta  = ($res1 != "?" && $res1 != "0") ? ' delta="'.$res1.'"' : '';
                $func = getTabValue($key,$ename."reserved6","?");
                $func = ($func == "1") ? "PERCENT" : "ADD";
                $ret  = '                <change stat="SPEED" func="'.$func.'" value="'.($res2 * -1).'"'.$dta.'/>'."\n". 
                        '                <change stat="FLY_SPEED" func="'.$func.'" value="'.($res2 * -1).'"'.$dta.'/>'."\n";
                $ret .= getEffectBasicConditions($efftyp,$key,$e);
            }
            return $ret;
        case "statboost"      :
        case "weaponstatboost":
        case "weaponstatup"   :
            if ($efftyp == "weaponstatboost"
            ||  $efftyp == "statboost")
            {
                // aus reserved2=ADD oder aus reserved4=PERCENT
                if ($res2 != "?")
                {
                    $val  = $res2;
                    $func = "ADD";
                }
                else
                {
                    $val  = getTabValue($key,$ename."reserved4","?");
                    $func = "PERCENT";
                }
            }
            else
            {
                // aus reserved2 und func gem. reserved6
                $val  = $res2;
                $func = getTabValue($key,$ename."reserved6","?");
                $func = ($func == "1") ? "PERCENT" : "ADD";
            }
            
            if ($val != "?")
            {
                $x13  = getTabValue($key,$ename."reserved13","?");
                $x05  = getEffSpecial( "upper",getTabValue($key,$ename."reserved5","?") );
                $dta  = ($res1 != "?" && $res1 != "0") ? ' delta="'.$res1.'"' : '';
                $stab = explode(",", getStatNames($x13) );
                $smax = count($stab);
                
                if ($x05 != "?" && $x05 != "" && $x05 != "ALLWP")
                    $cend = ">\n".
                            '                    <conditions>'."\n".
                            '                        <weapon weapon="'.getEffectWeaponType($x05).'"/>'."\n".
                            '                    </conditions>'."\n".
                            '                </change>';
                else
                    $cend = '/>';
                
                for ($s=0;$s<$smax;$s++) 
                {                
                    $sign = getValueSign($efftyp,$stab[$s],$res2,$tbneg);              
                    $ret .= '                <change stat="'.$stab[$s].'" func="'.$func.'" value="'.($val * $sign).'"'.$dta.$cend."\n";
                }
            }
            return $ret;
        case "subtypeboostresist":
            if ($res2 != "?")
            {
                $x03  = getTabValue($key,$ename."reserved3","?");
                $dta  = ($res1 != "?" && $res1 != "0") ? ' delta="'.$res1.'"' : '';
                $stab = explode(",", getStatNames($x03) );
                $smax = count($stab);
                
                for ($s=0;$s<$smax;$s++)
                    $ret .= '                <change stat="'.$stab[$s].'" func="ADD" value="'.$res2.'"'.$dta.'/>'."\n";         
            }
            return $ret;
        case "xpboost":
            if ($res2 == "?")
            {
                $val  = getTabValue($key,$ename."reserved4","?");
                $func = "ADD";
                $dval = getTabValue($key,$ename."reserved3","?");
            }
            else
            {
                $val  = $res2;
                $func = getTabValue($key,$ename."reserved6","?");
                $func = ($func == "1") ? "ADD" : "PERCENT";
                $dval = $res1;
            }
            
            if ($val != "?")
            {
                $stab = explode(",",getStatNames($efftyp) );
                $smax = count($stab);
                $dta  = ($dval != "?" && $dval != "0") ? ' delta="'.$dval.'"' : '';
                
                for ($s=0;$s<$smax;$s++) 
                {                              
                    $ret .= '                <change stat="'.$stab[$s].'" func="'.$func.'" value="'.$val.'"'.$dta."/>\n";
                }
            }
            return $ret;
        default:
            break;
    }
    
    // für alle Stat-Werte aus obiger Tabelle!
    for ($t=0;$t<$maxstats;$t++)
    {
        $name  = getTabValue($key,$ename.$tabstats[$t][0],"?");
        $value = getTabValue($key,$ename.$tabstats[$t][1],"0");
        $func  = getEffectFunc($efftyp,$key,$ename.$tabstats[$t][2] );
        
        if (($name != "?" && $name != "0") && $value != "?")
        {  
            $stab = explode(",", getStatNames($name) );
            $smax = count($stab);  
            
            for ($s=0;$s<$smax;$s++)
            {
                $sndel = getValueSign($efftyp,$stab[$s],$res1 ,$tbneg);
                $snval = getValueSign($efftyp,$stab[$s],$value,$tbneg);
                
                $ret .= '                <change stat="'.strtoupper($stab[$s]).'" func="'.$func.'"';
                
                // Delta ausgeben, wenn ungleich "?" oder "0"
                if ($res1 != "?" && $res1 != "0")
                    $ret .= ' delta="'.($res1 * $sndel).'"';
                                    
                $ret .= ' value="'.($value * $snval).'"'.$chend."\n";
            }
        }
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die Subeffekte aufbereiten
// ----------------------------------------------------------------------------
function getSubEffect($efftyp,$key,$e,$field)
{
    $ret    = "";
    
    $ename  = "effect".$e."_";
    $sub    = getEffSpecial( "upper",getTabValue($key,$ename.$field,"?") );
    
    if ($sub != "?")
    {
        $sid  = getRefSkillNameId($key,$sub);
        
        if ($sid != "?")
        {
            $x18  = getTabValue($key,$ename."reserved18","0");
            $ret .= '                <subeffect skill_id="'.$sid.'"';
            
            if ($x18 != "0" && $x18 != "100")
                $ret .= ' chance="'.$x18.'"';
                
            if (stripos($sub,"_ADDEFFECT") !== false)
                $ret .= ' addeffect="true"';
            
            $ret .= '/>'."\n";
        }
    }
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die Modifiers aufbereiten
// ----------------------------------------------------------------------------
function getModifiers($efftyp,$key,$e,$field)
{
    $ret    = "";
    
    $ename  = "effect".$e."_";
    $mod    = getEffSpecial( "upper",getTabValue($key,$ename.$field,"?") );
    $dta    = "";
        
    if ($mod != "?")
    {
        $mode = getEffValMode($efftyp,$key,$ename); 
        $val  = "?";
        
        if ($efftyp == "skillatk")
        {
            $val  = getTabValue($key,$ename."reserved10","?");
            $x09  = getEffSpecial( "nozero",getTabValue($key,$ename."reserved9","?") );
            $dta  = ($x09 != "?") ? ' delta="'.$x09.'"' : "";
        }
        elseif ($efftyp == "spellatkdraininstant" 
        ||      $efftyp == "spellatkinstant")
        {
            $val  = getTabValue($key,$ename."reserved9","?");
            $x08  = getEffSpecial( "nozero",getTabValue($key,$ename."reserved8","?") );
            $dta  = ($x08 != "?") ? ' delta="'.$x08.'"' : "";
        }
        
        $stab = explode(",",$mod);
        $smax = count($stab);
                
        for ($s=0;$s<$smax;$s++)
        {
            if ($val != "?")
            {
                $stab[$s] = trim($stab[$s]);
                
                if     (substr($stab[$s],0,6) == "_RACE_")
                {
                    $race = substr($stab[$s],6);
                    $race = ($race == "PC_DARK")  ? "ASMODIANS" : $race;
                    $race = ($race == "PC_LIGHT") ? "ELYOS"     : $race;
                    
                    $ret .= '                    <targetrace race="'.$race.'"'.$mode.' value="'.$val.'"'.$dta.'/>'."\n";
                }
                elseif (substr($stab[$s],0,7) == "_CLASS_")
                {
                    $class = substr($stab[$s],7);
                    if     ($class == "ELEMENTALLIST")       $class = "SPIRIT_MASTER";
                    elseif ($class == "FIGHTER")             $class = "GLADIATOR";
                    elseif ($class == "KNIGHT")              $class = "TEMPLAR";
                    elseif ($class == "WIZARD")              $class = "SORCERER";
                   
                    $ret .= '                    <targetclass class="'.$class.'" value="'.$val.'"'.$dta.'/>'."\n";
                }
                elseif ($stab[$s] == "_BACK")
                {
                    $ret .= '                    <backdamage'.$mode.' value="'.$val.'"'.$dta.'/>'."\n";
                }
                elseif ($stab[$s] == "_FRONT")
                {
                    $ret .= '                    <frontdamage'.$mode.' value="'.$val.'"'.$dta.'/>'."\n";
                }
                elseif ($stab[$s] == "_NFLYING")
                {
                    $ret .= '                    <abnormaldamage state="NOFLY"'.$mode.' value="'.$val.'"'.$dta.'/>'."\n";
                }
                elseif ($stab[$s] == "BLEED"
                ||      $stab[$s] == "CURSE" 
                ||      $stab[$s] == "OPENAERIAL"
                ||      $stab[$s] == "PARALYZE"
                ||      $stab[$s] == "POISON" 
                ||      $stab[$s] == "SNARE" 
                ||      $stab[$s] == "STUMBLE"
                ||      $stab[$s] == "STUN" )
                {
                    $ret .= '                    <abnormaldamage state="'.$stab[$s].'"'.$mode.' value="'.$val.'"'.$dta.'/>'."\n";
                }
            }
        }
    }
    
    if ($ret != "")
        $ret = '                <modifiers>'."\n".
               $ret.
               '                </modifiers>'."\n";               
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die SubConditions aufbereiten
// ----------------------------------------------------------------------------
function getSubConditions($efftyp,$key,$e,$field)
{
    $ret    = "";
    
    $ename  = "effect".$e."_";
    $mod    = getEffSpecial( "upper",getTabValue($key,$ename.$field,"?") );
    
    if ($mod != "?")
    {
        $stab = explode(",",$mod);
        $smax = count($stab);
                
        for ($s=0;$s<$smax;$s++)
        {
            if ($stab[$s] == "_NFLYING")  $ret .= '                    <noflying/>'."\n";
            if ($stab[$s] == "_FRONT")    $ret .= '                    <front/>'."\n";
            if ($stab[$s] == "BLEED" 
            ||  $stab[$s] == "PARALYZE"
            ||  $stab[$s] == "STUMBLE" 
            ||  $stab[$s] == "STUN"    )  $ret .= '                    <abnormal value="'.$stab[$s].'"/>'."\n";
        }
    }
    
    if ($ret != "")
        $ret = '                <subconditions>'."\n".
               $ret.
               '                </subconditions>'."\n";
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die Conditions zum Effekt aufbereiten (ohne CHANGE)
// ----------------------------------------------------------------------------
function getEffectBasicConditions($efftyp,$key,$e)
{
    $ret   = "";
    $ename = "effect".$e."_";
    
    if ($efftyp == "fall" 
    ||  $efftyp == "fpatkinstant"
    ||  $efftyp == "openaerial"
    ||  $efftyp == "paralyze"
    ||  $efftyp == "poison"
    ||  $efftyp == "skillatkdraininstant"
    ||  $efftyp == "snare"
    ||  $efftyp == "spellatk" 
    ||  $efftyp == "spellatkdraininstant"    
    ||  $efftyp == "stun"  )
    {
        $cst = getEffSpecial( "upper",getTabValue($key,$ename."cond_status","?") );
        $dir = getTabValue($key,$ename."cond_attack_dir","?");
        
        if ($cst != "?")
        {
            switch ($cst)
            {
                case "_FLYING" : 
                    $ret .= '                <conditions>'."\n".
                            '                    <targetflying/>'."\n".
                            '                </conditions>'."\n";
                    break;
                case "_NFLYING":                
                    $ret .= '                <conditions>'."\n".
                            '                    <noflying/>'."\n".
                            '                </conditions>'."\n"; 
                    break;
                default:
                    $ret .= '                <conditions>'."\n".
                            '                    <abnormal value="'.$cst.'"/>'."\n".
                            '                </conditions>'."\n";
                    break;
            }
        }
        
        if ($dir == "1")
        {
            $ret .= '                <conditions>'."\n".
                    '                    <back/>'."\n".
                    '                </conditions>'."\n";
        }
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die SubEffekte zum Effekt aufbereiten (ohne Change)
// ----------------------------------------------------------------------------
function getEffectBasicSubEffect($efftyp,$key,$e)
{
    $ret   = "";
    $ename = "effect".$e."_";
    
    if     ($efftyp == "procatk_instant")
        $ret = getSubEffect($efftyp,$key,$e,"reserved14");
    elseif ($efftyp == "skillatk")
        $ret = getSubEffect($efftyp,$key,$e,"reserved14");
    elseif ($efftyp == "spellatkinstant")
        $ret = getSubEffect($efftyp,$key,$e,"reserved14");
        
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die Modifiers zum Effekt aufbereiten (ohne Change)
// ----------------------------------------------------------------------------
function getEffectBasicModifiers($efftyp,$key,$e)
{
    $ret   = "";
    $ename = "effect".$e."_";    
    
    if ($efftyp == "skillatk"
    ||  $efftyp == "spellatkinstant")
        $ret = getModifiers($efftyp,$key,$e,"reserved16");
    elseif ($efftyp == "spellatkdraininstant")
        $ret = getModifiers($efftyp,$key,$e,"reserved7");
            
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die SubConditions zum Effekt aufbereiten (ohne Change)
// ----------------------------------------------------------------------------
function getEffectBasicSubConditions($efftyp,$key,$e)
{
    $ret   = "";
    $ename = "effect".$e."_";
    
    if ($efftyp == "skillatk")
    {
        $x14 = getTabValue($key,$ename."reserved14","?");
        
        // subconditions nur, wenn auch reserved14 vorgegeben wurde!
        if ($x14 != "?")
            $ret = getSubConditions($efftyp,$key,$e,"reserved16");
    }
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Effect-Werte speziell bearbeiten / zurückgeben           (ZENTRALE ROUTINE)
//
// params: $spec   =  Typ der speziellen Behandlung
//         $wert   =  Wert, der behandelt werden muss
// ----------------------------------------------------------------------------
function getEffSpecial($spec,$wert)
{
    $ret = "";
    
    switch($spec)
    {
        case "upper":  // Rückgabe in Grossbuchstaben
            return strtoupper($wert);
        case "lower":  // Rückgabe in Kleinbuchstaben
            return strtolower($wert);
        case "npcid":  // Rückgabe der NpcId
            $tab = getNpcIdNameTab($wert);
            $ret = ($tab['npcid'] != "000000") ? $tab['npcid'] : "?";            
            return $ret;
        case "nozero": // Rückgabe 0 = ? (keine "0")
            $ret = ($wert == "0") ? "?" : $wert;
            return $ret;
        case "true1":  // Rückgabe 1=true
            $ret = ($wert == "1") ? "true" : "?";
            return $ret;
        case "preff":  // Rückgabe von PreEffectId
            return substr($wert,1);
        case "prob2";  // Rückgabe bei 0/100 = ?
            if ($wert == "0" || $wert == "100")
                return "?";
            else
                return $wert;
        case "weapon": // Weapon-Type zurückgeben!
            return getEffectWeaponType($wert);
        case "state":  // State-Type zurückgeben
            if ($wert == "1")
                return "ROOT";
            else
                return "?";
        default:       // unbekannter Wert, protokollieren!
            logLine("Fehler getEffSpecial",$feld." / ".$wert);
            return $ret;
    }
    return "";
}
// ----------------------------------------------------------------------------
// EffectBasicLine zurückgeben
// Offensichtlich besitzen alle Block-XML-Tags identische Angaben, sodass
// diese hier zentral aufbereitet werden können
// ----------------------------------------------------------------------------
function getEffectBasicLine($efftyp,$key,$e)
{     
    $ename = "effect".$e."_";     
    $ret   = "";
    
    // alle notwendigen Daten aus dem Client auslesen
    $acmod = getEffSpecial( "nozero",getTabValue($key,$ename."acc_mod2","?") );
    $blev  = getEffSpecial( "nozero",getTabValue($key,$ename."basiclv","?") );
    $dura1 = getEffSpecial( "nozero",getTabValue($key,$ename."remain1","?") );
    $dura2 = getEffSpecial( "nozero",getTabValue($key,$ename."remain2","?") );
    $effid = getEffSpecial( "nozero",getTabValue($key,$ename."effectid","?") );
    $hopa  = getEffSpecial( "nozero",getTabValue($key,$ename."hop_a","?") ); 
    $hopb  = getEffSpecial( "nozero",getTabValue($key,$ename."hop_b","?") );   
    $htyp  = getEffSpecial( "upper" ,getTabValue($key,$ename."hop_type","?") );
    $model = getEffSpecial( "npcid" ,getTabValue($key,$ename."reserved9","?") );
    $nores = getEffSpecial( "true1" ,getTabValue($key,$ename."noresist","?") );
    $preff = getEffSpecial( "preff" ,getTabValue($key,$ename."cond_preeffect","?") );
    $prob2 = getEffSpecial( "prob2" ,getTabValue($key,$ename."cond_preeffect_prob2","?") );
    $tran  = getEffSpecial( "nozero",getTabValue($key,$ename."randomtime","?") );
    
    // nur bedingt relevante Werte per Funktion ermitteln
    $adist = getEffValDistance ($efftyp,$key,$ename);
    $check = getEffValChecktime($efftyp,$key,$ename);
    $crit2 = getEffValCritPM2  ($efftyp,$key,$ename);
    $delta = getEffValDelta    ($efftyp,$key,$ename); 
    $elem  = getEffValElement  ($efftyp,$key,$ename);
    $perct = getEffValPercent  ($efftyp,$key,$ename);
    $skid  = getEffValSkillid  ($efftyp,$key,$ename); 
    $state = getEffValState    ($efftyp,$key,$ename);
    $time  = getEffValTime     ($efftyp,$key,$ename);
    $type  = getEffValType     ($efftyp,$key,$ename);  
    $value = getEffValValue    ($efftyp,$key,$ename);
    
    // nur bedingt relevante Werte, werden nachfolgend direkt ermittelt
    $atcnt = "?";
    $condv = "?";
    $cradd = "?";
    $delay = "?";
    $distz = "?";
    $npcnt = "?";
    $panel = "?";
    $share = "?";
    
    // komplette Texte    
    $txkey = "";            // für Keys
    $txval = "";            // für Attribute am Anfang
    $txlst = "";            // für Atrribute am Ende
        
    // Tag-Name für die ID (Model/NPC) vorbereiten
    $npctag = (stripos($efftyp,"summon") !== false) ? "npc_id" : "model"; 
        
    // ----------------------------------------------------
    // Ermitteln einiger Effekt-abhängiger Werte (direkt)
    // ----------------------------------------------------  
    
    // ATTACK_COUNT ---------------------------------------
    if ($efftyp == "summonhoming")
    {
        $atcnt = getTabValue($key,$ename."reserved4","?");
    } 
    // COND_VALUE -----------------------------------------
    if ($efftyp == "caseheal")
    {
        $condv = getTabValue($key,$ename."reserved10","?");
    }
    // CRITADDDMG2 ----------------------------------------
    if ($efftyp == "spellatkdraininstant")
    {
        $cradd = getEffSpecial( "nozero",getTabValue($key,$ename."critical_add_dmg_mod2","?") );
    }
    // DELAY ----------------------------------------------
    if ($efftyp == "delaydamage"
    ||  $efftyp == "delayedfpatk_instant")
    {
        $delay = getTabValue($key,$ename."reserved9","?");
    }
    // DISTANCE_Z -----------------------------------------
    if ($efftyp == "aura")
    {
        $distz = getTabValue($key,$ename."reserved4","?");
    } 
    // NPC_COUNT ------------------------------------------
    if ($efftyp == "summonhoming")
    {
        $npcnt = getTabValue($key,$ename."reserved6","?");
    } 
    // PANELID --------------------------------------------
    if ($efftyp == "polymorph" 
    ||  $efftyp == "shapechange")
    {
        $panel = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4","?") );
    } 
    // SHARED ---------------------------------------------
    if ($efftyp == "delaydamage")
    {
        $share = getTabValue($key,$ename."reserved19","?");
        $share = ($share == "1") ? "true" : "?";
    }
    // ----------------------------------------------------
    // hinzufügen einiger, kompletter Tag-Texte 
    // ----------------------------------------------------
    // immer vorab einfügen!
    // ----------------------------------------------------
    
    $txval .= getFieldText("percent",$perct);
    
    // die Parameter txkey,txval und txlst werden in der
    // aufgerufenen Funktion verändert
    getEffValAddText($efftyp,$key,$ename,$txkey,$txval,$txlst);
    
    // ----------------------------------------------------
    // spezielle Aufbereitungen für einzelne Effekte
    //
    // lediglich die Reihenfolge der Attribute wird hier 
    // angepasst, damit der Abgleich erleichtert wird
    // ----------------------------------------------------
    switch($efftyp)
    {
        // STATE,TYPE,...
        case "hide":
            $txval = getFieldText("state",$state).
                     getFieldText("type" ,$type).$txval;
            $state = "?";
            $type  = "?";
            break;
        // ...CHECKTIME,...
        case "dpheal"       :
        case "fpheal"       :
        case "mpattack"     :
        case "mpheal"       :
        case "spellatkdrain":
            $txval .= getFieldText("checktime",$check);
            $check  = "?";
            break;
        // DISTANCE, ...
        case "targetteleport":
            $txval  = getFieldText("distance",$adist).$txval;
            $adist  = "?";
            break;            
        default:
            break;        
    }
    // ----------------------------------------------------
    // Allgemeine Zeile mit allen aktiven Tags aufbereiten 
    //
    // Felder mit Inhalt = "?" werden nicht ausgegeben !!!
    // ----------------------------------------------------    
        
    $ret .= $txkey.
            getFieldText( "checktime"     ,$check ).
            getFieldText( "delay"         ,$delay ).
            getFieldText( "shared"        ,$share ).
            getFieldText( "attack_count"  ,$atcnt ).
            getFieldText( "npc_count"     ,$npcnt ).
            getFieldText( "time"          ,$time  ).
            getFieldText( $npctag         ,$model ).
            getFieldText( "skill_id"      ,$skid  ).
            getFieldText( "type"          ,$type  ).
            $txval.
            getFieldText( "cond_value"    ,$condv ).
            getFieldText( "state"         ,$state ).   
            getFieldText( "panelid"       ,$panel ).
            getFieldText( "value"         ,$value ).
            getFieldText( "delta"         ,$delta ).
            getFieldText( "duration2"     ,$dura2 ).
            getFieldText( "duration1"     ,$dura1 ).
            getFieldText( "randomtime"    ,$tran  ).
            getFieldText( "distance"      ,$adist ).
            getFieldText( "distance_z"    ,$distz ).
            getFieldText( "effectid"      ,$effid ).
            getFieldText( "e"             ,$e     ).
            getFieldText( "basiclvl"      ,$blev  ).
            getFieldText( "noresist"      ,$nores ).
            $txlst.
            getFieldText( "accmod2"       ,$acmod ).
            getFieldText( "element"       ,$elem  ).
            getFieldText( "preeffect"     ,$preff ).
            getFieldText( "preeffect_prob",$prob2 ).
            getFieldText( "critadddmg2"   ,$cradd ).
            getFieldText( "critprobmod2"  ,$crit2 ).
            getFieldText( "hoptype"       ,$htyp  ).
            getFieldText( "hopb"          ,$hopb  ).
            getFieldText( "hopa"          ,$hopa  );
                            
    return $ret;
}
// ---------------------------------------------------------------------------
// merken Effekt für den SVN-Abgleich
// ---------------------------------------------------------------------------
function setEffectSvnCompare($efftyp,$linetyp)
{
    global $tabeffsvn,$tabeffxsd;
    
    $tabeffxsd[$efftyp] = 4;
    
    // wenn nicht gesetzt oder LineTyp == "B", dann setzen bzw. überschreiben
    if (!isset($tabeffsvn[$efftyp])
    ||  $linetyp == "B")
        $tabeffsvn[$efftyp] = $linetyp;
}
// ---------------------------------------------------------------------------
//
//                   D E F A U L T - E F F E K T - Z E I L E N
//
// ---------------------------------------------------------------------------
// Effect aufbereiten für: alle einzeiligen Effekte
//
// gibt einen Einzeiler für den jeweiligen Effekt zurück. Besonderheiten
// eines Effektes werden in der Funktion getEffectBasicLine behandelt!
// ---------------------------------------------------------------------------
function getEffectDefault($efftyp,$key,$e)
{    
    $ret   = getEffectBasicLine($efftyp,$key,$e);
    $cond  = getEffectBasicConditions($efftyp,$key,$e);
    $cond .= getEffectBasicSubEffect($efftyp,$key,$e);
    $cond .= getEffectBasicModifiers($efftyp,$key,$e);
    $cond .= getEffectBasicSubConditions($efftyp,$key,$e);
    
    if ($ret != "")
    {
        if ($cond != "")
        {
            setEffectSvnCompare($efftyp,"B");
            
            $ret = '            <'.$efftyp.$ret.'>'."\n".
                   $cond.
                   '            </'.$efftyp.'>'."\n";
        }
        else
        {
            setEffectSvnCompare($efftyp,"L");
            
            $ret = '            <'.$efftyp.$ret.'/>'."\n";
        }
    }
    
    return $ret;    
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: alle Effekte mit CHANGES-Zeilen
// ---------------------------------------------------------------------------
function getEffectDefaultChanges($efftyp,$key,$e,&$tbneg)
{  
    setEffectSvnCompare($efftyp,"B");
    
    $ret   = getEffectBasicLine($efftyp,$key,$e);
            
    if ($ret != "")
    {
        $stat = getChangeStats($efftyp,$key,$e,$tbneg);
        
        if ($stat != "")
        {
            $ret = '            <'.$efftyp.$ret.'>'."\n".
                   $stat.
                   '            </'.$efftyp.'>'."\n";
        }
        else
            $ret = '            <'.$efftyp.$ret.'/>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
//
//            I N D I V I D U E L L E   E F F E K T - Z E I L E N
//
// ---------------------------------------------------------------------------
// Effect aufbereiten für: ...signet...
// ---------------------------------------------------------------------------
function getEffectSignetAll($efftyp,$key,$e)
{    
    $xml = strtolower($efftyp);
    $ret = getEffectBasicLine($xml,$key,$e);
    $sub = "";    
    
    if ($ret != "")
    {        
        switch ($efftyp)
        {
            case "carvesignet":  
                $sub = getSubEffect($efftyp,$key,$e,"reserved7");  
                break;
            case "signetburst":  
                $sub = getSubEffect($efftyp,$key,$e,"reserved15"); 
                
                if ($sub == "")
                    $sub = getSubEffect($efftyp,$key,$e,"reserved13");
                break;
            default:                                                    
                break;
        }
        if ($sub != "")
        {
            setEffectSvnCompare($efftyp,"B");
            
            $ret  = '            <'.$efftyp.$ret.'>'."\n";
            $ret .= $sub;
            $ret .= '            </'.$efftyp.'>'."\n";
        }
        else
        {
            setEffectSvnCompare($efftyp,"L");
            
            $ret  = '            <'.$efftyp.$ret.'/>'."\n";
        }
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: dispel
// ---------------------------------------------------------------------------
function getEffectDispel($efftyp,$key,$e)
{
    $ret   = getEffectBasicLine($efftyp,$key,$e);
    $ename = "effect".$e."_";
    
    if ($ret != "")
    {
        $disp  = "";
        $x01   = getEffSpecial( "lower",getTabValue($key,$ename."reserved1","?") );        
        $x01   = str_replace("_","",$x01); 
        
        for ($x=2;$x<10;$x++)   // reserved2 bis reserved9
        {
            $x02   = getEffSpecial( "upper",getTabValue($key,$ename."reserved".$x,"?") );
            
            if ($x02 != "?")
            {
                if     ($x02 == "SPECIAL")   $x02 = "SPEC";
                elseif ($x02 == "SPECIAL2")  $x02 = "SPEC2";
                
                $disp .= '                <';
                
                if (stripos($x01,"TYPE") !== false)
                    $disp .= $x01.'>'.$x02.'</'.$x01.'>'."\n";
                else    
                    $disp .= 'effectids>'.$x02.'</effectids>'."\n";
            }
        }
        if ($disp != "")
        {
            setEffectSvnCompare($efftyp,"B");
            
            $ret = '            <'.$efftyp.$ret.'>'."\n".
                   $disp.
                   '            </'.$efftyp.'>'."\n";
        }
        else
        {
            setEffectSvnCompare($efftyp,"L");
            
            $ret = '            <'.$efftyp.$ret.'/>'."\n";
        }
    }
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: evade
// ---------------------------------------------------------------------------
function getEffectEvade($efftyp,$key,$e)
{
    $ret   = getEffectBasicLine($efftyp,$key,$e);
    $ename = "effect".$e."_";
    
    if ($ret != "")
    {
        $efft = "";
        
        for ($t=2;$t<10;$t++)
        {
            $fld = getEffSpecial( "upper",getTabValue($key,$ename."reserved".$t,"?") );
            
            if ($fld != "?")
                $efft .= '                <effecttype>'.$fld.'</effecttype>'."\n";
        }
        
        if ($efft != "")
        {
            setEffectSvnCompare($efftyp,"B");
            
            $ret = '            <'.$efftyp.$ret.'>'."\n".
                   $efft.
                   '            </'.$efftyp.'>'."\n";
        }
        else
        {
            setEffectSvnCompare($efftyp,"L");
            
            $ret = '            <'.$efftyp.$ret.'/>'."\n";
        }
    }
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aubereiten für: skillxpboost
// ---------------------------------------------------------------------------
function getEffectSkillXpBoost($pEfftyp,$key,$e)
{
    $tbneg  = array();
    $efftyp = substr($pEfftyp,0,stripos($pEfftyp,"#"));
    $ret    = getEffectBasicLine($efftyp,$key,$e);    
    
    if ($ret != "")
    {    
        $stat = getChangeStats($pEfftyp,$key,$e,$tbneg);
        
        if ($stat != "")
        {
            setEffectSvnCompare($efftyp,"B"); 
            
            $ret = '            <'.$efftyp.$ret.'>'."\n".
                   $stat.
                   '            </'.$efftyp.'>'."\n";
        }
        else
        {
            setEffectSvnCompare($efftyp,"L");
            
            $ret = '            <'.$efftyp.$ret.'/>'."\n";
        }
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: Effects
// ---------------------------------------------------------------------------
function getEffectsLines($key)
{
    global $tabcskill,$tabeffxsd;
    
    $ret    = "";
    
    // ........
    /*
        NOTINEMU    absoluteexppoint_heal_instant
        NOTINEMU    activate_enslave
        NOTINEMU    alwayshit
        NOTINEMU    alwaysnoresist
        NOTINEMU    dispelallcounteratk  (momentasn Ausgabe als dispelbuffcounteratk)
        NOTINEMU    dummy
        NOTINEMU    petorderunsummon
        NOTINEMU    petrification
        NOTINEMU    supportevent
        NOTINEMU    utility
        NOTINEMU    110282
        
        NOTUSED     buffsleep
        NOTUSED     changehateonattacked
        NOTUSED     deathblow
        NOTUSED     nofpconsum
        NOTUSED     stunalways
        NOTUSED     subtypeextendduration
        NOTUSED     summonbindinggroupgate 
        
        INTEST      dptransfer                  <= aktuell nicht im Client, Ausgabe daher nicht abgleichbar     
        INTEST      dispelnpcdebuff             <= aktuell nicht im Client, Ausgabe daher nicht abgleichbar 
        INTEST      dispelallcounterakt         <= siehe oben        
    */
    // die verschiedenen Tabellen für negierte Werte aufbereiten
    $tbneg0 = array();                      // keine negierten Werte
    $tbneg1 = array("ATTACK_SPEED");        // nur ATTACK_SPEED
    
    for ($e=1;$e<5;$e++)
    {
        $effkey = "effect".$e."_type";
        $efftyp = getEmuEffectTag(getTabValue($key,$effkey,"?"));
                
        switch($efftyp)
        { 
            // KEINE EFFECTS
            case "?"                         :  /* kein EffektType vorhanden */                              break;  

            // DEFAULT-ZEILE = EINZEILER
            case "absstatbuff"               :  
            case "absstatdebuff"             :  
            case "alwaysblock"               :
            case "alwaysdodge"               :
            case "alwaysparry"               :
            case "alwaysresist"              :   
            case "aura"                      : 
            case "backdash"                  :  
            case "bind"                      :  
            case "bleed"                     :  
            case "blind"                     :  
            case "buffbind"                  :
            case "buffsilence"               :
            case "buffstun"                  :  
            case "caseheal"                  :  
            case "closeaerial"               :  
            case "condskilllauncher"         :  
            case "confuse"                   :  
            case "convertheal"               :  
            case "dash"                      :  
            case "deform"                    :  
            case "delaydamage"               :  
            case "delayedfpatk_instant"      :  
            case "delayedskill"              :  
            case "disease"                   :  
            case "dispelbuff"                :
            case "dispelbuffcounteratk"      :
            case "dispeldebuff"              :
            case "dispeldebuffmental"        :
            case "dispeldebuffphysical"      :
            case "dispelnpcbuff"             :
            case "dispelnpcdebuff"           :
            case "dpheal"                    :
            case "dphealinstant"             :
            case "dptransfer"                :
            case "escape"                    :  
            case "fall"                      :  
            case "fear"                      :
            case "flyoff"                    :  
            case "fpatk"                     :
            case "fpatkinstant"              :
            case "fpheal"                    :
            case "fphealinstant"             :
            case "heal"                      :
            case "healcastoronatk"           :
            case "healcastorontargetdead"    :
            case "healinstant"               :
            case "hipass"                    : 
            case "hostileup"                 :
            case "invulnerablewing"          :
            case "magiccounteratk"           :
            case "movebehind"                :  
            case "mpattack"                  :
            case "mpattackinstant"           :
            case "mpheal"                    :
            case "mphealinstant"             :
            case "mpshield"                  :
            case "nodeathpenalty"            :
            case "nofly"                     :
            case "noreducespellatk"          :
            case "noresurrectpenalty"        :
            case "onetimeboostskillattack"   :
            case "onetimeboostskillcritical" :
            case "openaerial"                :
            case "paralyze"                  :
            case "petorderuseultraskill"     :
            case "poison"                    :
            case "polymorph"                 :
            case "procatk_instant"           :
            case "procdphealinstant"         :
            case "procfphealinstant"         :
            case "prochealinstant"           :
            case "procmphealinstant"         :
            case "procvphealinstant"         :
            case "protect"                   :
            case "provoker"                  :
            case "pulled"                    :
            case "randommoveloc"             :
            case "rebirth"                   :
            case "recallinstant"             :
            case "reflector"                 :
            case "resurrect"                 :
            case "resurrectbase"             :
            case "resurrectpos"              :
            case "return"                    :  
            case "returnpoint"               : 
            case "riderobot"                 :
            case "root"                      :
            case "sanctuary"                 :     
            case "search"                    :
            case "shapechange"               : 
            case "shield"                    :
            case "silence"                   :
            case "simpleroot"                :
            case "skillatk"                  :
            case "skillatkdraininstant"      :
            case "skillcooltimereset"        :
            case "skilllauncher"             :
            case "sleep"                     : 
            case "spellatk"                  :
            case "spellatkdrain"             :
            case "spellatkdraininstant"      :
            case "spellatkinstant"           :
            case "spin"                      :
            case "stagger"                   :
            case "stumble"                   :
            case "stun"                      :
            case "summon"                    :  
            case "summonfunctionalnpc"       : 
            case "summongroupgate"           :  
            case "summonhoming"              : 
            case "summonhousegate"           :  
            case "summonservant"             :  
            case "summonskillarea"           :  
            case "summontotem"               : 
            case "summontrap"                :  
            case "switchhostile"             :
            case "switchhpmp"                :  
            case "targetchange"              :
            case "targetteleport"            :
            case "wpndual"                   :
                                                $ret .= getEffectDefault($efftyp,$key,$e);                   break;

            // DEFAULT-ZEILE MIT CHANGES und TBNEG0 (keine Negierungen)
            case "apboost"                   :  
            case "boostdroprate"             :
            case "boosthate"                 :
            case "boostheal"                 :
            case "boostskillcastingtime"     :
            case "boostskillcost"            :
            case "boostspellattack"          :  
            case "curse"                     :  
            case "deboostheal"               :
            case "drboost"                   :  
            case "extendedaurarange"         :
            case "hide"                      :
            case "onetimeboostheal"          : 
            case "slow"                      : 
            case "statboost"                 : 
            case "subtypeboostresist"        : 
            case "weaponstatboost"           :
            case "xpboost"                   :            
                                                $ret .= getEffectDefaultChanges($efftyp,$key,$e,$tbneg0);    break;

            // DEFAULT-ZEILE MIT CHANGES und TBNEG1 (ATTACK_SPEED)
            case "absoluteslow"              :  
            case "absolutesnare"             :           
            case "armormastery"              :  
            case "shieldmastery"             :  
            case "snare"                     :  
            case "statdown"                  :  
            case "statup"                    :  
            case "weaponstatup"              :
            case "wpnmastery"                :
                                                $ret .= getEffectDefaultChanges($efftyp,$key,$e,$tbneg1);    break;  
            
            // SPEZIELLE EFFEKT-ZEILEN
            case "carvesignet"               :  
            case "signet"                    :
            case "signetburst"               :  $ret .= getEffectSignetAll($efftyp,$key,$e);                 break;
            case "dispel"                    :  $ret .= getEffectDispel($efftyp,$key,$e);                    break;
            case "evade"                     :  $ret .= getEffectEvade($efftyp,$key,$e);                     break;
            case "skillxpboost#combine"      :
            case "skillxpboost#extract"      :
            case "skillxpboost#gather"       :
            case "skillxpboost#menuisier"    :  $ret .= getEffectSkillXpBoost($efftyp,$key,$e);              break;
            
            // CLIENT LIEFERT KEINE AUSREICHENDEN EFFEKT-DATEN
            // case ""                       :  $tabeffxsd[$efftyp] = 1;                                     break;
            
            // IN DER EMU-XSD NICHT DEFINIERT / BEKANNT
            case "110282"                    :
            case "absoluteexppoint_heal_instant":
            case "activate_enslave"          :
            case "alwayshit"                 :
            case "alwaysnoresist"            :  
            case "combinepointboost"         :  
            case "dispelallcounteratk"       :  
            case "dummy"                     : 
            case "petorderunsummon"          :
            case "petrification"             :
            case "supportevent"              :
            case "utility"                   : $tabeffxsd[$efftyp] = 2;                                 
                                                   
                                               // ERSATZ-TEST: dispelallcounteratk => dispelbuffcounteratk
                                               if ($efftyp == "dispelallcounteratk")
                                               {    
                                                   logLine("<font color=red>- Skill-Ersatz (TEST)</font>",$key." = dispelallcounteratk ersetzt durch dispelbuffcounteratk");
                                                   $efftyp = "dispelbuffcounteratk";
                                                   $ret   .= getEffectDefault($efftyp,$key,$e); 
                                               }                                               
                                               break;            
            // SCRIPT FEHLT IM PARSER            
            default                          : if (isset($tabeffxsd[$efftyp]))
                                               {         
                                                   if ($tabeffxsd[$efftyp] == 0)                                               
                                                       $tabeffxsd[$efftyp] = 3; 
                                               }    
                                               else
                                                   $tabeffxsd[$efftyp] = 2;
                                               break;                                                   
        }
    }
    
    if ($ret != "")
        $ret = '        <effects>'."\n".
               $ret.'        </effects>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
//
//                              A C T I O N S
//
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: Actions
// ---------------------------------------------------------------------------
function getActionsLines($key)
{
    global $tabcskill;
    
    $ret  = "";        
    $time = getTabValue($key,"cost_time","");
    
    // Nutzungskosten für die Aktivierung
    
    // MPUSE / HPUSE
    $parm = strtolower(getTabValue($key,"cost_parameter",""));
    $cost = getTabValue($key,"cost_end","0");
    $delt = getTabValue($key,"cost_end_lv","0");
    $dtxt = "";
    $rtxt = "";
    
    if ($parm != "" && $cost != "0")
    {
        // RATIO
        if (stripos($parm,"_ratio") !== false)
        {
            $parm = str_replace("_ratio","",$parm);
            $rtxt = ' ratio="true"';
        }
        
        // DELTA
        if ($parm == "mp" || $parm = "hp")
        {
            // Delta nur, wenn kein Ratio bzw. wenn RATIO und Wert != 0
            if ($rtxt == "" || ($rtxt != "" && $delt != "0"))
                $dtxt = ' delta="'.$delt.'"';
        }
        
        $ret .= '            <'.$parm.'use value="'.$cost.'"'.$dtxt.$rtxt.'/>'."\n";
    }
    
    // DPUSE
    $cost = getTabValue($key,"cost_dp","");
    
    if ($cost != "")
        $ret .= '            <dpuse value="'.$cost.'"/>'."\n";
        
    // ITEMUSE
    $item = getTabValue($key,"component","");
    $icnt = getTabValue($key,"component_count","0");
    
    if ($item != "")
    {
        $itid = getClientItemId($item);
        
        $ret .= '            <itemuse itemid="'.$itid.'" count="'.$icnt.'"/>'."\n";
    }
    
    if ($ret != "")
        $ret = '        <actions>'."\n".
               $ret.
               '        </actions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: PeriodicActions
// ---------------------------------------------------------------------------
function getPeriodicActionsLines($key)
{
    global $tabcskill;
    
    $ret = "";
        
    // HPUSE / MPUSE  (kein DPUSE gem. XSD)
    $parm = strtolower(getTabValue($key,"cost_checktime_parameter",""));
    $cost = getTabValue($key,"cost_checktime","");
    $time = getTabValue($key,"effect1_checktime","0");
    
    // normale Nutzung
    if (($parm == "hp" || $parm == "mp") && $cost != "")
    {
        $ret .= '            <'.$parm.'use value="'.$cost.'"/>'."\n";
    }
    
    $parm = strtolower(getTabValue($key,"cost_parameter",""));
    $ptim = getTabValue($key,"cost_time","");
    $cost = getTabValue($key,"cost_toggle","0"); 
    
    if ($parm != "" && $ptim != "" && $cost != "0")
    {
        $time = $ptim;
        $delt = getTabValue($key,"cost_end_lv","");
        $ztxt = ($delt != "" && $parm == "hp") ? ' delta="'.$delt.'"' : '';
        
        // Nutzung bei RATIO
        if (stripos($parm,"_ratio") !== false)
        {
            $parm = str_replace("_ratio","",$parm);
            $cost = getTabValue($key,"cost_toggle","0");
            $ret .= '            <'.$parm.'use value="'.$cost.'"'.$ztxt.' ratio="true"/>'."\n";
        }
        else
        {
            // normale Nutzung
            if (($parm == "hp" || $parm == "mp") && $cost != "")
            {
                $ret .= '            <'.$parm.'use value="'.$cost.'"'.$delt.'/>'."\n";
            }
        }
    }
    if ($ret != "")
        $ret = '        <periodicactions checktime="'.$time.'">'."\n".
               $ret.
               '        </periodicactions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
//
//                                 M O T I O N
//
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: Motions
// ---------------------------------------------------------------------------
function getMotionLines($key)
{
    global $tabcskill;
    
    $ret = "";
        
    if (isset($tabcskill[$key]['motion_name']))
        $ret .= ' name="'.strtolower($tabcskill[$key]['motion_name']).'"';
    if (isset($tabcskill[$key]['motion_play_speed']))
        $ret .= ' speed="'.$tabcskill[$key]['motion_play_speed'].'"';
    if (isset($tabcskill[$key]['instant_skill']))
    {
        if ($tabcskill[$key]['instant_skill'] == "1")  
            $ret .= ' instant_skill="true"';
    }
    
    if ($ret != "")
        $ret = '        <motion'.$ret.'/>';
        
    return $ret;
}
// ----------------------------------------------------------------------------
//
//                        S K I L L _ T E M P L A T E S
//
// ----------------------------------------------------------------------------
// SkillTemplate-Datei ausgeben
// ----------------------------------------------------------------------------
function generSkillTemplateFile()
{
    global $pathdata, $tabcskill;
    
    $fileout = "../outputs/parse_output/skills/skill_templates.xml";
    
    logHead("Generierung der Datei: ".basename($fileout));
    logLine("Ausgabedatei",$fileout);
    
    $cntout = 0;
    $cntids = 0;
    $cntign = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<skill_data>'."\n");
    $cntout += 3;
            
    flush();
    
    while (list($key,$val) = each($tabcskill))
    {   
        $cntids++;
        
        $skillname = getIntSkillName($tabcskill[$key]['desc']);
        $skillnid  = getIntSkillNameId($tabcskill[$key]['desc']);
        
        // evtl. über "desc_abnormal" zu finden (OHNE "_Abnormal")
        if ($skillnid == "???")
        {
            $such = strtoupper($tabcskill[$key]['desc_abnormal']);
            $such = str_replace("_ABNORMAL","",$such);
            
            $skillname = getIntSkillName($such);
            $skillnid  = getIntSkillNameId($such);
        }
        // nur wenn zu dem Skill auch ein Name und eine Id gefunden werden konnten, ausgeben
        // (TEST über "desc_abnormal" bringt eine Beschreibung und keinen Namen zum Skill, s. 11129)
        if ($skillnid != "???")
        {
            $lout  = '    <skill_template skill_id="'.$key.'" name="'.$skillname.'" nameId="'.$skillnid.
                     '" name_desc="'.$tabcskill[$key]['name'].'"'; 
            $lout .= getTabFieldText($key,"delay_id","cooldownId",""); 
            $lout .= getStackName($key);
            $lout .= getTabFieldText($key,"chain_category_level","lvl","1");
            $lout .= getTabFieldText($key,"type","skilltype","NONE");
            $lout .= getTabFieldText($key,"sub_type","skillsubtype","NONE");
            $lout .= getTabFieldText($key,"target_slot","tslot","NONE");
            $lout .= getTabFieldText($key,"target_slot_level","tslot_level","");
            $lout .= getTabFieldText($key,"conflict_id","conflict_id","");
            $lout .= getTabFieldText($key,"dispel_category","dispel_category","");
            $lout .= getTabFieldText($key,"required_dispel_level","req_dispel_level","");
            $lout .= getTabFieldText($key,"activation_attribute","activation","NONE");
            $lout .= getTabFieldText($key,"delay_time","cooldown","0");
            $lout .= getTabFieldText($key,"toggle_timer","toggle_timer","");
            $lout .= getTabFieldText($key,"casting_delay","duration","0");
            $lout .= getTabFieldText($key,"pvp_damage_ratio","pvp_damage","");
            $lout .= getTabFieldText($key,"pvp_remain_time_ratio","pvp_duration","");
            $lout .= getTabFieldText($key,"ammo_speed","ammospeed","");
            $lout .= getPenaltySkillId($key);
            $lout .= getGroundStatus($key);
            $lout .= getTabFieldText($key,"cancel_rate","cancel_rate","");
            $lout .= getTabFieldText($key,"chain_skill_prob2","chain_skill_prob","");
            $lout .= getTabFieldText($key,"counter_skill","counter_skill","");
            $lout .= getStanceStatus($key);
            $lout .= getAvatarStatus($key);
            $lout .= getNoremoveStatus($key);
            
            // .... siehe xsd ...
            // momentan nicht genutzte Tags im akt. SVN:
            // - attack_type
            // - stigma
            // - unpottable
            // - remove_flyend
            fwrite($hdlout,$lout.">\n");
            $cntout++;
            
            // .............
            for ($l=1;$l<10;$l++)
            {
                switch($l)
                {
                    case  1: $oline = getPropertiesLines($key); break;
                    case  2: $oline = getStartConditionLines($key); break;
                    case  3: $oline = getUseConditionLines($key); break;
                    case  4: $oline = getEndConditionLines($key); break;
                    case  5: $oline = getUseEquipConditionLines($key); break;
                    case  6: $oline = getEffectsLines($key); break;
                    case  7: $oline = getActionsLines($key); break;
                    case  8: $oline = getPeriodicActionsLines($key); break;
                    case  9: $oline = getMotionLines($key); break;
                    default: $oline = "";
                }
                
                if ($oline != "")
                {
                    fwrite($hdlout,$oline."\n");
                    $cntout += (1 + substr_count($oline,"\n"));
                }
            }
            
            fwrite($hdlout,"    </skill_template>\n");
            $cntout++;
        }
        else
        {
            logLine("<font color=red>Skill ignoriert</font>",$key." (Name,Id nicht ermittelbar)");
            $cntign++;
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</skill_data>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen ausgegeben",$cntout);
    logLine("Skills ausgegeben",$cntids);
    logLine("Skills ignoriert ",$cntign);
    
    showMissingEffects();
}
// ----------------------------------------------------------------------------
//
//                            S K I L L _ T R E E
//
// ----------------------------------------------------------------------------
// SkillTree-Datei ausgeben
// ----------------------------------------------------------------------------
function generSkillTreeFile()
{
    global $pathdata, $tabcskill;
    
    $fileout = "../outputs/parse_output/skill_tree/skill_tree.xml";
    $fileu16 = formFileName($pathdata."\\skills\\client_skill_learns.xml");
    
    logHead("Generierung der Datei: ".basename($fileout));
    
    if (!file_exists($fileu16))
    {
        logLine("Datei nicht vorhanden",$fileu16);
        return;
    }
    
    $fileext = convFileToUtf8($fileu16);
    $hdlext  = openInputFile($fileext);
    
    if (!$hdlext)
    {
        logLine("Fehler openInputFile",$fileext);
        return;
    }
    
    $hdlout  = openOutputFile($fileout);
    $cntles  = 0;
    $cntout  = 0;
    $cntids  = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="utf-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<skill_tree xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="skill_tree.xsd">'."\n");
    $cntout += 3;
            
    flush();
    
    $id = $lmin = $race = $auto = $name = $slev = $sid = $class = $stigma = "";
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<id>")             !== false) $id     = getXmlValue("id",$line);
        elseif (stripos($line,"<pc_level>")       !== false) $lmin   = getXmlValue("pc_level",$line);
        elseif (stripos($line,"<race>")           !== false) $race   = strtoupper(getXmlValue("race",$line));
        elseif (stripos($line,"<autolearn>")      !== false) $auto   = strtolower(getXmlValue("autolearn",$line));
        elseif (stripos($line,"<skill>")          !== false) $name   = getXmlValue("skill",$line);
        elseif (stripos($line,"<skill_level>")    !== false) $slev   = getXmlValue("skill_level",$line);
        elseif (stripos($line,"<class>")          !== false) $class  = strtoupper(getXmlValue("class",$line));
        elseif (stripos($line,"<stigma_display>") !== false) $stigma = getXmlValue("stigma_display",$line);
        elseif (stripos($line,"</client_skill_learn>") !== false)
        {
            $cntids++;
            
            // fehlende Werte ermitteln
            
            // SkillId und Name
            $sid  = getSkillNameId($name);
            $desc = $tabcskill[$sid]['desc'];  // DESC zur akt. SkillId holen
            $name = getIntSkillName($desc);
            
            // Rasse
            if     ($race == "ALL")      $race = "PC_ALL";
            elseif ($race == "PC_LIGHT") $race = "ELYOS";
            elseif ($race == "PC_DARK")  $race = "ASMODIANS";
            
            // Klasse            
            if     ($class == "ELEMENTALLIST")       $class = "SPIRIT_MASTER";
            elseif ($class == "FIGHTER")             $class = "GLADIATOR";
            elseif ($class == "KNIGHT")              $class = "TEMPLAR";
            elseif ($class == "WIZARD")              $class = "SORCERER";
            /*
                spezielle Umsetzung: im Client sind die Vorgaben für die Klassen
                PRIEST/CLERIC vermischt, also nicht eindeutig. Daher Umsetzung
                wie folgt:
                
                Level 01 - 09 = PRIEST
                Level 10 - 65 = CLERIC
            */
            elseif ($class == "PRIEST" || $class == "CLERIC")
            { 
                if ($lmin > 9)                       $class = "CLERIC";
                else                                 $class = "PRIEST";
            }
            
            // Stigma
            if ($stigma == "1"     
            ||  $stigma == "2"
            ||  $stigma == "3")          $stigma = "true";
            else                         $stigma = "";
            
            /*
            <skill minLevel="1" race="PC_ALL" autolearn="true" name="Basic Harp Training" skillLevel="1" skillId="114" classId="ARTIST" />
            */
            $lout = '    <skill'.
                    getFieldText("minLevel"  ,$lmin).
                    getFieldText("race"      ,$race).
                    getFieldText("stigma"    ,$stigma).
                    getFieldText("autolearn" ,$auto).
                    getFieldText("name"      ,$name).
                    getFieldText("skillLevel",$slev).
                    getFieldText("skillId"   ,$sid).
                    getFieldText("classId"   ,$class).
                    ' />';
            fwrite($hdlout,$lout."\n");
            $cntout++;
            
            $id = $lmin = $race = $auto = $name = $slev = $sid = $class = $stigma = "";
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,'</skill_tree>');
    $cntout++;
    fclose($hdlext);
    fclose($hdlout);
    
    logLine("Anzahl Zeilen eingelesen",$cntles);
    logLine("Anzahl Zeilen ausgegeben",$cntout);
    logLine("Anzahl Skills gefunden"  ,$cntids);
}
// ----------------------------------------------------------------------------
//
//                       A B G L E I C H   M I T   S V N
//
// ----------------------------------------------------------------------------
function makeSvnCompareFile()
{
    global $pathsvn, $tabeffsvn;
    
    logHead("Erzeuge Abgleich-Test-Datei: svn_skill_templates.xml");
        
    $filesvn  = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\skills\\skill_templates.xml");
    $fileout  = "parse_temp/svn_skill_templates.xml";

    $hdlsvn   = openInputFile($filesvn);
    $hdlout   = openOutputFile($fileout);    
    
    $cntout   = 0;
    $doblock  = false;
    $doline   = false;
    $doeff    = false;
    $effwait  = "";
    $endblock = "";
    
    logLine("Ausgabedatei",$fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<skill_data>'."\n");
    $cntout += 3;
    
    flush();
    
    while (!feof($hdlsvn))
    {
        $line   = rtrim(fgets($hdlsvn));
        $line   = str_replace("\t","    ",$line);
        $doline = false;
        
        // spezielle Behandlung für die Effekte, da es 168 verschiedene gibt
        // und die erst nach und nach realisiert werden
        if (stripos($line,"<effects") !== false)
        {
            $effwait = $line;
            $doeff   = false;
        }
        else
        {   
            $xml = getXmlKey($line);
            
            // Effekt bereits vom Parser bearbeitet?            
            if (isset($tabeffsvn[$xml]))
            {
                // BLOCK
                if ($tabeffsvn[$xml] == "B")
                {
                    $doeff    = true;
                    
                    if (stripos($line,"/>") === false)
                    {
                        $doblock  = true;
                        $endblock = "</".$xml.">";
                    }
                    else
                        $doline = true;
                }
                // EINZEILER
                else
                {
                    $doline   = true;
                    $doeff    = true;
                }
            }
            // Start <effects> ausgeben
            if ($doeff)
            {
                if ($effwait != "")
                {
                    fwrite($hdlout,$effwait."\n");
                    $cntout++;
                    $effwait = "";
                }
                // Ende </effects> nur ausgeben, wenn Effekte vorhanden sind
                if (stripos($line,"</effects>")      !== false)
                {
                    $doline   = true;
                    $doeff    = false;
                }
            }
        }        
        
        // alles andere (ausser Effekte) prüfen
        if (!$doline && !$doblock)
        {
            // ganzen Block ausgeben?
            if (stripos($line,"<actions>")          !== false
            ||  stripos($line,"<periodicactions")   !== false
            ||  stripos($line,"<startconditions")   !== false
            ||  stripos($line,"<endconditions")     !== false
            ||  stripos($line,"<useconditions")     !== false
            ||  stripos($line,"<useequipmentconditions") !== false)
            {
                $doblock = true;
                $endblock = "</".getXmlKey($line).">";
            }
            
            // einzelne Zeilen ausgeben?
            if (stripos($line,"skill_template")     !== false
            ||  stripos($line,"properties")         !== false
            ||  stripos($line,"<motion")            !== false)
                $doline = true;
        }
        
        // Block/einzelnen Zeile ausgeben
        if ($doblock || $doline)
        {
            fwrite($hdlout,$line."\n");
            $cntout++;
        }
        
        // Blockende?
        if (stripos($line,$endblock)                !== false)
            $doblock = false;               
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</skill_data>");
    $cntout++;
    
    fclose($hdlsvn);
    fclose($hdlout);
    
    logLine("Anzahl Zeilen ausgegeben",$cntout);
}
// ----------------------------------------------------------------------------
//
//                     T E S T - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Protokollieren der übergebenen Tabelle mit den Effekten
// ----------------------------------------------------------------------------
function protErrorTable()
{
    global $tabeffxsd;
    
    $tx1 = "";
    $tx2 = "";
    $tab = array_keys($tabeffxsd);
    $max = count($tab);
    sort($tab);
    
    logHead("Listen der offenen Effekte (ungenutzt, fehlerhaft, unbekannt oder noch offen)");
    // Index=4 sind realisiert und werden nicht protokolliert!
    for ($i=0;$i<4;$i++)
    {
        switch($i)
        {
            case 0: $tx1 = "in EMU definiert, aber derzeit ungenutzt"; 
                    $tx2 = "<font color=cyan>nicht genutzt in EMU</font>";
                    break;
            case 1: $tx1 = "in EMU definiert, aber Client liefert keine ausreichenden Daten"; 
                    $tx2 = "<font color=yellow>Client liefert keine Daten</font>";
                    break;
            case 2: $tx1 = "in EMU nicht definiert / bekannt";       
                    $tx2 = "<font color=red>nicht in der XSD</font>";
                    break;
            case 3: $tx1 = "Realisierung im Parser fehlt noch"; 
                    $tx2 = "<font color=magenta>Parser-Script fehlt noch</font>";        
                    break;        
        }   
        
        $l = 0;
        
        for ($t=0;$t<$max;$t++)
        {
            if ($tabeffxsd[$tab[$t]] == $i)
            {
                if ($l == 0)
                    logSubHead("Liste der Effekte: <font color=orange>".$tx1."</font>");
                    
                $l++;
                logLine($tx2,"( ".$l." )",$tab[$t]);
            }
        }
        
        if ($i == 2)
            logLine("<font color=orange>ACHTUNG<br>Wichtiger Hinweis</font>",
                    "Aktuell wird <font color=red>dispelallcounteratk</font> als <font color=yellow>dispelbuffcounteratk</font> ".
                    "ausgegeben. Im Client sind aber weitere Informationen zu der Klasse und den Skills vorhanden, die evtl. ".
                    "erweitert werden sollten! Durch die o.a. fehlenden Definitionen in der Ausgabe kann es sp&auml;ter zu ".
                    "Problemen f&uuml;hren, da die Effekt-Reihenfolge und -Referenzen fehlerhaft sein k&ouml;nnten.");
                       
        if ($i < 3 && $l > 0) logSubHead("<hr>");     
    }
} 
// ----------------------------------------------------------------------------
// Anzeigen aller Effekte, die noch nicht bearbeitet werden
// ----------------------------------------------------------------------------
function showMissingEffects()
{
    global $tabeffxsd;
    
    // 0 = in EMU definiert aber nicht genutzt
    // 1 = Client liefert keine Daten
    // 2 = in der EMU nicht definiert
    // 3 = script fehlt im Parser
    // 4 = vom Parser ausgegeben
    
    $tabcnt = array(0,0,0,0,0);
    $fc     = "<font color=cyan>";
    $fy     = "<font color=yellow>";
    $fr     = "<font color=red>";
    $fm     = "<font color=magenta>";
    $fe     = "</font>";
    
    while (list($key,$val) = each($tabeffxsd))
    {
        $tabcnt[$val]++;
    }
    reset($tabeffxsd);
    
    logSubHead("");
    logLine("Anzahl Effekte in der EMU",$tabcnt[0] + $tabcnt[1] + $tabcnt[3] + $tabcnt[4]);
    logLine("- aktuell ungenutzt"      ,$fc.$tabcnt[0].$fe," siehe unten (in EMU definiert, aber nicht genutzt)");
    logLine("- keine Daten aus Client" ,$fy.$tabcnt[1].$fe," siehe unten (keine ausreichenden Daten im Client vorhanden)");
    logLine("- noch nicht realisiert"  ,$fm.$tabcnt[3].$fe," siehe unten (sind im Parser noch zu realisieren)");
    logLine("- erzeugt",$tabcnt[4]     ," siehe Datei (wurden im Parser bereits realisiert)");
    
    logSubHead("");
    logLine("Anzahl Effekte aus Client",$tabcnt[1] + $tabcnt[2] + $tabcnt[3] + $tabcnt[4]);
    logLine("- keine Daten"            ,$fy.$tabcnt[1].$fe," siehe unten (keine ausreichenden Daten im Client gefunden)");
    logLine("- in der EMU unbekannt"   ,$fr.$tabcnt[2].$fe," siehe unten (sind in der XSD-Datei der EMU nicht definiert)");
    logLine("- noch nicht realisiert"  ,$fm.$tabcnt[3].$fe," siehe unten (sind im Parser noch zu realisieren)");
    logLine("- realisiert",$tabcnt[4]  ," siehe Datei (wurden im Parser bereits realisiert)");
    
    protErrorTable();
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_getautonameids.php");
include("includes/inc_effect_tags.php");
include("includes/auto_inc_item_infos.php");
include("includes/auto_inc_skill_names.php");
include("includes/auto_inc_npc_infos.php");

$starttime = microtime(true);
$tabSNames = array();
$tabrskill = array();
$tabcskill = array();
$tabxskill = array();
$tabcharge = array();
$tabastats = array();
$tabeffsvn = array();
$tabeffxsd = array();

$chkCMP = $doCompareFile == true ? ' checked' : "";
$chkARR = $doCheckArrow  == true ? ' checked' : "";
$chkROB = $doCheckRobot  == true ? ' checked' : "";

echo '
   <tr>
     <td><font color=orange>XML zu &lt;arrowcheck&gt; einbinden</font></td>
     <td>
       <input type="checkbox" name="charrow" value="DO" '.$chkARR.'> markiert = Ja
     </td>
   </tr>
   <tr>
     <td><font color=orange>XML zu &lt;robotcheck&gt; einbinden</font></td>
     <td>
       <input type="checkbox" name="chrobot" value="DO" '.$chkROB.'> markiert = Ja
     </td>
   </tr>
   <tr>
     <td><font color=orange>SVN-Abgleich-Datei erstellen</font></td>
     <td>
       <input type="checkbox" name="compare" value="DO" '.$chkCMP.'> markiert = Ja
     </td>
   </tr>
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
        // VORARBEITEN
        scanPsSkillNames();
        scanSkillCharges();
        scanAbsoluteStat();
        scanEmuXsdEffects();
        scanClientSkills();
        makeSkillsRefTab();
        
        // GENERIERUNG
        generSkillTreeFile();
        generSkillTemplateFile();
        
        // SVN-ABGLEICHDATEI
        if ($doCompareFile)
            makeSvnCompareFile();  
        
        // AUFRÄUMEN      
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