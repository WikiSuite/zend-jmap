<?php

namespace Wikisuite\Jmap\Core;

class Cache
{
    /**
     * $store['Mailbox']['state', 'cacheKey']
     **/
    private $store = [];
    private $debug = false;
    private $enabled;

    public function construct($enabled = false)
    {
        $this->enabled = $enabled;
    }
    public function get($object, $cacheKey)
    {
        if ($this->enabled) {
            if (isset($this->store[$object][$cacheKey])) {
                if ($this->debug) {
                    echo "CACHE HIT on $object, $cacheKey\n";
                }
                //var_dump($this->store);
                return $this->store[$object][$cacheKey];
            }
            if ($this->debug) {
                echo "CACHE MISS on $object, $cacheKey\n";
            }
            //var_dump($this->store);
        }
    }
    public function set($object, $state, $cacheKey, $data)
    {
        if ($this->enabled) {
            if (!$state) {
                throw new Exception("parameter state cannot be empty");
            }
            if (!$object) {
                throw new Exception("parameter object cannot be empty");
            }
            if (!$cacheKey) {
                throw new Exception("parameter cacheKey cannot be empty");
            }
            $this->garbageCollectCache($object, $state);
            //The state should not exist, or be equal after the call to garbageCollectCache
            $this->store[$object]['state'] = $state;
            $this->store[$object][$cacheKey] = $data;
            return $this->store[$object][$cacheKey];
        }
        return $data;
    }
    public function garbageCollectCache($object, $newState)
    {
        if ($this->enabled) {
            if (!isset($this->store[$object])) {
                $this->store[$object] = [];
            } elseif (!isset($this->store[$object]['state'])) {
                $this->store[$object] = [];
            } elseif ($this->store[$object]['state'] !== $newState) {
                if ($this->debug) {
                    echo "CACHE Flushing obsolete state for $object\n";
                }
                $this->store[$object] = [];
                //var_dump($this->store);
            }
        }
    }
}
