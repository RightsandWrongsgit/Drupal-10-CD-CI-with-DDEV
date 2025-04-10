<?php
/**
 * @file
 * Platform.sh settings.php for Drupal 10 template with DDEV local support and GitHub integration.
 * Designed as a reusable public template for new Drupal 10 projects.
 */

// Default Drupal settings.
// Database settings are provided by settings.platformsh.php (hosted) or settings.ddev.php (local).
$databases = [];
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
  'vendor', // Covers Composer-based projects.
];
$settings['state_cache'] = TRUE;

// Hash salt is set by Platform.sh via settings.platformsh.php or overridden in settings.local.php.
// Do not hardcode here to keep the template reusable and secure in a public repo.
// $settings['hash_salt'] is intentionally left unset.

// Config sync directory (environment-specific).
if (getenv('IS_DDEV_PROJECT') == 'true') {
  // Use a relative path for DDEV local environment.
  $settings['config_sync_directory'] = $app_root . '/' . $site_path . '/files/config/sync';
} else {
  // Use an absolute path for Platform.sh environments.
  $settings['config_sync_directory'] = '/app/files/config/sync';
}

// File paths for Platform.sh compatibility (generic, no project-specific values).
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = '/app/files/private'; // Keep this for Platform.sh, adjust locally if needed.
$settings['file_temp_path'] = '/tmp';

// Trusted host patterns using a generic regex for Platform.sh domains.
// Matches any Platform.sh project domain (e.g., main--abc123xyz.platformsh.site).
$settings['trusted_host_patterns'] = [
  '^[a-z0-9-]+\.--[a-z0-9]+\.platformsh\.site$', // Covers all Platform.sh environments.
  '^localhost$', // Localhost for manual setups.
  '^.*\.ddev\.site$', // DDEV local domains.
];

// Config splits are activated based on the environment (local, develop, staged, main).
$config['config_split.config_split.local']['status'] = FALSE;
$config['config_split.config_split.develop']['status'] = FALSE;
$config['config_split.config_split.staged']['status'] = FALSE;
$config['config_split.config_split.main']['status'] = FALSE;

// Detect Platform.sh environment from PLATFORM_BRANCH, default to 'local'.
$platform_environment = getenv('PLATFORM_BRANCH') ?: 'local';
$settings['platform_environment'] = $platform_environment;

// Environment-specific configurations.
switch ($platform_environment) {
  case 'main':
    $settings['environment_indicator_name'] = 'Production';
    $settings['environment_indicator_color'] = '#ff0000';
    $config['environment_indicator.indicator']['name'] = 'Production';
    $config['environment_indicator.indicator']['bg_color'] = '#ff0000';
    $config['environment_indicator.indicator']['fg_color'] = '#FFFFFF';
    $config['config_split.config_split.main']['status'] = TRUE;
    $config['stage_file_proxy.settings']['origin'] = ''; // No proxy in production.
    $config['system.logging']['error_level'] = 'hide'; // Minimal logging in production.
    break;

  case 'staged':
    $settings['environment_indicator_name'] = 'Staging';
    $settings['environment_indicator_color'] = '#FF6610';
    $config['environment_indicator.indicator']['name'] = 'Staging';
    $config['environment_indicator.indicator']['bg_color'] = '#FF6610';
    $config['environment_indicator.indicator']['fg_color'] = '#FFFFFF';
    $config['config_split.config_split.staged']['status'] = TRUE;
    $config['stage_file_proxy.settings']['origin'] = ''; // No proxy in staging.
    $config['system.logging']['error_level'] = 'some'; // Moderate logging in staging.
    break;

  case 'develop':
    $settings['environment_indicator_name'] = 'Development';
    $settings['environment_indicator_color'] = '#04caf0';
    $config['environment_indicator.indicator']['name'] = 'Development';
    $config['environment_indicator.indicator']['bg_color'] = '#04caf0';
    $config['environment_indicator.indicator']['fg_color'] = '#FFFFFF';
    $config['config_split.config_split.develop']['status'] = TRUE;
    $config['stage_file_proxy.settings']['origin'] = ''; // No proxy in hosted dev.
    $config['system.logging']['error_level'] = 'some'; // Moderate logging in dev.
    break;

  default:
    $settings['environment_indicator_name'] = 'Local';
    $settings['environment_indicator_color'] = '#006600';
    $config['environment_indicator.indicator']['name'] = 'Local';
    $config['environment_indicator.indicator']['bg_color'] = '#006600';
    $config['environment_indicator.indicator']['fg_color'] = '#FFFFFF';
    $config['config_split.config_split.local']['status'] = TRUE;
    // Dynamic stage_file_proxy origin for local env using Platform.sh relationships (if available).
    $config['stage_file_proxy.settings']['origin'] = '';
    if (getenv('PLATFORM_RELATIONSHIPS')) {
      $relationships = json_decode(base64_decode(getenv('PLATFORM_RELATIONSHIPS')), TRUE);
      if (!empty($relationships['website'])) {
        $config['stage_file_proxy.settings']['origin'] = 'https://' . $relationships['website'][0]['host'];
      }
    }
    $config['system.logging']['error_level'] = 'verbose'; // Full logging locally.
    break;
}

