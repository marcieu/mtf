<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\Client\Driver\Selenium;

use Mtf\Client\Element as ElementInterface;
use Mtf\Client\Element\Locator;

/**
 * Class Element
 *
 * Class provides ability to work with page element
 * (Such as setting/getting value, clicking, drag-n-drop element, etc)
 *
 * @api
 */
class Element implements ElementInterface
{
    /**
     * PHPUnit Selenium test case
     *
     * @var TestCase;
     */
    protected $_driver;

    /**
     * Element locator
     *
     * @var Locator
     */
    protected $_locator;

    /**
     * Context element
     *
     * @var Element
     */
    protected $_context;

    /**
     * Selenium element
     *
     * @var \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected $_wrappedElement;

    /**
     * @var \Mtf\System\Event\EventManager
     */
    protected $_eventManager;

    /**
     * Selenium elements
     *
     * @var \PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    protected $_wrappedElements = [];

    /**
     * Full selector path
     *
     * @var string
     */
    protected $_absoluteSelector;

    /**
     * Initialization.
     * Set driver, eventManager, context and locator.
     *
     * @constructor
     * @param \Mtf\Client\Driver\Selenium\TestCase $driver
     * @param \Mtf\System\Event\EventManagerInterface $eventManager
     * @param Locator $locator
     * @param Element $context
     */
    final public function __construct(
        \Mtf\Client\Driver\Selenium\TestCase $driver,
        \Mtf\System\Event\EventManagerInterface $eventManager,
        Locator $locator,
        Element $context = null
    ) {
        $this->_driver = $driver;

        $this->_eventManager = $eventManager;

        $this->_context = $context;

        $this->_locator = $locator;

        $this->_absoluteSelector = ($context ? $context->getAbsoluteSelector() . ' > ' : '') . $this->_locator;
    }

    /**
     * Unset wrapped element
     *
     * @return Element
     */
    public function __clone()
    {
        if ($this->_context) {
            $this->_context = clone $this->_context;
        }
        $this->_wrappedElement = null;
    }

    /**
     * Return Wrapped Element.
     * If element was not created before:
     * 1. Context is defined. If context was not passed to constructor - test case (all page) is taken as context
     * 2. Attempt to get selenium element is performed in loop
     * that is terminated if element is found or after timeout set in configuration
     *
     * @param bool $waitForElementPresent
     * @throws \PHPUnit_Extensions_Selenium2TestCase_Exception|\PHPUnit_Extensions_Selenium2TestCase_WebDriverException
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected function _getWrappedElement($waitForElementPresent = true)
    {
        if (!$this->_wrappedElement) {
            $context = !empty($this->_context)
                ? $this->_context->_getWrappedElement($waitForElementPresent) : $this->_driver;
            $criteria = new \PHPUnit_Extensions_Selenium2TestCase_ElementCriteria($this->_locator['using']);
            $criteria->value($this->_locator['value']);
            if ($waitForElementPresent) {
                $this->_wrappedElement = $this->waitUntil(
                    function () use ($context, $criteria) {
                        return $context->element($criteria);
                    }
                );
            } else {
                $driver = $this->_driver;
                $this->waitUntil(
                    function () use ($driver) {
                        $result = $driver->execute(
                            ['script' => "return document['readyState']", 'args' => []]
                        );
                        return $result === 'complete' || $result === 'uninitialized';
                    }
                );
                $this->_wrappedElement = $context->element($criteria);
            }
        }
        return $this->_wrappedElement;
    }

    /**
     * Click at the current element
     *
     * @return void
     */
    public function click()
    {
        $this->_eventManager->dispatchEvent(['click_before'], [__METHOD__, $this->getAbsoluteSelector()]);
        $this->_driver->moveto($this->_getWrappedElement());
        $this->_driver->click();
        $this->_eventManager->dispatchEvent(['click_after'], [__METHOD__, $this->getAbsoluteSelector()]);
    }

    /**
     * Double-clicks at the current element
     *
     * @return void
     */
    public function doubleClick()
    {
        $this->_driver->moveto($this->_getWrappedElement());
        $this->_driver->doubleclick();
    }

    /**
     * Right-clicks at the current element
     *
     * @return void
     */
    public function rightClick()
    {
        $this->_driver->moveto($this->_getWrappedElement());
        $this->_driver->click(\PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Click::RIGHT);
    }

