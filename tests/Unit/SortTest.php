<?php

namespace Okipa\LaravelTable\Tests\Unit;

use ErrorException;
use Illuminate\Http\Request;
use Okipa\LaravelTable\Table;
use Okipa\LaravelTable\Test\LaravelTableTestCase;
use Okipa\LaravelTable\Test\Models\Company;
use Okipa\LaravelTable\Test\Models\User;

class SortTest extends LaravelTableTestCase
{
    public function testSetSortableAttribute()
    {
        $table = (new Table)->model(User::class);
        $table->column('name')->sortable();
        $this->assertTrue($table->columns->first()->isSortable);
        $this->assertEquals(1, $table->sortableColumns->count());
        $this->assertEquals('name', $table->sortableColumns->first()->attribute);
    }

    public function testSetSortByDefaultAttribute()
    {
        $this->routes(['users'], ['index']);
        $table = (new Table)->model(User::class)->routes(['index' => ['name' => 'users.index']]);
        $table->column('name');
        $table->column('email')->sortable(true, 'desc');
        $this->assertEquals('email', $table->sortBy);
        $this->assertEquals('desc', $table->sortDir);
        $table->render();
        $this->assertEquals('email', $table->sortBy);
        $this->assertEquals('desc', $table->sortDir);
    }

    public function testSortByDefault()
    {
        $users = $this->createMultipleUsers(5);
        $this->routes(['users'], ['index']);
        $table = (new Table)->routes(['index' => ['name' => 'users.index']])->model(User::class);
        $table->column('name')->title('Name');
        $table->column('email')->title('Email')->sortable(true);
        $table->render();
        $this->assertEquals($users->sortBy('email')->values()->toArray(), $table->list->toArray()['data']);
    }

    public function testSortByDefaultCalledMultiple()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('The table is already sorted by the « name » attribute. You only can sort a '
                                      . 'column by default once');
        $table = (new Table)->model(User::class);
        $table->column('name')->sortable(true);
        $table->column('email')->sortable(true);
    }

    public function testNoSortableColumnDefined()
    {
        $this->createMultipleUsers(5);
        $this->routes(['users'], ['index']);
        $table = (new Table)->routes(['index' => ['name' => 'users.index']])->model(User::class);
        $table->column('name');
        $table->column('email');
        $table->render();
        $this->assertNull($table->sortBy);
        $this->assertEquals($table->sortDir, 'asc');
    }

    public function testSortableColumnDefinedWithNoDefaultSort()
    {
        $this->createMultipleUsers(5);
        $this->routes(['users'], ['index']);
        $table = (new Table)->routes(['index' => ['name' => 'users.index']])->model(User::class);
        $table->column('name')->sortable();
        $table->column('email')->sortable();
        $table->render();
        $this->assertEquals($table->sortBy, $table->columns->first()->attribute);
        $this->assertEquals($table->sortDir, 'asc');
    }

    public function testSortByColumnWithoutAttribute()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('One of the sortable columns has no defined attribute. '
                                      . 'You have to define a column attribute for each sortable columns by setting a '
                                      . 'string parameter in the « column() » method.');
        $this->createMultipleUsers(5);
        $this->routes(['companies'], ['index']);
        $table = (new Table)->routes(['index' => ['name' => 'companies.index']])->model(User::class);
        $table->column()->sortable();
        $table->render();
    }

    public function testSortByColumn()
    {
        $users = $this->createMultipleUsers(3);
        $customRequest = app(Request::class);
        $customRequest->merge(['rows' => 20, 'sortBy' => 'email', 'sortDir' => 'desc']);
        $this->routes(['users'], ['index']);
        $table = (new Table)->model(User::class)
            ->routes(['index' => ['name' => 'users.index']])
            ->request($customRequest);
        $table->column('name')->title('Name')->sortable();
        $table->column('email')->title('Email')->sortable();
        $table->render();
        $this->assertEquals('email', $table->sortBy);
        $this->assertEquals('desc', $table->sortDir);
        $this->assertEquals($users->sortByDesc('email')->values()->toArray(), $table->list->toArray()['data']);
    }

    public function testSortOnOtherTableFieldWithoutCustomTableDeclaration()
    {
        $this->createMultipleUsers(5);
        $companies = $this->createMultipleCompanies(5);
        $this->routes(['companies'], ['index']);
        $customRequest = app(Request::class);
        $customRequest->merge([
            'rows'    => 20,
            'sortBy'  => 'owner',
            'sortDir' => 'desc',
        ]);
        $table = (new Table)->model(Company::class)
            ->routes(['index' => ['name' => 'companies.index']])
            ->query(function ($query) {
                $query->select('companies_test.*');
                $query->addSelect('users_test.name as owner');
                $query->join('users_test', 'users_test.id', '=', 'companies_test.owner_id');
            })
            ->request($customRequest);
        $table->column('owner')->sortable();
        $table->render();
        foreach ($companies->load('owner')->sortByDesc('owner.name')->values() as $key => $company) {
            $this->assertEquals($company->owner->name, $table->list->toArray()['data'][$key]['owner']);
        }
    }

    public function testPaginateSortOnOtherTableField()
    {
        $this->createMultipleUsers(5);
        $this->createMultipleCompanies(10);
        $this->routes(['companies'], ['index']);
        $customRequest = app(Request::class);
        $customRequest->merge(['rows' => 5, 'sortBy' => 'owner', 'sortDir' => 'desc']);
        $table = (new Table)->model(Company::class)
            ->routes(['index' => ['name' => 'companies.index']])
            ->query(function ($query) {
                $query->select('companies_test.*');
                $query->addSelect('users_test.name as owner');
                $query->join('users_test', 'users_test.id', '=', 'companies_test.owner_id');
            })
            ->request($customRequest);
        $table->column('owner')->sortable();
        $table->render();
        $paginatedCompanies = Company::join('users_test', 'users_test.id', '=', 'companies_test.owner_id')
            ->orderBy('users_test.name', 'desc')
            ->select('companies_test.*')
            ->with('owner')
            ->paginate(5);
        foreach ($paginatedCompanies as $key => $company) {
            $this->assertEquals($company->owner->name, $table->list->toArray()['data'][$key]['owner']);
        }
    }

    public function testSortableColumnHtml()
    {
        $this->routes(['users'], ['index']);
        $table = (new Table)->routes(['index' => ['name' => 'users.index']])->model(User::class);
        $table->column('name')->title('Name')->sortable();
        $table->column('email')->title('Email');
        $table->render();
        $thead = view('laravel-table::' . $table->theadComponentPath, compact('table'))->render();
        $this->assertContains(
            '<a href="http://localhost/users/index?sortBy=name&amp;sortDir=desc&amp;rows=20"',
            $thead
        );
        $this->assertNotContains(
            '<a href="http://localhost/users/index?sortBy=email&amp;sortDir=desc&amp;rows=20"',
            $thead
        );
    }
}
