<?php

namespace OlegKravec\Expirable\Query;

use Illuminate\Support\Facades\Redis;

class Builder extends \Illuminate\Database\Query\Builder
{
    protected $cache_enabled = true;
    protected $cache_reset_ttl = false;
    protected $cache_expire_in = 300;
    protected $cache_prefix = "Models";
    protected $cache_query_key = "";

    public function get($columns = ['*']){
        if($this->cache_enabled){
            {
                $this->init();
            }
            $model = Redis::get($this->cache_query_key);
            if(empty($model)){
                $model = parent::get($columns);
                Redis::set($this->cache_query_key, json_encode($model));
                Redis::expire($this->cache_query_key, $this->cache_expire_in);
                return $model;
            }else{
                if($this->cache_reset_ttl)
                    Redis::expire($this->cache_query_key, $this->cache_expire_in);
                return collect(json_decode($model));
            }
        }else
            return parent::get($columns);
    }

    public function first($columns = ['*']){
        if($this->cache_enabled){
            {
                $this->init();
            }
            $model = Redis::get($this->cache_query_key);
            if(empty($model)){
                $model = parent::take(1)->get($columns);
                Redis::set($this->cache_query_key, json_encode($model));
                Redis::expire($this->cache_query_key, $this->cache_expire_in);
                return $model;
            }else{
                if($this->cache_reset_ttl)
                    Redis::expire($this->cache_query_key, $this->cache_expire_in);
                return collect(json_decode($model))->first();
            }
        }else
            return parent::get($columns);
    }

    public function prefix(string $cachePrefix){
        $this->cache_prefix = $cachePrefix;
    }

    public function resetExpire(int $seconds){
        $this->cache_reset_ttl = true;
        $this->cache_expire_in = $seconds;
    }
    public function expire(int $seconds){
        $this->cache_expire_in = $seconds;
    }

    public function disableCache(){
        $this->cache_enabled = false;
    }

    public function enableCache(){
        $this->cache_enabled = true;
    }


    private function init(){
        $this->cache_query_key = "expirable:".$this->from.":".
            $this->cache_prefix.":".
            json_encode($this->wheres).":".
            json_encode($this->orders).":".
            json_encode($this->bindings);
    }
}
