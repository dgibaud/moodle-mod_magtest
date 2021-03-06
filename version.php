<?php // $Id: version.php,v 1.2 2012-11-01 18:05:14 vf Exp $
/**
 * Code fragment to define the version of magtest
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author 
 * @version $Id: version.php,v 1.2 2012-11-01 18:05:14 vf Exp $
 * @package magtest
 **/

$module->version  = 2014012802;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2012120304;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)
$module->component = 'mod_magtest';   // Full name of the plugin (used for diagnostics)
$module->maturity = MATURITY_RC;
$module->release = '2.4.0 (Build 2013070902)';


