<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class LoginFormTest extends TestCase
{
    private $appRoot;

    protected function setUp(): void
    {
        $this->appRoot = dirname(__DIR__);
    }

    public function testLoginFormFileExists(): void
    {
        $this->assertFileExists($this->appRoot . '/views/login_form.php');
    }

    public function testLoginFormFileIsReadable(): void
    {
        $this->assertIsReadable($this->appRoot . '/views/login_form.php');
    }

    public function testLoginFormHasSessionStart(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('session_start()', $content);
    }

    public function testLoginFormHasCsrfProtection(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('csrf_token', $content);
        $this->assertStringContainsString('bin2hex(random_bytes', $content);
    }

    public function testLoginFormUsesPreparedStatements(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('prepare', $content);
        $this->assertStringContainsString('bind_param', $content);
    }

    public function testLoginFormValidatesEmail(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('FILTER_VALIDATE_EMAIL', $content);
    }

    public function testLoginFormSanitizesOutput(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('htmlspecialchars', $content);
        $this->assertStringContainsString('ENT_QUOTES', $content);
    }

    public function testLoginFormHasErrorHandling(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('error_message', $content);
        $this->assertStringContainsString('connect_error', $content);
    }

    public function testLoginFormHasHtmlStructure(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<form', $content);
        $this->assertStringContainsString('type="email"', $content);
        $this->assertStringContainsString('type="password"', $content);
    }

    public function testLoginFormHasRequiredAttributes(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('required', $content);
    }

    public function testLoginFormHasLinks(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('register_form.php', $content);
        $this->assertStringContainsString('index.php', $content);
    }

    public function testLoginFormUsesPasswordVerify(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('password_verify', $content);
    }

    public function testLoginFormCheckUsertype(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        $this->assertStringContainsString('usertype', $content);
        $this->assertStringContainsString('admin', $content);
    }

    public function testLoginFormPhpSyntax(): void
    {
        $file = $this->appRoot . '/views/login_form.php';
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        $this->assertStringContainsString("No syntax errors", $output);
    }

    public function testLoginFormNoSqlInjection(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        // Should not have direct string interpolation in SQL queries
        $this->assertStringNotContainsString('SELECT * FROM users WHERE email = \'$', $content);
    }

    public function testLoginFormNoRequireOnce(): void
    {
        $content = file_get_contents($this->appRoot . '/views/login_form.php');
        // Should not use require_once for database connection
        $this->assertStringNotContainsString('require_once "database.php"', $content);
    }
}
