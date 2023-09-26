<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Tester\CommandTester;

class CreateAdministratorCommandTest extends KernelTestCase
{
    public function testExecuteCommandWithValidArguments()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $emailAdmin = 'john.doe@gmail.com';
        $passwordAdmin = 'azerty';

        $command = $application->find('app:create-admin');
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([$emailAdmin, $passwordAdmin]);

        $commandTester->execute(['command' => $command->getName()]);

        $commandTester->assertCommandIsSuccessful();

        // Get the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created admin '.$emailAdmin, $output);
    }

    public function testExecuteCommandWithInvalidArguments()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $emailAdmin = 'john.doe@gmail.com';
        $passwordAdmin = 'f';

        $command = $application->find('app:create-admin');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$emailAdmin, $passwordAdmin]);

        // An exception should be thrown
        $this->expectException(MissingInputException::class);

        $commandTester->execute(['command' => $command->getName()]);
    }
}
