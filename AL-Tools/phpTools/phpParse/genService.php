<html>
<head>
  <title>
    Service - Erzeugen Service-BonusAttr-Dateien"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

if (!file_exists("../outputs/parse_output/service_bonusattr"))
    mkdir("../outputs/parse_output/service_bonusattr");

$submit   = isset($_GET['submit'])   ? "J"               : "N";

?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Service-BonusAttr-Dateien</div>
  <div class="hinweis" id="hinw">
    Erzeugen der Service-BonusAttr.xml-Dateien<br>
    (service_bonusattr.xml und <font color=gray>players_bonus.xml</font>)
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genService.php" target="_self">
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
// Ausgabezeile formatieren
// ----------------------------------------------------------------------------
function rnd3($wert)
{
    return round($wert,3);
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// ServiceBonusAttr-Datei ausgeben
// ----------------------------------------------------------------------------
function generServiceBonusattrFile()
{
    global $pathdata,$tabbonusattrs;
    
    $fileu16 = formFileName($pathdata."\\skills\\conditional_bonuses.xml");
    $fileout = "../outputs/parse_output/service_bonusattr/service_bonusattr.xml";
    
    $fileext = convFileToUtf8($fileu16);
    logHead("Generierung der Datei: ".basename($fileout));
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $cntles = 0;
    $cntout = 0;
    
    $lines    = file($fileext);
    $domax    = count($lines);
    $tabbonus = array();
    $tabboost = array(
                  array("apbon","AP_BOOST"),
                  array("appen","AP_REDUCE_RATE"),
                  array("enchb","ENCHANT_BOOST"),
                  array("auth" ,"AUTHORIZE_BOOST"),
                  array("encho","ENCHANT_OPTION_BOOST"),
                  array("craft","BOOST_CRAFTING_XP_RATE"),
                  array("gath" ,"BOOST_GATHERING_XP_RATE"),
                  array("ghunt","BOOST_GROUP_HUNTING_XP_RATE"),
                  array("hunt" ,"BOOST_HUNTING_XP_RATE"),
                  array("death","DEATH_PENALTY_REDUCE")
                );
    $maxboost = count($tabboost);
        
    $id = $name = $desc = $anorm = $aspec = $bonus = "";
    
    for ($l=0;$l<$domax;$l++)
    {
        $line = rtrim($lines[$l]);
        $cntles++;
        
        if (stripos($line,"<?xml") === false
        &&  stripos($line,"client_ride_data") === false)
        {    
            // Start Service_bonusattr
            if     (stripos($line,"<id>")                !== false) 
                $id    = getXmlValue("id",$line);
            elseif (stripos($line,"<name>")              !== false) 
                $tabbonus[$id]['name']  = getXmlValue("name",$line);
            elseif (stripos($line,"<desc>")              !== false) 
                $tabbonus[$id]['desc']  = getXmlValue("desc",$line);
            elseif (stripos($line,"<apply_normal_svr>")  !== false) 
                $tabbonus[$id]['anorm'] = getXmlValue("apply_normal_svr",$line);
            elseif (stripos($line,"<apply_special_svr>") !== false) 
                $tabbonus[$id]['aspec'] = getXmlValue("apply_special_svr",$line);
            elseif (stripos($line,"<exp_bonus>")         !== false) 
                $tabbonus[$id]['bonus'] = getXmlValue("exp_bonus",$line);
            elseif (stripos($line,"<indun_drop_bonus>")  !== false)
                $tabbonus[$id]['idrop'] = getXmlValue("indun_drop_bonus",$line);
            elseif (stripos($line,"<ap_bonus>")          !== false) 
                $tabbonus[$id]['apbon'] = getXmlValue("ap_bonus",$line);
            elseif (stripos($line,"<ap_penalty_reduce>") !== false) 
                $tabbonus[$id]['appen'] = getXmlValue("ap_penalty_reduce",$line);
            elseif (stripos($line,"<enchant_boost>")  !== false)
                $tabbonus[$id]['enchb'] = getXmlValue("enchant_boost",$line);
            elseif (stripos($line,"<authorize_boost>")  !== false)
                $tabbonus[$id]['auth']  = getXmlValue("authorize_boost",$line);
            elseif (stripos($line,"<enchant_option_boost>")  !== false)
                $tabbonus[$id]['encho'] = getXmlValue("enchant_option_boost",$line);
            elseif (stripos($line,"<craft_exp_bonus>")  !== false)
                $tabbonus[$id]['craft'] = getXmlValue("craft_exp_bonus",$line);
            elseif (stripos($line,"<gather_exp_bonus>")  !== false)
                $tabbonus[$id]['gath']  = getXmlValue("gather_exp_bonus",$line);
            elseif (stripos($line,"<aerial_gather_exp_bonus>")  !== false)
            {
                $tabbonus[$id]['ghunt'] = getXmlValue("aerial_gather_exp_bonus",$line);
                $tabbonus[$id]['hunt']  = getXmlValue("aerial_gather_exp_bonus",$line);
            }
            elseif (stripos($line,"<death_penalty_reduce>")  !== false)
                $tabbonus[$id]['death'] = getXmlValue("death_penalty_reduce",$line);
            elseif (stripos($line,"<bonus_attr")         !== false)
            {
                $xml = getXmlKey($line);
                $tabbonus[$id]['attrs'][$xml] = getXmlValue($xml,$line);
            }
            elseif (stripos($line,"</client_conditional_bonus>") !== false)
            {                
                $id =  "";
            }
        }
    }
    
    // Daten ausgeben
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<service_bonusattrs xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="service_bonusattr.xsd">'."\n");
    $cntout += 3;
    
    while (list($id,$val) = each($tabbonus))
    {
        if (stripos($tabbonus[$id]['name'],"test") === false)
        {
            $tbuff = (stripos($tabbonus[$id]['name'],"buff") === false) ? " Buff" : "";
            
            fwrite($hdlout,'    <!-- '.$tabbonus[$id]['name'].$tbuff.' -->'."\n");
            fwrite($hdlout,'    <service_bonusattr buff_id="'.$id.'">'."\n");
            $cntout += 2;
            
            // Standard-Attribute
            if (!isset($tabbonus[$id]['attrs']))
            { 
                if (isset($tabbonus[$id]['bonus']))
                {
                    fwrite($hdlout,'        <bonus_attr stat="BOOST_CRAFTING_XP_RATE" func="ADD" value="'.$tabbonus[$id]['bonus'].'"/>'."\n");
                    fwrite($hdlout,'        <bonus_attr stat="BOOST_GATHERING_XP_RATE" func="ADD" value="'.$tabbonus[$id]['bonus'].'"/>'."\n");
                    fwrite($hdlout,'        <bonus_attr stat="BOOST_GROUP_HUNTING_XP_RATE" func="ADD" value="'.$tabbonus[$id]['bonus'].'"/>'."\n");
                    fwrite($hdlout,'        <bonus_attr stat="BOOST_HUNTING_XP_RATE" func="ADD" value="'.$tabbonus[$id]['bonus'].'"/>'."\n");
                    $cntout += 4;
                }
            }
            else
            {
                // zur Zeit maximaal 8 Attribute ausgeben
                for ($a=1;$a<9;$a++)
                {
                    if (isset($tabbonus[$id]['attrs']['bonus_attr'.$a]))
                    {
                        $atab = split(" ",$tabbonus[$id]['attrs']['bonus_attr'.$a]);
                        $func = "ADD";
                        
                        if (!isset($atab[1])) 
                            $atab[1] = "1";
                        else
                        {
                            if (stripos($atab[1],"%") !== false)
                            {
                                $func     = "PERCENT";;
                                $atab[1] = trim(str_replace("%","",$atab[1]));
                            }
                        }
                        // auch ohne %-Zeichen als PERCENT ausgeben
                        if (stripos($atab[0],"phyattack") !== false)
                            $func = "PERCENT";
                            
                        // negative Werte erzeugen!!!
                        if (strtoupper($atab[0]) == "BOOSTCASTINGTIME")
                            $atab[1] *= -1;
                            
                        fwrite($hdlout,'        <bonus_attr stat="'.getBonusAttrName($atab[0]).'" func="'.$func.'" value="'.$atab[1].'"/>'."\n");
                        $cntout++;
                    }
                }
            }
            
            // INDUN_DROP_BONUS: 2 Varianten!!!
            if (isset($tabbonus[$id]['idrop']))
            {                
                if (isset($tabbonus[$id]['enchb']) 
                ||  isset($tabbonus[$id]['encho']) 
                ||  isset($tabbonus[$id]['auth'] )) 
                    fwrite($hdlout,'        <bonus_attr stat="INDUN_DROP_BOOST" func="ADD" value="'.$tabbonus[$id]['idrop'].'"/>'."\n");
                else
                    fwrite($hdlout,'        <bonus_attr stat="DR_BOOST" func="ADD" value="'.$tabbonus[$id]['idrop'].'"/>'."\n");
                   
                $cntout++;
            }
            
            // Spezielle BOOST-Attribute
            for ($b=0;$b<$maxboost;$b++)
            {
                if (isset($tabbonus[$id][$tabboost[$b][0]]))
                {
                    fwrite($hdlout,'        <bonus_attr stat="'.$tabboost[$b][1].'" func="ADD" value="'.$tabbonus[$id][$tabboost[$b][0]].'"/>'."\n");
                    $cntout++;
                }
            }
            fwrite($hdlout,'    </service_bonusattr>'."\n");
            $cntout++;
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</service_bonusattrs>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen Eingabedatei",$domax);
    logLine("Zeilen verarbeitet ",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
}
// ----------------------------------------------------------------------------
// PlayersBonus-Datei ausgeben
// ----------------------------------------------------------------------------
function generPlayersBonusFile()
{
    global $pathdata,$tabbonusattrs;
    
    $fileout = "../outputs/parse_output/service_bonusattr/players_bonus.xml";
    
    logHead("Generierung der Datei: ".basename($fileout));
    
    logLine("HINWEIS","*** KEINE Informationen zum Generieren gefunden / erhalten ***");
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_bonusattrs.php");

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
        generServiceBonusAttrFile();
        generPlayersBonusFile();
        
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