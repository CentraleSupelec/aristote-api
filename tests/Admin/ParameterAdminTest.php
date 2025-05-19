<?php

namespace App\Tests\Admin;

use App\Constants;
use App\Entity\Administrator;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ParameterAdminTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient(['debug' => false, 'environment' => 'test']);
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $admin = new Administrator();
        $admin->setEmail('john.doe@gmail.com');
        $admin->setPassword('123456789');
        $admin->setEnabled(true);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();
    }

    public function testRemoveMandatoryParameter()
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the login form with credentials not present in the database
        $loginForm = $crawler->filterXPath('html/body/div[1]/div/div[2]/form')->form();

        $loginForm['_username'] = 'john.doe@gmail.com';
        $loginForm['_password'] = '123456789';
        $this->client->submit($loginForm);

        $this->assertResponseRedirects('http://localhost/admin/dashboard');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to dashboard
        $this->assertStringContainsString(
            'Entités',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/div/div[1]/div/div/div[1]')->getNode(0)->textContent
        );

        // Go to Parameter page
        $crawler = $this->client->request('GET', '/admin/app/parameter/list');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Paramètre',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/div/form/div/div[1]/table/thead/tr/th[2]/a')->getNode(0)->textContent
        );

        $mandatoryParameters = Constants::getMandatoryParameters();

        foreach ($mandatoryParameters as $mandatoryParameter) {
            $rowXPath = sprintf(
                '//table/tbody/tr[td[2][normalize-space()="%s"]]',
                $mandatoryParameter
            );
            $row = $crawler->filterXPath($rowXPath);
            $this->assertGreaterThan(
                0,
                $row->count(),
                "Mandatory parameter '$mandatoryParameter' not found in the table."
            );

            $actionsText = $row->filter('td')->eq(4)->text();
            $this->assertStringNotContainsString(
                'Delete',
                $actionsText,
                "Mandatory parameter '$mandatoryParameter' should not have a delete action."
            );
        }
    }

    public function testAddMandatoryParameter()
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the login form with credentials not present in the database
        $loginForm = $crawler->filterXPath('html/body/div[1]/div/div[2]/form')->form();

        $loginForm['_username'] = 'john.doe@gmail.com';
        $loginForm['_password'] = '123456789';
        $this->client->submit($loginForm);

        $this->assertResponseRedirects('http://localhost/admin/dashboard');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to dashboard
        $this->assertStringContainsString(
            'Entités',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/div/div[1]/div/div/div[1]')->getNode(0)->textContent
        );

        // Go to Parameter creation page
        $crawler = $this->client->request('GET', '/admin/app/parameter/create');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Create and add another',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/form/div[2]/button[3]')->getNode(0)->textContent
        );

        $creationForm = $crawler->filterXPath('html/body/div[1]/div/section[2]/div/form')->form();

        // Extract the prefix from any field, for example the "name" textarea
        $fieldNode = $crawler->filter('textarea[name$="[name]"]')->first();
        $fieldName = $fieldNode->attr('name'); // e.g., "s68263aebf34ed[name]"

        // Extract prefix using regex
        preg_match('/^(.*?)\[name\]$/', $fieldName, $matches);
        $formPrefix = $matches[1] ?? null;

        $this->assertNotNull($formPrefix, 'Form prefix could not be determined.');

        $creationForm[$formPrefix.'[name]'] = Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS;
        $creationForm[$formPrefix.'[description]'] = 'This is a mandatory parameter';
        $creationForm[$formPrefix.'[value]'] = '100';

        $this->client->submit($creationForm);

        $this->assertResponseStatusCodeSame(500);
    }

    public function testAddAndEditOtherParameter()
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the login form with credentials not present in the database
        $loginForm = $crawler->filterXPath('html/body/div[1]/div/div[2]/form')->form();

        $loginForm['_username'] = 'john.doe@gmail.com';
        $loginForm['_password'] = '123456789';
        $this->client->submit($loginForm);

        $this->assertResponseRedirects('http://localhost/admin/dashboard');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to dashboard
        $this->assertStringContainsString(
            'Entités',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/div/div[1]/div/div/div[1]')->getNode(0)->textContent
        );

        // Go to Parameter creation page
        $crawler = $this->client->request('GET', '/admin/app/parameter/create');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Create and add another',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/form/div[2]/button[3]')->getNode(0)->textContent
        );

        $creationForm = $crawler->filterXPath('html/body/div[1]/div/section[2]/div/form')->form();

        // Extract the prefix from any field, for example the "name" textarea
        $fieldNode = $crawler->filter('textarea[name$="[name]"]')->first();
        $fieldName = $fieldNode->attr('name'); // e.g., "s68263aebf34ed[name]"

        // Extract prefix using regex
        preg_match('/^(.*?)\[name\]$/', $fieldName, $matches);
        $formPrefix = $matches[1] ?? null;

        $this->assertNotNull($formPrefix, 'Form prefix could not be determined.');

        $creationForm[$formPrefix.'[name]'] = 'TEST';
        $creationForm[$formPrefix.'[description]'] = 'This is a test parameter';
        $creationForm[$formPrefix.'[value]'] = 'test';

        $this->client->submit($creationForm);

        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $editForm = $crawler->filterXPath('html/body/div[1]/div/section[2]/div/form')->form();

        // Extract the prefix from any field, for example the "name" textarea
        $fieldNode = $crawler->filter('textarea[name$="[name]"]')->first();
        $fieldName = $fieldNode->attr('name'); // e.g., "s68263aebf34ed[name]"

        // Extract prefix using regex
        preg_match('/^(.*?)\[name\]$/', $fieldName, $matches);
        $formPrefix = $matches[1] ?? null;

        $this->assertNotNull($formPrefix, 'Form prefix could not be determined.');

        $editForm[$formPrefix.'[name]'] = Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS;

        $crawler = $this->client->submit($editForm);

        $this->assertResponseStatusCodeSame(500);

        $editForm[$formPrefix.'[name]'] = 'TEST';
        $editForm[$formPrefix.'[value]'] = '1';

        $crawler = $this->client->submit($editForm);

        $this->assertResponseRedirects();

        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testEditMandatoryParameter()
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the login form with credentials not present in the database
        $loginForm = $crawler->filterXPath('html/body/div[1]/div/div[2]/form')->form();

        $loginForm['_username'] = 'john.doe@gmail.com';
        $loginForm['_password'] = '123456789';
        $this->client->submit($loginForm);

        $this->assertResponseRedirects('http://localhost/admin/dashboard');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to dashboard
        $this->assertStringContainsString(
            'Entités',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/div/div[1]/div/div/div[1]')->getNode(0)->textContent
        );

        // Go to Parameter page
        $crawler = $this->client->request('GET', '/admin/app/parameter/list');
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Paramètre',
            $crawler->filterXPath('html/body/div[1]/div/section[2]/div/div/form/div/div[1]/table/thead/tr/th[2]/a')->getNode(0)->textContent
        );

        $rowXPath = sprintf(
            '//table/tbody/tr[td[2][normalize-space()="%s"]]',
            Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS
        );
        $row = $crawler->filterXPath($rowXPath);
        $this->assertGreaterThan(0, $row->count());

        $editLink = $row->filter('td a:contains("Edit")');
        $this->assertGreaterThan(0, $editLink->count());

        $crawler = $this->client->click($editLink->link());
        $this->assertResponseIsSuccessful();

        $editForm = $crawler->filterXPath('html/body/div[1]/div/section[2]/div/form')->form();

        // Extract the prefix from any field, for example the "name" textarea
        $fieldNode = $crawler->filter('textarea[name$="[name]"]')->first();
        $fieldName = $fieldNode->attr('name'); // e.g., "s68263aebf34ed[name]"

        // Extract prefix using regex
        preg_match('/^(.*?)\[name\]$/', $fieldName, $matches);
        $formPrefix = $matches[1] ?? null;

        $this->assertNotNull($formPrefix, 'Form prefix could not be determined.');

        $editForm[$formPrefix.'[name]'] = 'TEST';
        $editForm[$formPrefix.'[description]'] = 'This is a test parameter';
        $editForm[$formPrefix.'[value]'] = 'test';

        $crawler = $this->client->submit($editForm);

        $this->assertResponseIsSuccessful();
        $nameErrorMessage = $crawler->filterXPath(sprintf('//*[@id="sonata-ba-field-container-%s_name"]/div/div', $formPrefix))->first();
        $this->assertStringContainsString(sprintf("Les paramètres [%s] n'ont pas été retrouvés", Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS), $nameErrorMessage->text());

        $editForm[$formPrefix.'[name]'] = Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS;

        $crawler = $this->client->submit($editForm);
        $this->assertResponseIsSuccessful();

        $valueErrorMessage = $crawler->filterXPath(sprintf('//*[@id="sonata-ba-field-container-%s_value"]/div/div', $formPrefix))->first();
        $this->assertStringContainsString(sprintf('Les paramètres [%s] doivent avoir une valeur numérique', Constants::PARAMETER_MAX_MEDIA_DURATION_IN_SECONDS), $valueErrorMessage->text());

        $editForm[$formPrefix.'[value]'] = '3600';

        $crawler = $this->client->submit($editForm);
        $this->assertResponseRedirects();

        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
