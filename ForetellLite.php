<?php
namespace ForetellLite;

require_once __DIR__ . '/src/SettingsTrait.php';
require_once __DIR__ . '/src/LogWindowTrait.php';
require_once __DIR__ . '/src/AnalyticsTrait.php';
require_once __DIR__ . '/src/TablesTrait.php';
require_once __DIR__ . '/src/SparklineTrait.php';
require_once __DIR__ . '/src/AlertsTrait.php';

use ExternalModules\AbstractExternalModule;

class ForetellLite extends AbstractExternalModule
{
    use SettingsTrait;
    use LogWindowTrait;
    use AnalyticsTrait;
    use TablesTrait;
    use SparklineTrait;
    use AlertsTrait;
}