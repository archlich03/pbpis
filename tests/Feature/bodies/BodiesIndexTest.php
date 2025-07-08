<?php

use App\Models\Body;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function Pest\Laravel\{actingAs, get, post, put, delete};

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);
});

it('allows IT admin to access bodies index', function () {
    Session::start();
    app()->setLocale('en');
    actingAs($this->adminUser)
        ->get(route('bodies.index'))
        ->assertSee(__('List of all bodies'));

    Session::flush();
    Session::invalidate();
});

it('allows secretaries to access bodies index', function () {
    Session::start();
    app()->setLocale('en');
    actingAs($this->secretaryUser)
        ->get(route('bodies.index'))
        ->assertSee(__('List of all bodies'));

    Session::flush();
    Session::invalidate();
});

it('allows voters to access bodies index', function () {
    Session::start();
    app()->setLocale('en');
    actingAs($this->voterUser)
        ->get(route('bodies.index'))
        ->assertSee(__('List of all bodies'));

    Session::flush();
    Session::invalidate();
});

it('prevents guests from bodies index', function () {
    $response = $this->get(route('bodies.index'));

    $response->assertRedirect(route('login'));
});