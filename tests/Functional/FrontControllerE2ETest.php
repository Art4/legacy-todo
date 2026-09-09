<?php

declare(strict_types=1);

/**
 * Boot proof and the full end-to-end journey scenarios against the running
 * app: real HTTP requests to the public/ entry points, a real seeded SQLite
 * file, and real session cookies.
 */
final class FrontControllerE2ETest extends E2eTestCase
{
    public function testLoginPageRendersUnauthenticated(): void
    {
        $response = $this->http->request('GET', '/login.php');

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->contains('Login'));
        $this->assertTrue($response->contains("name='username'"));
    }
}