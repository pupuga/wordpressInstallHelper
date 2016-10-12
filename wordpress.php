<?php

/**
 * It is helps to get WordPress and Plugins
 * @author Alex Shandor <mvshandor@gmail.com>
 * @copyright Copyright (c) 2016, Alex Shandor
 * @version 1.0
 */
class GetWordPress
{

    private $archiveName;
    private $url;
    private $delFiles = array();


    /**
     * GetWordPress constructor
     * @param string $url
     */
    public function __construct($url) {
        $this->lPrint();

        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'wordpress';

        $this->getNameFileFromUrl($url)
            ->getFileFromUrl('WordPress Archive')
            ->decompressesArchive()
            ->extractArchive()
            ->moveDirectory($dir)
            ->deleteFiles();

        $plugins = __DIR__.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR.'plugins';
        $this->deleteDirectory($plugins);
        mkdir($plugins);
        $themes = __DIR__.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR.'themes';
        $this->deleteDirectory($themes);
        mkdir($themes);

    }

    /**
     * Adds break by end
     * @param string $string
     */
    private function lPrint($string = '') {
        $string = $string . "\n\n";
        echo $string;
    }

    /**
     * Adds break by end
     * @param boolean $boolean
     * @param string  $message
     * @return string $string
     */
    private function echoMessage($boolean, $message = '') {
        if ($boolean) {
            $this->lPrint($message);
        } else {
            $this->lPrint('ERROR - ' . $message);
            exit;
        }

        return $this;
    }

    /**
     * Gets file name from downloading url
     * @param string $url
     * @return $this
     */
    private function getNameFileFromUrl($url) {
        $this->url = $url;
        $urlArray = explode('/', $url);
        $this->archiveName = end($urlArray);

        return $this;
    }


    /**
     * Downloads tar.gz archive from url
     * @param string $downloadingString
     * @param string $dir
     * @return $this
     */
    private function getFileFromUrl($downloadingString, $dir = '') {
        $this->lPrint($downloadingString . ' downloading');
        if($dir != '') {
            $this->archiveName = $dir . $this->archiveName;
        }
        $downloading = file_put_contents($this->archiveName, fopen($this->url, 'r'));
        $message = $downloadingString . ' has downloaded';
        $this->echoMessage($downloading, $message);

        return $this;
    }

    /**
     * Decompresses archive
     * @return $this
     */
    private function decompressesArchive() {
        $phar = new PharData($this->archiveName);
        $phar->decompress();
        $this->delFiles[] = $this->archiveName;
        $this->lPrint('Archive has decompressed');

        $urlArray = explode('.', $this->archiveName);
        $this->archiveName = $urlArray[0] . '.' . $urlArray[1];

        return $this;
    }

    /**
     * Extracts archive
     * @param string $dir
     * @return $this
     */
    private function extractArchive($dir = '') {
        if ($dir == '') {
            $dir = __DIR__;
        }
        $phar = new PharData($this->archiveName);
        $extract = $phar->extractTo($dir);
        $this->delFiles[] = $this->archiveName;
        $this->echoMessage($extract, 'Archive has extracted');

        return $this;
    }

    /**
     * Delete Directory
     * @param $dir
     * @return $this
     */
    private function deleteDirectory($dir) {

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . DIRECTORY_SEPARATOR . $object) == "dir") $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $object); else unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }

        if(is_dir($dir)) {
            $this->deleteDirectory($dir);
        }

        return $this;
    }

    /**
     * Copy custom directory to current directory
     * @param string $directory
     * @return $this
     */
    private function copyDirectory($directory) {
        $recursiveDirectoryIterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $recursiveIteratorIterator = new RecursiveIteratorIterator($recursiveDirectoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($recursiveIteratorIterator as $item
        ) {
            if ($item->isDir()) {
                mkdir(__DIR__ . DIRECTORY_SEPARATOR . $recursiveIteratorIterator->getSubPathName());
            } else {
                copy($item, __DIR__ . DIRECTORY_SEPARATOR . $recursiveIteratorIterator->getSubPathName());
            }
        }

        return $this;
    }

    /**
     * Move custom directory to current directory
     * @param string $directory
     * @return $this
     */
    private function moveDirectory($directory) {
        $this->copyDirectory($directory)->deleteDirectory($directory);

        $this->lPrint('Directory ' . $directory . ' moved to ' . __DIR__);

        return $this;
    }

    /**
     * Delete files
     * @return $this
     */
    private function deleteFiles() {

        foreach ($this->delFiles as $file) {
            @unlink($file);
        }

        $this->lPrint('Files has been deleted');

        return $this;
    }

    /**
     * Downloads plugins
     * @return $this
     */
    public function setPlugins($pluginsPartUrl, $plugins) {
        $pluginsPartUrlArray = explode('%%plugin%%', $pluginsPartUrl);
        $pluginUrl = __DIR__.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR;
        foreach ($plugins as $plugin) {
            $url =  $pluginsPartUrlArray[0] . $plugin .  $pluginsPartUrlArray[1];
            $this->getNameFileFromUrl($url)
                 ->getFileFromUrl('It is ' . $plugin, $pluginUrl)
                 ->extractArchive($pluginUrl)
                 ->deleteFiles();
        }

        @unlink(__FILE__);

        return $this;
    }

}

$url = 'http://wordpress.org/latest.tar.gz';

$pluginsPartUrl = 'https://downloads.wordpress.org/plugin/%%plugin%%.latest-stable.zip';

$plugins = array(
    'contact-form-7',
    'carbon-fields',
);

$wordPress = new GetWordPress($url);
$wordPress->setPlugins($pluginsPartUrl, $plugins);
