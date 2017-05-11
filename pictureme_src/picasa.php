<?php
    require_once 'config.inc.php';
    try {
        Manager::getInstance('UserManager')->checkCurrentUser();
        if (isset($_REQUEST['oauth_token'])) {
            Manager::getInstance('UserManager')->userAuthorized($_REQUEST['oauth_token']);
        }
        $picasaPicture = Manager::getInstance('PictureManager')->getLatestPicasaPicture($_REQUEST['picasa_username']);
    } catch (Exception $e) {
        $picasaMessage = $e->getMessage();
    }
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Picture Me (Cloud)</title>
        <link href="style/main.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <div>
            <?php
            echo !empty($picasaMessage) ? $picasaMessage : "<img src='{$picasaPicture->feed->entry[0]->content->src}'> </img>";
            ?>
        </div>
        <div class="control">
            <div class="console">
                <p>[<a href = 'index.php'>Home</a>][<a href = 'search.php'>Search</a>][<a href = 'access.php?logout=yes'>Logout</a>]</p>
            </div>        
        </div>
    </body>
</html>
