<?php
spl_autoload_register(function ($class) {
    // Ensure $class is not empty
    if (empty($class)) {
        return;
    }

    $rootDir = dirname(__FILE__);
    $classFile = str_replace('\\', '/', $class) . '.php';

    /**
     * @throws ReflectionException
     */
    $searchInDirectory = function ($directory) use ($classFile, $class, &$searchInDirectory) {
        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_dir($path)) {
                $searchInDirectory($path);
            } elseif ($file === basename($classFile)) {
                // Check if the class or interface already exists
                if (!class_exists($class, false) && !interface_exists($class, false)) {
                    require $path;

                    // Ensure the class exists before using Reflection
                    if (class_exists($class, false) || interface_exists($class, false)) {
                        $reflection = new ReflectionClass($class);

                        // Load parent class (if it exists)
                        if ($parentClass = $reflection->getParentClass()) {
                            spl_autoload_call($parentClass->getName());
                        }

                        // Load Interfaces (if they exist)
                        foreach ($reflection->getInterfaces() as $interface) {
                            spl_autoload_call($interface->getName());
                        }
                    }
                }
                return;
            }
        }
    };

    // Start searching from the root directory
    $searchInDirectory($rootDir);
});