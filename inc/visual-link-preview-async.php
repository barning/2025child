<?php
/**
 * Background fetch helpers for Visual Link Preview.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_VLP_CACHE_TTL          = DAY_IN_SECONDS;
const CHILD_VLP_NEGATIVE_CACHE_TTL = HOUR_IN_SECONDS;
const CHILD_VLP_STALE_CACHE_TTL    = WEEK_IN_SECONDS;
const CHILD_VLP_MAX_BODY_BYTES     = 250000;
const CHILD_VLP_FETCH_LOCK_TTL     = 30;
const CHILD_VLP_REFRESH_HOOK       = 'child_vlp_refresh_metadata';

$child_vlp_module_dir = __DIR__ . '/visual-link-preview/';

require_once $child_vlp_module_dir . 'url-security.php';
require_once $child_vlp_module_dir . 'metadata-rendering.php';
require_once $child_vlp_module_dir . 'cache-scheduling.php';
