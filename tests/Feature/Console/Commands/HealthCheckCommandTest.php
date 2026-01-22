<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Console\Commands\HealthCheckCommand;

describe('HealthCheckCommand', function () {

    beforeEach(function () {
        Http::preventStrayRequests();
    });

    describe('command signature', function () {
        it('has correct signature', function () {
            $command = new HealthCheckCommand();

            expect($command->getName())->toBe('evolution-api:health');
        });

        it('has connection option', function () {
            $command = new HealthCheckCommand();
            $definition = $command->getDefinition();

            expect($definition->hasOption('connection'))->toBeTrue();
        });
    });

    describe('command description', function () {
        it('has description', function () {
            $command = new HealthCheckCommand();

            expect($command->getDescription())->not->toBeEmpty();
        });
    });

});
