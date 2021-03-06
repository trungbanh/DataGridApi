<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit406d99c74a97a4d19b5db16af794a0e7
{
    public static $prefixLengthsPsr4 = array (
        'B' => 
        array (
            'Banhtrung\\DataGridApi\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Banhtrung\\DataGridApi\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInit406d99c74a97a4d19b5db16af794a0e7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit406d99c74a97a4d19b5db16af794a0e7::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit406d99c74a97a4d19b5db16af794a0e7::$classMap;

        }, null, ClassLoader::class);
    }
}
