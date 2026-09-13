<?php

use Flarum\Testing\integration\Setup\SetupScript;

require __DIR__.'/../../vendor/autoload.php';

$setup = new SetupScript();
$setup->run();

// The extension's migration updates the web server configuration.
// Flarum's test installer does not provide this file.
file_put_contents($setup->tmpDir().'/public/.htaccess', "# Flarum test site\n");
