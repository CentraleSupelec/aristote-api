<?php

namespace App\Tests\Admin;

use App\Constants;
use App\Entity\Administrator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class AdministratorAdminTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient(['debug' => false, 'environment' => 'test']);
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testLoginLogic(): void
    {
        // Try to access the dashboard without being authenticated
        $this->client->request('GET', '/admin/dashboard');
        $this->assertResponseRedirects('http://localhost/admin/login');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to the login page
        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the login form with credentials not present in the database
        $loginForm = $crawler->filterXPath('html/body/div[1]/div/div[2]/form')->form();

        $loginForm['_username'] = 'john.doe@gmail.com';
        $loginForm['_password'] = '123456789';

        $this->client->submit($loginForm);
        $this->assertResponseRedirects('http://localhost/admin/login');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to the login page
        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the login form with valid credentials but disabled user
        $admin = new Administrator();
        $admin->setEmail('john.doe@gmail.com');
        $admin->setPassword('123456789');

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $loginForm = $crawler->filterXPath('html/body/div[1]/div/div[2]/form')->form();
        $loginForm['_username'] = 'john.doe@gmail.com';
        $loginForm['_password'] = '123456789';

        $this->client->submit($loginForm);
        $this->assertResponseRedirects('http://localhost/admin/login');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to the login page
        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the form with invalid credentials
        $loginForm = $crawler->filterXPath('html/body/div[1]/div/div[2]/form')->form();
        $loginForm['_username'] = 'john.doe@gmail.com';
        $loginForm['_password'] = 'azerty';

        $this->client->submit($loginForm);
        $this->assertResponseRedirects('http://localhost/admin/login');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to the login page
        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );

        // Fill the login form with valid credentials and enbaled user
        $admin->setEnabled(true);

        $this->entityManager->flush();

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

        // Logout
        $crawler = $this->client->click(
            $crawler->filterXPath('//a[@id="logout-link"]')->link()
        );

        $this->assertResponseRedirects('http://localhost/admin/login');
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Redirection to the login page
        $this->assertStringContainsString(
            'Veuillez vous identifier',
            $crawler->filterXPath('html/body/div[1]/div/div[2]')->getNode(0)->textContent
        );
    }

    public function testLoginHistory(): void
    {
        $dispatcher = $this->client->getContainer()->get('event_dispatcher');
        $firewallContext = 'admin';

        $dateBeforeLogin = new DateTime();
        $admin = new Administrator();
        $admin->setEmail('john.doe@gmail.com');
        $admin->setPassword('123456789');
        $admin->setEnabled(true);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        // Check login history for this user
        $this->assertNull($admin->getLastLoginAt());

        $this->client->loginUser($admin, $firewallContext);
        $crawler = $this->client->request('GET', '/admin/dashboard');
        $this->assertResponseIsSuccessful();

        // Register the login listener
        $event = new InteractiveLoginEvent($this->client->getRequest(), $this->client->getContainer()->get('security.untracked_token_storage')->getToken());
        $dispatcher->dispatch($event, 'security.interactive_login');

        // Check that lastLoginAt value has been updated
        $dateAfterLogin = new DateTime();
        $this->assertNotNull($admin->getLastLoginAt());
        $this->assertTrue($dateBeforeLogin <= $admin->getLastLoginAt() && $admin->getLastLoginAt() <= $dateAfterLogin);
    }

    public function testCreateAdmin(): void
    {
        $adminAdmin = static::getContainer()->get('admin.administrator');

        // Create a new instance of Administrator
        $admin = new Administrator();
        $admin->setEmail('john.doe@gmail.com');
        $now = new DateTime();
        $admin->setLastLoginAt($now);
        $admin->setPlainPassword('1234');

        // Set password and erase sensitive data before persisting data in database
        $adminAdmin->prePersist($admin);

        // Assert the method call was effective
        $this->assertNull($admin->getPlainPassword());
        $this->assertEquals('1234', $admin->getPassword());

        // Assert other fields values are correct
        $this->assertFalse($admin->getEnabled());
        $this->assertContains(Constants::ROLE_SUPER_ADMIN, $admin->getRoles());

        // Test updatePassword method behavior if no plainPassword
        $admin = new Administrator();
        $admin->setEmail('john.doe@gmail.com');
        $now = new DateTime();
        $admin->setLastLoginAt($now);

        $adminAdmin->prePersist($admin);

        $this->assertNull($admin->getPassword());

        // Test prePersist method behavior if not instance of Administrator
        $this->expectException(LogicException::class);
        $adminAdmin->prePersist('admin');
    }

    public function testModifyAdmin()
    {
        $adminRepository = $this->entityManager->getRepository(Administrator::class);
        $adminAdmin = static::getContainer()->get('admin.administrator');

        // Create a new instance of Administrator
        $admin = new Administrator();
        $admin->setEmail('john.doe@gmail.com');
        $now = new DateTime();
        $admin->setLastLoginAt($now);
        $admin->setPlainPassword('1234');

        $adminAdmin->prePersist($admin);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        // Get administrator entity, modify it and persist it
        $persistedAdmin = $adminRepository->findOneBy(['email' => 'john.doe@gmail.com']);
        $persistedAdmin->setEnabled(true);
        $persistedAdmin->setPlainPassword('azerty');

        $adminAdmin->preUpdate($persistedAdmin);
        $this->entityManager->flush();

        // Get the modified administrator and assert its new status and password
        $modifiedAdmin = $adminRepository->findOneBy(['email' => 'john.doe@gmail.com']);
        $this->assertTrue($modifiedAdmin->getEnabled());
        $this->assertEquals('azerty', $modifiedAdmin->getPassword());

        // Test update behavior when no plainPassword
        $modifiedAdmin->setPassword('1234');
        $adminAdmin->preUpdate($modifiedAdmin);

        $this->assertNull($admin->getPlainPassword());
        $this->assertEquals('1234', $modifiedAdmin->getPassword());

        // Test preUpdate method behavior if not instance of Administrator
        $this->expectException(LogicException::class);
        $adminAdmin->preUpdate('admin');
    }
}
