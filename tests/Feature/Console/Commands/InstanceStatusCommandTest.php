<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Lynkbyte\EvolutionApi\Console\Commands\InstanceStatusCommand;

describe('InstanceStatusCommand', function () {

    beforeEach(function () {
        Http::preventStrayRequests();
    });

    describe('command signature', function () {
        it('has correct signature', function () {
            $command = new InstanceStatusCommand();

            expect($command->getName())->toBe('evolution-api:instances');
        });

        it('has instance argument', function () {
            $command = new InstanceStatusCommand();
            $definition = $command->getDefinition();

            expect($definition->hasArgument('instance'))->toBeTrue();
        });

        it('has connection option', function () {
            $command = new InstanceStatusCommand();
            $definition = $command->getDefinition();

            expect($definition->hasOption('connection'))->toBeTrue();
        });
    });

    describe('command description', function () {
        it('has description', function () {
            $command = new InstanceStatusCommand();

            expect($command->getDescription())->not->toBeEmpty();
        });
    });

});
