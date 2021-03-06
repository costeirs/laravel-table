<?php

namespace Okipa\LaravelTable\Tests\Unit;

use Okipa\LaravelTable\Table;
use Okipa\LaravelTable\Test\Models\User;
use Okipa\LaravelTable\Test\LaravelTableTestCase;

class ColumnsCountTest extends LaravelTableTestCase
{
    public function testGetColumnsCount()
    {
        $table = (new Table)->model(User::class);
        $table->column('id');
        $table->column('name');
        $table->column('email');
        $this->assertEquals(3, $table->columnsCount());
    }
}
