<?php

namespace App\MessagePipeline\Consumer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Consumes
{
    /**
     * @param  string|string[]  $patterns  Router-key pattern(s)
     */
    public function __construct(public string|array $patterns) {}
}
