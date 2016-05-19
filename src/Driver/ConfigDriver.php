<?php

namespace Vicus\Driver;

interface ConfigDriver
{
    function load($filename);
    function supports($filename);
}
