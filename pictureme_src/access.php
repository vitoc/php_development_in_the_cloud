<?php
    require_once 'config.inc.php';
    $accessMessage = Manager::getInstance('UserManager')->manageRequest($_REQUEST);
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
        <?php echo !empty($accessMessage) ? $accessMessage : NULL; ?>
        </div>
        <div>
            <form method="post" action="access.php">
                <p>Log in with your OpenID URL: </p>
                <p>
                <input type="text" name="openIdUrl" value="" size=50 /> <br/>
                </p>
                <input type="submit" value="Login" />
            </form>
        </div>
        <div>
        [<a href = 'signup.php'>Sign Up</a>]
        </div>
    </body>
</html>
