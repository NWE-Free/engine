<?php
if (IsAdmin()) {
    throw new Exception("This is a wished error to try the Bug Reporting Tool");
}
