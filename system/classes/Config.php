<?php

use Aws\S3\S3Client;
use Aws\Ssm\SsmClient;
use Aws\SecretsManager\SecretsManagerClient;

/**
 * This class is responsible for storing and accessing
 * the configurations for each class with case sensitive keys.
 *
 * Config getting and setting is done using the dot syntax i.e.
 *     Config::set("admin.topmenu", true);
 * Translates to:
 *     $register["admin"]["topmenu"] = true;
 * Then the inverse function also works:
 *     Config::get("admin.topmenu"); // returns true
 *
 * If any keys aren't found in set then they are created, also the value
 * itself can be an array, of which the values within can then be subsequently
 * retrieved with the abovementioned get function and dot syntax.
 *
 * Note: When calling Config::get, if a key is not found NULL is returned so it
 * is important to check that condition when fetching config keys.
 *
 * @author Adam Buckley
 */
class Config
{
    /**
     * The reserved section of a config document listing keys to replace.
     */
    private const SET_KEY = '@set';

    /**
     * The reserved section of a config document listing keys to append to.
     */
    private const APPEND_KEY = '@append';

    // Storage array
    private static $register = [];
    private static $_config_cache = [];
    private static $_keys_cache = [];

    private static $_use_sandbox = false;
    private static $shadow_register = [];
    private static $_shadow_config_cache = [];
    private static $_shadow_keys_cache = [];

    /**
     * The client used to interact with S3.
     *
     * @var Aws\S3\S3Client
     */
    private static $s3_client;

    /**
     * The client used to interact with parameter store.
     *
     * @var Aws\Ssm\SsmClient
     */
    private static $ssm_client;

    /**
     * The client used to interact with secrets manager.
     *
     * @var Aws\SecretsManager\SecretsManagerClient
     */
    private static $secrets_manager_client;

    /**
     * Set a configuration value using dot notation.
     *
     * @param string $key config key (e.g. "system.timeout")
     * @param mixed $value the value to set
     * @return null
     */
    public static function set($key, $value)
    {
        // Allow escaping "." with "\"
        $exploded_key = preg_split('/\\\\\.(*SKIP)(*FAIL)|\./', $key, -1, PREG_SPLIT_DELIM_CAPTURE);
        // $exploded_key = explode('.', $key);
        if (!empty($exploded_key)) {
            $register = &self::$register;
            if (self::$_use_sandbox === true) {
                $register = &self::$shadow_register;
            }

            // Loop through each key
            foreach ($exploded_key as $ekey) {
                if (is_array($register) && !array_key_exists($ekey, $register)) {
                    // Replace escaped periods with normal periods
                    $ekey = str_replace("\\.", ".", $ekey);

                    $register[$ekey] = [];
                }
                $register = &$register[$ekey];
            }
            $register = $value;
            self::invalidateCacheByKey($key);
        }
    }

    /**
     * Remove the cached lookups that a write to $key makes wrong: the entry
     * for $key itself, and every entry cached below it.
     *
     * Ancestors are left alone. A cached ancestor holds a reference to the
     * register and sees the write through it, and a cached miss is stored as
     * null, which get() treats as a miss and resolves again.
     *
     * @param string $key config key that has just been written
     * @return void
     */
    private static function invalidateCacheByKey(string $key): void
    {
        $prefix = $key . '.';
        $prefix_length = strlen($prefix);

        if (self::$_use_sandbox === true) {
            unset(self::$_shadow_config_cache[$key]);
            foreach (array_keys(self::$_shadow_config_cache) as $cached_key) {
                if (strncmp((string)$cached_key, $prefix, $prefix_length) === 0) {
                    unset(self::$_shadow_config_cache[$cached_key]);
                }
            }
        } else {
            unset(self::$_config_cache[$key]);
            foreach (array_keys(self::$_config_cache) as $cached_key) {
                if (strncmp((string)$cached_key, $prefix, $prefix_length) === 0) {
                    unset(self::$_config_cache[$cached_key]);
                }
            }
        }

        if (self::$_use_sandbox === true) {
            self::$_shadow_keys_cache = [];
        } else {
            self::$_keys_cache = [];
        }
    }

    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key config key (e.g. "system.timeout")
     * @param mixed $default value to return if key is not found
     * @return mixed the configuration value or default
     */
    public static function get($key, $default = null)
    {
        if (self::$_use_sandbox !== true && !empty(self::$_config_cache[$key])) {
            return self::$_config_cache[$key];
        }
        if (self::$_use_sandbox === true && !empty(self::$_shadow_config_cache[$key])) {
            return self::$_shadow_config_cache[$key];
        }

        $exploded_key = explode('.', $key);
        // Copy the register for processing
        $value = &self::$register;
        if (self::$_use_sandbox === true) {
            $value = &self::$shadow_register;
        }

        if (!empty($exploded_key)) {
            $i = 0;
            // Loop through each key
            while (isset($exploded_key[$i]) && isset($value[$exploded_key[$i]])) {
                $value = &$value[$exploded_key[$i]];
                $i++;
            }
            if (self::$_use_sandbox !== true) {
                if ($i !== count($exploded_key)) {
                    self::$_config_cache[$key] = null;
                } else {
                    self::$_config_cache[$key] = &$value;
                }
                return self::$_config_cache[$key] ?? $default;
            } else {
                if ($i !== count($exploded_key)) {
                    self::$_shadow_config_cache[$key] = null;
                } else {
                    self::$_shadow_config_cache[$key] = &$value;
                }
                return self::$_shadow_config_cache[$key] ?? $default;
            }
        }

        if (self::$_use_sandbox !== true) {
            self::$_config_cache[$key] = null;
        } else {
            self::$_shadow_config_cache[$key] = null;
        }
        return !is_null($default) ? $default : null;
    }

