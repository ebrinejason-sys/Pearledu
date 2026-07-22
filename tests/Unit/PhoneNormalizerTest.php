<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_normalizes_ugandan_local_numbers(): void
    {
        $this->assertSame('+256712345678', PhoneNormalizer::normalize('0712345678'));
        $this->assertSame('+256712345678', PhoneNormalizer::normalize('256712345678'));
        $this->assertSame('+256712345678', PhoneNormalizer::normalize('+256 712 345 678'));
    }

    public function test_empty_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::normalize(null));
        $this->assertNull(PhoneNormalizer::normalize('  '));
    }
}
