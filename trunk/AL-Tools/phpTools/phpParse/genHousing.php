<html>
<head>
  <title>
    Housing - Erzeugen house-xml-Dateien"
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js"></script>
</head>
<?PHP
include("../includes/inc_globals.php");

getConfData();

if (!file_exists("../outputs/parse_output/housing"))
    mkdir("../outputs/parse_output/housing");

$submit   = isset($_GET['submit'])   ? "J"               : "N";

?>
<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Erzeugen House-Xml-Dateien</div>
  <div class="hinweis" id="hinw">
    Erzeugen der House-xml-Dateien.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte Generierung starten</h1>
<form name="edit" method="GET" action="genHousing.php" target="_self">
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
// Name (string) zum DESC zurückgeben
// ----------------------------------------------------------------------------
function getStringName($desc)
{
    global $tabNames;
    
    $key = strtoupper($desc);
    
    if (isset($tabNames[$key]))
        return $tabNames[$key];
    else
        return "???".$desc;
}
// ----------------------------------------------------------------------------
// SizeText zum Building zurückgeben
// ----------------------------------------------------------------------------
function getSizeText($size)
{
    switch($size)
    {
        case "s" : return "PALACE";
        case "a" : return "ESTATE";
        case "b" : return "MANSION";
        case "c" : return "HOUSE";
        case "d" : return "STUDIO";
        
        default  : return "???".$size;
    }
}
// ----------------------------------------------------------------------------
// PartID zum Namen zurückgeben
// ----------------------------------------------------------------------------
function getPartId($name)
{
    global $tabParts;
    
    $key = strtoupper($name);
    
    if (isset($tabParts[$key]))
        return $tabParts[$key];
    else
        return "???".$name;
}
// ----------------------------------------------------------------------------
// Zeile zu einem House-Building-Part ausgeben
// ----------------------------------------------------------------------------
function writePartLine($hdlout,$xmltag,$xmlvalue)
{
    global $tabParts;
    
    if ($xmlvalue != "")
    {
        fwrite($hdlout,'            <'.$xmltag.'>'.getPartId($xmlvalue).'</'.$xmltag.'>'."\n");
        return 1;
    }
    else
        return 0;
}
// ----------------------------------------------------------------------------
// XML-Tag-Name zurückgeben (Object)
// ----------------------------------------------------------------------------
function getObjectXmlTag($func,$categ)
{
    // über die Funktion, sofern vorhanden
    if ($func != "")
    {
        switch (strtoupper($func))
        {
            case "STORAGE"     : return "storage";
            case "NPCTYPE"     : return "npc";
            case "EMBLEM"      : return "emblem";
            case "USECOUNT"    : return "use_item";
            case "CHAIR"       : return "chair";
            case "POSTBOX"     : return "postbox";
            case "PICTURE"     : return "picture";
            case "JUKEBOXMOVIE": return "moviejukebox";
            
            default:             return "passive";
        }
    }
    else
    {
        // sonst über die Kategorie
        switch (strtoupper($categ))
        {            
            default     : return "passive";
        }
    }
}
// ----------------------------------------------------------------------------
//NameId zurückgeben (Object)
// ----------------------------------------------------------------------------
function getObjectNameId($desc)
{
    global $tabNamid;
    
    $key = strtoupper($desc);
    
    if (isset($tabNamid[$key]))
        return $tabNamid[$key];
    else
        return "???".$desc;
}
// ----------------------------------------------------------------------------
// gerundeten Wert für DIST zurückgeben 
// ----------------------------------------------------------------------------
function getDistValue($dist)
{
    $ret = round(floatval($dist),1);
    if (stripos($ret,".") === false)  $ret .= ".0";
    
    return $ret;
}
// ----------------------------------------------------------------------------
// LIMIT-Text zurückgeben 
// ----------------------------------------------------------------------------
function getLimit($limit)
{
    if     ($limit == "WALLDECO_PICTURE")    return ' limit="PICTURE"';
    elseif ($limit == "COOKINGTABLE")        return ' limit="COOKING"';
    elseif ($limit == "FLOWERPOT")           return ' limit="POT"';
    elseif ($limit == "FLOWERPOT_OWNER")     return ' limit="OWNER_POT"';
    elseif ($limit == "JUKE_BOX")            return ' limit="JUKEBOX"';
    elseif ($limit == "FLOWERPOT_VISITOR")   return ' limit="VISITOR_POT"';
    
    return ' limit="'.$limit.'"';
}
// ----------------------------------------------------------------------------
//
//                         S C A N - F U N K T I O N E N
//
// ----------------------------------------------------------------------------
// BuildingParts in interne Tabelle einlesen
// ----------------------------------------------------------------------------
function scanClientParts()
{
    global $pathdata,$tabParts;
    
    logHead("Scannen der Client-Parts-Datei");
    
    $fileu16 = formFileName($pathdata."\\Housing\\client_housing_custom_part.xml");  
    $fileext = convFileToUtf8($fileu16);
    
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    
    $cntles = 0;    
    $hdlext = openInputFile($fileext);
    
    $id = $name = "";
    
    flush();
    
    while (!feof($hdlext))
    {
        $line = rtrim(fgets($hdlext));
        $cntles++;
        
        if     (stripos($line,"<id>")    !== false)
            $id = getXmlValue("id",$line);
        elseif (stripos($line,"<name>")  !== false)
            $name = strtoupper(getXmlValue("name",$line));
        elseif (stripos($line,"</client_housing_custom_part>") !== false)
        {
            $tabParts[$name] = $id;
            
            $id = $name = "";
        }
    }
    fclose($hdlext);
    
    logline("Zeilen eingelesen",$cntles);
    logLine("gefundene PartIds",count($tabParts));
}
// ----------------------------------------------------------------------------
// PS-Strings in interne Tabelle einlesen
// ----------------------------------------------------------------------------
function scanClientStrings()
{
    global $pathstring,$tabNames;
    
    logHead("Scannen der PS-String-Dateien");
    
    $tabString = array( "client_strings_ui.xml",
                        "client_strings_item2.xml"
                      );
    $maxString = count($tabString);

    for ($s=0;$s<$maxString;$s++)
    {    
        $fileext = formFileName($pathstring."\\".$tabString[$s]);
        
        logSubHead("Scanne PS-String-Datei");
        logLine("Eingabedatei",$fileext);
        
        $cntles = 0;    
        $hdlext = openInputFile($fileext);
        
        $name = $body = "";
        
        flush();
        
        while (!feof($hdlext))
        {
            $line = rtrim(fgets($hdlext));
            $cntles++;
            
            if     (stripos($line,"<name>")    !== false)
                $name = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"<body>")  !== false)
                $body = getXmlValue("body",$line);
            elseif (stripos($line,"</string>") !== false)
            {
                $tabNames[$name] = $body;
                
                $name = $body = "";
            }
        }
        fclose($hdlext);
        
        logline("Zeilen eingelesen",$cntles);
    }
    logLine("gefundene Strings",count($tabNames));
}
// ----------------------------------------------------------------------------
// PS-Name-Ids in interne Tabelle einlesen
// ----------------------------------------------------------------------------
function scanClientNameIds()
{
    global $pathstring,$tabNamid;
    
    logHead("Scannen der PS-String-Dateien für die NameIds");
    
    $tabString = array( "client_strings_npc.xml",
                        "client_strings_item.xml",
                        "client_strings_item2.xml",
                        "client_strings_item3.xml"
                      );
    $maxString = count($tabString);

    for ($s=0;$s<$maxString;$s++)
    {    
        $fileext = formFileName($pathstring."\\".$tabString[$s]);
        
        logSubHead("Scanne PS-String-Datei");
        logLine("Eingabedatei",$fileext);
        
        $cntles = 0;    
        $hdlext = openInputFile($fileext);
        
        $id = $name = "";
        
        flush();
        
        while (!feof($hdlext))
        {
            $line = rtrim(fgets($hdlext));
            $cntles++;
            
            if     (stripos($line,"<id>")    !== false)
                $id   = getXmlValue("id",$line);
            elseif (stripos($line,"<name>")  !== false)
                $name = strtoupper(getXmlValue("name",$line));
            elseif (stripos($line,"</string>") !== false)
            {
                $tabNamid[$name] = $id;
                
                $id = $name = "";
            }
        }
        fclose($hdlext);
        
        logline("Zeilen eingelesen",$cntles);
    }
    logLine("gefundene Strings",count($tabNamid));
}
// ----------------------------------------------------------------------------
// HouseBuildings ausgeben
// ----------------------------------------------------------------------------
function generHouseBuildings()
{
    global $pathdata;
    
    logHead("Erzeugen Datei: house_buildings.xml");
    
    $fileu16 = formFileName($pathdata."\\Housing\\client_housing_building.xml");  
    $fileext = convFileToUtf8($fileu16);
    $fileout = "../outputs/parse_output/housing/house_buildings.xml";  
    
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $cntles = 0;
    $cntout = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,'<buildings>'."\n");
    $cntout += 2;
    
    $lines = file($fileext);
    $domax = count($lines);
    
    $id = $type = $size = $match = $roof = $owall = $frame = $door = $gard = $fence = $iwall = $floor = "";
    
    flush();
    
    for ($l=0;$l<$domax;$l++)
    {
        $line = rtrim($lines[$l]);
        $cntles++;
              
        if (stripos($line,"<?xml") === false
        &&  stripos($line,"<buildings>") === false)
        {       
            if     (stripos($line,"<id>")               !== false)
                $id    = getXmlValue("id",$line);
            elseif (stripos($line,"<type>")             !== false)
                $type  = getXmlValue("type",$line);
            elseif (stripos($line,"<size>")             !== false)
                $size  = getXmlValue("size",$line);
            elseif (stripos($line,"<tag>")              !== false)
                $match = getXmlValue("tag",$line);
            elseif (stripos($line,"<default_roof>")     !== false)
                $roof  = getXmlValue("default_roof",$line);
            elseif (stripos($line,"<default_outwall>")  !== false)
                $owall = getXmlValue("default_outwall",$line);
            elseif (stripos($line,"<default_frame>")    !== false)
                $frame = getXmlValue("default_frame",$line);
            elseif (stripos($line,"<default_door>")     !== false)
                $door  = getXmlValue("default_door",$line);
            elseif (stripos($line,"<default_garden>")   !== false)
                $gard  = getXmlValue("default_garden",$line);
            elseif (stripos($line,"<default_fence>")    !== false)
                $fence = getXmlValue("default_fence",$line);
            elseif (stripos($line,"<default_inwall>")   !== false)
                $iwall = getXmlValue("default_inwall",$line);
            elseif (stripos($line,"<default_infloor>")  !== false)
                $floor = getXmlValue("default_infloor",$line);
            elseif (stripos($line,"</client_housing_building>") !== false)
            {
                // Buildings vom Typ "legion" ignorieren
                if (strtolower($type) != "legion")
                {
                    if ($match == "") $match = strtoupper("CP_".$size);
                    
                    fwrite($hdlout,'    <building id="'.$id.'" type="'.strtoupper($type).'" size="'.
                                   getSizeText($size).'" parts_match="'.strtoupper($match).'">'."\n");
                    fwrite($hdlout,'        <parts>'."\n");
                    $cntout += 2;
                    
                    $cntout += writePartLine($hdlout,"roof",$roof);  
                    $cntout += writePartLine($hdlout,"outwall",$owall);     
                    $cntout += writePartLine($hdlout,"frame",$frame);
                    $cntout += writePartLine($hdlout,"door",$door);
                    $cntout += writePartLine($hdlout,"garden",$gard);
                    $cntout += writePartLine($hdlout,"fence",$fence);
                    $cntout += writePartLine($hdlout,"inwall",$iwall);
                    $cntout += writePartLine($hdlout,"infloor",$floor);
                    
                    fwrite($hdlout,'        </parts>'."\n");
                    fwrite($hdlout,'    </building>'."\n");
                    $cntout += 2;
                }
                $id = $type = $size = $match = $roof = $owall = $frame = 
                      $door = $gard = $fence = $iwall = $floor = "";
            }
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</buildings>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen Eingabedatei",$domax);
    logLine("Zeilen verarbeitet ",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
}
// ----------------------------------------------------------------------------
// HouseNpcs ausgeben
// ----------------------------------------------------------------------------
function generHouseNpcs()
{
    logHead("Erzeugen Datei: house_npcs.xml");
    logLine("<font color=red>keine Generierung</font>","nicht alle Daten im Client vorhanden/gefunden");
}
// ----------------------------------------------------------------------------
// HouseParts ausgeben
// ----------------------------------------------------------------------------
function generHouseParts()
{
    global $pathdata;
    
    logHead("Erzeugen Datei: house_parts.xml");
    
    $fileu16 = formFileName($pathdata."\\Housing\\client_housing_custom_part.xml");  
    $fileext = convFileToUtf8($fileu16);
    $fileout = "../outputs/parse_output/housing/house_parts.xml";  
    
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $cntles = 0;
    $cntout = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,'<house_parts>'."\n");
    $cntout += 2;
    
    $lines = file($fileext);
    $domax = count($lines);
    
    $id = $desc = $qual = $type = $tag = "";
    
    flush();
    
    for ($l=0;$l<$domax;$l++)
    {
        $line = rtrim($lines[$l]);
        $cntles++;        
        
        if (stripos($line,"<?xml") === false
        &&  stripos($line,"<house_parts>") === false)
        {            
            // Start HouseParts 
            if     (stripos($line,"<id>")             !== false)
                $id   = getXmlValue("id",$line);
            elseif (stripos($line,"<desc>")           !== false)
                $desc = strtoupper(getXmlValue("desc",$line));
            elseif (stripos($line,"<quality>")        !== false)
                $qual = strtoupper(getXmlValue("quality",$line));
            elseif (stripos($line,"<type>")           !== false)
                $type = strtoupper(getXmlValue("type",$line));
            elseif (stripos($line,"<tag>")            !== false)
                $tag  = strtoupper(getXmlValue("tag",$line));
            elseif (stripos($line,"</client_housing_custom_part>") !== false)
            {
                $tag  = str_replace(","," ",$tag);
                $lout ='    <house_part id="'.$id.'" name="'.getStringName($desc).'"'.
                               ' quality="'.$qual.'" type="'.$type.'" building_tags="'.$tag.'"/>';
                               
                if (strlen($lout) > 120)
                {
                    $pos = stripos($lout,"building_tags");
                    fwrite($hdlout,substr($lout,0,$pos - 1)."\n");
                    fwrite($hdlout,'                '.substr($lout,$pos)."\n");
                    
                    $cntout += 2;
                }
                else
                {
                    fwrite($hdlout,$lout."\n");
                    $cntout++; 
                }                    
            }
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</house_parts>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen Eingabedatei",$domax);
    logLine("Zeilen verarbeitet ",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
}
// ----------------------------------------------------------------------------
// Houses ausgeben
// ----------------------------------------------------------------------------
function generHouses()
{
    logHead("Erzeugen Datei: houses.xml");
    logLine("<font color=red>keine Generierung</font>","nicht alle Daten im Client vorhanden/gefunden");
    
    flush();
}
// ----------------------------------------------------------------------------
// HousingObjects ausgeben
// ----------------------------------------------------------------------------
function generHousingObjects()
{
    global $pathdata;
    
    logHead("Erzeugen Datei: housing_objects.xml");
    
    $fileu16 = formFileName($pathdata."\\Housing\\client_housing_object.xml");  
    $fileext = convFileToUtf8($fileu16);
    $fileout = "../outputs/parse_output/housing/housing_objects.xml";  
    
    logLine("Eingabedatei UTF16",$fileu16);
    logLine("Eingabedatei UTF8",$fileext);
    logLine("Ausgabedatei",$fileout);
    
    $cntles = 0;
    $cntout = 0;
    
    $hdlout = openOutputFile($fileout);
    
    // Vorspann ausgeben
    fwrite($hdlout,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    fwrite($hdlout,'<housing_objects>'."\n");
    $cntout += 2;
    
    $lines = file($fileext);
    $domax = count($lines);
    
    $id    = $desc   = $area   = $loc   = $udays  = $namid = $categ = $qual  = $func = "";
    $ware  = $npc    = $dist   = $cdye  = $glev   = $limit = $item  = $delay = $cd   = "";
    $check = $remove = $reward = $final = $owner = $usecnt = $xmltag = "";
    
    flush();
    
    for ($l=0;$l<$domax;$l++)
    {
        $line = rtrim($lines[$l]);
        $cntles++;
              
        if (stripos($line,"<?xml") === false
        &&  stripos($line,"<housing_objects>") === false)
        {            
            // Start HousingObjects
            if     (stripos($line,"<id>")                          !== false)
                $id    = getXmlValue("id",$line);
            elseif (stripos($line,"<desc>")                        !== false)
                $desc  = strtoupper(getXmlValue("desc",$line));
            elseif (stripos($line,"<place_area>")                  !== false)
                $area  = strtoupper(getXmlValue("place_area",$line));
            elseif (stripos($line,"<place_location>")              !== false)
                $loc   = strtoupper(getXmlValue("place_location",$line));
            elseif (stripos($line,"<housingobj_max_use_days>")     !== false)
                $udays = getXmlValue("housingobj_max_use_days",$line);
            elseif (stripos($line,"<category>")                    !== false)
                $categ = strtoupper(getXmlValue("category",$line));
            elseif (stripos($line,"<quality>")                     !== false)
                $qual  = strtoupper(getXmlValue("quality",$line));
            elseif (stripos($line,"<housingobj_function>")         !== false)
                $func  = strtoupper(getXmlValue("housingobj_function",$line));
            elseif (stripos($line,"<in_house_warehouse_idx>")      !== false)
                $ware  = strtoupper(getXmlValue("in_house_warehouse_idx",$line));
            elseif (stripos($line,"<place_limit_tag>")             !== false)
                $limit = strtoupper(getXmlValue("place_limit_tag",$line));
            elseif (stripos($line,"<npcname>")                     !== false)
                $npc   = strtoupper(getXmlValue("npcname",$line));
            elseif (stripos($line,"<required_item>")               !== false)
                $item  = strtoupper(getXmlValue("required_item",$line));
            elseif (stripos($line,"<use_delay>")                   !== false)
                $delay = getXmlValue("use_delay",$line);
            elseif (stripos($line,"<use_cooltime>")                !== false)
                $cd    = getXmlValue("use_cooltime",$line);
            elseif (stripos($line,"<pc_use_type>")                 !== false)
                $owner = getXmlValue("pc_use_type",$line);
            elseif (stripos($line,"<pc_use_count_max>")            !== false)
                $usecnt= getXmlValue("pc_use_count_max",$line);
            elseif (stripos($line,"<check_type>")                  !== false)
                $check = getXmlValue("check_type",$line);
            elseif (stripos($line,"<erase_value>")                 !== false)
                $remove= getXmlValue("erase_value",$line);
            elseif (stripos($line,"<reward_use>")                  !== false)
                $reward= strtoupper(getXmlValue("reward_use",$line));            
            elseif (stripos($line,"<reward_final>")                !== false)
                $final = strtoupper(getXmlValue("reward_final",$line));          
            elseif (stripos($line,"<talking_distance>")            !== false)
                $dist  = getXmlValue("talking_distance",$line);
            elseif (stripos($line,"<cannot_changecolor>")          !== false)
                $cdye  = getXmlValue("cannot_changecolor",$line);
            elseif (stripos($line,"<guild_level_permitted>")       !== false)
                $glev  = getXmlValue("guild_level_permitted",$line);
            elseif (stripos($line,"</client_housing_object>")      !== false)
            {
                $cdye   = ($cdye   !=  1 ) ? ' can_dye="true"'          : '';
                $owner  = ($owner   == 1 ) ? ' owner="true"'            : ' owner="false"';
                $usecnt = ($usecnt != "" ) ? ' use_count="'.$usecnt.'"' : '';
                $cd     = ($cd     != "" ) ? ' cd="'.$cd.'"'            : '';
                $delay  = ($delay  != "" ) ? ' delay="'.$delay.'"'      : ' delay="0"';
                
                $remove = ($remove != "" ) ? ' remove_count="'.$remove.'"' : '';
                $udays  = ($udays  != "" ) ? ' use_days="'.$udays.'"'      : '';
                
                $item   = ($item   != "" ) ? ' required_item="'.getClientItemId($item).'"'    : '';
                $final  = ($final  != "" ) ? ' final_reward_id="'.getClientItemId($final).'"' : '';
                
                if ($dist  != "" ) $dist  = getDistValue($dist);
                if ($npc   != "" ) $namid = getNpcIdNameTab($npc);
                if ($limit != "" ) $limit = getLimit($limit);
                if ($check == "" ) $check = "0";
                if ($glev  == "" ) $glev  = "0";
                
                $xmltag = getObjectXmlTag($func,$categ);
                
                $lout = '    <'.$xmltag;
                
                switch ($xmltag)
                {
                    case "storage":   // storage
                        // <storage warehouse_id="1" area="INTERIOR" location="FLOOR" limit="STORAGE" id="3000007" 
                        //          name_id="360007" category="TABLE" quality="COMMON" talking_distance="5.0" can_dye="true"/>
                        $lout .= ' warehouse_id="'.$ware.'" area="'.$area.'" location="'.$loc.'"'.$udays.$limit.
                                 ' id="'.$id.'" name_id="'.getObjectNameId($desc).'" category="'.$categ.'"'.
                                 ' quality="'.$qual.'" talking_distance="'.$dist.'"'.$cdye.'/>';
                        break;
                    case "npc":       // npc
                        // <npc npc_id="810013" area="INTERIOR" location="FLOOR" use_days="30" id="3001000" name_id="461892" 
                        //          category="NPC" quality="COMMON" talking_distance="5.0"/>
                        $lout .= ' npc_id="'.$namid['npcid'].'" area="'.$area.'" location="'.$loc.'"'.$udays.
                                 ' id="'.$id.'" name_id="'.getObjectNameId($desc).'" category="'.$categ.'"'.
                                 ' quality="'.$qual.'" talking_distance="'.$dist.'"/>';
                        break;
                    case "emblem":    // legion emblem
                        // <emblem level="1" area="ALL" location="WALL" id="3020041" name_id="799057" category="TABLE" 
                        //         quality="RARE" talking_distance="5.0"/>
                        $lout .= ' level="'.$glev.'" area="'.$area.'" location="'.$loc.'" id="'.$id.'" name_id="'.
                                 getObjectNameId($desc).'" category="'.$categ.'" quality="'.$qual.'"'.
                                 ' talking_distance="'.$dist.'"/>';
                        break;
                    case "use_item":  // use_item
                        // <use_item required_item="186000166" delay="3000" cd="0" owner="true" area="INTERIOR" location="STACK" 
                        //           limit="POT" use_days="7" id="3190001" name_id="791972" category="DECORATION" quality="COMMON" 
                        //           talking_distance="2.0">
                        //    <action check_type="2" remove_count="1" reward_id="188051519"/>
                        //</use_item>
                        $lout .= $item.$usecnt.$delay.$cd.$owner.
                                 ' area="'.$area.'" location="'.$loc.'"'.$limit.$udays.' id="'.$id.'"'.
                                 ' name_id="'.getObjectNameId($desc).'" category="'.$categ.'" quality="'.$qual.'"'.
                                 ' talking_distance="'.$dist.'"';
                                 
                        if ($reward != ""  ||  $final != "")
                        {
                            fwrite($hdlout,$lout.">\n");
                            $cntout++;
                            
                            $lout  = '        <action check_type="'.$check.'"'.$remove.' reward_id="'.getClientItemId($reward).'"'.$final.'/>';
                            fwrite($hdlout,$lout."\n");
                            $cntout++;
                            
                            $lout  = '    </'.$xmltag.'>';
                        }
                        else
                            $lout .= '/>';
                        break;
                    default:          // passive, chair
                        // <passive area="INTERIOR" location="FLOOR" use_days="1" id="3000001" name_id="360001" category="CARPET" 
                        //          quality="COMMON" talking_distance="5.0" can_dye="true"/>                        
                        $lout .= ' area="'.$area.'" location="'.$loc.'"'.$udays.$limit.
                                 ' id="'.$id.'" name_id="'.getObjectNameId($desc).'" category="'.$categ.'"'.
                                 ' quality="'.$qual.'" talking_distance="'.$dist.'"'.$cdye.'/>';
                        break;
                }
                fwrite($hdlout,$lout."\n");
                $cntout++;
                
                $id    = $desc   = $area   = $loc   = $udays  = $namid = $categ = $qual  = $func = ""; 
                $ware  = $npc    = $dist   = $cdye  = $glev   = $limit = $item  = $delay = $cd   = "";
                $check = $remove = $reward = $final = $owner = $usecnt = $xmltag = "";
            }
          
        }
    }
    // Nachspann ausgeben
    fwrite($hdlout,"</housing_objects>");
    $cntout++;
    
    fclose($hdlout);
    
    logLine("Zeilen Eingabedatei",$domax);
    logLine("Zeilen verarbeitet ",$cntles);
    logLine("Zeilen ausgegeben  ",$cntout);
}
// ----------------------------------------------------------------------------
// Scripts ausgeben
// ----------------------------------------------------------------------------
function generScripts()
{
    logHead("Erzeugen Datei: scripts.xml");
    logLine("<font color=red>keine Generierung</font>","Datei ...lbox_sample.xml manuell kopieren");
    
    flush();
}
// ----------------------------------------------------------------------------
//                             M  A  I  N
// ----------------------------------------------------------------------------

include("includes/inc_getautonameids.php");
include("includes/auto_inc_npc_infos.php");
include("includes/auto_inc_item_infos.php");

$tabParts  = array();
$tabNames  = array();
$tabNamid  = array();
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
        scanClientParts();
        scanClientStrings();
        scanClientNameIds();
        
        generHouseBuildings();
        generHouseNpcs();
        generHouseParts();
        generHouses();
        generHousingObjects();
        generScripts();
        
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