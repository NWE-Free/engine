<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title><?php echo $gameName; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $webBaseDir; ?>favicon.ico"/>
    <link href="<?php echo $webBaseDir; ?>templates/public_plain/plain.css" type="text/css" rel="stylesheet"/>
</head>
<body>
<div id="title">
    <form method="post" action="index.php?h=login"><?php echo Translate("Username"); ?>: <input type='text'
                                                                                                name='username'> <?php echo Translate("Password"); ?>
        : <input type='password' name='password'> <input type='submit' value='<?php echo Translate("Login"); ?>'/>
    </form>
</div>
<div id="sideMenu">
    Menu:
    <br>
    <?php echo $content['sideMenu']; ?>
    <br>
    <center><?php VerifyIt(); ?></center>
</div>
<div id="mainContent"><?php echo $content['main']; ?></div>
</body>