<?php

/**
 * Null cache driver
 *
 * @package     Doctrine
 * @subpackage  Cache
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 7490 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Cache_Null extends Doctrine_Cache_Driver
{
    /**
     * Fetch a cache record from this cache driver instance
     *
     * @param string $id cache id
     * @param boolean $testCacheValidity        if set to false, the cache validity won't be tested
     * @return mixed  Returns either the cached data or false
     */
    protected function _doFetch($id, $testCacheValidity = true)
    {
        return false;
    }

    /**
     * Test if a cache record exists for the passed id
     *
     * @param string $id cache id
     * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    protected function _doContains($id)
    {
        return false;
    }

    /**
     * Save a cache record directly. This method is implemented by the cache
     * drivers and used in Doctrine_Cache_Driver::save()
     *
     * @param string $id cache id
     * @param string $data data to cache
     * @param bool $lifeTime if != false, set a specific lifetime for this cache record (null => infinite lifeTime)
     * @return boolean true if no problem
     */
    protected function _doSave($id, $data, $lifeTime = false)
    {
        return true;
    }

    /**
     * Remove a cache record directly. This method is implemented by the cache
     * drivers and used in Doctrine_Cache_Driver::delete()
     *
     * @param string $id cache id
     * @return boolean true if no problem
     */
    protected function _doDelete($id)
    {
        return true;
    }

    /**
     * Fetch an array of all keys stored in cache
     *
     * @return array Returns the array of cache keys
     */
    protected function _getCacheKeys()
    {
        return array();
    }
}