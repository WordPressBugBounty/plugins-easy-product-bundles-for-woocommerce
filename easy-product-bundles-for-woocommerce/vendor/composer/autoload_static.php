<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita39d7ea75786aebd5a8a8cb3ead40169
{
    public static $files = array (
        '8f9cc1113bcd9de296f496f681a59e0f' => __DIR__ . '/../..' . '/src/Helpers.php',
        '8b2ad64e324ab75e873f1b6ec3995562' => __DIR__ . '/../..' . '/src/Helpers/Cart.php',
        'df20db2df630a9dd6462afb5dae31728' => __DIR__ . '/../..' . '/src/Helpers/Products.php',
    );

    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'AsanaPlugins\\WooCommerce\\ProductBundles\\' => 40,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'AsanaPlugins\\WooCommerce\\ProductBundles\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita39d7ea75786aebd5a8a8cb3ead40169::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita39d7ea75786aebd5a8a8cb3ead40169::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita39d7ea75786aebd5a8a8cb3ead40169::$classMap;

        }, null, ClassLoader::class);
    }
}
