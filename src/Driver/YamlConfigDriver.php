<?php

namespace Vicus\Driver;

use Symfony\Component\Yaml\Yaml;
use Vicus\Resource\YamlResourceLoader;

class YamlConfigDriver implements ConfigDriver
{
    public function load($filename)
    {
        if (!class_exists('Symfony\\Component\\Yaml\\Yaml')) {
            throw new \RuntimeException('Unable to read yaml as the Symfony Yaml Component is not installed.');
        }
        $resourceLoader = new YamlResourceLoader();
        
        if (! $resourceLoader->supports($filename)) {
            throw new \InvalidArgumentException('Invalid file passed to YamlConfigDriver');
        }

        $config = $resourceLoader->load($flename);

        return $config ?: array();
    }

    public function supports($filename)
    {
        return (bool) preg_match('#\.ya?ml(\.dist)?$#', $filename);
    }
}
