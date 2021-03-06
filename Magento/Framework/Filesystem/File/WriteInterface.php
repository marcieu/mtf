<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */
namespace Magento\Framework\Filesystem\File;

interface WriteInterface extends ReadInterface
{
    /**
     * Writes the data to file.
     *
     * @param string $data
     * @return int
     * @throws \Magento\Framework\Filesystem\FilesystemException
     */
    public function write($data);

    /**
     * Writes one CSV row to the file.
     *
     * @param array $data
     * @param string $delimiter
     * @param string $enclosure
     * @return int
     * @throws \Magento\Framework\Filesystem\FilesystemException
     */
    public function writeCsv(array $data, $delimiter = ',', $enclosure = '"');

    /**
     * Flushes the output.
     *
     * @return bool
     * @throws \Magento\Framework\Filesystem\FilesystemException
     */
    public function flush();

    /**
     * Portable advisory file locking
     *
     * @param int $lockMode
     * @return bool
     */
    public function lock($lockMode = LOCK_EX);

    /**
     * File unlocking
     *
     * @return bool
     */
    public function unlock();
}
