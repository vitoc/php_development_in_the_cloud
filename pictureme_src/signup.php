<?php
    require_once 'config.inc.php';
    if (!empty($_REQUEST['create'])) {
        try {
            $payment = new Payment($_REQUEST);
            $paymentResponse = $payment->processSales();
        } catch (Exception $e) {
            $paymentResponse['success'] = FALSE;
            $paymentResponse['Message'] = $e->getMessage();
        }
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
        <?php echo !empty($paymentResponse['Message']) ? $paymentResponse['Message'] : NULL; ?>
        </div>
        <div id="signup">
            <?php if (!$paymentResponse['success'] || empty($paymentResponse)) { ?>
            <form method="post" action="signup.php">
                <table>
                    <tr><td>OpenID URL</td><td><input type="text" name="openID" value="http://sample-openid-provider.net" size=50 /> </td></tr>
                    <tr><td>Card number</td><td><input type="text" name="cardNumber" value="38520000023237" size=24 /></td></tr>
                    <tr><td>Name on card</td><td><input type="text" name="nameOnCard" value="Sean Wayne" size=24 /></td></tr>
                    <tr><td>Expires on</td><td><input type="text" name="expiresOn" value="1012" size=4 /> (MMYY)</td></tr>
                    <tr><td>Security Code</td><td><input type="text" name="csc" value="268" size=4 /> (e.g. CVV2)</td></tr>
                </table>
                <input type="submit" name="create" value="Create" />
            </form>
            <?php } ?>
        </div>
        <div>
        [<a href = 'access.php'>Home</a>]
        </div>
    </body>
</html>

