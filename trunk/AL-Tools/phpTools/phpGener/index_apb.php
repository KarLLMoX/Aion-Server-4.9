<html>
<head>
  <title>
    DecomposableItems - Decomposables zu einem Item scannen
  </title>
  <link rel='stylesheet' type='text/css' href='../includes/aioneutools.css'>
</head>

<body style="background-color:#000055;color:silver;padding:0px;">
<center>
<div id="body" style='width:800px;padding:0px;'>
  <div width="100%"><img src="../includes/aioneulogo.png" width="100%"></div>
  <div class="aktion">Hilfsfunktion - DecomposableItem-Definitionen generieren</div>
  <div class="hinweis" id="hinw">
    Bitte die notwendigen Informationen eintragen.
    <br><br>
    Bitte die gew&uuml;nschte <font color="orange">ItemId</font> eingeben!<br><br> 
    Das Script scannt bei <font color='orange'>aionpowerbook.com</font> die entsprechende Item-Seite und zeigt
    das Ergebnis an. Gleichzeitig wird eine itemid.xml-Datei erstellt.
  </div>
  <div width=100%>
<h1 style="color:orange">Bitte notwendige Informationen einf&uuml;gen</h1>
<form name="edit" method="GET" action="scanitem_apb.php">
 <br><br><br>
 <span style="color:cyan;font-size:16px;padding-right:15px;padding-left:15px;">ItemId</span>
 <input type="text" name="itmid" value="" style="width:70px;">
 <br><br><br><br>
 <input style="width:120px" name="submit" type="submit">
 <input style="width:120px" name="reset" type="reset">
</form>

<?PHP
include("../includes/inc_globals.php");

putIndexFoot();
?>

</body>
</html>