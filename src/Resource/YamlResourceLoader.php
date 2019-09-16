<?php

namespace Vicus\Resource;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlResourceLoader extends FileLoader
{
    public function load($resource, $type = null)
    {
        if(empty($resource))
            return array();
        $configValues = Yaml::parse(file_get_contents($resource));

        return $configValues;
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }
}
