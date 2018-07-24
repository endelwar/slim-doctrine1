<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.org>.
 */

/**
 * Redis cache driver
 *
 * @package     Doctrine
 * @subpackage  Cache
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Cache_Redis extends Doctrine_Cache_Driver
{
    /**
     * @var \Predis\Client $_redis Predis object
     */
    protected $_redis;

    /**
     * Doctrine_Cache_Redis constructor.
     *
     * @param array $options associative array of cache driver options
     * @throws Doctrine_Cache_Exception
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        if (isset($options['redis'])) {
            if ($options['redis'] instanceof \Predis\Client) {
                $this->_redis = $options['redis'];
            } else {
                throw new Doctrine_Cache_Exception('The "redis" parameter must be an instance of Predis\Client (https://github.com/nrk/predis)');
            }
        } else {
            $this->_redis = \Predis\Client::create($this->getOption('server'));
        }
    }

    /**
     * return list of cache keys
     *
     * @return array
     */
    protected function _getCacheKeys()
    {
        $iterationIndex = 0;
        $resultsArray = array();
        $resultsArray[] = array();
        while ($results = $this->_redis->scan($iterationIndex, array('MATCH' => $this->getOption('prefix') . '*'))) {
            $iterationIndex = $results[0];
            $resultsArray[] = $results[1];
            if (0 === (int)$iterationIndex) {
                break;
            }
        }

        if (PHP_VERSION_ID >= 506000) {
            return array_merge(...$resultsArray);
        }
        return call_user_func_array('array_merge', $resultsArray);
    }

    /**
     * return count of cache keys
     */
    public function count()
    {
        return count($this->_getCacheKeys());
    }

    /**
     * Test if a cache record exists for the passed id
     *
     * @param string $id cache id
     * @return mixed  Returns either the cached data or false
     */
    protected function _doFetch($id, $testCacheValidity = true)
    {
        $value = $this->_redis->get($id);

        return null === $value ? false : $value;
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param string $id cache id
     * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    protected function _doContains($id)
    {
        return $this->_redis->exists($id) ? $this->_redis->get($id . ':timestamp') : false;
    }

    /**
     * Save a cache record directly. This method is implemented by the cache
     * drivers and used in Doctrine_Cache_Driver::save()
     *
     * @param string $id cache id
     * @param string $data data to cache
     * @param bool|int $lifeTime if != false, set a specific lifetime for this cache record (null => infinite lifeTime)
     * @throws Exception
     * @return boolean true if no problem
     */
    protected function _doSave($id, $data, $lifeTime = false)
    {
        $pipe = $this->_redis->pipeline();
        $pipe->mset(array($id => $data, $id . ':timestamp' => time()));
        if ($lifeTime) {
            $pipe->expire($id, $lifeTime);
            $pipe->expire($id . ':timestamp', $lifeTime);
        }
        $reply = $pipe->execute();

        return $reply[0] and (!$lifeTime or ($reply[1] and $reply[2]));
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
        return $this->_redis->del($id, $id . ':timestamp');
    }
}
