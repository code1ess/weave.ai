<?php

$root = dirname(__DIR__);
if (! is_file($root.'/.env')) {
    if (! copy($root.'/.env.example', $root.'/.env')) {
        fwrite(STDERR, "Unable to create .env\n");
        exit(1);
    }
    echo "Created .env from template.\n";
} else {
    echo "Existing .env preserved.\n";
}
echo "Configure MySQL, Redis and R2 in .env. Generate APP_KEY only for a new installation.\n";
echo "Run php artisan migrate explicitly after checking the target database.\n";
