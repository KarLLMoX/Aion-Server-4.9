<html>
<head>
  <title>
    Recipe-Templates - Erzeugen recipe_templates.xml"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");
include("includes/auto_inc_item_infos.php");
include("includes/auto_inc_skill_names.php");
include("includes/inc_getautonameids.php");

getConfData();

if (!file_exists("../outputs/parse_output/recipe"))
    mkdir("../outputs/parse_output/recipe");

$submit   = isset($_GET['submit'])   ? "J"               : "N";

?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen Recipe-Templates-Datei</div>
  <div class="hinweis" id="hinw">
    Erzeugen der Datei recipe_templaes.xml.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genRecipes.php" target="_self">
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
//                        H I L F S - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
function getItemNameId($name)
{
    global $tabNames;
    
    $key = strtoupper($name);
    
    if (isset($tabNames[$key]))
        return $tabNames[$key];
    else
        return $key;
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
function scanClientStrings()
{
    global $pathstring, $tabNames;
    
    LogHead("Scanne die Namen aus den PS-Client-Dateien");
        
    $tabfiles = array( "client_strings_item.xml",
                       "client_strings_item2.xml",
                       "client_strings_item3.xml"
                     );
    $maxfiles = count($tabfiles);

    for ($f=0;$f<$maxfiles;$f++)
    {  
        $filestr = formFileName($pathstring."\\".$tabfiles[$f]);
        $hdlstr  = openInputFile($filestr);
                
        if (!$hdlstr)
        {
            logLine("Fehler openInputFile",$filestr);
            return;
        }
        
        logSubHead("Scanne PS-Client-Strings");
        logLine("Eingabedatei",$filestr);
        $id     = "";
        $name   = "";
        $body   = "";
        $cntles = 0;
        $cntstr = 0;
        
        flush();
        
        while (!feof($hdlstr))
        {
            $line = rtrim(fgets($hdlstr));
            $cntles++;
            
            if     (stripos($line,"<id>")     !== false)
                $id   = getXmlValue("id",$line);
            elseif (stripos($line,"<name>")   !== false)
                $name = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"</string") !== false)
            {   
                $tabNames[$name]   = ($id * 2) + 1;
                
                $id = $name = "";
                $cntstr++;
            }
        }
        fclose($hdlstr);
        
        logLine("Anzahl Zeilen gelesen",$cntles);
        logLine("Anzahl Strings gefunden",$cntstr);
    }
}
// ----------------------------------------------------------------------------
//
//                        G E N E R - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// Recipe-Template-Datei ausgeben
// ----------------------------------------------------------------------------
function generRecipeTemplateFile()
{
    global $pathdata,$hdlout;
    
    $tabclient = array(
                       "client_combine_recipe.xml" 
                      );
    $tabrecipe = array();                   
    $taberror  = array();
    $domax     = count($tabclient);     
    
    logHead("Scanne die Client-Dateien");
    
    flush();
    
    for ($c=0;$c<$domax;$c++)
    {
        $fileu16   = formFileName($pathdata."\\Items\\".$tabclient[$c]);    
        $fileext   = convFileToUtf8($fileu16);
        
        logSubHead("Scanne von Client-Datei: ".$tabclient[$c]);
        logLine("Eingabedatei UTF16",$fileu16);
        logLine("Eingabedatei UTF8",$fileext);
        
        flush();
        
        $cntles = 0;
        $cntrec = 0;
        
        $id     = "";
        
        $hdlext = openInputFile($fileext);
        
        while (!feof($hdlext))
        {
            $line = rtrim(fgets($hdlext));
            $cntles++;
            
            if     (stripos($line,"<id>") !== false)
            {
                $id = getXmlValue("id",$line);
                $tabrecipe[$id]['id'] = $id;
                $cntrec++;
            }
            elseif (stripos($line,"<name>") !== false)
                $tabrecipe[$id]['name']   = getXmlValue("name",$line);
            elseif (stripos($line,"<desc>") !== false)
                $tabrecipe[$id]['desc']   = getXmlValue("desc",$line);
            elseif (stripos($line,"<combineskill>") !== false)
                $tabrecipe[$id]['skill']  = getXmlValue("combineskill",$line);
            elseif (stripos($line,"qualification_race>") !== false)
                $tabrecipe[$id]['race']   = getXmlValue("qualification_race",$line);
            elseif (stripos($line,"<required_skillpoint>") !== false)
                $tabrecipe[$id]['point']  = getXmlValue("required_skillpoint",$line);
            elseif (stripos($line,"<auto_learn>") !== false)
                $tabrecipe[$id]['auto']   = getXmlValue("auto_learn",$line);
            elseif (stripos($line,"<product>") !== false)
                $tabrecipe[$id]['prod']   = getXmlValue("product",$line);
            elseif (stripos($line,"<product_quantity>") !== false)
                $tabrecipe[$id]['quant']  = getXmlValue("product_quantity",$line);
            elseif (stripos($line,"<component_quantity>") !== false)
                $tabrecipe[$id]['count']  = getXmlValue("component_quantity",$line);
            // alle numerierten Componentx
            elseif (stripos($line,"<component") !== false)
            {
                $xml = getXmlKey($line);
                $tabrecipe[$id]['item'][$xml] = getXmlValue($xml,$line);
            }
            // alle numerierten compox_quantity
            elseif (stripos($line,"<compo") !== false)
            {
                $xml = getXmlKey($line);
                $tabrecipe[$id]['item'][$xml] = getXmlValue($xml,$line);
            }
            elseif (stripos($line,"<require_dp>") !== false)
                $tabrecipe[$id]['dp'] = getXmlValue("require_dp",$line);
            // alle numerierten combox_product
            elseif (stripos($line,"<combo") !== false)
            {
                $xml = getXmlKey($line);
                $tabrecipe[$id]['combo'][$xml] = getXmlValue($xml,$line);
            }
            elseif (stripos($line,"<max_production_count>") !== false)
                $tabrecipe[$id]['maxpr'] = getXmlValue("max_production_count",$line);
            elseif (stripos($line,"<craft_delay_id>") !== false)
                $tabrecipe[$id]['delid'] = getXmlValue("craft_delay_id",$line);
            elseif (stripos($line,"<craft_delay_time>") !== false)
                $tabrecipe[$id]['dtime'] = getXmlValue("craft_delay_time",$line);
            elseif (stripos($line,"</client_combine_recipe>") !== false)
                $id = "";            
        }
    
        logLine("- eingelesene Zeilen",$cntles);
        logLine("- darin enthaltene Rezepte ",$cntrec);
        
        fclose($hdlext);
    }
        
    logHead("Generierung der Datei: recipe_templates.xml");    
    
    flush();
    
    $fileout = "../outputs/parse_output/recipe/recipe_templates.xml";
    $hdlout  = openOutputFile($fileout);
    $cntout  = 0;
        
    $cmax = 0;
    $comax = 0;
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
    fwrite($hdlout,getCopyrightLine()."\n");
    fwrite($hdlout,'<recipe_templates xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'."\n");
    $cntout += 3;
        
    while (list($id,$val) = each($tabrecipe))
    {            
        $lout = '    <recipe_template id="'.$id.'" nameid="'.getItemNameId($tabrecipe[$id]['desc']).
                '" skillid="'.getSkillNameId($tabrecipe[$id]['skill']).'" race="';
        
        switch(strtoupper($tabrecipe[$id]['race']))
        {
            case "PC_LIGHT": $lout .= "ELYOS";     break;
            case "PC_DARK" : $lout .= "ASMODIANS"; break;
            default        : $lout .= "PC_ALL";    break;
        }
        
        $lout .= '" skillpoint="'.$tabrecipe[$id]['point'].'"';
        
        if (isset($tabrecipe[$id]['dp']))
            $lout .= ' dp="'.$tabrecipe[$id]['dp'].'"';
            
        if (isset($tabrecipe[$id]['auto']))
        {
            if ($tabrecipe[$id]['auto'] != "0")
                $lout .= ' autolearn="'.$tabrecipe[$id]['auto'].'"';
        }
        $lout .= ' productid="'.getClientItemId($tabrecipe[$id]['prod']).'" quantity="'.$tabrecipe[$id]['quant'].'"';
        
        if (isset($tabrecipe[$id]['maxpr']))
            $lout .= ' max_production_count="'.$tabrecipe[$id]['maxpr'].'"';
        
        if (isset($tabrecipe[$id]['delid']))
            $lout .= ' craft_delay_id="'.$tabrecipe[$id]['delid'].'"';
        
        if (isset($tabrecipe[$id]['dtime']))
            $lout .= ' craft_delay_time="'.$tabrecipe[$id]['dtime'].'"';
            
        $lout .= ">";
        
        fwrite($hdlout,$lout."\n");
        $cntout++;
        
        for ($c=1;$c<10;$c++)
        {
            if (isset($tabrecipe[$id]['item']['component'.$c])
            &&  isset($tabrecipe[$id]['item']['compo'.$c.'_quantity']))
            {
                if ($c > $cmax) $cmax = $c;
                
                fwrite($hdlout,'        <component quantity="'.$tabrecipe[$id]['item']['compo'.$c.'_quantity'].
                               '" itemid="'.getClientItemId($tabrecipe[$id]['item']['component'.$c]).'"/>'."\n");
                $cntout++;
            }
        }
        for ($c=1;$c<10;$c++)
        {
            if (isset($tabrecipe[$id]['combo']['combo'.$c.'_product']))
            {
                if ($c > $comax) $comax = $c;
                fwrite($hdlout,'        <comboproduct itemid="'.getClientItemId($tabrecipe[$id]['combo']['combo'.$c.'_product']).'"/>'."\n");
                $cntout++;
            }
        }
        fwrite($hdlout,'    </recipe_template>'."\n");
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</recipe_templates>");
    $cntout++;    
    fclose($hdlout);
    
    logSubHead("Erzeugen der Ausgabedatei");
    logLine("Ausgabedatei",$fileout);
    logLine("- ausgegebene Zeilen",$cntout);
    logLine("TEST - CMAX",$cmax);
    logLine("TEST - COMAX",$comax);
    
    if (count($taberror) > 0)
    {    
        logHead("Liste der nicht gefundenen Items");
        
        while (list($key,$val) = each($taberror))
        {
            logLine("ID: $val",$key);
        }
    }
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

$tabNames = array();

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
        scanClientStrings();
        generRecipeTemplateFile();
        
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