    /**
     * A small helper function for web to get the list of keys (modules)
     *
     * @return array
     */
    public static function keys($getAll = false)
    {
        if ($getAll === true) {
            return array_keys(self::$_use_sandbox === true ? self::$shadow_register : self::$register);
        }
        if (self::$_use_sandbox !== true && !empty(self::$_keys_cache)) {
            return self::$_keys_cache;
        }
        if (self::$_use_sandbox == true && !empty(self::$_shadow_keys_cache)) {
            return self::$_shadow_keys_cache;
        }

        $required = ["topmenu", "active", "path"];
        $req_count = count($required);
        $modules = array_filter(self::$_use_sandbox === true ? self::$shadow_register : self::$register, function ($var) use ($required, $req_count) {
            return ($req_count === count(array_intersect_key($var, array_flip($required))));
        });
        if (self::$_use_sandbox !== true) {
            self::$_keys_cache = array_keys($modules);
        } else {
            self::$_shadow_keys_cache = array_keys($modules);
        }
        return array_keys($modules);
    }

    /**
     * Append $value to the config at $key. Associative arrays are merged into
     * the existing value; list arrays are appended and deduplicated; scalars
     * are added to a list.
     *
     * @param string $key config key (e.g. "system.allow_action")
     * @param mixed $value scalar or array of config entries to add
     * @return null
     */
    public static function append($key, $value)
    {
        $target_value = self::get($key);

        if (empty($target_value)) {
            if (is_array($value)) {
                if (is_complete_associative_array($value)) {
                    self::set($key, $value);
                } else {
                    self::set($key, array_values(array_unique($value, SORT_REGULAR)));
                }
            } else {
                self::set($key, [$value]);
            }
        } else {
            if (is_array($target_value)) {
                if (is_array($value)) {
                    if (is_complete_associative_array($value) || is_complete_associative_array($target_value)) {
                        self::set($key, array_merge($target_value, $value));
                    } else {
                        self::set($key, array_values(array_unique(array_merge($target_value, $value), SORT_REGULAR)));
                    }
                } else {
                    $target_value[] = $value;
                    self::set($key, array_values(array_unique($target_value, SORT_REGULAR)));
                }
            } else {
                if (is_array($value)) {
                    if (is_complete_associative_array($value)) {
                        self::set($key, $value);
                    } else {
                        self::set($key, array_values(array_unique($value, SORT_REGULAR)));
                    }
                } else {
                    self::set($key, [$value]);
                }
            }
        }
    }

    public static function enableSandbox()
    {
        self::$_use_sandbox = true;
    }

    public static function disableSandbox()
    {
        self::$_use_sandbox = false;
    }

    public static function isSandboxing()
    {
        return self::$_use_sandbox;
    }

    public static function getSandbox()
    {
        return self::$shadow_register;
    }

    public static function setSandbox($shadow_register = [])
    {
        self::$shadow_register = $shadow_register;
        self::$_shadow_config_cache = [];
        self::$_shadow_keys_cache = [];
    }

