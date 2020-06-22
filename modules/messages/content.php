<?php
if (isset($_POST["cmd"]) && $_POST["cmd"] == "delSelection") {
    if (isset($_POST["del"]) && is_array($_POST["del"])) {
        foreach ($_POST["del"] as $i) {
            $db->Execute("delete from messages where id = ? and inbox_of = ? and not (has_attachement = 'yes' and is_new = 'yes')",
                $i, $userId);
        }
        if (count($_POST["del"]) > 1) {
            ResultMessage("Messages removed.");
        } else {
            ResultMessage("Message removed.");
        }
        header("Location: " . Secure("index.php?p=messages", true));
        return;
    }
} // We are sending messages
else if (isset($_POST["msgTo"])) {
    // A to is mandatory
    if ($_POST["msgTo"] == "") {
        ErrorMessage("You must define a destination");
    } // A content too
    else if ($_POST["msgContent"] == "") {
        ErrorMessage("You must type some message");
    } else {
        try {
            // If no subject is set, then we define one.
            if ($_POST["msgSubject"] == "") {
                $_POST["msgSubject"] = "(" . Translate("No subject") . ")";
            }
            $msgContent = $_POST["msgContent"];

            // Somebody tries to hack the message system?
            if (strpos($msgContent, "--* DO NOT TOUCH *--") !== false || strpos($msgContent, "--* MODULE:") !== false) {
                return;
            }

            // A module has been linked, we shall call the module
            if (isset($_SESSION["messageMetaData"]) && trim($_SESSION["messageMetaData"]) != "") {
                $mods = explode("\n", $_SESSION["messageMetaData"]);
                foreach ($mods as $messageModule) {
                    list ($moduleName, $moduleValues) = explode(",",
                        str_replace("*--", "", trim(substr($messageModule, 11))));
                    $moduleName = strtolower(trim($moduleName));
                    parse_str(trim($moduleValues), $moduleValues);

                    if (file_exists("$baseDir/modules/$moduleName/message_handling.php")) {
                        include "$baseDir/modules/$moduleName/message_handling.php";
                    }
                }
            }

            global $moduleLink, $allOk;
            $moduleLink = "";
            $allOk = true;
            if (isset($_SESSION["messageMetaData"])) {
                $moduleLink = "" . $_SESSION["messageMetaData"];
            }

            RunHook("message_sending.php", array("moduleLink", "allOk"));

            if ($moduleLink != "") {
                $msgContent .= "\n\n--* DO NOT TOUCH *--\n" . $moduleLink;
            }

            $_SESSION["messageMetaData"] = null;
            if ($allOk == true) {
                SendMessage($_POST["msgTo"], $_POST["msgSubject"], $msgContent);
                ResultMessage("Message sent successfully");

                $_POST["msgSubject"] = "";
                $_POST["msgTo"] = "";
                $_POST["msgContent"] = "";
                if (isset($_GET["reply"])) {
                    unset($_GET["reply"]);
                }
            }
        } catch (Exception $ex) {
            ErrorMessage($ex->getMessage(), false);
        }
    }
}

$isMessageNew = false;

// We need to delete a message
if (isset($_GET['delete'])) {
    // Check if it is in the inbox of the user too. Otherwise we could delete a
    // message which is not ours.
    $db->Execute("delete from messages where id = ? and inbox_of = ?", $_GET['delete'], $userId);
    ResultMessage("Message removed.");
} // We are viewing a message, therefore remove the "new" flag
else if (isset($_GET['view'])) {
    $result = $db->Execute("select is_new from messages where inbox_of = ? and id = ?", $userId, $_GET['view']);
    if (!$result->EOF && $result->fields[0] == 'yes') {
        $isMessageNew = true;
        $db->Execute("update messages set is_new = 'no', sent_on=sent_on where inbox_of = ? and id = ?", $userId,
            $_GET['view']);
    }
    $result->Close();
}

