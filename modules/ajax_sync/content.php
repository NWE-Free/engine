<?php
function MyPhpFunction($name)
{
    return Translate("Hello there %s!", $name);
}

Ajax::RegisterReturnFunction("MyPhpFunction");
?>
    <script>
        function do_my_work() {
            alert(MyPhpFunction("Your name"));
        }
    </script>
<?php

ButtonArea();
Ajax::Button('Click me', 'do_my_work()');
EndButtonArea();
