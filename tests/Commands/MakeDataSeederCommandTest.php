<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempPath = sys_get_temp_dir().'/data-seeders-make-'.uniqid();
    config()->set('data-seeders.path', $this->tempPath);
});

afterEach(function () {
    if (File::isDirectory($this->tempPath)) {
        File::deleteDirectory($this->tempPath);
    }
});

it('creates a timestamp-prefixed seeder file from the stub', function () {
    $this->artisan('make:data-seeder', ['name' => 'CreateInitialAdmins'])
        ->assertSuccessful();

    $files = File::files($this->tempPath);
    expect($files)->toHaveCount(1);

    $name = $files[0]->getFilename();
    expect($name)->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_create_initial_admins\.php$/');

    $contents = File::get($files[0]->getRealPath());
    expect($contents)->toContain('use Bmckay959\\DataSeeders\\DataSeeder;')
        ->and($contents)->toContain('public function seed(Seeder $seeder)')
        ->and($contents)->toContain('public function rollback(Seeder $seeder)');
});

it('creates the seeder directory if missing', function () {
    expect(File::isDirectory($this->tempPath))->toBeFalse();

    $this->artisan('make:data-seeder', ['name' => 'AnotherOne'])
        ->assertSuccessful();

    expect(File::isDirectory($this->tempPath))->toBeTrue()
        ->and(File::files($this->tempPath))->toHaveCount(1);
});