    public static function promoteSandbox()
    {
        if (self::isSandboxing()) {
            if (!empty(self::$register)) {
                self::$shadow_register = array_merge(self::$shadow_register, self::$register);
                self::$_shadow_config_cache = [];
                self::$_shadow_keys_cache = [];
            }
        }
    }

    public static function mergeSandbox()
    {
        if (self::isSandboxing()) {
            if (!empty(self::$shadow_register)) {
                self::$register = array_merge(self::$register, self::$shadow_register);
                self::$_config_cache = [];
                self::$_keys_cache = [];
            }
            self::clearSandbox();
        }
    }

    public static function clearSandbox()
    {
        self::$_shadow_config_cache = [];
        self::$_shadow_keys_cache = [];
        self::$shadow_register = [];
    }

    // Sanity checking
    public static function dump()
    {
        if (self::$_use_sandbox) {
            var_dump(self::$shadow_register);
        } else {
            var_dump(self::$register);
        }
    }

    public static function toJson()
    {
        return json_encode(self::$register);
    }

    public static function fromJson($string)
    {
        self::$register = json_decode($string, true);
        self::$_config_cache = [];
        self::$_keys_cache = [];
    }

    /**
     * Extend the config by merging in a JSON document.
     *
     * @param string $string JSON config document
     * @return void
     */
    public static function extendFromJson(string $string): void
    {
        // validate
        if (empty($string)) {
            return;
        }

        // decode
        $source = json_decode($string, true);
        if (empty($source) || !is_array($source)) {
            return;
        }

        self::extend($source);
    }

    /**
     * Extend the config with a decoded config document.
     *
     * Config sections are merged first, then special items under "@set" then
     * "@append" are applied after. Items defined under "@set" will completely
     * replace the given section in the config (see Config::set). Items
     * defined under "@append" will then be added on to existing config (see
     * Config::append).
     *
     * @param array $source decoded config document
     * @return void
     */
    private static function extend(array $source): void
    {
        $set = $source[self::SET_KEY] ?? null;
        $append = $source[self::APPEND_KEY] ?? null;
        unset($source[self::SET_KEY], $source[self::APPEND_KEY]);

        if (!empty($source)) {
            self::merge($source);
        }

        if (is_array($set) && is_complete_associative_array($set)) {
            foreach ($set as $key => $value) {
                self::set($key, $value);
            }
        }

        if (is_array($append) && is_complete_associative_array($append)) {
            foreach ($append as $key => $value) {
                self::append($key, $value);
            }
        }
    }

    /**
     * Recursively merge a config array into the existing config. Associative
     * arrays are merged; list arrays are appended and deduplicated.
     *
     * @param array $source config to merge in
     * @param string $prefix current key path (used internally for recursion)
     * @return void
     */
    private static function merge(array $source, string $prefix = ''): void
    {
        foreach ($source as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;
            if (is_array($value) && is_complete_associative_array($value)) {
                self::merge($value, $path);
            } elseif (is_array($value)) {
                self::append($path, $value);
            } else {
                self::set($path, $value);
            }
        }
    }

    /**
     * Will get and object from an S3 bucket and merge it with the existing config. The object
     * is expected to be valid JSON. If the JSON decode fails an exception will be thrown.
     *
     * @param string $bucket
     * @param string $key
     * @return void
     * @throws Exception
     */
    public static function setFromS3Object(string $bucket, string $key): void
    {
        if (self::$s3_client === null) {
            $args = [
                'region' => 'ap-southeast-2',
                'version' => '2006-03-01',
            ];

            // Only load key and secret credentials when developing locally. Otherwise assume
            // IAM role has been correctly set.
            if (Config::get("system.environment", ENVIRONMENT_PRODUCTION) === ENVIRONMENT_DEVELOPMENT) {
                $args["credentials"] = Config::get('system.aws.credentials');
            }

            self::$s3_client = new S3Client($args);
        }

        $result = self::$s3_client->getObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        $data = json_decode($result->get('Body'), true);
        if (empty($data) || !is_array($data)) {
            throw new Exception("Failed to decode config data from $bucket/$key");
        }

        self::extend($data);
    }

