<?php
    require_once 'config.inc.php';
    Manager::getInstance('UserManager')->checkCurrentUser();
    $pictureManager = Manager::getInstance('PictureManager');
    $picture = $pictureManager->manageRequest($_REQUEST);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Picture Me (Cloud)</title>
        <link href="style/main.css" rel="stylesheet" type="text/css" />
        <?php echo $pictureManager->headScriptHelper(); ?>
    </head>
    <body <?php echo $pictureManager->bodyAttributeHelper(); ?>>
        <div>
        <?php 
            echo !empty($picture['message']) ? $picture['message'] : NULL;
            echo !empty($picture['src']) ? sprintf("<br/><img id=picture src = '%s'/>", $picture['src']) : NULL;
            echo !empty($picture['location']) ? sprintf("<br/> <p>Shot At:</p> %s", $picture['location']) : NULL;
        ?>
        </div>
        <div class="console">
<?php
printf("<p>[<a href = 'index.php?delete=%s'>Delete</a>]</p>", $pictureManager->getCurrentPictureName());
?>
        </div>
        <div class="control"> <br>
            <form enctype="multipart/form-data" action="index.php" method="POST">
                Upload: <input name="picture" type="file" />
                <input name = "submit" type="submit" value="Go" />
            </form>
        </div>
        <div class="control">
            <div class="console">
                <p><form action='picasa.php'>
                    Picasa username: <input type=text name=picasa_username />
                    <input type=submit value=Picasa />
                </form></p>
                <p>[<a href = 'search.php'>Search</a>][<a href = 'access.php?logout=yes'>Logout</a>]</p>
            </div>        
            <ul>
<?php
$pictures = $pictureManager->listPictures();
if ($pictures === false) {
    echo 'No photos stored';
} else { 
    foreach ($pictures as $picture) {
        printf("<li><a href = 'index.php?pictureName=%s' >%s</a></li>",
            $picture['name'], $picture['name']);
    }
}

?>
            </ul>
        </div>
    </body>
</html>
