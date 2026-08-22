<?php

namespace Tests\Feature;

use Tests\TestCase;

class OfflineFirstTest extends TestCase
{
    public function test_offline_shell_files_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('js/offline-first.js'));
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertStringContainsString('pearledu-offline-v2', (string) file_get_contents(public_path('sw.js')));
        $this->assertStringContainsString('form[data-offline-queue]', (string) file_get_contents(public_path('js/offline-first.js')));
        $this->assertStringContainsString('standalone', (string) file_get_contents(public_path('manifest.webmanifest')));
        $this->assertStringContainsString('/favicon.svg', (string) file_get_contents(public_path('manifest.webmanifest')));
        $this->assertStringContainsString('/images/brand/logo-192.png', (string) file_get_contents(public_path('manifest.webmanifest')));
    }

    public function test_login_page_registers_the_offline_shell(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('js/offline-first.js', false)
            ->assertSee('offline-banner', false);
    }
}
