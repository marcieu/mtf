<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */
namespace Magento\Framework\Config;

interface ScopeListInterface
{
    /**
     * Retrieve list of all scopes
     *
     * @return string[]
     */
    public function getAllScopes();
}
