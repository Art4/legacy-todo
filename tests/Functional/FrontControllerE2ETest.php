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

    public function testHappyPathAsUser(): void
    {
        $this->login('user', 'user123');

        $dashboard = $this->http->request('GET', '/index.php');
        $this->assertSame(200, $dashboard->status());
        $this->assertStringContainsString('Zweites Todo', $dashboard->body());
        $this->assertStringContainsString('Eingeloggt als user', $dashboard->body());

        $create = $this->http->request('POST', '/addtodo.php', [
            'save' => 'Speichern',
            'title' => 'E2E Neues Todo',
            'text' => 'E2E Beschreibung',
            'priority' => '2',
            'due_date' => '2026-12-31',
        ]);
        $this->assertRedirect($create, 'index.php');
        $id = $this->todoIdByTitle('E2E Neues Todo');

        $afterCreate = $this->http->request('GET', '/index.php');
        $this->assertStringContainsString('E2E Neues Todo', $afterCreate->body());

        $edit = $this->http->request('POST', '/edittodo.php?id=' . $id, [
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

        $comment = $this->http->request('POST', '/todo.php?id=' . $id, [
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

    public function testBadLoginRejected(): void
    {
        $response = $this->http->request('POST', '/login.php', [
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

        $response = $this->http->request('POST', '/addtodo.php', [
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

    private function todoIdByTitle(string $title): int
    {
        $stmt = $this->dbPdo()->prepare('SELECT id FROM todos WHERE title = ?');
        $stmt->execute([$title]);
        $id = $stmt->fetchColumn();

        return (int) $id;
    }
}