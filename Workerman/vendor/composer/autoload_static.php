<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3003a46e9bd4fb3365b033a0e1f79dab
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\MySQL\\' => 16,
            'Workerman\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\MySQL\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/mysql/src',
        ),
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3003a46e9bd4fb3365b033a0e1f79dab::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3003a46e9bd4fb3365b033a0e1f79dab::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}