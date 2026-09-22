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

    /**
     * Hook executed automatically when system settings are modified.
     * @param int|string $project_id The project ID context, or empty for system context.
     * @return void
     */
    public function redcap_module_system_settings_save($project_id): void
    {
        $trigger = (bool)$this->getSystemSetting('trigger_test_alert');
        if ($trigger) {
            $this->setSystemSetting('trigger_test_alert', false);
            if (method_exists($this, 'sendAlertDigest')) {
                $this->sendAlertDigest();
            }
        }
    }
}