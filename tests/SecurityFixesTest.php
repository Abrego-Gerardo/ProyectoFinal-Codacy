<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SecurityFixesTest extends TestCase
{
    /* ============================================================
       Tests para detalles_viaje.php
       ============================================================ */

    public function testDetallesViajeFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../views/detalles_viaje.php');
    }

    public function testDetallesViajeUsesPreparedStatements(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/detalles_viaje.php');
        
        // Verify prepared statement is used
        $this->assertStringContainsString('$conn->prepare($sql)', $content);
        $this->assertStringContainsString('$stmt->bind_param', $content);
        
        // Verify no direct string interpolation in SQL
        $this->assertStringNotContainsString('WHERE id=$', $content);
        $this->assertStringNotContainsString('WHERE id = $_GET', $content);
    }

    public function testDetallesViajeUsesHTMLSpecialchars(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/detalles_viaje.php');
        
        // Verify output escaping with htmlspecialchars
        $this->assertStringContainsString('htmlspecialchars($row["foto"]', $content);
        $this->assertStringContainsString('htmlspecialchars($row["city"]', $content);
        $this->assertStringContainsString('htmlspecialchars($row["pais"]', $content);
        $this->assertStringContainsString('htmlspecialchars($row["tipo_destino"]', $content);
        $this->assertStringContainsString('htmlspecialchars($row["precio', $content);
    }

    public function testDetallesViajeUsesInputValidation(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/detalles_viaje.php');
        
        // Verify filter_var for ID validation
        $this->assertStringContainsString('filter_var($_GET[\'id\']', $content);
        $this->assertStringContainsString('FILTER_VALIDATE_INT', $content);
    }

    /* ============================================================
       Tests para procesar_reserva.php
       ============================================================ */

    public function testProcesarReservaFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../views/procesar_reserva.php');
    }

    public function testProcesarReservaUsesPreparedStatements(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/procesar_reserva.php');
        
        // Verify prepared statement is used
        $this->assertStringContainsString('$conn->prepare($sql)', $content);
        $this->assertStringContainsString('$stmt->bind_param', $content);
        
        // Verify no direct string interpolation in SQL
        $this->assertStringNotContainsString('WHERE id = $id_viaje', $content);
        $this->assertStringNotContainsString('WHERE id = $_POST', $content);
    }

    public function testProcesarReservaUsesHTMLSpecialchars(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/procesar_reserva.php');
        
        // Verify output escaping
        $this->assertStringContainsString('htmlspecialchars($destino["city"]', $content);
        $this->assertStringContainsString('htmlspecialchars($destino["pais"]', $content);
        $this->assertStringContainsString('htmlspecialchars($destino["precio', $content);
        $this->assertStringContainsString('htmlspecialchars($destino[\'id\']', $content);
    }

    public function testProcesarReservaUsesInputValidation(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/procesar_reserva.php');
        
        // Verify filter_var for ID validation
        $this->assertStringContainsString('filter_var($_POST[\'id_viaje\']', $content);
        $this->assertStringContainsString('FILTER_VALIDATE_INT', $content);
    }

    /* ============================================================
       Tests para register_form.php
       ============================================================ */

    public function testRegisterFormFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../views/register_form.php');
    }

    public function testRegisterFormUsesPreparedStatements(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/register_form.php');
        
        // Verify prepared statement is used
        $this->assertStringContainsString('$conn->prepare($sql)', $content);
        $this->assertStringContainsString('$stmt->bind_param', $content);
        
        // Verify no direct string interpolation with user input
        $this->assertStringNotContainsString("WHERE email = '$email'", $content);
        $this->assertStringNotContainsString('WHERE email = \'' . $content, $content);
    }

    public function testRegisterFormRemovesFileInclusion(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/register_form.php');
        
        // Verify no require_once database.php - vulnerable to file inclusion
        $this->assertStringNotContainsString('require_once "database.php"', $content);
    }

    public function testRegisterFormUsesDirectDatabaseConnection(): void
    {
        $content = file_get_contents(__DIR__ . '/../views/register_form.php');
        
        // Verify uses direct mysqli connection instead of external file
        $this->assertStringContainsString('new mysqli($db_host, $db_user, $db_pass, $db_name)', $content);
        $this->assertStringContainsString('$db_host = \'localhost\'', $content);
        $this->assertStringContainsString('$db_name = \'agencia_db\'', $content);
    }

    /* ============================================================
       Integration Tests
       ============================================================ */

    public function testAllFilesHaveSyntaxOK(): void
    {
        $files = [
            __DIR__ . '/../views/detalles_viaje.php',
            __DIR__ . '/../views/procesar_reserva.php',
            __DIR__ . '/../views/register_form.php'
        ];

        foreach ($files as $file) {
            $output = [];
            $returnVar = 0;
            exec('php -l ' . escapeshellarg($file), $output, $returnVar);
            
            $this->assertEquals(0, $returnVar, "Syntax error in $file: " . implode(' ', $output));
        }
    }

    public function testNoSQLInjectionPatterns(): void
    {
        $files = [
            __DIR__ . '/../views/detalles_viaje.php',
            __DIR__ . '/../views/procesar_reserva.php',
            __DIR__ . '/../views/register_form.php'
        ];

        $dangerousPatterns = [
            '/WHERE\s+\w+\s*=\s*\$_(?:GET|POST)\[/',
            '/\$sql\s*=\s*"[^"]*\$_(?:GET|POST)\[/',
            '/INSERT\s+INTO\s+\w+[^;]*\$_(?:GET|POST)\[/',
            '/UPDATE\s+\w+[^;]*\$_(?:GET|POST)\[/',
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            foreach ($dangerousPatterns as $pattern) {
                $this->assertEmpty(
                    preg_grep($pattern, [$content]),
                    "Potential SQL injection pattern found in $file"
                );
            }
        }
    }

    public function testOutputEscapingPresent(): void
    {
        $files = [
            __DIR__ . '/../views/detalles_viaje.php',
            __DIR__ . '/../views/procesar_reserva.php',
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // All database data should use htmlspecialchars
            $this->assertStringContainsString('htmlspecialchars(', $content);
            // With proper flags
            $this->assertStringContainsString('ENT_QUOTES', $content);
            $this->assertStringContainsString('UTF-8', $content);
        }
    }
}
