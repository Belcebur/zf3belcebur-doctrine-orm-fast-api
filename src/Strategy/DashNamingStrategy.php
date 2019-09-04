<?php
/**
 *
 * User: belcebur
 * Date: 09/05/2018
 * Time: 10:11
 */

namespace ZF3Belcebur\DoctrineORMFastApi\Strategy;

use Zend\Filter\FilterChain;
use Zend\Hydrator\NamingStrategy\NamingStrategyInterface;

class DashNamingStrategy implements NamingStrategyInterface
{
    /**
     * @var FilterChain|null
     */
    protected static $camelCaseToUnderscoreFilter;

    /**
     * @var FilterChain|null
     */
    protected static $underscoreToStudlyCaseFilter;

    /**
     * Remove underscores and capitalize letters
     *
     * @param string $name
     *
     * @return string
     */
    public function hydrate($name): string
    {
        return $this->getUnderscoreToStudlyCaseFilter()->filter($name);
    }

    /**
     * @return FilterChain
     */
    protected function getUnderscoreToStudlyCaseFilter(): FilterChain
    {
        if (static::$underscoreToStudlyCaseFilter instanceof FilterChain) {
            return static::$underscoreToStudlyCaseFilter;
        }

        $filter = new FilterChain();

        $filter->attachByName('WordUnderscoreToStudlyCase');

        return static::$underscoreToStudlyCaseFilter = $filter;
    }

    /**
     * Remove capitalized letters and prepend underscores.
     *
     * @param string $name
     *
     * @return string
     */
    public function extract($name): string
    {
        return $this->getCamelCaseToUnderscoreFilter()->filter($name);
    }

    /**
     * @return FilterChain
     */
    protected function getCamelCaseToUnderscoreFilter(): FilterChain
    {
        if (static::$camelCaseToUnderscoreFilter instanceof FilterChain) {
            return static::$camelCaseToUnderscoreFilter;
        }

        $filter = new FilterChain();

        $filter->attachByName('WordCamelCaseToDash');
        $filter->attachByName('StringToLower');

        return static::$camelCaseToUnderscoreFilter = $filter;
    }
}