    /**
     * Will get an object from parameter store and merge it with the existing config. The object
     * is expected to be valid JSON. If the JSON decode fails the function will fall back to using setFromS3Object or
     * an exception will be thrown.
     *
     * @param string $parameterName
     * @return void
     * @throws Exception
     */
    public static function setFromParameterStore(string $parameterName): void
    {
        if (!empty($parameterName)) {
            //retrieve JSON from paramater store and merge with config
            // Create SSM Client
            if (self::$ssm_client === null) {
                self::$ssm_client = new SsmClient([
                    'region' => getenv('AWS_REGION') ?: 'ap-southeast-2',
                    'version' => 'latest'
                ]);
            }
            $result = self::$ssm_client->getParameter([
                'Name' => $parameterName,
                'WithDecryption' => true
            ]);
            $value = $result['Parameter']['Value'];
            $data = json_decode($value, true);
            if (empty($data) || !is_array($data)) {
                throw new Exception("Failed to decode config data from parameter store");
            }
            self::extend($data);
            return;
        }
        throw new Exception("No parameter name provided");
    }
    /**
     * Will get an object from secrets manager and merge it with the existing config. The object
     * is expected to be valid JSON. If the JSON decode fails the function will fall back to using setFromS3Object or
     * an exception will be thrown.
     *
     * @param string $secretName
     * @return void
     * @throws Exception
     */
    public static function setFromSecretsManager(string $secretName): void
    {
        if (!empty($secretName)) {
            //retrieve JSON from secrets manager and merge with config
            // Create Secrets Manager Client
            if (self::$secrets_manager_client === null) {
                self::$secrets_manager_client = new SecretsManagerClient([
                    'region' => getenv('AWS_REGION') ?: 'ap-southeast-2',
                    'version' => 'latest'
                ]);
            }
            $result = self::$secrets_manager_client->getSecretValue([
                'SecretId' => $secretName
            ]);
            $value = $result->toArray()['SecretString'];
            $data = json_decode($value, true);
            if (empty($data) || !is_array($data)) {
                throw new Exception("Failed to decode config data from secrets manager");
            }
            self::extend($data);
            return;
        }
        throw new Exception("No secret name provided");
    }
}

/**
 * A static class to handle module dependencies. The key that is referenced is 'depends_on' and should
 * be located in the base level of the module config.
 *
 * @author Adam Buckley <adam@2pisoftware.com>
 */
class ConfigDependencyLoader
{
    private static $dependency_stack = [];

    /**
     * Registers a module to be loaded
     *
     * @param string $module
     * @param array $config
     */
    public static function registerModule($module, $config, $include_path)
    {
        $stack_class = new stdClass();
        $stack_class->config = $config;
        $stack_class->module_name = $module;
        $stack_class->loaded = false;
        $stack_class->visited = false;
        $stack_class->include_path = $include_path;

        self::$dependency_stack[] = $stack_class;
    }

    /**
     * Searches the dependency stack for a registered module
     *
     * @param string $module
     * @return ?stdClass registered module
     */
    public static function getRegisteredModule($module)
    {
        if (!empty(self::$dependency_stack)) {
            foreach (self::$dependency_stack as $dependency) {
                if (strtolower($module) === strtolower($dependency->module_name)) {
                    return $dependency;
                }
            }
        }
        return null;
    }

    /**
     * Loops over the dependency stack and attempts to load each node
     */
    public static function load()
    {
        if (!empty(self::$dependency_stack)) {
            foreach (self::$dependency_stack as $node) {
                self::visitNode($node);
            }
        }
    }

    /**
     * Checks given node for dependencies and recursively calls this function on
     * any dependencies specified.
     *
     * Throws exceptions when function cannot find a specified dependency or if it
     * detects a cyclic dependency definition.
     *     e.g module a -> module b -> module a
     *
     * @param stdClass $node
     * @return void
     * @throws Exception
     */
    private static function visitNode($node): void
    {
        $node->visited = true;
        if ($node->loaded === true) {
            return;
        }

        // Go through dependencies and load them
        if (!empty($node->config[$node->module_name]) && is_array($node->config[$node->module_name]) && array_key_exists('depends_on', $node->config[$node->module_name]) && is_array($node->config[$node->module_name]['depends_on'])) {
            foreach ($node->config[$node->module_name]['depends_on'] as $_module_dependency) {
                $_dependent_node = self::getRegisteredModule($_module_dependency);
                if (empty($_dependent_node)) {
                    throw new Exception($node->module_name . ' depends on ' . $_module_dependency . ' but it is not available');
                }

                if ($_dependent_node->loaded === true) {
                    continue;
                }

                if ($_dependent_node->visited === true) {
                    throw new Exception('Cyclic depedency detected in ' . $node->module_name);
                }

                self::visitNode($_dependent_node);
            }
        }

        // Load module and flag
        include($node->include_path);
        $node->loaded = true;
    }
}
