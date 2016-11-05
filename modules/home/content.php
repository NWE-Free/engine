<?php
TableHeader(Translate("Welcome to %s", $gameName), false);
echo Translate("Hi %s,<br>Please take some time to visit the area!", $username);
TableFooter();

echo "<div class='box'>";
RunHook("home.php");
echo "</div>";

RunHook("home_special.php");