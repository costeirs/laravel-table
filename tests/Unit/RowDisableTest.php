<?php

namespace Okipa\LaravelTable\Tests\Unit;

use Okipa\LaravelTable\Table;
use Okipa\LaravelTable\Test\Models\User;
use Okipa\LaravelTable\Test\LaravelTableTestCase;

class RowDisableTest extends LaravelTableTestCase
{
    public function testRowDisableAttribute()
    {
        $rowDisableClosure = function ($model) {
            return $model->id === 1;
        };
        $classes = ['test-disabled-custom-class'];
        $table = (new Table)->disableRows($rowDisableClosure, $classes);
        $this->assertEquals($rowDisableClosure, $table->disableRows->first()['closure']);
        $this->assertEquals($classes, $table->disableRows->first()['classes']);
    }
    
    public function testDisableLineWithDefaultClassHtml()
    {
        $this->routes(['users'], ['index', 'create', 'edit', 'destroy']);
        $users = $this->createMultipleUsers(5);
        $closure = function ($model) use ($users) {
            return $model->id === 1 || $model->id === 2;
        };
        $classes = ['test-disabled-default-class'];
        config()->set('laravel-table.rows.disabled.classes', $classes);
        $table = (new Table)->model(User::class)
            ->routes([
                'index'   => ['name' => 'users.index'],
                'create'  => ['name' => 'users.create'],
                'edit'    => ['name' => 'users.edit'],
                'destroy' => ['name' => 'users.destroy'],
            ])
            ->disableRows($closure);
        $table->column('name');
        $table->column('email');
        $table->render();
        foreach ($table->list->getCollection() as $user) {
            $closure($user)
                ? $this->assertEquals($user->disabledClasses, $classes)
                : $this->assertEmpty($user->disabledClasses);
        }
        $html = view('laravel-table::' . $table->tbodyComponentPath, compact('table'))->render();
        $this->assertContains(implode(' ', $classes), $html);
        $this->assertContains('disabled="disabled"', $html);
        $this->assertEquals(2, substr_count($html, implode(' ', $classes)));
        $this->assertEquals(4, substr_count($html, 'disabled="disabled"'));
    }

    public function testDisableLineWithCustomClassHtml()
    {
        $this->routes(['users'], ['index', 'create', 'edit', 'destroy']);
        $users = $this->createMultipleUsers(5);
        $closure = function ($model) use ($users) {
            return $model->id === 1 || $model->id === 2;
        };
        $classes = ['test-disabled-custom-class'];
        $table = (new Table)->model(User::class)
            ->routes([
                'index'   => ['name' => 'users.index'],
                'create'  => ['name' => 'users.create'],
                'edit'    => ['name' => 'users.edit'],
                'destroy' => ['name' => 'users.destroy'],
            ])
            ->disableRows($closure, $classes);
        $table->column('name')->title('Name');
        $table->column('email')->title('Email');
        $table->render();
        foreach ($table->list->getCollection() as $user) {
            $closure($user)
                ? $this->assertEquals($user->disabledClasses, $classes)
                : $this->assertEmpty($user->disabledClasses);
        }
        $html = view('laravel-table::' . $table->tbodyComponentPath, compact('table'))->render();
        $this->assertContains(implode(' ', $classes), $html);
        $this->assertContains('disabled', $html);
        $this->assertContains('disabled="disabled"', $html);
        $this->assertEquals(2, substr_count($html, implode(' ', $classes)));
        $this->assertEquals(14, substr_count($html, 'disabled'));
        $this->assertEquals(4, substr_count($html, 'disabled="disabled"'));
    }

    public function testWithNoDisableLinesHtml()
    {
        $classes = ['test-disabled-default-class'];
        config()->set('laravel-table.value.disabled_line.class', $classes);
        $this->routes(['users'], ['index', 'create', 'edit', 'destroy']);
        $this->createMultipleUsers(5);
        $table = (new Table)->model(User::class)
            ->routes([
                'index'   => ['name' => 'users.index'],
                'create'  => ['name' => 'users.create'],
                'edit'    => ['name' => 'users.edit'],
                'destroy' => ['name' => 'users.destroy'],
            ]);
        $table->column('name')->title('Name');
        $table->column('email')->title('Email');
        $table->render();
        $html = view('laravel-table::' . $table->tbodyComponentPath, compact('table'))->render();
        $this->assertNotContains(implode(' ', $classes), $html);
        $this->assertNotContains('disabled="disabled"', $html);
    }
}
