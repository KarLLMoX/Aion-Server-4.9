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
?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Skill-Dateien</div>
  <div class="hinweis" id="hinw">
  Erzeugen der skill....xml-Dateien.<br>
  (skill_charge.xml und skill_templates.xml)
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
// Feld-Text zurückgeben
// Params:  $key        Key in der Tabelle ( = id)
//          $cxml       Xml-Tag-Name Client
//          $fname      Ausgabename für Feld
//          $deflt      Default-Wert, wenn nicht vorhanden
// ----------------------------------------------------------------------------
function getFieldText($key,$cxml,$fname,$deflt)
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
        return "???";
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
            // Feld nicht gesetzt und darf n icht vorhanden sein,
            // dann Feld-Zeile der Tabelle berücksichtigen!
            if ( isset($tabcskill[$key][$cfld]) && $ctab[$cind][0] == true
            ||  !isset($tabcskill[$key][$cfld]) && $ctab[$cind][0] == false)
                $ret .= getFieldText($key,$ftab[$f][0],$ftab[$f][1],$ftab[$f][2]);
        }
        else
            // keine Condition vorhanden, also Feld-Zeile berücksichtigen
            $ret .= getFieldText($key,$ftab[$f][0],$ftab[$f][1],$ftab[$f][2]);
    }
    
    if ($ret != "")
        $ret = '        <properties'.$ret.'/>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: StartConditions
// ---------------------------------------------------------------------------
function getStartConditionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // ........
    /*    
        <xs:complexType name="Conditions">
            <xs:sequence minOccurs="0" maxOccurs="unbounded">
                <xs:element name="mp" type="MpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hp" type="HpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dp" type="DpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="target" type="TargetCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="move_casting" type="PlayerMovedCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="arrowcheck" type="ArrowCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="robotcheck" type="RobotCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="abnormal" type="AbnormalStateCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="onfly" type="OnFlyCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="noflying" type="NoFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="weapon" type="WeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="lefthandweapon" type="LeftHandCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="targetflying" type="TargetFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="selfflying" type="SelfFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="combatcheck" type="CombatCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chain" type="ChainCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="back" type="BackCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="front" type="FrontCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="form" type="FormCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="charge" type="ItemChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargeweapon" type="ChargeWeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargearmor" type="ChargeArmorCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="polishchargeweapon" type="PolishChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillcharge" type="SkillChargeCondition" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
        </xs:complexType>
    */
    
    if ($ret != "")
        $ret = '        <startconditions>'."\n".
               $ret.'</startconditions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: UseConditions
// ---------------------------------------------------------------------------
function getuseConditionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // ........
    /*    
        <xs:complexType name="Conditions">
            <xs:sequence minOccurs="0" maxOccurs="unbounded">
                <xs:element name="mp" type="MpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hp" type="HpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dp" type="DpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="target" type="TargetCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="move_casting" type="PlayerMovedCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="arrowcheck" type="ArrowCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="robotcheck" type="RobotCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="abnormal" type="AbnormalStateCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="onfly" type="OnFlyCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="noflying" type="NoFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="weapon" type="WeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="lefthandweapon" type="LeftHandCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="targetflying" type="TargetFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="selfflying" type="SelfFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="combatcheck" type="CombatCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chain" type="ChainCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="back" type="BackCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="front" type="FrontCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="form" type="FormCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="charge" type="ItemChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargeweapon" type="ChargeWeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargearmor" type="ChargeArmorCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="polishchargeweapon" type="PolishChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillcharge" type="SkillChargeCondition" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
        </xs:complexType>
    */
    
    if ($ret != "")
        $ret = '        <useconditions>'."\n".
               $ret.'</useconditions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: EndConditions
