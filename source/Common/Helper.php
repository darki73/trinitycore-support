<?php namespace FreedomCore\TrinityCore\Support\Common;

/**
 * Class Helper
 * @package FreedomCore\TrinityCore\Support\Common
 */
class Helper {

    /**
     * Implementation of the recursive array search by key => $value
     * @param array $array
     * @param string $key
     * @param $value
     * @return array
     */
    public static function arrayMultiSearch(array $array, string $key, $value) : array {
        $results = [];
        Helper::array_multi_search_base($array, $key, $value, $results);
        return $results;
    }

    /**
     * Get character name as in database
     * @param string $characterName
     * @return string
     */
    public static function getCharacterName(string $characterName) : string {
        return ucfirst(strtolower($characterName));
    }

    /**
     * Throw Runtime Exception
     * @param string $message
     * @throws \RuntimeException
     */
    public static function throwRuntimeException(string $message) {
        $trace = debug_backtrace()[1];
        $callParameters = [
            'class'         =>  substr(strrchr($trace['class'], "\\"), 1),
            'method'        =>  $trace['function']
        ];
        throw new \RuntimeException(sprintf('%s::%s error: %s', $callParameters['class'], $callParameters['method'], $message));
    }

    /**
     * Low level implementation of the recursive array search by key => $value
     * @param array|integer $array
     * @param string $key
     * @param $value
     * @param array $results
     */
    private static function array_multi_search_base($array, string $key, $value, array &$results) {
        if (!is_array($array))
            return;

        if (isset($array[$key]) && $array[$key] === $value)
            $results[] = $array;

        foreach ($array as $subArray)
            Helper::array_multi_search_base($subArray, $key, $value, $results);
    }

}