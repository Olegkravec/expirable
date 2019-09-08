<?php
namespace OlegKravec\Expirable;

use OlegKravec\Expirable\Query\Builder;

trait Expirable
{
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        \Debugbar::info("Connected!");
        $conn = $this->getConnection();
        $grammar = $conn->getQueryGrammar();
        $builder = new Builder($conn, $grammar, $conn->getPostProcessor());
        if (isset($this->rememberFor)) {
            $builder->remember($this->rememberFor);
        }
        if (isset($this->rememberCacheTag)) {
            $builder->cacheTags($this->rememberCacheTag);
        }
        if (isset($this->rememberCachePrefix)) {
            $builder->prefix($this->rememberCachePrefix);
        }
        if (isset($this->rememberCacheDriver)) {
            $builder->cacheDriver($this->rememberCacheDriver);
        }
        return $builder;
    }
}