// ---------------------------------------------------------------------------
function getEndConditionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // ........
    /*    
        <xs:complexType name="Conditions">
            <xs:sequence minOccurs="0" maxOccurs="unbounded">
                <xs:element name="mp" type="MpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hp" type="HpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dp" type="DpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="target" type="TargetCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="move_casting" type="PlayerMovedCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="arrowcheck" type="ArrowCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="robotcheck" type="RobotCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="abnormal" type="AbnormalStateCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="onfly" type="OnFlyCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="noflying" type="NoFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="weapon" type="WeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="lefthandweapon" type="LeftHandCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="targetflying" type="TargetFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="selfflying" type="SelfFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="combatcheck" type="CombatCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chain" type="ChainCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="back" type="BackCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="front" type="FrontCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="form" type="FormCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="charge" type="ItemChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargeweapon" type="ChargeWeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargearmor" type="ChargeArmorCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="polishchargeweapon" type="PolishChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillcharge" type="SkillChargeCondition" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
        </xs:complexType>
    */
    
    if ($ret != "")
        $ret = '        <endconditions>'."\n".
               $ret.'</endconditions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: UseEquipmentConditions
// ---------------------------------------------------------------------------
function getUseEquipConditionLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // ........
    /*    
        <xs:complexType name="Conditions">
            <xs:sequence minOccurs="0" maxOccurs="unbounded">
                <xs:element name="mp" type="MpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hp" type="HpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dp" type="DpCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="target" type="TargetCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="move_casting" type="PlayerMovedCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="arrowcheck" type="ArrowCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="robotcheck" type="RobotCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="abnormal" type="AbnormalStateCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="onfly" type="OnFlyCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="noflying" type="NoFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="weapon" type="WeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="lefthandweapon" type="LeftHandCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="targetflying" type="TargetFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="selfflying" type="SelfFlyingCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="combatcheck" type="CombatCheckCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chain" type="ChainCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="back" type="BackCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="front" type="FrontCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="form" type="FormCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="charge" type="ItemChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargeweapon" type="ChargeWeaponCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="chargearmor" type="ChargeArmorCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="polishchargeweapon" type="PolishChargeCondition" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillcharge" type="SkillChargeCondition" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
        </xs:complexType>
    */
    
    if ($ret != "")
        $ret = '        <useequipmentconditions>'."\n".
               $ret.'</useequipmentconditions>';
        
    return $ret;
}
// ---------------------------------------------------------------------------
// Zeilen aufbereiten für: Effects
// ---------------------------------------------------------------------------
function getEffectsLines($key)
{
    global $tabcskill;
    
    $ret = "";
    
    // ........
    /*
        <xs:complexType name="Effects">
            <xs:sequence minOccurs="0" maxOccurs="unbounded">
                <xs:element name="root" type="RootEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="stun" type="StunEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="sleep" type="SleepEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="snare" type="SnareEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="absolutesnare" type="AbsoluteSnareEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="slow" type="SlowEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="absoluteslow" type="AbsoluteSlowEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="poison" type="PoisonEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="bleed" type="BleedEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="stumble" type="StumbleEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="spin" type="SpinEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="stagger" type="StaggerEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="openaerial" type="OpenAerialEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="closeaerial" type="CloseAerialEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="bind" type="BindEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="shield" type="ShieldEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="mpshield" type="MPShieldEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispel" type="DispelEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="statup" type="StatupEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="statboost" type="StatboostEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="weaponstatboost" type="WeaponStatboostEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="wpnmastery" type="WeaponMasteryEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="statdown" type="StatdownEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="spellatk" type="SpellAttackEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="deform" type="DeformEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="shapechange" type="ShapeChangeEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="polymorph" type="PolymorphEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hide" type="HideEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="search" type="SearchEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="healinstant" type="HealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="mphealinstant" type="MPHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dphealinstant" type="DPHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="fphealinstant" type="FPHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillatk" type="SkillAttackInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="spellatkinstant" type="SpellAttackInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dash" type="DashEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="backdash" type="BackDashEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="delaydamage" type="DelayedSpellAttackInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="return" type="ReturnEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="prochealinstant" type="ProcHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="procmphealinstant" type="ProcMPHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="procdphealinstant" type="ProcDPHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="procfphealinstant" type="ProcFPHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="carvesignet" type="CarveSignetEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="signet" type="SignetEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="signetburst" type="SignetBurstEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="silence" type="SilenceEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="curse" type="CurseEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="blind" type="BlindEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="disease" type="DiseaseEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="boosthate" type="BoostHateEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hostileup" type="HostileUpEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="paralyze" type="ParalyzeEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="confuse" type="ConfuseEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="alwaysresist" type="AlwaysResistEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="alwaysblock" type="AlwaysBlockEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="alwaysparry" type="AlwaysParryEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="alwaysdodge" type="AlwaysDodgeEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispeldebuffphysical" type="DispelDebuffPhysicalEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispeldebuff" type="DispelDebuffEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="switchhpmp" type="SwitchHpMpEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="aura" type="AuraEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summon" type="SummonEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="fear" type="FearEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="resurrect" type="ResurrectEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispeldebuffmental" type="DispelDebuffMentalEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="reflector" type="ReflectorEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="returnpoint" type="ReturnPointEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="provoker" type="ProvokerEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="spellatkdraininstant" type="SpellAtkDrainInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="onetimeboostskillattack" type="OneTimeBoostSkillAttackEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="onetimeboostskillcritical" type="OneTimeBoostSkillCriticalEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="armormastery" type="ArmorMasteryEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="weaponstatup" type="WeaponStatupEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="boostskillcastingtime" type="BoostSkillCastingTimeEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summontrap" type="SummonTrapEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summongroupgate" type="SummonGroupGateEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summonservant" type="SummonServantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillatkdraininstant" type="SkillAtkDrainInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="petorderuseultraskill" type="PetOrderUseUltraSkillEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="boostheal" type="BoostHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispelbuff" type="DispelBuffEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="procatk_instant" type="ProcAtkInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skilllauncher" type="SkillLauncherEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="pulled" type="PulledEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="movebehind" type="MoveBehindEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="rebirth" type="RebirthEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="boostskillcost" type="BoostSkillCostEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="protect" type="ProtectEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="resurrectbase" type="ResurrectBaseEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="magiccounteratk" type="MagicCounterAtkEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="recallinstant" type="RecallInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="randommoveloc" type="RandomMoveLocEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summonhoming" type="SummonHomingEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispelbuffcounteratk" type="DispelBuffCounterAtkEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="xpboost" type="XPBoostEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="fpatkinstant" type="FpAttackInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="fpatk" type="FpAttackEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="onetimeboostheal" type="OneTimeBoostHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="deboostheal" type="DeboostHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summonskillarea" type="SummonSkillAreaEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="resurrectpos" type="ResurrectPositionalEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="nofly" type="NoFlyEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="nofpconsum" type="NoFPConsumEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="healcastoronatk" type="HealCastorOnAttackedEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="wpndual" type="WeaponDualEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="invulnerablewing" type="InvulnerableWingEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="shieldmastery" type="ShieldMasteryEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="simpleroot" type="SimpleRootEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dptransfer" type="DPTransferEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="mpattack" type="MpAttackEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="boostdroprate" type="BoostDropRateEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="spellatkdrain" type="SpellAtkDrainEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="extendedaurarange" type="ExtendAuraRangeEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="changehateonattacked" type="ChangeHateOnAttackedEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="healcastorontargetdead" type="HealCastorOnTargetDeadEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="noreducespellatk" type="NoReduceSpellATKInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="mpattackinstant" type="MpAttackInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="condskilllauncher" type="CondSkillLauncherEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="fall" type="FallEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="evade" type="EvadeEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="buffbind" type="BuffBindEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="buffsilence" type="BuffSilenceEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="buffsleep" type="BuffSleepEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="buffstun" type="BuffStunEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="heal" type="HealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="mpheal" type="MPHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="fpheal" type="FPHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dpheal" type="DPHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summontotem" type="SummonTotemEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="boostspellattack" type="BoostSpellAttackEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="switchhostile" type="SwitchHostileEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="noresurrectpenalty" type="NoResurrectPenaltyEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hipass" type="HiPassEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="nodeathpenalty" type="NoDeathPenaltyEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="caseheal" type="CaseHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="procvphealinstant" type="ProcVPHealInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summonhousegate" type="SummonHouseGateEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summonbindinggroupgate" type="SummonBindingGroupGateEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="convertheal" type="ConvertHealEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="sanctuary" type="SanctuaryEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="subtypeextendduration" type="SubTypeExtendDurationEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="subtypeboostresist" type="SubTypeBoostResistEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispelnpcbuff" type="DispelNpcBuffEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dispelnpcdebuff" type="DispelNpcDebuffEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="deathblow" type="DeathBlowEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="delayedskill" type="DelayedSkillEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="delayedfpatk_instant" type="DelayedFpAtkInstantEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="drboost" type="DRBoostEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="apboost" type="APBoostEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillxpboost" type="SkillXPBoostEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="summonfunctionalnpc" type="SummonFunctionalNpcEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="targetteleport" type="TargetTeleportEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="flyoff" type="FlyOffEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="escape" type="EscapeEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="skillcooltimereset" type="SkillCooltimeResetEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="riderobot" type="RideRobotEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="absstatbuff" type="AbsoluteStatToPCBuff" minOccurs="0" maxOccurs="1"/>
                <xs:element name="absstatdebuff" type="AbsoluteStatToPCDebuff" minOccurs="0" maxOccurs="1"/>
                <xs:element name="stunalways" type="StunAlwaysEffect" minOccurs="0" maxOccurs="1"/>
                <xs:element name="targetchange" type="TargetChangeEffect" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
        </xs:complexType>
    */
    
    if ($ret != "")
        $ret = '        <effects>'."\n".
               $ret.'</effects>';
        
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
                <xs:element name="itemuse" type="ItemUseAction" minOccurs="0" maxOccurs="1"/>
                <xs:element name="mpuse" type="MpUseAction" minOccurs="0" maxOccurs="1"/>
                <xs:element name="hpuse" type="HpUseAction" minOccurs="0" maxOccurs="1"/>
                <xs:element name="dpuse" type="DpUseAction" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
        </xs:complexType>
    */
    
    if ($ret != "")
        $ret = '        <actions>'."\n".
               $ret.'</actions>';
        
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
                <xs:element name="hpuse" type="HpUsePeriodicAction" minOccurs="0" maxOccurs="1"/>
                <xs:element name="mpuse" type="MpUsePeriodicAction" minOccurs="0" maxOccurs="1"/>
            </xs:sequence>
            <xs:attribute name="checktime" type="xs:int"/>
        </xs:complexType>
    */
    
    if ($ret != "")
        $ret = '        <periodicactions>'."\n".
               $ret.'</periodicactions>';
        
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
    if ($ret != "")
        $ret = '        <motion>'."\n".
               $ret.'/>';
        
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
        /*
        <skill_template skill_id="1" name="Transformation: White Tiger" nameId="4571867" name_desc="RA_Light_WhiteTiger_G1" cooldownId="792" stack="SKILLN_RA_LIGHT_WHITETIGER" lvl="1" skilltype="MAGICAL" skillsubtype="BUFF" tslot="BUFF" dispel_category="BUFF" req_dispel_level="2" activation="ACTIVE" cooldown="100" duration="0" cancel_rate="20">
            <properties first_target="ME" first_target_range="1" target_relation="FRIEND" target_type="ONLYONE"/>
            <startconditions>
                <dp value="2000"/>
            </startconditions>
            <endconditions>
                <chargeweapon value="10"/>
                <chargearmor value="11"/>
                <polishchargeweapon value="77"/>
            </endconditions>
            <effects>
                <shapechange model="202641" type="PC" duration2="120000" effectid="175" e="1" basiclvl="100" noresist="true" hoptype="SKILLLV" hopb="248"/>
                <statup duration2="120000" effectid="107922" e="2" basiclvl="100" noresist="true" preeffect="1">
                    <change stat="PHYSICAL_ATTACK" func="PERCENT" value="10"/>
                </statup>
                <statup duration2="120000" effectid="107923" e="3" basiclvl="100" noresist="true" preeffect="1">
                    <change stat="ATTACK_SPEED" func="PERCENT" value="-30"/>
                </statup>
                <statup duration2="120000" effectid="107924" e="4" basiclvl="100" noresist="true" preeffect="1">
                    <change stat="SPEED" func="PERCENT" value="30"/>
                </statup>
            </effects>
            <actions>
                <dpuse value="2000"/>
            </actions>
            <motion name="phburst"/>
        </skill_template>
        */
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
            $lout .= getFieldText($key,"delay_id","cooldownId",""); 
            $lout .= getStackName($key);
            $lout .= getFieldText($key,"chain_category_level","lvl","1");
            $lout .= getFieldText($key,"type","skilltype","NONE");
            $lout .= getFieldText($key,"sub_type","skillsubtype","NONE");
            $lout .= getFieldText($key,"target_slot","tslot","NONE");
            $lout .= getFieldText($key,"target_slot_level","tslot_level","");
            $lout .= getFieldText($key,"conflict_id","conflict_id","");
            $lout .= getFieldText($key,"dispel_category","dispel_category","");
            $lout .= getFieldText($key,"required_dispel_level","req_dispel_level","");
            $lout .= getFieldText($key,"activation_attribute","activation","NONE");
            $lout .= getFieldText($key,"delay_time","cooldown","0");
            $lout .= getFieldText($key,"toggle_timer","toggle_timer","");
            $lout .= getFieldText($key,"casting_delay","duration","0");
            $lout .= getFieldText($key,"pvp_damage_ratio","pvp_damage","");
            $lout .= getFieldText($key,"pvp_remain_time_ratio","pvp_duration","");
            $lout .= getFieldText($key,"ammo_speed","ammospeed","");
            $lout .= getPenaltySkillId($key);
            $lout .= getGroundStatus($key);
            $lout .= getFieldText($key,"cancel_rate","cancel_rate","");
            $lout .= getFieldText($key,"chain_skill_prob2","chain_skill_prob","");
            $lout .= getFieldText($key,"counter_skill","counter_skill","");
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
                    case  3: $oline = getuseConditionLines($key); break;
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
    logLine("Skilsl ignoriert ",$cntign);
}
// ----------------------------------------------------------------------------
//
//                       A B G L E I C H   M I T   S V N
//
// ----------------------------------------------------------------------------
function makeAbgleichSvnFile()
{
    global $pathsvn;
    
    $filesvn = formFileName($pathsvn."\\trunk\\AL-Game\\data\\static_data\\skills\\skill_templates.xml");
    $fileout = "parse_temp/svn_skill_templates.xml";

    $hdlsvn = openInputFile($filesvn);
    $hdlout = openOutputFile($fileout);    
    
    $cntout = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<skill_data>'."\n");
    $cntout += 3;
    
    while (!feof($hdlsvn))
    {
        $line = trim(fgets($hdlsvn));
        
        if     (stripos($line,"skill_template") !== false)
        {
            fwrite($hdlout,'    '.$line."\n");
            $cntout++;
        }
        elseif (stripos($line,"properties")     !== false)
        {
            fwrite($hdlout,'        '.$line."\n");
            $cntout++;
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</skill_data>");
    $cntout++;
    
    fclose($hdlsvn);
    fclose($hdlout);
    
    logLine("Anzahl Zeilen ausgegeben",$cntout);
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_getautonameids.php");
include("includes/auto_inc_skill_names.php");
include("includes/auto_inc_npc_infos.php");

$starttime = microtime(true);
$tabSNames = array();
$tabcskill = array();
$tabxskill = array();

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
        scanClientSkills();
        // showSkillTags();
        
        generSkillTemplateFile();
        
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