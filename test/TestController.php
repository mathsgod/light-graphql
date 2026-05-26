<?php

namespace Controllers;

use TheCodingMachine\GraphQLite\Annotations\Query;

class TestController
{
    #[Query]
    public function hello(): string
    {
        return 'world';
    }
}
