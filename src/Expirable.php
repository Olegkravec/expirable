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
        $conn = $this->getConnection();
        $grammar = $conn->getQueryGrammar();
        $builder = new Builder($conn, $grammar, $conn->getPostProcessor());
        if (isset($this->_expirable_ttl)) {
            $builder->expire($this->_expirable_ttl);
        }
        if (isset($this->_expirable_prefix)) {
            $builder->prefix($this->_expirable_prefix);
        }

        return $builder;
    }
}
