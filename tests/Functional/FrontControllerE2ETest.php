<?php

declare(strict_types=1);

use Art4\LegacyTodo\E2eTestCase;

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

    public function testHappyPathAsUser(): void
    {
        $this->login('user', 'user123');

        $dashboard = $this->http->request('GET', '/index.php');
        $this->assertSame(200, $dashboard->status());
        $this->assertStringContainsString('Zweites Todo', $dashboard->body());
        $this->assertStringContainsString('Eingeloggt als user', $dashboard->body());

        $createToken = $this->csrfTokenFrom($this->http->request('GET', '/addtodo.php'));
        $create = $this->http->request('POST', '/addtodo.php', [
            '_csrf_token' => $createToken,
            'save' => 'Speichern',
            'title' => 'E2E Neues Todo',
            'text' => 'E2E Beschreibung',
            'priority' => '2',
            'due_date' => '2026-12-31',
        ]);
        $this->assertRedirect($create, 'index.php');
        $id = $this->todoIdByTitle('E2E Neues Todo');
        $this->assertGreaterThan(0, $id);

        $afterCreate = $this->http->request('GET', '/index.php');
        $this->assertStringContainsString('E2E Neues Todo', $afterCreate->body());

        $editToken = $this->csrfTokenFrom($this->http->request('GET', '/edittodo.php?id=' . $id));
        $edit = $this->http->request('POST', '/edittodo.php?id=' . $id, [
            '_csrf_token' => $editToken,
            'save' => 'Speichern',
            'title' => 'E2E Geändertes Todo',
            'text' => 'E2E Neuer Text',
            'priority' => '1',
            'status' => 'open',
        ]);
        $this->assertRedirect($edit, 'todo.php?id=' . $id);

        $detail = $this->http->request('GET', '/todo.php?id=' . $id);
        $this->assertSame(200, $detail->status());
        $this->assertStringContainsString('E2E Geändertes Todo', $detail->body());
        $this->assertStringContainsString('E2E Neuer Text', $detail->body());

        $commentToken = $this->csrfTokenFrom($detail);
        $comment = $this->http->request('POST', '/todo.php?id=' . $id, [
            '_csrf_token' => $commentToken,
            'add_comment' => 'Kommentieren',
            'body' => 'E2E Kommentar',
        ]);
        $this->assertRedirect($comment, 'todo.php?id=' . $id);

        $withComment = $this->http->request('GET', '/todo.php?id=' . $id);
        $this->assertStringContainsString('E2E Kommentar', $withComment->body());

        $delete = $this->http->request('GET', '/deletetodo.php?id=' . $id . '&confirm=1');
        $this->assertRedirect($delete, 'index.php');

        $afterDelete = $this->http->request('GET', '/index.php');
        $this->assertSame(200, $afterDelete->status());
        $this->assertStringContainsString('Zweites Todo', $afterDelete->body());
        $this->assertStringNotContainsString('E2E Geändertes Todo', $afterDelete->body());

        $csv = $this->http->request('GET', '/index.php?export=csv');
        $this->assertSame(200, $csv->status());
        $this->assertStringStartsWith('text/csv', (string) $csv->header('Content-Type'));
        $this->assertStringContainsString('id,title,status,priority,due_date,category,owner', $csv->body());
        $this->assertStringContainsString('Zweites Todo', $csv->body());

        $logout = $this->http->request('GET', '/logout.php');
        $this->assertRedirect($logout, 'login.php');

        $afterLogout = $this->http->request('GET', '/index.php');
        $this->assertRedirect($afterLogout, 'login.php');
    }

    public function testDashboardListEscapesTodoFields(): void
    {
        $this->login('user', 'user123');

        $createToken = $this->csrfTokenFrom($this->http->request('GET', '/addtodo.php'));
        $create = $this->http->request('POST', '/addtodo.php', [
            '_csrf_token' => $createToken,
            'save' => 'Speichern',
            'title' => '<script>alert(1)</script>',
            'text' => 'beschreibung',
            'priority' => '2',
            'due_date' => '2026-12-31',
        ]);
        $this->assertRedirect($create, 'index.php');

        $dashboard = $this->http->request('GET', '/index.php');
        $this->assertSame(200, $dashboard->status());
        $this->assertStringNotContainsString('<script>', $dashboard->body());
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $dashboard->body());
    }

    public function testBadLoginRejected(): void
    {
        $token = $this->csrfTokenFromLoginForm();
        $response = $this->http->request('POST', '/login.php', [
            '_csrf_token' => $token,
            'login' => '1',
            'username' => 'user',
            'password' => 'wrong-password',
        ]);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Login failed', $response->body());

        $index = $this->http->request('GET', '/index.php');
        $this->assertRedirect($index, 'login.php');
    }

    public function testUnauthenticatedRequestsRedirectToLogin(): void
    {
        $index = $this->http->request('GET', '/index.php');
        $this->assertRedirect($index, 'login.php');

        $addTodo = $this->http->request('GET', '/addtodo.php');
        $this->assertRedirect($addTodo, 'login.php?next=addtodo.php');
    }

    public function testAllEightEntryPointsServeWithoutFatalAsAdmin(): void
    {
        $this->login('admin', 'admin123');

        $admin = null;
        // order matters: logout.php destroys the session, so it comes last
        $entryPoints = [
            '/login.php' => 200,
            '/index.php' => 200,
            '/addtodo.php' => 200,
            '/todo.php' => 200,
            '/edittodo.php' => 200,
            '/deletetodo.php' => 200,
            '/admin.php' => 200,
            '/logout.php' => 302,
        ];
        foreach ($entryPoints as $path => $expectedStatus) {
            $response = $this->http->request('GET', $path);
            $this->assertSame($expectedStatus, $response->status(), 'unexpected status for ' . $path);
            $this->assertStringNotContainsString('Fatal error', $response->body(), 'request-time fatal on ' . $path);
            $this->assertStringNotContainsString('Class "Art4\LegacyTodo\\', $response->body(), 'missing class on ' . $path);
            if ($path === '/admin.php') {
                $admin = $response;
            }
        }
        if ($admin === null) {
            $this->fail('admin.php response was not captured');
        }

        $this->assertStringContainsString('Admin', $admin->body());
        $this->assertStringContainsString('Benutzer', $admin->body());
        $this->assertStringContainsString('Kategorien', $admin->body());
    }

    public function testTodoNotFoundRendersNotfound(): void
    {
        $this->login('user', 'user123');

        $response = $this->http->request('GET', '/todo.php?id=99999');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Not found', $response->body());
    }

    public function testUploadViaAddTodoStoresFileInWebroot(): void
    {
        $this->login('user', 'user123');

        $uploadsDir = dirname(__DIR__) . '/../public/uploads';
        $before = glob($uploadsDir . '/*');
        $before = $before !== false ? $before : [];

        $tmpFile = (string) tempnam(sys_get_temp_dir(), 'e2e-upload-');
        file_put_contents($tmpFile, 'e2e upload content');

        $uploadToken = $this->csrfTokenFrom($this->http->request('GET', '/addtodo.php'));

        $response = $this->http->request('POST', '/addtodo.php', [
            '_csrf_token' => $uploadToken,
            'save' => 'Speichern',
            'title' => 'E2E Upload Todo',
            'text' => 'Mit Upload',
            'priority' => '2',
            'due_date' => '2026-12-31',
        ], [
            'upload' => [
                'tmp_name' => $tmpFile,
                'name' => 'notiz.txt',
                'type' => 'text/plain',
            ],
        ]);
        @unlink($tmpFile);

        $this->assertRedirect($response, 'index.php');
        $this->assertGreaterThan(0, $this->todoIdByTitle('E2E Upload Todo'));

        $after = glob($uploadsDir . '/*');
        $after = $after !== false ? $after : [];
        $newFiles = array_values(array_diff($after, $before));
        $this->assertCount(1, $newFiles);
        $stored = $newFiles[0];
        $this->assertStringEndsWith('.txt', $stored);
        $this->assertFileExists($stored);
        $this->trackUploadedFile($stored);
    }

    public function testRegisterJourneyCreatesUserAndReportsSuccess(): void
    {
        $token = $this->csrfTokenFromLoginForm();
        $response = $this->http->request('POST', '/login.php', [
            '_csrf_token' => $token,
            'register' => 'Registrieren',
            'username' => 'newe2euser',
            'password' => 'securepw123',
            'email' => 'newe2euser@example.com',
        ]);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('<p>Registriert</p>', $response->body());

        $stmt = $this->dbPdo()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute(['newe2euser']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotFalse($row);
        $this->assertSame('newe2euser', $row['username']);
        $this->assertTrue(password_verify('securepw123', $row['password']));
        $this->assertSame('user', $row['role']);
    }

    public function testRegisterWithEmptyFieldsReportsErrorAndWritesNoUser(): void
    {
        $token = $this->csrfTokenFromLoginForm();
        $response = $this->http->request('POST', '/login.php', [
            '_csrf_token' => $token,
            'register' => 'Registrieren',
            'username' => '',
            'password' => '',
            'email' => 'x@example.com',
        ]);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Fehler: Benutzername und Passwort sind erforderlich.', $response->body());
        $this->assertStringNotContainsString('Registriert', $response->body());

        $stmt = $this->dbPdo()->query('SELECT COUNT(*) FROM users WHERE username = ""');
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }

    public function testRegisterWithTakenUsernameReportsError(): void
    {
        $this->login('user', 'user123');
        $this->http->request('GET', '/logout.php');

        $token = $this->csrfTokenFromLoginForm();
        $response = $this->http->request('POST', '/login.php', [
            '_csrf_token' => $token,
            'register' => 'Registrieren',
            'username' => 'user',
            'password' => 'pw',
            'email' => 'x@example.com',
        ]);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Fehler: Dieser Benutzername ist bereits vergeben.', $response->body());
        $this->assertStringNotContainsString('Registriert', $response->body());
    }

    private function todoIdByTitle(string $title): int
    {
        $stmt = $this->dbPdo()->prepare('SELECT id FROM todos WHERE title = ?');
        $stmt->execute([$title]);
        $id = $stmt->fetchColumn();

        return (int) $id;
    }
}
