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
function getRefSkillNameId($sname)
{
    global $tabrskill;
    
    $key = strtoupper($sname);
    $org = $key;
    
    // direkte Suche
    if (isset($tabrskill[$key]))
        return $tabrskill[$key];
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
    
    logLine("<font color=yellow>RefSkillId nicht gefunden",$org);
    
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
        
        logSubHead("Scanne PS-String-Datei: ".$filestr);
        
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
    
    if ($arrow == "1")
        $ret .= '            <arrowcheck/>'."\n";
        
    if ($robot == "1")
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
//                             E F F E C T S
//
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
function getEffectFunc($key,$field)
{
    $val = getTabValue($key,$field,"?");
    
    // wenn das Feld gesetzt ist, also nicht "?" und nicht "0", dann PERCENT
    if ($val == "?" || $val == "0")
        return "ADD";
    else
        return "PERCENT";
}
// ----------------------------------------------------------------------------
// Vorzeichen für die Value-Angabe ermitteln
// ----------------------------------------------------------------------------
function getValueSign($block,$stat,$value,$tbneg)
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
        if ($block == "statdown")
            $ret *= -1;
    }  
    
    return $ret;
}
// ----------------------------------------------------------------------------
// Zeilen für die StatUp Changes aufbereiten
// ----------------------------------------------------------------------------
function getChangeStats($block,$key,$e,$tbneg)
{    
    global $protkey;
    
    $protkey = $key;
    
    // Tabelle für alle Client-Felder, die STAT-Werte enthalten
    $tabstats = array(
                  //    Name         Value        Stats???
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
     
    // spezielle Conditions vorab prüfen (Default-Changes !!!)
    switch($block)
    {
        case "absoluteslow":
            if ($res2 != "?")
                $ret = '                <change stat="ATTACK_SPEED" func="REPLACE" value="'.($res2 * 100 ).'"/>'."\n"; 
            return $ret;   
        case"absolutesnare":
            if ($res2 != "?")
                $ret = '                <change stat="SPEED" func="REPLACE" value="'.($res2 * 100 ).'"/>'."\n". 
                       '                <change stat="FLY_SPEED" func="REPLACE" value="'.($res2 * 100 ).'"/>'."\n";
            return $ret;
        case "armormastery":
            $ret = '                <change stat="PHYSICAL_DEFENSE" func="PERCENT" value="'.$res2.'"/>'."\n";
            return $ret;
        case "shieldmastery":
            $dta = ($res1 != "?" && $res1 != "0") ? ' delta="'.$res1.'"' : '';
            $ret = '                <change stat="BLOCK" func="PERCENT"'.$dta.' value="'.$res2.'"/>'."\n";
            return $ret;
        default:
            break;
    }
    
    // Condition ONFLY aufbereiten
    if ($res9 == "1")
        $chend = ">\n".
                 '                    <conditions>'."\n".
                 '                        <onfly/>'."\n".
                 '                    </conditions>'."\n".
                 '                </change>';
    
    // für alle Stat-Werte aus obiger Tabelle!
    for ($t=0;$t<$maxstats;$t++)
    {
        $name  = getTabValue($key,$ename.$tabstats[$t][0],"?");
        $value = getTabValue($key,$ename.$tabstats[$t][1],"0");
        
        if (($name != "?" && $name != "0") && $value != "?")
        {  
            $func = getEffectFunc($key, $ename.$tabstats[$t][2] );
            $stab = explode(",", getStatNames($name) );
            $smax = count($stab);    

            for ($s=0;$s<$smax;$s++)
            {
                $sndel = getValueSign($block,$stab[$s],$res1 ,$tbneg);
                $snval = getValueSign($block,$stab[$s],$value,$tbneg);
                
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
// ---------------------------------------------------------------------------
// Effect-Werte speziell bearbeiten / zurückgeben           (ZENTRALE ROUTINE)
//
// params: $spec   =  Typ der speziellen Behandlung
//         $wert   =  Wert, der behandelt werden muss
// ---------------------------------------------------------------------------
function getEffSpecial($spec,$wert)
{
    $ret = "";
    
    switch($spec)
    {
        case "upper":  // Rückgabe in Grossbuchstaben
            return strtoupper($wert);
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
// ---------------------------------------------------------------------------
// EffectBasicLine zurückgeben
// Offensichtlich besitzen alle Block-XML-Tags identische Angaben, sodass
// diese hier zentral aufbereitet werden können
// ---------------------------------------------------------------------------
function getEffectBasicLine($efftyp,$key,$e)
{     
    $ename = "effect".$e."_";     
    $ret   = "";
    
    // alle notwendigen Daten aus dem Client auslesen
    $model = getEffSpecial( "npcid" ,getTabValue($key,$ename."reserved9","?") );
    $type  = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved8","?") );
    $dura1 = getEffSpecial( "nozero",getTabValue($key,$ename."remain1","?") );
    $dura2 = getEffSpecial( "nozero",getTabValue($key,$ename."remain2","?") );
    $tran  =                         getTabValue($key,$ename."randomtime","?");
    $effid =                         getTabValue($key,$ename."effectid","?");
    $blev  =                         getTabValue($key,$ename."basiclv","?");
    $nores = getEffSpecial( "true1" ,getTabValue($key,$ename."noresist","?") );
    $elem  = getEffSpecial( "upper" ,getTabValue($key,$ename."reserved10","?") );
    $preff = getEffSpecial( "preff" ,getTabValue($key,$ename."cond_preeffect","?") );
    $prob2 = getEffSpecial( "prob2" ,getTabValue($key,$ename."cond_preeffect_prob2","?") );
    $acmod =                         getTabValue($key,$ename."acc_mod2","?");
    $htyp  = getEffSpecial( "upper" ,getTabValue($key,$ename."hop_type","?") );
    $hopb  = getEffSpecial( "nozero",getTabValue($key,$ename."hop_b","?") );   
    $hopa  = getEffSpecial( "nozero",getTabValue($key,$ename."hop_a","?") ); 
    // nur bedingt genutzte Tags generell deaktivieren (werden später gesetzt)
    $atcnt = "?";
    $npcnt = "?";
    $owner = "?";
    $skill = "?";
    $skid  = "?";
    $time  = "?";
    $panel = "?";
    $state = "?";
    $weapn = "?";
    $armor = "?";
    
    // einige Inhalte an die EMU anpassen
    $elem   = ($elem == "AIR") ? "WIND" : $elem;  
    $npctag = (stripos($efftyp,"summon") !== false) ? "npc_id" : "model"; 
    
    // ----------------------------------------------------
    // Deaktivierung einiger Tags
    // ----------------------------------------------------  
    // TYPE deaktivieren
    if ($efftyp == "statup"
    ||  $efftyp == "summontrap"
    ||  $efftyp == "shieldmastery"
    ||  $efftyp == "armormastery")
        $type = "?";
    
    // ELEMENT deaktivieren
    if ($efftyp == "armormastery")
        $elem = "?";
    
    // ----------------------------------------------------
    // Hinzufügen einiger Tags
    // ----------------------------------------------------
    // ATTACK_COUNT und NPC_COUNT
    if ($efftyp == "summonhoming")
    {
        $atcnt = getTabValue($key,$ename."reserved4","?");
        $npcnt = getTabValue($key,$ename."reserved6","?");
    }    
    // OWNER
    if ($efftyp == "summonfunctionalnpc")
    {
        $owner = getEffSpecial( "upper",getTabValue($key,$ename."reserved7","?") );
        
        switch($owner)
        {
            case "FORCE"  : $owner = "ALLIANCE"; break;
            case "PARTY"  : $owner = "GROUP";    break;
            case "GUILD"  : $owner = "LEGION";   break;
            case "PRIVATE": $owner = "PRIVATE";  break;
            default;        $owner = "?";        break;
        }
    }    
    // SKILL-ID 
    if ($efftyp == "summonservant"
    ||  $efftyp == "summonskillarea"
    ||  $efftyp == "summontrap"
    ||  $efftyp == "summontotem")
    {
        $skill = getTabValue($key,$ename."reserved9","?");
        $skid  = getRefSkillNameId($skill); 
    }        
    // WEAPON 
    if ($efftyp == "wpnmastery")
    {
        $weapn = getEffSpecial( "weapon",getTabValue($key,$ename."reserved5","?") );
    }    
    // ARMOR 
    if ($efftyp == "armormastery")
    {
        $armor = getEffSpecial( "upper",getTabValue($key,$ename."reserved5","?") );
    }    
    // TIME 
    if (stripos($efftyp,"summon") !== false)
    {
        if     ($efftyp == "summonfunctionalnpc"
        ||      $efftyp == "summongroupgate"
        ||      $efftyp == "summonhousegate")
            $time = getEffSpecial( "nozero",getTabValue($key,$ename."reserved2","?") );
        elseif ($efftyp == "summonhoming")
            $time = getEffSpecial( "nozero",getTabValue($key,$ename."reserved5","?") );
        else
            $time = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4","?") ); 
    }    
    // PANELID und STATE 
    if ($efftyp == "shapechange")
    {
        $panel = getEffSpecial( "nozero",getTabValue($key,$ename."reserved4","?") );
        $state = getEffSpecial( "state" ,getTabValue($key,$ename."reserved13","?") );
    }    
    // ----------------------------------------------------
    // Allgemeine Zeile mit allen aktiven Tags aufbereiten 
    // ----------------------------------------------------
    $ret .= getFieldText( "weapon"        ,$weapn ).
            getFieldText( "armor"         ,$armor ).
            getFieldText( "attack_count"  ,$atcnt ).
            getFieldText( "npc_count"     ,$npcnt ).
            getFieldText( $npctag         ,$model ).
            getFieldText( "skill_id"      ,$skid  ).
            getFieldText( "time"          ,$time  ).
            getFieldText( "type"          ,$type  ).
            getFieldText( "state"         ,$state ).   
            getFieldText( "panelid"       ,$panel ).
            getFieldText( "duration2"     ,$dura2 ).
            getFieldText( "duration1"     ,$dura1 ).
            getFieldText( "randomtime"    ,$tran  ).
            getFieldText( "effectid"      ,$effid ).
            getFieldText( "e"             ,$e     ).
            getFieldText( "basiclvl"      ,$blev  ).
            getFieldText( "noresist"      ,$nores ).
            getFieldText( "accmod2"       ,$acmod ).
            getFieldText( "element"       ,$elem  ).
            getFieldText( "preeffect"     ,$preff ).
            getFieldText( "preeffect_prob",$prob2 ).
            getFieldText( "hoptype"       ,$htyp  ).
            getFieldText( "hopb"          ,$hopb  ).
            getFieldText( "hopa"          ,$hopa  );
                            
    return $ret;
}
// ---------------------------------------------------------------------------
//
// ---------------------------------------------------------------------------
// Effect aufbereiten für: ShapeChange
// ---------------------------------------------------------------------------
function getEffectShapeChange($key,$e)
{
    $ret   = getEffectBasicLine("shapechange",$key,$e);
            
    if ($ret != "")
        $ret = '            <shapechange'.$ret.'/>'."\n";
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: StatUp
// ---------------------------------------------------------------------------
function getEffectStatUp($key,$e)
{
    $tbneg = array("ATTACK_SPEED");    
    $ret   = getEffectBasicLine("statup",$key,$e);
            
    if ($ret != "")
    {
        $ret  = '            <statup'.$ret.'>'."\n";
        $ret .= getChangeStats("statup",$key,$e,$tbneg);
        $ret .= '            </statup>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: StatDown
// ---------------------------------------------------------------------------
function getEffectStatDown($key,$e)
{
    $tbneg = array("ATTACK_SPEED");   
    $ret   = getEffectBasicLine("statdown",$key,$e);
            
    if ($ret != "")
    {
        $ret  = '            <statdown'.$ret.'>'."\n";
        $ret .= getChangeStats("statdown",$key,$e,$tbneg);
        $ret .= '            </statdown>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: Sleep
// ---------------------------------------------------------------------------
function getEffectSleep($key,$e)
{    
    $ret   = getEffectBasicLine("sleep",$key,$e);
            
    if ($ret != "")
        $ret  = '            <sleep'.$ret.'/>'."\n";
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: Summon... (alle Summon-Blöcke!)
// ---------------------------------------------------------------------------
function getEffectSummonAll($block,$key,$e)
{
    $ret   = getEffectBasicLine($block,$key,$e);
            
    if ($ret != "")
        $ret  = '            <'.$block.$ret.'/>'."\n";
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: WpnMastery
// ---------------------------------------------------------------------------
function getEffectWpnMastery($key,$e)
{
    $ename = "effect".$e."_";
    $tbneg = array("ATTACK_SPEED");
    $ret   = getEffectBasicLine("wpnmastery",$key,$e);
    
    if ($ret != "")
    {
        $ret  = '            <wpnmastery'.$ret.'>'."\n";
        $ret .= getChangeStats("wpnmastery",$key,$e,$tbneg);
        $ret .= '            </wpnmastery>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: ArmorMastery
// ---------------------------------------------------------------------------
function getEffectArmorMastery($key,$e)
{
    $ename = "effect".$e."_";
    $tbneg = array("ATTACK_SPEED");
    $ret   = getEffectBasicLine("armormastery",$key,$e);
    
    if ($ret != "")
    {
        $ret  = '            <armormastery'.$ret.'>'."\n";
        $ret .= getChangeStats("armormastery",$key,$e,$tbneg);
        $ret .= '            </armormastery>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: ShieldMastery
// ---------------------------------------------------------------------------
function getEffectShieldMastery($key,$e)
{
    $ename = "effect".$e."_";
    $tbneg = array("ATTACK_SPEED");
    $ret   = getEffectBasicLine("shieldmastery",$key,$e);
    
    if ($ret != "")
    {
        $ret  = '            <shieldmastery'.$ret.'>'."\n";
        $ret .= getChangeStats("shieldmastery",$key,$e,$tbneg);
        $ret .= '            </shieldmastery>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: AbsoluteSlow
// ---------------------------------------------------------------------------
function getEffectAbsoluteSlow($key,$e)
{
    $ename = "effect".$e."_";
    $tbneg = array("ATTACK_SPEED");
    $ret   = getEffectBasicLine("absoluteslow",$key,$e);
    
    if ($ret != "")
    {
        $stat = getChangeStats("absoluteslow",$key,$e,$tbneg);
        
        if ($stat != "")
        {
            $ret  = '            <absoluteslow'.$ret.'>'."\n";
            $ret .= getChangeStats("absoluteslow",$key,$e,$tbneg);
            $ret .= '            </absoluteslow>'."\n";
        }
        else
            $ret  = '            <absoluteslow'.$ret.'/>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: AbsoluteSnare
// ---------------------------------------------------------------------------
function getEffectAbsoluteSnare($key,$e)
{
    $ename = "effect".$e."_";
    $tbneg = array("ATTACK_SPEED");
    $ret   = getEffectBasicLine("absolutesnare",$key,$e);
    
    if ($ret != "")
    {
        $stat = getChangeStats("absolutesnare",$key,$e,$tbneg);
        
        if ($stat != "")
        {
            $ret  = '            <absolutesnare'.$ret.'>'."\n";
            $ret .= getChangeStats("absolutesnare",$key,$e,$tbneg);
            $ret .= '            </absolutesnare>'."\n";
        }
        else
            $ret  = '            <absolutesnare'.$ret.'/>'."\n";
    }
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Effect aufbereiten für: ???
// ---------------------------------------------------------------------------
function getEffectMuster($key,$e)
{
    $ename = "effect".$e."_";
    
    $ret   = getEffectBasicLine("???",$key,$e);
            
    if ($ret != "")
        $ret  = '            <???'.$ret.'/>'."\n";
    
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: Effects
// ---------------------------------------------------------------------------
function getEffectsLines($key)
{
    global $tabcskill, $taberreff;
    
    $ret = "";
    
    // ........
    /*
        name="absstatbuff" type="AbsoluteStatToPCBuff"
        name="absstatdebuff" type="AbsoluteStatToPCDebuff"
        name="alwaysblock" type="AlwaysBlockEffect"
        name="alwaysdodge" type="AlwaysDodgeEffect"
        name="alwaysparry" type="AlwaysParryEffect"
        name="alwaysresist" type="AlwaysResistEffect"
        name="apboost" type="APBoostEffect"
        name="aura" type="AuraEffect"
        name="backdash" type="BackDashEffect"
        name="bind" type="BindEffect"
        name="bleed" type="BleedEffect"
        name="blind" type="BlindEffect"
        name="boostdroprate" type="BoostDropRateEffect"
        name="boosthate" type="BoostHateEffect"
        name="boostheal" type="BoostHealEffect"
        name="boostskillcastingtime" type="BoostSkillCastingTimeEffect"
        name="boostskillcost" type="BoostSkillCostEffect"
        name="boostspellattack" type="BoostSpellAttackEffect"
        name="buffbind" type="BuffBindEffect"
        name="buffsilence" type="BuffSilenceEffect"
        name="buffsleep" type="BuffSleepEffect"
        name="buffstun" type="BuffStunEffect"
        name="carvesignet" type="CarveSignetEffect"
        name="caseheal" type="CaseHealEffect"
        name="changehateonattacked" type="ChangeHateOnAttackedEffect"
        name="closeaerial" type="CloseAerialEffect"
        name="condskilllauncher" type="CondSkillLauncherEffect"
        name="confuse" type="ConfuseEffect"
        name="convertheal" type="ConvertHealEffect"
        name="curse" type="CurseEffect"
        name="dash" type="DashEffect"
        name="deathblow" type="DeathBlowEffect"
        name="deboostheal" type="DeboostHealEffect"
        name="deform" type="DeformEffect"
        name="delaydamage" type="DelayedSpellAttackInstantEffect"
        name="delayedfpatk_instant" type="DelayedFpAtkInstantEffect"
        name="delayedskill" type="DelayedSkillEffect"
        name="disease" type="DiseaseEffect"
        name="dispel" type="DispelEffect"
        name="dispelbuff" type="DispelBuffEffect"
        name="dispelbuffcounteratk" type="DispelBuffCounterAtkEffect"
        name="dispeldebuff" type="DispelDebuffEffect"
        name="dispeldebuffmental" type="DispelDebuffMentalEffect"
        name="dispeldebuffphysical" type="DispelDebuffPhysicalEffect"
        name="dispelnpcbuff" type="DispelNpcBuffEffect"
        name="dispelnpcdebuff" type="DispelNpcDebuffEffect"
        name="dpheal" type="DPHealEffect"
        name="dphealinstant" type="DPHealInstantEffect"
        name="dptransfer" type="DPTransferEffect"
        name="drboost" type="DRBoostEffect"
        name="escape" type="EscapeEffect"
        name="evade" type="EvadeEffect"
        name="extendedaurarange" type="ExtendAuraRangeEffect"
        name="fall" type="FallEffect"
        name="fear" type="FearEffect"
        name="flyoff" type="FlyOffEffect"
        name="fpatk" type="FpAttackEffect"
        name="fpatkinstant" type="FpAttackInstantEffect"
        name="fpheal" type="FPHealEffect"
        name="fphealinstant" type="FPHealInstantEffect"
        name="heal" type="HealEffect"
        name="healcastoronatk" type="HealCastorOnAttackedEffect"
        name="healcastorontargetdead" type="HealCastorOnTargetDeadEffect"
        name="healinstant" type="HealInstantEffect"
        name="hide" type="HideEffect"
        name="hipass" type="HiPassEffect"
        name="hostileup" type="HostileUpEffect"
        name="invulnerablewing" type="InvulnerableWingEffect"
        name="magiccounteratk" type="MagicCounterAtkEffect"
        name="movebehind" type="MoveBehindEffect"
        name="mpattack" type="MpAttackEffect"
        name="mpattackinstant" type="MpAttackInstantEffect"
        name="mpheal" type="MPHealEffect"
        name="mphealinstant" type="MPHealInstantEffect"
        name="mpshield" type="MPShieldEffect"
        name="nodeathpenalty" type="NoDeathPenaltyEffect"
        name="nofly" type="NoFlyEffect"
        name="nofpconsum" type="NoFPConsumEffect"
        name="noreducespellatk" type="NoReduceSpellATKInstantEffect"
        name="noresurrectpenalty" type="NoResurrectPenaltyEffect"
        name="onetimeboostheal" type="OneTimeBoostHealEffect"
        name="onetimeboostskillattack" type="OneTimeBoostSkillAttackEffect"
        name="onetimeboostskillcritical" type="OneTimeBoostSkillCriticalEffect"
        name="openaerial" type="OpenAerialEffect"
        name="paralyze" type="ParalyzeEffect"
        name="petorderuseultraskill" type="PetOrderUseUltraSkillEffect"
        name="poison" type="PoisonEffect"
        name="polymorph" type="PolymorphEffect"
        name="procatk_instant" type="ProcAtkInstantEffect"
        name="procdphealinstant" type="ProcDPHealInstantEffect"
        name="procfphealinstant" type="ProcFPHealInstantEffect"
        name="prochealinstant" type="ProcHealInstantEffect"
        name="procmphealinstant" type="ProcMPHealInstantEffect"
        name="procvphealinstant" type="ProcVPHealInstantEffect"
        name="protect" type="ProtectEffect"
        name="provoker" type="ProvokerEffect"
        name="pulled" type="PulledEffect"
        name="randommoveloc" type="RandomMoveLocEffect"
        name="rebirth" type="RebirthEffect"
        name="recallinstant" type="RecallInstantEffect"
        name="reflector" type="ReflectorEffect"
        name="resurrect" type="ResurrectEffect"
        name="resurrectbase" type="ResurrectBaseEffect"
        name="resurrectpos" type="ResurrectPositionalEffect"
        name="return" type="ReturnEffect"
        name="returnpoint" type="ReturnPointEffect"
        name="riderobot" type="RideRobotEffect"
        name="root" type="RootEffect"
        name="sanctuary" type="SanctuaryEffect"
        name="search" type="SearchEffect"
        name="shield" type="ShieldEffect"
        name="signet" type="SignetEffect"
        name="signetburst" type="SignetBurstEffect"
        name="silence" type="SilenceEffect"
        name="simpleroot" type="SimpleRootEffect"
        name="skillatk" type="SkillAttackInstantEffect"
        name="skillatkdraininstant" type="SkillAtkDrainInstantEffect"
        name="skillcooltimereset" type="SkillCooltimeResetEffect"
        name="skilllauncher" type="SkillLauncherEffect"
        name="skillxpboost" type="SkillXPBoostEffect"
        name="slow" type="SlowEffect"
        name="snare" type="SnareEffect"
        name="spellatk" type="SpellAttackEffect"
        name="spellatkdrain" type="SpellAtkDrainEffect"
        name="spellatkdraininstant" type="SpellAtkDrainInstantEffect"
        name="spellatkinstant" type="SpellAttackInstantEffect"
        name="spin" type="SpinEffect"
        name="stagger" type="StaggerEffect"
        name="statboost" type="StatboostEffect"
        name="stumble" type="StumbleEffect"
        name="stun" type="StunEffect"
        name="stunalways" type="StunAlwaysEffect"
        name="subtypeboostresist" type="SubTypeBoostResistEffect"
        name="subtypeextendduration" type="SubTypeExtendDurationEffect"
NOTUSED name="summonbindinggroupgate" type="SummonBindingGroupGateEffect"
        name="switchhostile" type="SwitchHostileEffect"
        name="switchhpmp" type="SwitchHpMpEffect"
        name="targetchange" type="TargetChangeEffect"
        name="targetteleport" type="TargetTeleportEffect"
        name="weaponstatboost" type="WeaponStatboostEffect"
        name="weaponstatup" type="WeaponStatupEffect"
        name="wpndual" type="WeaponDualEffect"
        name="xpboost" type="XPBoostEffect"
    */
    for ($e=1;$e<5;$e++)
    {
        $effkey = "effect".$e."_type";
        $efftyp = strtolower(getTabValue($key,$effkey,"?"));
        
        switch(strtolower($efftyp))
        { 
            case "?"                         :  /* kein EffektType vorhanden */                              break;

            case "absoluteslow"              :  $ret .= getEffectAbsoluteSlow($key,$e);                      break;
            case "absolutesnare"             :  $ret .= getEffectAbsoluteSnare($key,$e);                     break;
            case "amr_mastery"               :  $ret .= getEffectArmorMastery($key,$e);                      break;
            case "shapechange"               :  $ret .= getEffectShapeChange($key,$e);                       break;
            case "shieldmastery"             :  $ret .= getEffectShieldMastery($key,$e);                     break;
            case "statup"                    :  $ret .= getEffectStatUp($key,$e);                            break;
            case "statdown"                  :  $ret .= getEffectStatDown($key,$e);                          break; 
            case "sleep"                     :  $ret .= getEffectSleep($key,$e);                             break;
            case "summon"                    :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summonfunctionalnpc"       :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summongroupgate"           :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summonhoming"              :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summonhousegate"           :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summonservant"             :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summonskillarea"           :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summontotem"               :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "summontrap"                :  $ret .= getEffectSummonAll($efftyp,$key,$e);                 break;
            case "wpn_mastery"               :  $ret .= getEffectWpnMastery($key,$e);                        break;
      
            default                          :  $taberreff[$efftyp] = 1;                                     break;
        }
    }
    
    if ($ret != "")
        $ret = '        <effects>'."\n".
               $ret.'        </effects>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: Actions
// ---------------------------------------------------------------------------
function getActionsLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // ........
    /*
        <xs:complexType name="Actions">
            <xs:sequence minOccurs="0" maxOccurs="unbounded">
                name="itemuse" type="ItemUseAction"
                name="mpuse" type="MpUseAction"
                name="hpuse" type="HpUseAction"
                name="dpuse" type="DpUseAction"
            </xs:sequence>
        </xs:complexType>
    */
    
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
    
    // ........
    /*
        <xs:complexType name="PeriodicActions">
            <xs:sequence minOccurs="0" maxOccurs="unbounded">
                name="hpuse" type="HpUsePeriodicAction"
                name="mpuse" type="MpUsePeriodicAction"
            </xs:sequence>
            <xs:attribute name="checktime" type="xs:int"/>
        </xs:complexType>
    */
    
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
// Zeilen aufbereiten für: Motions
// ---------------------------------------------------------------------------
function getMotionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // ........
    /*
        <xs:complexType name="Motion">
            <xs:attribute name="name" type="xs:string"/>
            <xs:attribute name="speed" type="xs:int" use="optional" default="100"/>
            <xs:attribute name="instant_skill" type="xs:boolean" use="optional" default="false"/>
        </xs:complexType>
    */
    
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
}
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
        /*
        <client_skill_learn>
            <id>1</id>
            <name>1</name>
            <race>All</race>
            <class>ARTIST</class>
            <pc_level>1</pc_level>
            <skill>P_EQUIP_EnhancedHarp_G1</skill>
            <skill_level>1</skill_level>
            <autolearn>TRUE</autolearn>
            <ui_display>1</ui_display>
            <stigma_display>0</stigma_display>
        </client_skill_learn>
        */
        
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
function makeAbgleichSvnFile()
{
    global $pathsvn;
    
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
            // momentan auch für die Effekte nur Zeilenweise!  
            // Effekt Blöcke
            if     (stripos($line,"<statup")        !== false
            ||      stripos($line,"<statdown")      !== false
            ||      stripos($line,"<wpnmastery")    !== false
            ||      stripos($line,"<absoluteslow")  !== false
            ||      stripos($line,"<absolutesnare") !== false
            ||      stripos($line,"<armormastery")  !== false
            ||      stripos($line,"<shieldmastery") !== false)
            {
                $doeff    = true;
                
                if (stripos($line,"/>") === false)
                {
                    $doblock  = true;
                    $endblock = "</".getXmlKey($line).">";
                }
                else
                    $doline = true;
            }
            // Effekt Zeilen
            elseif (stripos($line,"<shapechange")         !== false
            ||      stripos($line,"<sleep")               !== false
            ||      stripos($line,"<summon")              !== false) // alle Summon-Zeilen !!!
            {
                $doline   = true;
                $doeff    = true;
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
// alle Skill-Tags ausgeben
// ----------------------------------------------------------------------------
function showSkillTags()
{
    global $tabxskill;
    
    logHead("Liste aller gefundenen Skill-Xml-Tags");
    
    $tabSort = array_keys($tabxskill);
    $maxSort = count($tabSort);
    sort($tabSort);
    
    for ($s=0;$s<$maxSort;$s++)
    {
        logLine("- Skill-Tag gefunden",$tabSort[$s]);
    }
    
    logLine("Anzahl gefundene Tags",$maxSort);
}
// ----------------------------------------------------------------------------
// alle genutzten Condition-Tags im akt. SVN ermitteln/ausgeben
// ----------------------------------------------------------------------------
function showAllConditions()
{
    global $pathsvn;
    
    $tabcond = array();
    
    logHead("Erzeuge Conditions-Liste aus dem SVN");
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\skills\\skill_templates.xml");
    $hdlsvn  = openInputFile($filesvn);
     
    $incond = "";
    $endkey = "???";
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        
        if (stripos($line,"<startconditions>") !== false
        ||  stripos($line,"<endconditions>")   !== false
        ||  stripos($line,"<useconditions>")   !== false
        ||  stripos($line,"<useequipmentconditions>") !== false)
        {
            $incond = getXmlKey($line);
            $endkey = "</$incond>";
        }
        else
        {
            if ($incond)
            {
                if (stripos($line,$endkey) !== false)
                {
                    $incond = "";
                    $endkey = "???";
                }
                else
                {
                    $xml = getXmlKey($line);
                    $tabcond[$incond][$xml] = 1;
                }
            }
        }
    }
    fclose($hdlsvn);
    
    while (list($key,$val) = each($tabcond))
    {
        logLine($key,"Condition Start");
        
        while (list($xml,$xval) = each($tabcond[$key]))
        {   
            logLine("- XML-Key",$xml);
        }
    }
    /* aktuelles Ergebnis !!!!
        startconditions
        - XML-Key 	dp
        - XML-Key 	mp
        - XML-Key 	chain
        - XML-Key 	target
        - XML-Key 	selfflying
        - XML-Key 	weapon
        - XML-Key 	combatcheck
        - XML-Key 	form
        - XML-Key 	targetflying
        - XML-Key 	skillcharge
        - XML-Key 	lefthandweapon
        - XML-Key 	hp
        endconditions 
        - XML-Key 	chargeweapon
        - XML-Key 	chargearmor
        - XML-Key 	polishchargeweapon
        useconditions 
        - XML-Key 	move_casting
        useequipmentconditions
        - XML-Key 	lefthandweapon
    */
}  
// ----------------------------------------------------------------------------
// alle genutzten Effect-Tags im akt. SVN ermitteln/ausgeben
// ----------------------------------------------------------------------------
function showAllEffects()
{
    global $pathsvn;
    
    $tabcond = array();
    
    logHead("Erzeuge Effect-Liste aus dem SVN");
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\skills\\skill_templates.xml");
    $hdlsvn  = openInputFile($filesvn);
     
    $incond = "";
    $endkey = "???";
    $id     = "";
    
    while (!feof($hdlsvn))
    {
        $line = rtrim(fgets($hdlsvn));
        
        if (stripos($line,"<effects>") !== false)
        {
            $incond = getXmlKey($line);
            $endkey = "</$incond>";
        }
        else
        {
            if ($incond)
            {
                if (stripos($line,$endkey) !== false)
                {
                    $incond = "";
                    $endkey = "???";
                }
                else
                {
                    $xml = getXmlKey($line);
                    if ($xml != "" && substr($xml,0,1) != "/")
                    {
                        if (isset($tabcond[$incond][$xml]))
                        {
                            if (stripos($tabcond[$incond][$xml],$id) === false)
                                $tabcond[$incond][$xml] .= $id." ";
                        }
                        else
                            $tabcond[$incond][$xml]  = $id." ";
                    }
                }
            }
            elseif (stripos($line,"skill_id=") !== false)
                $id = getKeyValue("skill_id",$line);
        }
    }
    fclose($hdlsvn);
    
    while (list($key,$val) = each($tabcond))
    {
        logLine($key,"Condition Start");
        
        while (list($xml,$xval) = each($tabcond[$key]))
        {   
            logLine("- ".$xml,$xval);
        }
    }
    /* aktuelles Ergebnis !!!!
    
    
    */
} 
// ----------------------------------------------------------------------------
// Anzeigen aller Effekte, die noch nicht bearbeitet werden!
// ---------------------------------------------------------------------------- 
function showMissingEffects()
{
    global $taberreff;
    
    logLine("Anzahl fehlende Effekt-Scripts",count($taberreff)." von 168 (sind im Parser noch nicht realisiert!)");
    
    while (list($key,$val) = each($taberreff))
        logLine("<font color=red>Add Effect-Script",$key);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_getautonameids.php");
include("includes/auto_inc_item_infos.php");
include("includes/auto_inc_skill_names.php");
include("includes/auto_inc_npc_infos.php");

$starttime = microtime(true);
$tabSNames = array();
$tabrskill = array();
$tabcskill = array();
$tabxskill = array();
$tabcharge = array();
$taberreff = array();

$protkey   = ""; // wird nur zu Testzwecken genutzt!

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
        scanPsSkillNames();
        scanSkillCharges();
        scanClientSkills();
        makeSkillsRefTab();
        // -------------------------
        // nur zum Testen benötigt!
        // showSkillTags();
        // showAllConditions();
        // showAllEffects();
        // -------------------------
        generSkillTemplateFile();
        showMissingEffects();
        generSkillTreeFile();
        
        makeAbgleichSvnFile();
        
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