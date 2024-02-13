<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitafb4cd7505f1c449a7f199eef9ca7135
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInitafb4cd7505f1c449a7f199eef9ca7135', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitafb4cd7505f1c449a7f199eef9ca7135', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitafb4cd7505f1c449a7f199eef9ca7135::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}