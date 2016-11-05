<?php
TableHeader(Translate("Welcome to %s !", $gameName), false);
echo Translate("This is a developer version for the <a href='http://www.nw-engine.com/' target='_blank'>New Worlds Engine</a>.");
TableFooter();
echo "<div class='box'>";
RunHook("welcome.php");
echo "</div>";