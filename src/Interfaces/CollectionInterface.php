<?php declare(strict_types = 1);

namespace rguezque\Interfaces;

/**
 * Interface for variable collection.
 * 
 * @method mixed get(string $key, $default = null) Retrieve a variable by name
 * @method void set(string $key, $value) Set or overwrite a parameter by name
 * @method array all() Retrieve all variables array
 * @method bool has(string $key) Return true if a variable exists
 * @method bool valid(string $key) Return true if a variable exists and is not empty or null
 * @method int count() Return the count of variables
 */
interface CollectionInterface {

    /**
     * Return a variable by name
     * 
     * @param string $key Variable name
     * @param mixed $default Value to return if the variable isn't found
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Set or overwrite a parameter by name
     * 
     * @param string $key Parameter name
     * @param mixed $value Parameter value
     * @return void
     */
    public function set(string $key, $value): void;

    /**
     * Retrieve all variables array
     * 
     * @return array
     */
    public function all(): array;

    /**
     * Return true if a variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Return true if a variable exists and is not empty or null
     * 
     * @param string $key variable name
     * @return bool
     */
    public function valid(string $key): bool;

    /**
	 * Return the count of variables
	 * 
	 * @return int
	 */
    public function count(): int;

}

?>