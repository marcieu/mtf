<?php
/**
 * {license_notice}
 *
 * @copyright  {copyright}
 * @license    {license_link}
 */
namespace Mtf\System\Event;

use Mtf\System\Event\Config\Reader;
use Magento\Framework\Config\Data;

/**
 * Class for dealing wth events.xml config file and adoption it as Config to an application
 */
class Config extends Data
{
    /**
     * Configuration reader model
     *
     * @var \Magento\Framework\Config\ReaderInterface
     */
    protected $reader;

    /**
     * Preset name for observers configuration
     *
     * @var string
     */
    protected $presetName;

    /**
     * Map of events-observers
     *
     * @var array
     */
    protected $observers;

    /**
     * Parsed presets
     *
     * @var array
     */
    protected $parsedPresets;

    /**
     * Constructor
     *
     * @param Reader $reader
     */
    public function __construct(
        Reader $reader
    ) {
        $this->reader = $reader;
        $data = $reader->read();
        $this->presetName = isset($_ENV['events_preset'])
            ? $_ENV['events_preset']
            : 'default';
        $this->merge($data);
    }

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Merge config data to the object
     *
     * @param array $config
     * @return void
     */
    public function merge(array $config)
    {
        $this->_data = array_replace_recursive($this->_data, $config);
    }

    /**
     * Get config value by key
     *
     * @param string $path
     * @param mixed $default
     * @return array|string|null
     */
    public function get($path = null, $default = null)
    {
        if ($path === null) {
            return $this->_data;
        }
        $keys = explode('/', $path);
        $data = $this->_data;
        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return $default;
            }
        }
        return $data;
    }

    /**
     * Returns observers
     *
     * @return array
     */
    public function getObservers()
    {
        if (!isset($this->presetName)) {
            return [];
        }
        $metadata = $this->get('config');
        return $this->getPresetObservers($metadata[0], $this->presetName);
    }

    /**
     * Get observers for preset
     *
     * @param array $metadata
     * @param string $name
     * @throws \Exception
     * @return array
     */
    protected function getPresetObservers($metadata, $name)
    {
        $extendedObservers = $observers = [];
        foreach ($metadata['preset'] as $preset) {
            if ($preset['name'] == $name && isset($preset['observer'])) {
                $this->parsedPresets[] = $preset['name'];
                if (isset($preset['extends'])) {
                    if (!in_array($preset['extends'], $this->parsedPresets)) {
                        $extendedObservers = $this->getPresetObservers($metadata, $preset['extends']);
                    } else {
                        throw(
                            new \Exception(
                                sprintf(
                                    'Preset "%s" extends preset "%s" and vice versa. Please avoid recursion',
                                    $preset['name'],
                                    $preset['extends']
                                )
                            )
                        );
                    }
                }
                foreach ($preset['observer'] as $observer) {
                    foreach ($observer['tag'] as $tag) {
                        if (!isset($extendedObservers[$observer['class']])
                            || !in_array($tag['name'], $extendedObservers[$observer['class']])
                        ) {
                            $observers[$observer['class']][] = $tag['name'];
                        }
                    }
                }
                break;
            }
        }
        return array_merge_recursive($extendedObservers, $observers);
    }
}
