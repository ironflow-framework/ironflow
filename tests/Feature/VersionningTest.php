<?php

use IronFlow\Versioning\VersionManager;

beforeEach(function () {
    $this->versionManager = app(VersionManager::class);
});

test('version manager handles caret constraints', function (){
    expect($this->versionManager->satisfies('1.2.3', '^1.2.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('1.9.9', '^1.2.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('2.0.0', '^1.2.0'))->toBeFalse();
});

test('version manager handles tilde constraints', function () {
    expect($this->versionManager->satisfies('1.2.3', '~1.2.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('1.2.9', '~1.2.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('1.3.0', '~1.2.0'))->toBeFalse();
});

test('version manager handles comparison operators', function () {

    expect($this->versionManager->satisfies('2.0.0', '>=1.5.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('1.4.0', '>=1.5.0'))->toBeFalse()
        ->and($this->versionManager->satisfies('1.5.0', '>1.5.0'))->toBeFalse()
        ->and($this->versionManager->satisfies('1.5.1', '>1.5.0'))->toBeTrue();
});

test('version manager handles OR conditions', function () {
    expect($this->versionManager->satisfies('1.5.0', '^1.0 || ^2.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('2.5.0', '^1.0 || ^2.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('3.0.0', '^1.0 || ^2.0'))->toBeFalse();
});

test('version manager handles AND conditions', function () {
    expect($this->versionManager->satisfies('1.5.0', '>=1.0.0 <2.0.0'))->toBeTrue()
        ->and($this->versionManager->satisfies('2.0.0', '>=1.0.0 <2.0.0'))->toBeFalse();
});

test('version manager can bump versions', function () {
    expect($this->versionManager->bump('1.2.3', 'patch'))->toBe('1.2.4')
        ->and($this->versionManager->bump('1.2.3', 'minor'))->toBe('1.3.0')
        ->and($this->versionManager->bump('1.2.3', 'major'))->toBe('2.0.0');
});

test('version manager checks stability', function () {
    expect($this->versionManager->isStable('1.2.3'))->toBeTrue()
        ->and($this->versionManager->isStable('1.2.3-beta'))->toBeFalse()
        ->and($this->versionManager->isStable('1.2.3-alpha.1'))->toBeFalse();
});
