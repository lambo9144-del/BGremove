<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/bootstrap.php';
cleanup_expired_files();
echo "RemoveBG temporary-file cleanup completed.\n";
