<?php

/**
 * Memcached cache driver
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
class Doctrine_Cache_Memcached extends Doctrine_Cache_Driver
{
    /**
     * @var Memcached $_memcache     memcache object
     */
    protected $_memcached;

    /**
     * constructor
     *
     * @param array $options        associative array of cache driver options
     * @throws Doctrine_Cache_Exception
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('memcached')) {
            throw new Doctrine_Cache_Exception('In order to use Memcached driver, the memcached extension must be loaded.');
        }
        parent::__construct($options);

        if (isset($options['servers'])) {
            $value = $options['servers'];
            if (isset($value['host'])) {
                // in this case, $value seems to be a simple associative array (one server only)
                $value = array(0 => $value); // let's transform it into a classical array of associative arrays
            }
            $this->setOption('servers', $value);
        }
        if (!array_key_exists('persistent', $options)) {
            $this->_memcached = new Memcached();
        } else {
            if (!array_key_exists('persistent_id', $options)) {
                throw new Doctrine_Cache_Exception('In order to use a persistent connection, a "persistent_id" option must be set.');
            }
            $this->_memcached = new Memcached($options['persistent_id']);
        }

        $this->_memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

        if (isset($this->_options['compression']) && (true == $this->_options['compression'])) {
            $this->_memcached->setOption(Memcached::OPT_COMPRESSION, true);
        } else {
            $this->_memcached->setOption(Memcached::OPT_COMPRESSION, false);
        }

        if (isset($this->_options['binaryprotocol']) && (true == $this->_options['binaryprotocol'])) {
            $this->_memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        } else {
            $this->_memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, false);
        }

        foreach ($this->_options['servers'] as $server) {
            if (!array_key_exists('port', $server)) {
                $server['port'] = 11211;
            }
            if (!array_key_exists('weight', $server)) {
                $server['weight'] = 0;
            }
            $this->_memcached->addServer($server['host'], $server['port'], $server['weight']);
        }
    }

    /**
     * Test if a cache record exists for the passed id
     *
     * @param string $id cache id
     * @param bool $testCacheValidity
     * @return mixed  Returns either the cached data or false
     */
    protected function _doFetch($id, $testCacheValidity = true)
    {
        if (false === $this->_memcached->getOption(Memcached::OPT_BINARY_PROTOCOL)) {
            $id = str_replace(' ', '_', $id);
        }
        return $this->_memcached->get($id);
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param string $id cache id
     * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    protected function _doContains($id)
    {
        if (false === $this->_memcached->getOption(Memcached::OPT_BINARY_PROTOCOL)) {
            $id = str_replace(' ', '_', $id);
        }
        return (bool) $this->_memcached->get($id);
    }

    /**
     * Save a cache record directly. This method is implemented by the cache
     * drivers and used in Doctrine_Cache_Driver::save()
     *
     * @param string $id        cache id
     * @param string $data      data to cache
     * @param int $lifeTime     if != 0, set a specific lifetime for this cache record (null => infinite lifeTime)
     * @return boolean true if no problem
     */
    protected function _doSave($id, $data, $lifeTime = 0)
    {
        if (false === $this->_memcached->getOption(Memcached::OPT_BINARY_PROTOCOL)) {
            $id = str_replace(' ', '_', $id);
        }

        return $this->_memcached->set($id, $data, $lifeTime);
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
        if (false === $this->_memcached->getOption(Memcached::OPT_BINARY_PROTOCOL)) {
            $id = str_replace(' ', '_', $id);
        }
        return $this->_memcached->delete($id);
    }

    /**
     * Fetch an array of all keys stored in cache
     *
     * @return array Returns the array of cache keys
     */
    protected function _getCacheKeys()
    {
        return $this->_memcached->getAllKeys();
    }
}