    /**
     * Check whether element is visible.
     * Return false if element cannot be found
     *
     * @return bool
     */
    public function isVisible()
    {
        try {
            $this->_eventManager->dispatchEvent(['is_visible'], [__METHOD__, $this->getAbsoluteSelector()]);
            $visible = $this->_getWrappedElement(false)->displayed();
        } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            $visible = false;
        }
        return $visible;
    }

    /**
     * Check whether element is enabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return !$this->_getWrappedElement(false)->enabled();
    }

    /**
     * Check whether element is selected
     *
     * @return bool
     */
    public function isSelected()
    {
        return $this->_getWrappedElement(false)->selected();
    }

    /**
     * Clear and set new value to an element
     *
     * @param string|array $value
     * @return void
     */
    public function setValue($value)
    {
        $this->_eventManager->dispatchEvent(['set_value'], [__METHOD__, $this->getAbsoluteSelector()]);
        $this->_getWrappedElement()->clear();
        $this->_getWrappedElement()->value($value);
    }

    /**
     * Get the value of form element
     *
     * @return string
     */
    public function getValue()
    {
        $this->_eventManager->dispatchEvent(['get_value'], [(string) $this->_locator]);
        return $this->_getWrappedElement()->value();
    }

    /**
     * Get content of the element
     *
     * @return string
     */
    public function getText()
    {
        return $this->_getWrappedElement()->text();
    }

    /**
     * Find element on the page
     *
     * @param string $selector
     * @param string $strategy [optional]
     * @param string $typifiedElement = select|multiselect|checkbox|null OR custom class with full namespace
     * @return mixed
     */
    public function find($selector, $strategy = Locator::SELECTOR_CSS, $typifiedElement = null)
    {
        $this->_eventManager->dispatchEvent(['find'], [__METHOD__, $this->getAbsoluteSelector()]);
        $locator = new Locator($selector, $strategy);
        $className = '\Mtf\Client\Driver\Selenium\Element';

        if (null !== $typifiedElement) {
            if (strpos($typifiedElement, '\\') === false) {
                $typifiedElement = ucfirst(strtolower($typifiedElement));
                if (class_exists($className . '\\' . $typifiedElement . 'Element')) {
                    $className .= '\\' . $typifiedElement . 'Element';
                }
            } else {
                $className = $typifiedElement;
            }
        }

        return new $className($this->_driver, $this->_eventManager, $locator, $this);
    }

    /**
     * Drag and drop element to(between) another element(s)
     *
     * @param ElementInterface $target
     * @return void
     */
    public function dragAndDrop(ElementInterface $target)
    {
        $this->_driver->moveto($this->_getWrappedElement());
        $this->_driver->buttondown();

        /** @var $target Element */
        $this->_driver->moveto($target->_getWrappedElement());
        $this->_driver->buttonup();
    }

    /**
     * Send a sequence of key strokes to the active element.
     *
     * @param array $keys
     * @return void
     */
    public function keys(array $keys)
    {
        $this->_getWrappedElement()->value(['value' => $keys]);
    }

    /**
     * Wait until callback isn't null or timeout occurs.
     * Callback example: function() use ($element) {$element->isVisible();}
     * Timeout can be defined in configuration
     *
     * @param callable $callback
     * @return mixed|void
     * @throws \Exception
     */
    public function waitUntil($callback)
    {
        try {
            return $this->_driver->waitUntil($callback);
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    "Error occurred during waiting for an element %s with message (%s)",
                    $this->getAbsoluteSelector(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Get the alert dialog text
     *
     * @return string
     */
    public function getAlertText()
    {
        return $this->_driver->alertText();
    }

    /**
     * Set the text to the prompt popup
     *
     * @param string $text
     * @return void
     */
    public function setAlertText($text)
    {
        $this->_driver->alertText($text);
    }

    /**
     * Press OK on an alert or confirm a dialog
     *
     * @return void
     */
    public function acceptAlert()
    {
        $this->_driver->acceptAlert();
        $this->_eventManager->dispatchEvent(['accept_alert_after'], [__METHOD__]);
    }

    /**
     * Press Cancel on alert or does not confirm a dialog
     *
     * @return void
     */
    public function dismissAlert()
    {
        $this->_driver->dismissAlert();
        $this->_eventManager->dispatchEvent(['dismiss_alert_after'], [__METHOD__]);
    }

    /**
     * Get current page Url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->_driver->url();
    }

    /**
     * @return string
     */
    public function getAbsoluteSelector()
    {
        return $this->_absoluteSelector;
    }

    /**
     * Get all elements
     *
     * @return Element[]
     */
    public function getElements()
    {
        return $this->_getWrappedElements();
    }

    /**
     * Return Wrapped Elements.
     * If element was not created before:
     * 1. Context is defined. If context was not passed to constructor - test case (all page) is taken as context
     * 2. Attempt to get selenium elements is performed in loop
     * that is terminated if elements is found or after timeout set in configuration
     *
     * @param bool $waitForElementPresent
     * @throws \PHPUnit_Extensions_Selenium2TestCase_Exception|\PHPUnit_Extensions_Selenium2TestCase_WebDriverException
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    protected function _getWrappedElements($waitForElementPresent = true)
    {
        if (!$this->_wrappedElements) {
            $context = $this->getContext($waitForElementPresent);
            $criteria = new \PHPUnit_Extensions_Selenium2TestCase_ElementCriteria($this->_locator['using']);
            $criteria->value($this->_locator['value']);
            if ($waitForElementPresent) {
                $wrappedElements = $this->_driver->waitUntil(
                    function () use ($context, $criteria) {
                        return $context->elements($criteria);
                    }
                );
            } else {
                $this->waitPageToLoad();
                $wrappedElements = $context->elements($criteria);
            }
            foreach ($wrappedElements as $wrappedElement) {
                $element = \Mtf\ObjectManager::getInstance()->create(
                    get_class($this),
                    ['locator' => $this->_locator, 'driver' => $this->_driver, 'context' => $this->_context]
                );
                $element->_wrappedElement = $wrappedElement;
                $this->_wrappedElements[] = $element;
            }
        }
        return $this->_wrappedElements;
    }

    /**
     * Get context for an element
     *
     * @param bool $waitForElementPresent
     * @return TestCase|\PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected function getContext($waitForElementPresent)
    {
        return !empty($this->_context)
            ? $this->_context->_getWrappedElement($waitForElementPresent)
            : $this->_driver;
    }

    /**
     * Wait while page will be loaded to search an element
     *
     * @return void
     */
    protected function waitPageToLoad()
    {
        $driver = $this->_driver;
        $this->_driver->waitUntil(
            function () use ($driver) {
                $result = $driver->execute(
                    ['script' => "return document['readyState']", 'args' => []]
                );
                return $result === 'complete' || $result === 'uninitialized';
            }
        );
    }
}
