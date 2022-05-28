<?php

namespace App\Utils;

use Illuminate\Support\Str;

class Helper
{
    /**
     * Return a boolean if entity contains
     * a column in schema
     */
    public static function hasAttribute($attribute, $entity)
    {
        return \Illuminate\Support\Facades\Schema::hasColumn((new $entity)->getTable(), $attribute);
    }

    /**
     * Returns url friendy using class name
     *
     * @param object $entity
     *
     * @return string
     */
    public static function getNameFriendly(object $entityClass)
    {
        $exploded = explode('\\', get_class($entityClass));

        return Str::lower((Str::plural($exploded[count($exploded) - 1])));
    }
}