<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdd84e4bad8892db7c659da269a89fe55
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Yaml\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Yaml\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/yaml',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitdd84e4bad8892db7c659da269a89fe55::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitdd84e4bad8892db7c659da269a89fe55::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
