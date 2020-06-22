<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title><?php echo $gameName; ?></title>
    <link rel="icon" type="image/png"
          href="<?php echo $webBaseDir; ?>favicon.ico"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="<?php echo $webBaseDir; ?>templates/plain/plain.css"
          type="text/css" rel="stylesheet"/>
    <?php echo $content['header']; ?>
</head>
<body>
<div id="statArea"><?php ImageGameTitle();
    echo "<br><br>";
    echo $content['stats']; ?></div>
<div id="sideMenu">
    <div id='sideMenuHeader'></div>
    <div id='sideMenuContent'><?php echo $content['sideMenu']; ?></div>
    <div id='sideMenuFooter'></div>
    <br>
    <center><?php VerifyIt(); ?></center>
</div>
<div id="mainContent"><?php echo $content['main']; ?></div><?php echo $content['footer']; ?>
</body>
