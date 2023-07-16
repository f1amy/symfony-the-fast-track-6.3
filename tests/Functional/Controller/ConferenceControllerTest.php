<?php

namespace App\Tests\Functional\Controller;

use App\Enum\PublishState;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testCommentSubmission(): void
    {
        $client = static::createClient();

        $client->request('GET', '/en/conference/amsterdam-2019');

        $email = 'me@automat.ed';
        $client->submitForm('Submit', [
            'comment[author]' => 'Fabien',
            'comment[text]' => 'Some feedback from an automated functional test',
            'comment[email]' => $email,
            'comment[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);

        $this->assertResponseRedirects();

        // simulate comment validation
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState(PublishState::Published);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->followRedirect();

        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

    public function testConferencePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There is one comment")');
    }
}
