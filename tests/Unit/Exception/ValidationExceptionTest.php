<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Unit\Exception;

use Acelot\Struct\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testErrors()
    {
        $e = new ValidationException(['errors' => [1, 2, 3]], new \InvalidArgumentException());
        $this->assertEquals(['errors' => [1, 2, 3]], $e->getErrors());
        $this->assertEquals(new \InvalidArgumentException(), $e->getPrevious());
    }
}
