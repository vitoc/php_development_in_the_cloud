<?php
    require_once 'config.inc.php';
    Manager::getInstance('UserManager')->checkCurrentUser();
    $searchManager = Manager::getInstance('SearchManager');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Picture Me (Cloud) Color Search</title>
        <link href="style/main.css" rel="stylesheet" type="text/css" />
        <link href="style/jqcp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="javascript/jquery-1.3.2.min.js"></script>
        <script type="text/javascript" src="javascript/jquery.jqcp.min.js"></script>
    </head>
<body>
<div class="console">
<form action="search.php" method="POST">
    <table id = "colorpicker">
       <tr>
          <td colspan="3">
             R:<input type="text" name = "r" id="jqcp_r" size="3" value="98">
             G:<input type="text" name = "g" id="jqcp_g" size="3" value="102">
             B:<input type="text" name = "b" id="jqcp_b" size="3" value="215">
             <input type="text" name = "color_value" id="color_value" class="jqcp_value" size="8" value="#FFFFFF">
          </td>
       </tr>
       <tr>
          <td><div id="color_picker"></div></td>
       </tr>
       <tr>
            <td colspan="3">
             <input type="hidden" id="jqcp_h" size="3" value="0">
             <input type="hidden" id="jqcp_s" size="3" value="0">
             <input type="hidden" id="jqcp_l" size="3" value="0">
            </td>    
       </tr>   
    </table>
    Proximity (1-255): <input type=text name="proximity" id="proximity" size=5 value ='10'></input>
    <input type="submit" name="submit" value="Go" />
</form>
</div>
<div class = "control">
        <div>
<?php
if (isset($_POST['submit'])) {
    echo "<input type=hidden id='searched_color' value='{$_POST['color_value']}'>";
    echo "<input type=hidden id='searched_proximity' value='{$_POST['proximity']}'>";
    $results = $searchManager->searchColors($_POST['proximity'], $_POST['r'], $_POST['g'], $_POST['b']);
    if ($results === false) {
        echo 'Color not found. Try widening the search proximity.';
    } else {
        foreach ($results as $pictureName => $result) {
            list($fileName, $searchTime) = explode('|', $pictureName);
            echo "<br><br><a href = 'index.php?showSearchMatches={$pictureName}'>{$fileName}</a><br>";
            echo "Occurences:".sizeof($result); 
        }
    }
} elseif (isset($_GET['reindex'])) {
    echo $searchManager->updateIndex();
}
?>
    <div class="console">
        <p>[<a href = 'index.php'>Home</a>] [<a href = 'search.php?reindex=yes'>Update Index</a>] [<a href = 'access.php?logout=yes'>Logout</a>]</p>
    </div>        
</div>
<script>
jQuery(document).ready(function(){
    $("#color_picker").jqcp();
    $("#color_value").jqcp_setObject();
    var searchedColor = $("#searched_color").val(); 
    if (searchedColor == null) {
        jQuery.jQCP.setColor("color_value","#5EE9F4");   
    } else {
        jQuery.jQCP.setColor("color_value", searchedColor);
    }
    var searchedProximity = $("#searched_proximity").val();
    if (searchedColor != null) {
        $("#proximity").val(searchedProximity)
    }
    $("#proximity").change(function(){
        if ($(this).val() < 1 || $(this).val() > 255) {
            alert("Search proximity specified is out of range");           
        }
    });
    
});
</script>
</body>
</html>
