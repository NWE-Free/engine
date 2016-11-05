<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title><?php echo $gameName; ?></title>
    <link rel="icon" type="image/png"
          href="<?php echo $webBaseDir; ?>favicon.ico"/>
    <link href="<?php echo $webBaseDir; ?>templates/plain/plain.css"
          type="text/css" rel="stylesheet"/>
    <?php echo $content['header']; ?>
</head>
<body>
<div id="statArea"><?php ImageGameTitle();
    echo "<br><br>";
    echo $content['stats']; ?></div>
<div id="sideMenu">
    Menu: <br>
    <?php echo $content['sideMenu']; ?>
    <br>
    <center><?php VerifyIt(); ?></center>
</div>
<div id="mainContent"><?php echo $content['main']; ?></div><?php echo $content['footer']; ?>
</body>