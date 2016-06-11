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
    // Prüfen auf Rückgabe in Grossbuchstaben
    switch($fname)
    {
        case "skilltype":
        case "skillsubtype":
        case "tslot":
        case "dispel_category":
        case "activation":
        case "counter_skill":
            $fvalue = strtoupper($fvalue);
            break;
        default:
            break;
    }
    
    switch($fname)
    {
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
        // ansonsten keine Veränderungen!        
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
    
    if (isset($tabcskill[$key][$cxml]))
        $tmp = $tabcskill[$key][$cxml];
    
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
        
        if (stripos($line,"skill_template") !== false)
        {
            fwrite($hdlout,'    '.$line."\n");
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