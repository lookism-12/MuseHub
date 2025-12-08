<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    #[DataProvider('urlProvider')]
    public function testPageIsSuccessful($url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful(sprintf('The %s public URL loads correctly.', $url));
    }

    public static function urlProvider(): \Generator
    {
        yield ['/'];
        yield ['/artworks'];
        yield ['/artists'];
        yield ['/events'];
        yield ['/marketplace'];
        yield ['/community'];
        yield ['/harvard-artworks'];
    }
}
