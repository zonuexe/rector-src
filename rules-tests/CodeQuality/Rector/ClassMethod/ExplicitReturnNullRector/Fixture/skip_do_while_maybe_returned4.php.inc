<?php

namespace Rector\Tests\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector\Fixture;

final class SkipDoWhileMaybeReturned4
{
    public function run(int $i)
    {
		do {
			if (rand(0, 1)) {
				goto execute;
			}

			return 2;
		} while (++$i < 1);
		execute:
			echo 'test';
    }
}