echo "<form method='post' name='frmMessageSelection'>";
echo "<input type='hidden' name='cmd' value='delSelection'>";
TableHeader("Received Messages");
// Add a scrollable area
echo "<div id='scrollTop' style='height: " . GetConfigValue("messageListHeight") . "px; overflow: auto;'>";
// Shows all the messages
$result = $db->Execute(
    "select messages.id, users.username, messages.subject, messages.sent_on, messages.is_new, messages.has_attachement
        from messages left join users on messages.from_user = users.id
        where messages.inbox_of = ? order by messages.id desc", $userId);
echo "<table class='plainTable'>";
echo "<tr class='titleLine'>";
echo "<td><input type='checkbox' name='del_checker' id='delSelector' onchange='messagesSelectAll()'></td>";
echo "<td colspan='2'>&nbsp;</td>";
echo "<td>" . Translate("From") . "</td>";
echo "<td>" . Translate("Subject") . "</td>";
echo "<td>" . Translate("Date") . "</td>";
echo "</tr>";
$row = 0;
while (!$result->EOF) {
    if ($row % 2 == 0) {
        echo "<tr class='evenLine'>";
    } else {
        echo "<tr class='oddLine'>";
    }

    if (!($result->fields[4] == 'yes' && $result->fields[5] == 'yes')) {
        echo "<td width='1%'><input type='checkbox' name='del[]' class='delCheckbox' value='{$result->fields[0]}'></td>";
    } else {
        echo "<td width='1%'><img src='{$webBaseDir}modules/messages/old.png'></td>";
    }

    if ($result->fields[4] == 'yes') {
        echo "<td width='1%'><img src='{$webBaseDir}modules/messages/new.png'></td>";
    } else {
        echo "<td width='1%'><img src='{$webBaseDir}modules/messages/old.png'></td>";
    }
    if ($result->fields[5] == 'yes') {
        echo "<td width='1%'><img src='{$webBaseDir}modules/messages/attach.png'></td>";
    } else {
        echo "<td width='1%'><img src='{$webBaseDir}modules/messages/old.png'></td>";
    }

    echo "<td><a href='index.php?p=messages&view={$result->fields[0]}'>{$result->fields[1]}</a></td>";
    echo "<td><a href='index.php?p=messages&view={$result->fields[0]}'>" . htmlentities($result->fields[2]) . "</a></td>";
    echo "<td><a href='index.php?p=messages&view={$result->fields[0]}'>" . FormatDate($result->fields[3]) . "</a></td>";

    echo "</tr>";
    $row++;
    $result->MoveNext();
}
$result->Close();
echo "</table>";
echo "</div>";
TableFooter();
echo "</form>";

ButtonArea();
LinkButton("Delete Selected", "#",
    "if(confirm(unescape('" . rawurlencode(Translate("Are you sure you want to delete the selected messages?")) . "'))){ document.forms['frmMessageSelection'].submit();} return false;");
EndButtonArea();

Ajax::IncludeLib();
?>
    <script>
        function messagesSelectAll() {
            $(".delCheckbox").prop("checked", $("#delSelector").first().prop("checked"));
        }
    </script>
<?php

echo "<br>";

global $moduleLink;
$moduleLink = "";
$moduleName = "";
$moduleValues = "";

// Viewing the message
if (isset($_GET['view'])) {
    $result = $db->Execute(
        "select users.username, messages.subject, messages.sent_on,
            messages.sent_to, messages.message
            from messages left join users on messages.from_user = users.id
            where messages.inbox_of = ? and messages.id = ?", $userId, $_GET['view']);

    TableHeader("Read");
    echo "<table class='plainTable'>";
    echo "<tr><td width='1%' class='titleLine'>" . str_replace(" ", "&nbsp;",
            Translate("From")) . ":</td><td>" . htmlentities($result->fields[0]) . "</td></tr>";
    echo "<tr><td width='1%' class='titleLine'>" . str_replace(" ", "&nbsp;",
            Translate("To")) . ":</td><td>" . htmlentities($result->fields[3]) . "</td></tr>";
    echo "<tr><td width='1%' class='titleLine'>" . str_replace(" ", "&nbsp;",
            Translate("Subject")) . ":</td><td>" . htmlentities($result->fields[1]) . "</td></tr>";
    echo "<tr><td colspan='2' class='titleLine'>" . str_replace(" ", "&nbsp;", Translate("Message")) . ":</td></tr>";
    $data = $result->fields[4];
    // Extract attached module data
    if (strpos($data, "--* DO NOT TOUCH *--") !== false) {
        $moduleLink = trim(substr($data, strpos($data, "--* DO NOT TOUCH *--") + 20));
        $data = trim(substr($data, 0, strpos($data, "--* DO NOT TOUCH *--")));
    }
    echo "<tr><td colspan='2'>" . PrettyMessage($data) . "</td></tr>";

    // Call the module message_view code if n
    if ($moduleLink != "") {
        $mods = explode("\n", $moduleLink);
        foreach ($mods as $messageModule) {
            list ($moduleName, $moduleValues) = explode(",", str_replace("*--", "", trim(substr($messageModule, 11))));
            $moduleName = strtolower($moduleName);
            parse_str(trim($moduleValues), $moduleValues);
            if ($moduleName != "" && file_exists("$baseDir/modules/$moduleName/message_view.php")) {
                include "$baseDir/modules/$moduleName/message_view.php";
            }
        }
    }

    echo "</table>";
    TableFooter();

    $result->Close();

    ButtonArea();
    LinkButton("Delete", "index.php?p=messages&delete=" . $_GET['view'],
        "return confirm(unescape('" . rawurlencode(Translate("Are you sure you want to delete this message?")) . "'));");
    LinkButton("Reply", "index.php?p=messages&reply=" . $_GET['view']);
    LinkButton("Compose", "index.php?p=messages");
    EndButtonArea();
} // Either composing or replying to a message
else {
    if (!isset($_POST["msgTo"])) {
        if (isset($_GET["sendto"])) {
            $result = $db->Execute("select username from users where id = ?", $_GET["sendto"]);
            if (!$result->EOF) {
                $_POST["msgTo"] = $result->fields[0];
            }
            $result->Close();
        } else {
            $_POST["msgTo"] = "";
        }
    }
    if (!isset($_POST["msgSubject"])) {
        if (isset($_GET["msgSubject"])) {
            $_POST["msgSubject"] = $_GET["msgSubject"];
        } else {
            $_POST["msgSubject"] = "";
        }
    }
    if (!isset($_POST["msgContent"])) {
        $_POST["msgContent"] = "";
    }

    // We reply therefore let's load back the original message and add the >
    // sign on front
    if (isset($_GET['reply'])) {
        $result = $db->Execute(
            "select users.username, messages.subject, messages.sent_on,
                messages.sent_to, messages.message
                from messages left join users on messages.from_user = users.id
                where messages.inbox_of = ? and messages.id = ?", $userId, $_GET['reply']);

        $_POST["msgTo"] = str_replace($username, $result->fields[0], $result->fields[3]);
        $reply = Translate("Re:") . " ";
        if (strncmp($result->fields[1], $reply, strlen($reply)) == 0) {
            $_POST["msgSubject"] = $result->fields[1];
        } else {
            $_POST["msgSubject"] = $reply . $result->fields[1];
        }

        // Checks if there is a module tag to the message such that we need
        // to run a module for this message as well.
        $data = $result->fields[4];
        $moduleLink = "";
        if (strpos($data, "--* DO NOT TOUCH *--") !== false) {
            $moduleLink = trim(substr($data, strpos($data, "--* DO NOT TOUCH *--") + 20));
            $data = trim(substr($data, 0, strpos($data, "--* DO NOT TOUCH *--")));
        }

        $_POST["msgContent"] = "\n\n\n> " . str_replace("\n", "\n> ", wordwrap(str_replace(array(
                "[li]",
                "[ol]\r",
                "[/ol]\r",
                "[ul]\r",
                "[/ul]\r",
                "[ol]\n",
                "[/ol]\n",
                "[ul]\n",
                "[/ul]\n",
                "[ul]",
                "[ol]",
                "[/li]",
                "[/ul]",
                "[/ol]",
                "\r"
            ), array("", "", "", "", "", "", "", "", "", "", "", "", "", "", ""), $data), 40, "\n"));

        $result->Close();
    }

    TableHeader("Compose");
    echo "<form method='post' name='composeFrm' action='index.php?p=messages'>";

    echo "<table class='plainTable'>";
    if (GetConfigValue("allowsMultipleDestinations") == "true") {
        echo "<tr><td width='1%' class='titleLine'>" . str_replace(" ", "&nbsp;",
                Translate("To")) . ":</td><td><input type='text' name='msgTo' value='" . htmlentities($_POST['msgTo']) . "'></td></tr>";
    } else {
        echo "<tr><td width='1%' class='titleLine'>" . str_replace(" ", "&nbsp;",
                Translate("To")) . ":</td><td>" . SmartSelection("select id,username from users where id <> 1", "msgTo",
                FindUser(abs((int)$_POST["msgTo"]))) . "</td></tr>";
    }
    echo "<tr><td width='1%' class='titleLine'>" . str_replace(" ", "&nbsp;",
            Translate("Subject")) . ":</td><td><input type='text' name='msgSubject' value='" . htmlentities($_POST['msgSubject']) . "'></td></tr>";
    echo "<tr><td colspan='2' class='titleLine'>" . str_replace(" ", "&nbsp;", Translate("Message")) . ":</td></tr>";
    echo "<tr><td colspan='2'>" . RichEditor("msgContent", $_POST['msgContent']) . "</td></tr>";

    // Some modules have already some meta data inside. Let's handle them
    if ($moduleLink != "") {
        $newModuleLink = "";
        $mods = explode("\n", $moduleLink);
        foreach ($mods as $messageModule) {
            // Allows modules to drop their attached data while replying
            $keepMessageData = true;

            list ($moduleName, $moduleValues) = explode(",", str_replace("*--", "", trim(substr($messageModule, 11))));
            $moduleName = strtolower($moduleName);
            parse_str(trim($moduleValues), $moduleValues);

            if ($moduleName != "" && file_exists("$baseDir/modules/$moduleName/message_form.php")) {
                include "$baseDir/modules/$moduleName/message_form.php";
            }

            // Message data shall be kept
            if ($keepMessageData) {
                if ($newModuleLink != "") {
                    $newModuleLink .= "\n";
                }
                $newModuleLink .= $messageModule;
            }
        }
        if ($newModuleLink == "") {
            $_SESSION["messageMetaData"] = null;
        } else {
            $_SESSION["messageMetaData"] = $newModuleLink;
        }
    } else {
        $_SESSION["messageMetaData"] = null;
    }

    // Let's allow to run additional modules
    RunHook("message_compose.php", "moduleLink");
    echo "</table>";

    echo "</form>";
    TableFooter();

    ButtonArea();
    SubmitButton("Send", "composeFrm");
    if (isset($_GET['reply'])) {
        LinkButton("Cancel", "index.php?p=messages");
    }
    EndButtonArea();
}
