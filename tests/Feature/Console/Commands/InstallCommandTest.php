<?php

declare(strict_types=1);

use Lynkbyte\EvolutionApi\Console\Commands\InstallCommand;

describe('InstallCommand', function () {

    describe('command signature', function () {
        it('has correct signature', function () {
            $command = new InstallCommand;

            expect($command->getName())->toBe('evolution-api:install');
        });

        it('has force option', function () {
            $command = new InstallCommand;
            $definition = $command->getDefinition();

            expect($definition->hasOption('force'))->toBeTrue();
        });
    });

    describe('command description', function () {
        it('has description', function () {
            $command = new InstallCommand;

            expect($command->getDescription())->not->toBeEmpty();
        });
    });

});
