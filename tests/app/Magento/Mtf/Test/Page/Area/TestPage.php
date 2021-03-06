<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Magento\Mtf\Test\Page\Area;

use Mtf\Page\Page;

/**
 * Class TestPage
 */
class TestPage extends Page
{
    const MCA = 'testPage';

    /**
     * @var array
     */
    protected $_blocks = array(
        'testBlock' => array(
            'name' => 'testBlock',
            'class' => 'Magento\Mtf\Test\Block\TestBlock',
            'locator' => 'body',
            'strategy' => 'tag name',
        ),
    );

    /**
     * @return \Magento\Mtf\Test\Block\TestBlock
     */
    public function getTestBlock()
    {
        return $this->getBlockInstance('testBlock');
    }

    /**
     * Init page. Set page url
     *
     * @return void
     */
    protected function _init()
    {
        $this->_url = 'http://google.com/';
    }
}