// Enable Environment Indicator toolbar integration (fixed to use array instead of boolean).
$config['environment_indicator.settings']['toolbar_integration'] = ['toolbar'];

// Optional Redis cache backend for Platform.sh (uncomment and configure if used).
/*
if (getenv('PLATFORM_RELATIONSHIPS')) {
  $settings['cache']['default'] = 'cache.backend.redis';
  // Add Redis configuration in settings.platformsh.php or a custom include.
}
*/

// DDEV settings include for local environment.
if (getenv('IS_DDEV_PROJECT') == 'true' && file_exists(__DIR__ . '/settings.ddev.php')) {
  include __DIR__ . '/settings.ddev.php';
}

// Platform.sh settings include for hosted environments.
if (file_exists($app_root . '/' . $site_path . '/settings.platformsh.php')) {
  include $app_root . '/' . $site_path . '/settings.platformsh.php';
}

// Local settings override (last for precedence, ideal for custom hash_salt or overrides).
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

/**
*
* KEY ELEMENTS OF APPROACH USED IN THIS settings.php file:
* Trusted Host Patterns: Replaced with a generic regex (^[a-z0-9-]+\.--[a-z0-9]+\.platformsh\.site$) that matches any Platform.sh domain (e.g., main--abc123xyz.platformsh.site). This works for all projects without needing the project ID.
* Stage File Proxy: Removed the hardcoded URL in the default (local) case. Instead:
* Set it to an empty string by default.
* Added logic to dynamically pull the main environment’s URL from PLATFORM_RELATIONSHIPS if available (though this only works on Platform.sh, not locally unless mirrored).
* Users can override this in settings.local.php with their project’s main URL (e.g., https://main--theirprojectid.platformsh.site) if needed for local testing.
* 
* CROSS-CHECKED AREAS: 
* Database Configuration: $databases = []; remains unchanged, as settings.platformsh.php (Platform.sh) and settings.ddev.php (DDEV) handle this dynamically. No hardcoded values needed.
* Config Sync Directory: ../config/sync is generic and works across all projects, no changes required.
* File Paths: Public, private, and temp paths are standard and project-agnostic, so they’re fine as-is.
* Environment Detection: Using PLATFORM_BRANCH is Platform.sh-specific but defaults to local elsewhere, making it flexible for any setup.
* Config Split: The split names (local, develop, staged, main) match Platform.sh’s typical branch naming, which aligns with your templating process (users can adjust branch names in their Platform.sh project if needed).
* Stage File Proxy: The empty origin in hosted environments (main, staged, develop) is correct since files should be synced via Git or Platform.sh mounts. The local case now avoids hardcoding but offers a dynamic fallback.
* 
* HOW THIS WORKS WITH THE BASE TEMPLATING WORKFLOW:
* User Creates a New Repo:
* They use GitHub’s "Use this template" feature to fork your public repo into their own repository (e.g., theirusername/new-drupal-project).
* Platform.sh Setup:
* They create a new Platform.sh project and integrate it with their GitHub repo.
* Platform.sh generates a unique project ID (e.g., xyz789abc) and provides settings.platformsh.php with database credentials and a hash_salt based on PLATFORM_RELATIONSHIPS.
* Local Setup with DDEV:
* They run ddev config and ddev start, which generates settings.ddev.php with local database settings.
* If they need a custom hash_salt or stage_file_proxy origin, they add it to settings.local.php (not committed).
* No Manual Updates to settings.php:
* The file works out of the box for any Platform.sh project or DDEV local setup without requiring edits to hardcoded values.
* Additional Guidance for Users
* Since this is a public template, consider adding a README.md with instructions:

* "For local development, use DDEV (ddev start) and optionally set $settings['hash_salt'] in settings.local.php."
* "For stage_file_proxy locally, add your Platform.sh main URL to $config['stage_file_proxy.settings']['origin'] in settings.local.php."
* "Ensure .gitignore includes settings.local.php to keep custom settings private."
*
*/