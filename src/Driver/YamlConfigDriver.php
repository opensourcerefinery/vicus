<?php

namespace Vicus\Driver;

use Symfony\Component\Yaml\Yaml;
use Vicus\Resource\YamlResourceLoader;
use Symfony\Component\Config\FileLocator;

class YamlConfigDriver implements ConfigDriver
{
    public function load($filename)
    {
        if (!class_exists('Symfony\\Component\\Yaml\\Yaml')) {
            throw new \RuntimeException('Unable to read yaml as the Symfony Yaml Component is not installed.');
        }
        $fileLocator = new FileLocator([]);
        $resourceLoader = new YamlResourceLoader($fileLocator);
        
        if (! $resourceLoader->supports($filename)) {
            throw new \InvalidArgumentException('Invalid file passed to YamlConfigDriver');
        }

        $config = $resourceLoader->load($filename);

        return $config ?: array();
    }

    public function supports($filename)
    {
        return (bool) preg_match('#\.ya?ml(\.dist)?$#', $filename);
    }
}
