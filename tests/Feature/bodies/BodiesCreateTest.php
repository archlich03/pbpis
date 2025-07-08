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

// Shared setup
beforeEach(function () {
    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);
});

it('allows IT admin to access bodies create', function () {
    Session::start();
    app()->setLocale('en');
    actingAs($this->adminUser)
        ->get(route('bodies.create'))
        ->assertSee(__('Create new body'));

    Session::flush();
    Session::invalidate();
});

it('prevents secretaries from accessing bodies create', function () {
    Session::start();
    app()->setLocale('en');
    actingAs($this->secretaryUser)
        ->get(route('bodies.create'))
        ->assertForbidden();

    Session::flush();
    Session::invalidate();
});

it('prevents voters from accessing bodies create', function () {
    Session::start();
    app()->setLocale('en');
    actingAs($this->voterUser)
        ->get(route('bodies.create'))
        ->assertForbidden();

    Session::flush();
    Session::invalidate();
});

it('prevents guests from accessing bodies create', function () {
    $response = $this->get(route('bodies.create'));

    $response->assertRedirect(route('login'));
});