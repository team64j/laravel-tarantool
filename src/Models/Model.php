<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    /**
     * @var string
     */
    protected $connection = 'tarantool';
}
