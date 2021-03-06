<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\Constraint;

use Mtf\ObjectManager;

/**
 * Abstract Constraint Class
 * Constraint objects are stateful,
 * so when need to call for assertions more then one time,
 * the object should be configured again (see method "configure")
 *
 * @api
 * @abstract
 */
abstract class AbstractConstraint extends \PHPUnit_Framework_Constraint implements ConstraintInterface
{
    /**
     * Object Manager
     *
     * @var \Mtf\ObjectManager
     */
    protected $objectManager;

    /**
     * Test Case
     *
     * @var \PHPUnit_Framework_TestCase
     */
    protected $testCase;

    /**
     * Test Case Name
     *
     * @var string
     */
    protected $testCaseName = '';

    /**
     * Test Case DI Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Assertion Result
     *
     * @var boolean
     */
    protected $result;

    /**
     * Constructor
     *
     * @constructor
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Set DI Arguments to Constraint
     *
     * @param array $arguments
     * @return void
     */
    public function configure(array $arguments = [])
    {
        $this->result = null;
        $this->arguments = $arguments;
    }

    /**
     * Get constraint result
     *
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get class representation is string format
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Evaluates the constraint for test case
     *
     * @param string $testCaseName
     * @return bool
     */
    protected function matches($testCaseName)
    {
        if ($this->result === null) {
            $this->testCaseName = $testCaseName;
            $this->result = true;
            $this->objectManager->invoke($this, 'processAssert', $this->arguments);
        }
        return $this->result;
    }
}
