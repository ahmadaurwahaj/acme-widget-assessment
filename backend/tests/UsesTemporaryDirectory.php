<?php

declare(strict_types=1);

namespace Acme\Tests;

use PHPUnit\Framework\Attributes\After;

trait UsesTemporaryDirectory
{
    private ?string $temporaryDirectory = null;

    private function temporaryDirectory(): string
    {
        $this->temporaryDirectory ??= sys_get_temp_dir() . '/acme-test-' . bin2hex(random_bytes(4));

        return $this->temporaryDirectory;
    }

    #[After]
    public function removeTemporaryDirectory(): void
    {
        if ($this->temporaryDirectory === null || !is_dir($this->temporaryDirectory)) {
            return;
        }

        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->temporaryDirectory);
    }
}
