<?php

namespace App\Models;

use App\Helper\Validation;
use Exception;
use Illuminate\Database\QueryException;

class UniqueCode extends BaseModel
{
    protected $primaryKey = 'code';
    protected $keyType = 'string';
}
