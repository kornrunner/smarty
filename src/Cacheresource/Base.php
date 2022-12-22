<?php

namespace Smarty\Cacheresource;

use Smarty\Smarty;
use Smarty_Internal_Template;
use Smarty_Template_Cached;

/**
 * Smarty Internal Plugin
 *
 * @package    Smarty
 * @subpackage Cacher
 */

/**
 * Cache Handler API
 *
 * @package    Smarty
 * @subpackage Cacher
 * @author     Rodney Rehm
 */
abstract class Base
{
    /**
     * resource types provided by the core
     *
     * @var array
     */
    protected static $sysplugins = array('file' => 'File.php',);

    /**
     * populate Cached Object with meta data from Resource
     *
     * @param Smarty_Template_Cached  $cached    cached object
     * @param Smarty_Internal_Template $_template template object
     *
     * @return void
     */
    abstract public function populate(Smarty_Template_Cached $cached, Smarty_Internal_Template $_template);

    /**
     * populate Cached Object with timestamp and exists from Resource
     *
     * @param Smarty_Template_Cached $cached
     *
     * @return void
     */
    abstract public function populateTimestamp(Smarty_Template_Cached $cached);

    /**
     * Read the cached template and process header
     *
     * @param Smarty_Internal_Template $_template template object
     * @param Smarty_Template_Cached   $cached    cached object
     * @param boolean                  $update    flag if called because cache update
     *
     * @return boolean true or false if the cached content does not exist
     */
    abstract public function process(
        Smarty_Internal_Template $_template,
        Smarty_Template_Cached $cached = null,
        $update = false
    );

    /**
     * Write the rendered template output to cache
     *
     * @param Smarty_Internal_Template $_template template object
     * @param string                   $content   content to cache
     *
     * @return boolean success
     */
    abstract public function storeCachedContent(Smarty_Internal_Template $_template, $content);

    /**
     * Read cached template from cache
     *
     * @param Smarty_Internal_Template $_template template object
     *
     * @return string  content
     */
    abstract public function retrieveCachedContent(Smarty_Internal_Template $_template);

    /**
     * Return cached content
     *
     * @param Smarty_Internal_Template $_template template object
     *
     * @return null|string
     */
    public function getCachedContent(Smarty_Internal_Template $_template)
    {
        if ($_template->cached->handler->process($_template)) {
            ob_start();
            $unifunc = $_template->cached->unifunc;
            $unifunc($_template);
            return ob_get_clean();
        }
        return null;
    }

    /**
     * Empty cache
     *
     * @param Smarty  $smarty   Smarty object
     * @param integer $exp_time expiration time (number of seconds, not timestamp)
     *
     * @return integer number of cache files deleted
     */
    abstract public function clearAll(Smarty $smarty, $exp_time = null);

    /**
     * Empty cache for a specific template
     *
     * @param Smarty  $smarty        Smarty object
     * @param string  $resource_name template name
     * @param string  $cache_id      cache id
     * @param string  $compile_id    compile id
     * @param integer $exp_time      expiration time (number of seconds, not timestamp)
     *
     * @return integer number of cache files deleted
     */
    abstract public function clear(Smarty $smarty, $resource_name, $cache_id, $compile_id, $exp_time);

    /**
     * @param Smarty                 $smarty
     * @param Smarty_Template_Cached $cached
     *
     * @return bool|null
     */
    public function locked(Smarty $smarty, Smarty_Template_Cached $cached)
    {
        // theoretically locking_timeout should be checked against time_limit (max_execution_time)
        $start = microtime(true);
        $hadLock = null;
        while ($this->hasLock($smarty, $cached)) {
            $hadLock = true;
            if (microtime(true) - $start > $smarty->locking_timeout) {
                // abort waiting for lock release
                return false;
            }
            sleep(1);
        }
        return $hadLock;
    }

    /**
     * Check is cache is locked for this template
     *
     * @param Smarty                 $smarty
     * @param Smarty_Template_Cached $cached
     *
     * @return bool
     */
    public function hasLock(Smarty $smarty, Smarty_Template_Cached $cached)
    {
        // check if lock exists
        return false;
    }

    /**
     * Lock cache for this template
     *
     * @param Smarty                 $smarty
     * @param Smarty_Template_Cached $cached
     *
     * @return bool
     */
    public function acquireLock(Smarty $smarty, Smarty_Template_Cached $cached)
    {
        // create lock
        return true;
    }

    /**
     * Unlock cache for this template
     *
     * @param Smarty                 $smarty
     * @param Smarty_Template_Cached $cached
     *
     * @return bool
     */
    public function releaseLock(Smarty $smarty, Smarty_Template_Cached $cached)
    {
        // release lock
        return true;
    }

    /**
     * Load Cache Resource Handler
     *
     * @param Smarty $smarty Smarty object
     * @param string $type   name of the cache resource
     *
     * @throws SmartyException
     * @return Base Cache Resource Handler
     */
    public static function load(Smarty $smarty, $type = null)
    {
        if (!isset($type)) {
            $type = $smarty->caching_type;
        }
        // try smarty's cache
        if (isset($smarty->_cacheresource_handlers[ $type ])) {
            return $smarty->_cacheresource_handlers[ $type ];
        }
        // try registered resource
        if (isset($smarty->registered_cache_resources[ $type ])) {
            // do not cache these instances as they may vary from instance to instance
            return $smarty->_cacheresource_handlers[ $type ] = $smarty->registered_cache_resources[ $type ];
        }
        // try sysplugins dir
        if (isset(self::$sysplugins[ $type ])) {
            $cache_resource_class = 'Smarty_Internal_CacheResource_' . \smarty_ucfirst_ascii($type);
            return $smarty->_cacheresource_handlers[ $type ] = new $cache_resource_class();
        }
        // try plugins dir
        $cache_resource_class = 'Smarty_CacheResource_' . \smarty_ucfirst_ascii($type);
	    return $smarty->_cacheresource_handlers[ $type ] = new $cache_resource_class();
    }
}