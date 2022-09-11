<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit57796f6a7d6eaac6f831c2a8cbea9649
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'AppleSignIn\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'AppleSignIn\\' => 
        array (
            0 => __DIR__ . '/..' . '/jsoprano/php-apple-signin-php56',
        ),
    );

    public static $classMap = array (
        'AppleSignIn\\ASDecoder' => __DIR__ . '/..' . '/jsoprano/php-apple-signin-php56/ASDecoder.php',
        'AppleSignIn\\Vendor\\JWK' => __DIR__ . '/..' . '/jsoprano/php-apple-signin-php56/Vendor/JWK.php',
        'AppleSignIn\\Vendor\\JWT' => __DIR__ . '/..' . '/jsoprano/php-apple-signin-php56/Vendor/JWT.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit57796f6a7d6eaac6f831c2a8cbea9649::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit57796f6a7d6eaac6f831c2a8cbea9649::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit57796f6a7d6eaac6f831c2a8cbea9649::$classMap;

        }, null, ClassLoader::class);
    }
}