<?php
require_once('wp-plugin.php');
function uninstall_options($name) {
    unregister_setting("${name}_group", $name);
    WPPlugin::remove_options($name);
}
uninstall_options('confidentCaptcha_options');
?>